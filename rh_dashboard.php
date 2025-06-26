<?php
session_start();
include 'conexao.php';

// Proteção da página: Apenas usuários de RH podem acessar
if (!isset($_SESSION['nivel_acesso']) || $_SESSION['nivel_acesso'] !== 'rh') {
    echo "<script>alert('Acesso negado! Você não tem permissão para acessar esta página.');</script>";
    echo "<script>window.location.href = 'login.php';</script>";
    exit();
}

// Lógica de logout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}

// Buscar todos os usuários (exceto administradores)
$stmt = $conn->prepare("SELECT id, nome, email, nivel_acesso, criado_em FROM usuarios WHERE nivel_acesso != 'admin'");
$stmt->execute();
$result = $stmt->get_result();
$usuarios = $result->fetch_all(MYSQLI_ASSOC);

// Mensagens de feedback da sessão
$feedback_message = $_SESSION['feedback_message'] ?? null;
$feedback_type = $_SESSION['feedback_type'] ?? 'info'; // 'success', 'error', 'info'
unset($_SESSION['feedback_message'], $_SESSION['feedback_type']);

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard de RH</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .logout-button-container {
            text-align: right;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($feedback_message): ?>
            <div class="alert alert-<?= $feedback_type === 'error' ? 'danger' : 'success' ?>">
                <?= htmlspecialchars($feedback_message) ?>
            </div>
        <?php endif; ?>

        <div class="dashboard-header">
            <h1>Painel de Recursos Humanos</h1>
            <form method="POST" action="">
                <button type="submit" name="logout" class="button-danger">Sair</button>
            </form>
        </div>

        <p>Bem-vindo ao seu painel. Use os links abaixo para gerenciar os usuários do sistema.</p>

        <div class="dashboard-actions" style="margin: 30px 0; display: flex; gap: 15px; flex-wrap: wrap;">
            <a href="rh_cadastrar_vendedor.php" class="button">Cadastrar Vendedor</a>
            
            <!-- Adicionar outros links de gerenciamento aqui, se necessário -->
        </div>

        <h2>Usuários do Sistema (Visão Geral)</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>Email</th>
                    <th>Nível de Acesso</th>
                    <th>Data de Cadastro</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($usuarios) > 0): ?>
                    <?php foreach ($usuarios as $usuario): ?>
                        <tr>
                            <td><?= htmlspecialchars($usuario['id']) ?></td>
                            <td><?= htmlspecialchars($usuario['nome']) ?></td>
                            <td><?= htmlspecialchars($usuario['email']) ?></td>
                            <td><?= htmlspecialchars(ucfirst($usuario['nivel_acesso'])) ?></td>
                            <td><?= htmlspecialchars(date('d/m/Y', strtotime($usuario['criado_em']))) ?></td>
                            <td>
                                <?php if ($usuario['nivel_acesso'] === 'vendedor'): ?>
                                    <form method="POST" action="rh_eliminar_vendedor.php" onsubmit="return confirm('Tem certeza que deseja eliminar este vendedor?');" style="margin: 0;">
                                        <input type="hidden" name="id_vendedor" value="<?= $usuario['id'] ?>">
                                        <button type="submit" class="button-danger">Eliminar</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6">Nenhum usuário encontrado.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html> 