<?php
include 'conexao.php';
session_start();

// Verifica se o usuário é administrador
if (!isset($_SESSION['nivel_acesso']) || $_SESSION['nivel_acesso'] !== 'admin') {
    echo "<script>alert('Acesso negado! Apenas administradores podem acessar esta página.');</script>";
    echo "<script>window.location.href = 'index.php';</script>";
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<script>alert('ID de vendedor inválido.'); window.location.href = 'vendedor.php';</script>";
    exit();
}

$id = intval($_GET['id']);

// Buscar dados do vendedor
$stmt = $conn->prepare("SELECT nome, email FROM usuarios WHERE id = ? AND nivel_acesso = 'vendedor'");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    echo "<script>alert('Vendedor não encontrado.'); window.location.href = 'vendedor.php';</script>";
    exit();
}
$vendedor = $result->fetch_assoc();

// Atualizar dados
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $senha = trim($_POST['senha']);

    if (!empty($senha)) {
        $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE usuarios SET nome = ?, email = ?, senha = ? WHERE id = ? AND nivel_acesso = 'vendedor'");
        $stmt->bind_param("sssi", $nome, $email, $senha_hash, $id);
    } else {
        $stmt = $conn->prepare("UPDATE usuarios SET nome = ?, email = ? WHERE id = ? AND nivel_acesso = 'vendedor'");
        $stmt->bind_param("ssi", $nome, $email, $id);
    }
    if ($stmt->execute()) {
        echo "<script>alert('Dados atualizados com sucesso!'); window.location.href = 'vendedor.php';</script>";
        exit();
    } else {
        echo "<script>alert('Erro ao atualizar os dados.');</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Vendedor</title>
    <link rel="stylesheet" href="vendedor-style.css">
</head>
<body>
    <div class="container">
        <h1>Editar Vendedor</h1>
        <form method="POST">
            <div class="form-group">
                <label for="nome">Nome</label>
                <input type="text" id="nome" name="nome" value="<?= htmlspecialchars($vendedor['nome']) ?>" required>
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($vendedor['email']) ?>" required>
            </div>
            <div class="form-group">
                <label for="senha">Nova Senha (deixe em branco para não alterar)</label>
                <input type="password" id="senha" name="senha">
            </div>
            <button type="submit">Salvar Alterações</button>
            <a href="vendedor.php" class="button-edit">Cancelar</a>
        </form>
    </div>
</body>
</html> 