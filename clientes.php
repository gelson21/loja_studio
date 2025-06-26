<?php
// filepath: c:\xampp\htdocs\PROGAMACAO\Tarefa1\clientes.php

include 'conexao.php'; // Inclui o arquivo de conexão com o banco de dados
session_start();

// Proteção da página: Apenas administradores ou RH podem acessar
if (!isset($_SESSION['nivel_acesso']) || !in_array($_SESSION['nivel_acesso'], ['admin', 'rh'])) {
    echo "<script>alert('Acesso negado!'); window.location.href = 'login.php';</script>";
    exit();
}

// Função para buscar todos os clientes da tabela de usuários
function buscarClientes($conn) {
    $stmt = $conn->prepare("SELECT id, nome, email, criado_em FROM usuarios WHERE nivel_acesso = 'cliente'");
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Função para eliminar um cliente da tabela de usuários
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar'])) {
    $idCliente = intval($_POST['id_cliente']);
    $stmt = $conn->prepare("DELETE FROM usuarios WHERE id = ? AND nivel_acesso = 'cliente'");
    $stmt->bind_param("i", $idCliente);
    if ($stmt->execute()) {
        echo "<script>alert('Cliente eliminado com sucesso!');</script>";
    } else {
        echo "<script>alert('Erro ao eliminar o cliente.');</script>";
    }
    header("Location: clientes.php");
    exit();
}

// Função para encerrar a sessão
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout'])) {
    session_destroy(); // Encerra a sessão
    header("Location: index.php"); // Redireciona para index.php
    exit();
}

// Busca todos os clientes
$clientes = buscarClientes($conn);

// Fecha a conexão com o banco de dados
mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Clientes</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>Lista de Clientes</h1>
        <?php if (!empty($clientes)): ?>
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
                    <?php foreach ($clientes as $cliente): ?>
                        <tr>
                            <td><?= htmlspecialchars($cliente['id']) ?></td>
                            <td><?= htmlspecialchars($cliente['nome']) ?></td>
                            <td><?= htmlspecialchars($cliente['email']) ?></td>
                            <td><?= htmlspecialchars(date('d/m/Y', strtotime($cliente['criado_em']))) ?></td>
                            <td>
                                <form method="POST" action="">
                                    <input type="hidden" name="id_cliente" value="<?= $cliente['id'] ?>">
                                    <button type="submit" name="eliminar" class="button-danger" onclick="return confirm('Tem certeza que deseja eliminar este cliente?');">Eliminar</button>
                                </form>
                                <a href="editar_cliente.php?id=<?= $cliente['id'] ?>" class="button-edit">Editar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="text-align: center;">Nenhum cliente encontrado.</p>
        <?php endif; ?>
        
        <div class="back-button" style="margin-top: 20px;">
            <?php if ($_SESSION['nivel_acesso'] === 'admin'): ?>
                <a href="admin_dashboard.php">Voltar ao Painel</a>
            <?php else: ?>
                <a href="rh_dashboard.php">Voltar ao Painel</a>
            <?php endif; ?>
        </div>
        <div class="logout-button" style="text-align: center; margin-top: 10px;">
            <form method="POST" action="">
                <button type="submit" name="logout" class="button-danger">Terminar Sessão</button>
            </form>
        </div>
    </div>
</body>
</html>