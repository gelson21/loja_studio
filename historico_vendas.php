<?php
session_start();
include 'conexao.php';

// Proteção da página: Apenas admin pode acessar
if (!isset($_SESSION['nivel_acesso']) || $_SESSION['nivel_acesso'] !== 'admin') {
    echo "<script>alert('Acesso negado!'); window.location.href = 'login.php';</script>";
    exit();
}

// Processar limpeza do histórico
if (isset($_POST['limpar_historico'])) {
    $data_limite = $_POST['data_limite'] ?? date('Y-m-d', strtotime('-30 days'));
    
    try {
        $conn->begin_transaction();
        
        // Mover vendas antigas para tabela de histórico
        $query_mover = "
            INSERT INTO historico_vendas 
            SELECT *, NOW() as data_arquivamento 
            FROM pedidos 
            WHERE status = 'aprovado' 
            AND data_pedido < ?
        ";
        $stmt = $conn->prepare($query_mover);
        $stmt->bind_param("s", $data_limite);
        $stmt->execute();
        
        // Remover vendas antigas da tabela principal
        $query_remover = "
            DELETE FROM pedidos 
            WHERE status = 'aprovado' 
            AND data_pedido < ?
        ";
        $stmt = $conn->prepare($query_remover);
        $stmt->bind_param("s", $data_limite);
        $stmt->execute();
        
        $conn->commit();
        $feedback = ['type' => 'success', 'message' => 'Histórico limpo com sucesso!'];
    } catch (Exception $e) {
        $conn->rollback();
        $feedback = ['type' => 'error', 'message' => 'Erro ao limpar histórico: ' . $e->getMessage()];
    }
}

// Buscar histórico de vendas com detalhes dos produtos
$query = "
    SELECT 
        p.id,
        p.data_pedido,
        c.nome as cliente_nome,
        COALESCE(v.nome, 'Não atribuído') as vendedor_nome,
        p.total_pedido,
        p.status,
        p.data_pedido as data_atualizacao,
        GROUP_CONCAT(
            CONCAT(
                pr.nome, ' (', pp.quantidade, ' x Kz ', 
                FORMAT(pp.preco_unitario, 2), ')'
            ) SEPARATOR '\\n'
        ) as produtos_detalhes,
        CASE 
            WHEN p.status = 'aprovado' THEN 'Aprovado'
            WHEN p.status = 'recusado' THEN 'Recusado'
            ELSE 'Pendente'
        END as status_pedido,
        CASE 
            WHEN p.status = 'aprovado' THEN 'Pago'
            WHEN p.status = 'recusado' THEN 'Não pago'
            ELSE 'Pendente'
        END as status_pagamento
    FROM pedidos p
    JOIN usuarios c ON p.id_cliente = c.id
    LEFT JOIN usuarios v ON p.vendedor_id = v.id
    LEFT JOIN pedidos_produtos pp ON p.id = pp.pedido_id
    LEFT JOIN produtos pr ON pp.produto_id = pr.id
    WHERE p.status IN ('aprovado', 'recusado')
    GROUP BY p.id, p.data_pedido, c.nome, v.nome, p.total_pedido, p.status
    ORDER BY p.data_pedido DESC, p.id
";

$result = $conn->query($query);
$vendas = $result->fetch_all(MYSQLI_ASSOC);

// Calcular totais
$total_vendas = count($vendas);
$total_valor = array_sum(array_column($vendas, 'total_pedido'));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Histórico de Vendas</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="dashboard-header">
            <h1>Histórico de Vendas</h1>
            <div class="header-buttons">
                <a href="admin_dashboard.php" class="button">Voltar ao Dashboard</a>
            </div>
        </div>

        <?php if (isset($feedback)): ?>
            <div class="feedback <?= htmlspecialchars($feedback['type']) ?>">
                <?= htmlspecialchars($feedback['message']) ?>
            </div>
        <?php endif; ?>

        <!-- Resumo -->
        <div class="stats-container">
            <div class="stat-box">
                <h3>Total de Vendas</h3>
                <p><?= $total_vendas ?></p>
            </div>
            <div class="stat-box">
                <h3>Valor Total</h3>
                <p>Kz <?= number_format($total_valor, 2, ',', '.') ?></p>
            </div>
        </div>

        <!-- Formulário de Limpeza -->
        <div class="clean-history-form">
            <h2>Limpar Histórico</h2>
            <form method="POST" action="" onsubmit="return confirm('Tem certeza que deseja limpar o histórico de vendas? Esta ação não pode ser desfeita.');">
                <div class="form-group">
                    <label for="data_limite">Limpar vendas anteriores a:</label>
                    <input type="date" id="data_limite" name="data_limite" value="<?= date('Y-m-d', strtotime('-30 days')) ?>" required>
                </div>
                <button type="submit" name="limpar_historico" class="button-danger">Limpar Histórico</button>
            </form>
        </div>

        <!-- Tabela de Vendas -->
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Data da Compra</th>
                        <th>Cliente</th>
                        <th>Status do Pedido</th>
                        <th>Status do Pagamento</th>
                        <th>Data da Atualização</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($vendas as $venda): ?>
                        <tr>
                            <td><?= date('d/m/Y H:i', strtotime($venda['data_pedido'])) ?></td>
                            <td><?= htmlspecialchars($venda['cliente_nome']) ?></td>
                            <td>
                                <span class="status-badge <?= strtolower($venda['status_pedido']) ?>">
                                    <?= htmlspecialchars($venda['status_pedido']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="status-badge <?= strtolower($venda['status_pagamento']) ?>">
                                    <?= htmlspecialchars($venda['status_pagamento']) ?>
                                </span>
                            </td>
                            <td>
                                <?= date('d/m/Y H:i', strtotime($venda['data_atualizacao'])) ?>
                            </td>
                            <td>Kz <?= number_format($venda['total_pedido'], 2, ',', '.') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html> 