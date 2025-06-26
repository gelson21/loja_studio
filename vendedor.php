<?php
// filepath: c:\xampp\htdocs\PROGAMACAO\Tarefa1\vendedor.php

include 'conexao.php'; // Inclui o arquivo de conexão com o banco de dados
session_start();

// Verifica se o usuário é administrador
if (!isset($_SESSION['nivel_acesso']) || $_SESSION['nivel_acesso'] !== 'admin') {
    echo "<script>alert('Acesso negado! Apenas administradores podem acessar esta página.');</script>";
    echo "<script>window.location.href = 'index.php';</script>";
    exit();
}

// Adicionar Vendedor
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['adicionar'])) {
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $senha = trim($_POST['senha']);

    // Verifica se o email já está cadastrado na tabela de usuários
    $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        echo "<script>alert('Email já cadastrado no sistema!');</script>";
    } else {
        // Criptografa a senha
        $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
        $nivel_acesso = 'vendedor';

        // Insere o novo vendedor na tabela de usuários
        $stmt = $conn->prepare("INSERT INTO usuarios (nome, email, senha, nivel_acesso) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $nome, $email, $senha_hash, $nivel_acesso);

        if ($stmt->execute()) {
            echo "<script>alert('Vendedor cadastrado com sucesso!');</script>";
        } else {
            echo "<script>alert('Erro ao cadastrar o vendedor.');</script>";
        }
    }
}

// Remover Vendedor (agora remove da tabela de usuários)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remover'])) {
    $id = intval($_POST['id']);
    // Garante que o admin não pode se remover ou outros admins por esta interface
    $stmt = $conn->prepare("DELETE FROM usuarios WHERE id = ? AND nivel_acesso = 'vendedor'");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo "<script>alert('Vendedor removido com sucesso!');</script>";
    } else {
        echo "<script>alert('Erro ao remover o vendedor.');</script>";
    }
}

// Buscar todos os vendedores (usuários com nivel_acesso 'vendedor')
$stmt = $conn->prepare("SELECT id, nome, email, criado_em FROM usuarios WHERE nivel_acesso = 'vendedor'");
$stmt->execute();
$result = $stmt->get_result();
$vendedores = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Vendedores</title>
    <link rel="stylesheet" href="vendedor-style.css">
</head>
<body>
    <div class="container">
        <h1>Gerenciar Vendedores</h1>
        <form method="POST" action="">
            <div class="form-group">
                <label for="nome">Nome do Vendedor</label>
                <input type="text" id="nome" name="nome" required>
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="senha">Senha</label>
                <input type="password" id="senha" name="senha" required>
            </div>
            <button type="submit" name="adicionar">Adicionar Vendedor</button>
        </form>
        <h2>Vendedores Cadastrados</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>Email</th>
                    <th>Data de Cadastro</th>
                    <th>Ação</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($vendedores as $vendedor): ?>
                    <tr>
                        <td><?= htmlspecialchars($vendedor['id']) ?></td>
                        <td><?= htmlspecialchars($vendedor['nome']) ?></td>
                        <td><?= htmlspecialchars($vendedor['email']) ?></td>
                        <td><?= htmlspecialchars($vendedor['criado_em']) ?></td>
                        <td>
                            <form method="POST" action="" style="display: inline;">
                                <input type="hidden" name="id" value="<?= $vendedor['id'] ?>">
                                <button type="submit" name="remover" class="button-danger">Remover</button>
                            </form>
                            <a href="editar_vendedor.php?id=<?= $vendedor['id'] ?>" class="button-edit">Editar</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div class="back-button">
            <a href="admin_dashboard.php">Voltar</a>
        </div>
    </div>
</body>
</html>