<?php
session_start();
include 'conexao.php';

// Proteção da página: Apenas clientes podem acessar
if (!isset($_SESSION['usuario_id']) || $_SESSION['nivel_acesso'] !== 'cliente') {
    echo "<script>alert('Acesso negado!'); window.location.href = 'login.php';</script>";
    exit();
}

$id_cliente = $_SESSION['usuario_id'];
$cliente_nome = $_SESSION['usuario_nome'] ?? 'Cliente';

// Marcar notificações como lidas (exemplo simples ao carregar a página)
$conn->query("UPDATE notificacoes SET lida = 1 WHERE id_cliente = $id_cliente AND lida = 0");

// Buscar pedidos do cliente
$stmt_pedidos = $conn->prepare("SELECT id, total_pedido, status, data_pedido FROM pedidos WHERE id_cliente = ? ORDER BY data_pedido DESC");
$stmt_pedidos->bind_param('i', $id_cliente);
$stmt_pedidos->execute();
$pedidos = $stmt_pedidos->get_result()->fetch_all(MYSQLI_ASSOC);

// Buscar notificações do cliente
$stmt_notificacoes = $conn->prepare("SELECT mensagem, data_criacao FROM notificacoes WHERE id_cliente = ? ORDER BY data_criacao DESC LIMIT 10");
$stmt_notificacoes->bind_param('i', $id_cliente);
$stmt_notificacoes->execute();
$notificacoes = $stmt_notificacoes->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Painel do Cliente</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <div class="dashboard-header">
   
    <a href="suporte.php">Suporte</a>
    <a href="Sobre.php">Sobre nós</a>
        <h1>Bem-vindo, <?= htmlspecialchars($cliente_nome) ?>!</h1>
        <div>
            <a href="loja.php" class="button" style="margin-right: 10px;">Ir para a Loja</a>
            <a href="index.php" class="button-danger">Sair</a>
        </div>
    </div>

    <div class="dashboard-content">
        <div class="dashboard-main">
            <h2>Meus Pedidos</h2>
            <?php if (!empty($pedidos)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID Pedido</th>
                            <th>Data</th>
                            <th>Total</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pedidos as $pedido): ?>
                            <tr>
                                <td>#<?= $pedido['id'] ?></td>
                                <td><?= date('d/m/Y', strtotime($pedido['data_pedido'])) ?></td>
                                <td>Kz <?= number_format($pedido['total_pedido'], 2, ',', '.') ?></td>
                                <td class="status-<?= strtolower(htmlspecialchars($pedido['status'])) ?>"><?= htmlspecialchars(ucfirst($pedido['status'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>Você ainda não fez nenhum pedido.</p>
            <?php endif; ?>
        </div>
        <div class="dashboard-sidebar">
            <h2>Notificações</h2>
            <?php if (!empty($notificacoes)): ?>
                <ul class="notification-list">
                    <?php foreach ($notificacoes as $notificacao): ?>
                        <li>
                            <strong><?= date('d/m/Y H:i', strtotime($notificacao['data_criacao'])) ?>:</strong><br>
                            <?= htmlspecialchars($notificacao['mensagem']) ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p>Nenhuma notificação nova.</p>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html> 