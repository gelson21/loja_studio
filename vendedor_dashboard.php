<?php
session_start();
include 'conexao.php';

// Proteção da página: Apenas vendedores podem acessar
if (!isset($_SESSION['nivel_acesso']) || !in_array($_SESSION['nivel_acesso'], ['vendedor', 'admin'])) {
    echo "<script>alert('Acesso negado!'); window.location.href = 'login.php';</script>";
    exit();
}

$vendedor_nome = $_SESSION['usuario_nome'] ?? 'Vendedor';
$vendedor_id = $_SESSION['usuario_id'] ?? null;

// Lógica de logout
if (isset($_POST['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit();
}

// Estatísticas de Produtos
$query_produtos = "
    SELECT 
        COUNT(*) as total_produtos,
        SUM(CASE WHEN quantidade > 0 THEN 1 ELSE 0 END) as produtos_disponiveis,
        SUM(quantidade) as total_estoque
    FROM produtos
";
$produtos_stats = $conn->query($query_produtos)->fetch_assoc();

// Total de vendas do vendedor
$query_total_vendas = "
    SELECT 
        COUNT(*) as total_pedidos,
        SUM(total_pedido) as valor_total_vendas
    FROM pedidos 
    WHERE status = 'aprovado'
    AND vendedor_id = ?
";
$stmt_vendas = $conn->prepare($query_total_vendas);
$stmt_vendas->bind_param("i", $vendedor_id);
$stmt_vendas->execute();
$total_vendas = $stmt_vendas->get_result()->fetch_assoc();

// Buscar vendas do dia atual
$hoje = date('Y-m-d');
$query_vendas_dia = "
    SELECT 
        COALESCE(SUM(total_pedidos), 0) as total_pedidos,
        COALESCE(SUM(total_vendas), 0) as total_vendas
    FROM vendas_dia 
    WHERE data = ? 
    AND vendedor_id = ?
";
$stmt_vendas = $conn->prepare($query_vendas_dia);
$stmt_vendas->bind_param("si", $hoje, $vendedor_id);
$stmt_vendas->execute();
$vendas_dia = $stmt_vendas->get_result()->fetch_assoc();

// Buscar histórico de vendas dos últimos 7 dias
$query_historico = "
    SELECT 
        DATE(p.data_pedido) as data,
        COUNT(DISTINCT p.id) as total_pedidos,
        SUM(p.total_pedido) as total_vendas,
        GROUP_CONCAT(
            CONCAT(
                'Pedido #', p.id, 
                ' (Vendedor: ', COALESCE(v.nome, 'Não atribuído'), ')',
                ' - Cliente: ', c.nome,
                ' - Produtos: ',
                (
                    SELECT GROUP_CONCAT(
                        CONCAT(
                            pr.nome, ' (', pp.quantidade, ' x Kz ', 
                            FORMAT(pp.preco_unitario, 2), ')'
                        ) SEPARATOR ', '
                    )
                    FROM pedidos_produtos pp
                    JOIN produtos pr ON pp.produto_id = pr.id
                    WHERE pp.pedido_id = p.id
                )
            ) SEPARATOR '\\n'
        ) as detalhes_pedidos
    FROM pedidos p
    LEFT JOIN usuarios v ON p.vendedor_id = v.id
    JOIN usuarios c ON p.id_cliente = c.id
    WHERE p.vendedor_id = ?
    AND p.status = 'aprovado'
    AND p.data_pedido >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY DATE(p.data_pedido)
    ORDER BY data DESC
";
$stmt_historico = $conn->prepare($query_historico);
$stmt_historico->bind_param("i", $vendedor_id);
$stmt_historico->execute();
$historico_vendas = $stmt_historico->get_result()->fetch_all(MYSQLI_ASSOC);

// Buscar pedidos pendentes
$query_pedidos = "
    SELECT 
        p.id, 
        u.nome as cliente_nome, 
        p.total_pedido, 
        p.data_pedido
    FROM pedidos p
    JOIN usuarios u ON p.id_cliente = u.id
    WHERE p.status = 'pendente'
    ORDER BY p.data_pedido ASC
";
$pedidos_result = $conn->query($query_pedidos);
$pedidos_pendentes = $pedidos_result->fetch_all(MYSQLI_ASSOC);

$feedback = $_SESSION['feedback_vendedor'] ?? null;
unset($_SESSION['feedback_vendedor']);

// Buscar todos os produtos
$produtos_result = $conn->query("SELECT * FROM produtos ORDER BY nome ASC");
$produtos = $produtos_result->fetch_all(MYSQLI_ASSOC);

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Dashboard do Vendedor</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <div class="dashboard-header">
        <h1>Bem-vindo, <?= htmlspecialchars($vendedor_nome) ?>!</h1>
        <div class="header-buttons">
            <a href="listar_clientes.php" class="button">Gerenciar Clientes</a>
            <a href="cadastrar_cliente.php" class="button">Cadastrar Novo Cliente</a>
            <a href="index.php">Voltar</a>
        </div>
    </div>

    <?php if ($feedback): ?>
        <div class="feedback <?= htmlspecialchars($feedback['type']) ?>">
            <?= htmlspecialchars($feedback['message']) ?>
        </div>
    <?php endif; ?>

    <!-- Estatísticas de Produtos -->
    <div class="stats-container">
        <div class="stat-box">
            <h3>Total de Produtos</h3>
            <p><?= $produtos_stats['total_produtos'] ?></p>
        </div>
        <div class="stat-box">
            <h3>Produtos Disponíveis</h3>
            <p><?= $produtos_stats['produtos_disponiveis'] ?></p>
        </div>
        <div class="stat-box">
            <h3>Total em Estoque</h3>
            <p><?= $produtos_stats['total_estoque'] ?></p>
        </div>
    </div>

    <!-- Estatísticas de Vendas -->
    <div class="stats-container">
        <div class="stat-box">
            <h3>Total de Vendas</h3>
            <p><?= $total_vendas['total_pedidos'] ?? 0 ?></p>
        </div>
        <div class="stat-box">
            <h3>Valor Total Vendido</h3>
            <p>Kz <?= number_format($total_vendas['valor_total_vendas'] ?? 0, 2, ',', '.') ?></p>
        </div>
    </div>

    <!-- Relatório de Vendas do Dia -->
    <div class="sales-report">
        <h2>Vendas de Hoje</h2>
        <div class="sales-stats">
            <div class="stat-box">
                <h3>Total de Pedidos</h3>
                <p><?= $vendas_dia['total_pedidos'] ?? 0 ?></p>
            </div>
            <div class="stat-box">
                <h3>Total de Vendas</h3>
                <p>Kz <?= number_format($vendas_dia['total_vendas'] ?? 0, 2, ',', '.') ?></p>
            </div>
        </div>
    </div>

    <!-- Histórico de Vendas -->
    <div class="sales-history">
        <h2>Histórico de Vendas (Últimos 7 dias)</h2>
        <table>
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Total de Pedidos</th>
                    <th>Total de Vendas</th>
                    <th>Detalhes dos Pedidos</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($historico_vendas as $venda): ?>
                    <tr>
                        <td><?= date('d/m/Y', strtotime($venda['data'])) ?></td>
                        <td><?= $venda['total_pedidos'] ?></td>
                        <td>Kz <?= number_format($venda['total_vendas'], 2, ',', '.') ?></td>
                        <td>
                            <div class="detalhes-pedidos">
                                <?= nl2br(htmlspecialchars($venda['detalhes_pedidos'])) ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <h2>Pedidos Pendentes de Aprovação</h2>
    <?php if (!empty($pedidos_pendentes)): ?>
        <table>
            <thead>
                <tr>
                    <th>ID Pedido</th>
                    <th>Cliente</th>
                    <th>Total</th>
                    <th>Data</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pedidos_pendentes as $pedido): ?>
                    <tr>
                        <td>#<?= $pedido['id'] ?></td>
                        <td><?= htmlspecialchars($pedido['cliente_nome']) ?></td>
                        <td>Kz <?= number_format($pedido['total_pedido'], 2, ',', '.') ?></td>
                        <td><?= date('d/m/Y H:i', strtotime($pedido['data_pedido'])) ?></td>
                        <td>
                            <a href="ver_pedido.php?id=<?= $pedido['id'] ?>" class="button">Ver Detalhes</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>Nenhum pedido pendente no momento.</p>
    <?php endif; ?>

    <h2>Lista de Produtos Cadastrados</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>Preço</th>
                <th>Estoque</th>
                <th>Imagem</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($produtos as $produto): ?>
                <tr>
                    <td><?= $produto['id'] ?></td>
                    <td><?= htmlspecialchars($produto['nome']) ?></td>
                    <td>Kz <?= number_format($produto['preco'], 2, ',', '.') ?></td>
                    <td><?= $produto['quantidade'] ?></td>
                    <td><img src="<?= htmlspecialchars($produto['imagem']) ?>" alt="<?= htmlspecialchars($produto['nome']) ?>" width="50"></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
</body>
</html> 