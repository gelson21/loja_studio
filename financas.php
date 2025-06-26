<?php
include 'conexao.php';
session_start();

// Proteção da página: Apenas administradores podem acessar
if (!isset($_SESSION['nivel_acesso']) || $_SESSION['nivel_acesso'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Buscar todas as vendas
$result = $conn->query("SELECT * FROM vendas ORDER BY data_venda DESC");
$vendas = $result->fetch_all(MYSQLI_ASSOC);

// Calcular KPIs
$receita_total = 0;
$total_vendas = 0;
$total_produtos_vendidos = 0;

foreach ($vendas as $venda) {
    $receita_total += $venda['preco_total'];
    $total_produtos_vendidos += $venda['quantidade'];
}
$total_vendas = count($vendas);
$ticket_medio = ($total_vendas > 0) ? $receita_total / $total_vendas : 0;

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finanças</title>
    <link rel="stylesheet" href="admin-style.css">
    <style>
        .kpi-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .kpi-card {
            background-color: #2c3e50;
            padding: 25px;
            border-radius: 10px;
            text-align: center;
            color: #ecf0f1;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        .kpi-card h3 {
            margin-top: 0;
            font-size: 1.2em;
            color: #1abc9c;
        }
        .kpi-card p {
            font-size: 2em;
            margin: 10px 0 0 0;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="top-menu">
        <a href="index.php">Sair</a>
        <a href="suporte.php">Suporte</a>
    </div>
    <button class="toggle-button" onclick="toggleMenu()">☰ Menu</button>
    <div class="side-menu" id="sideMenu">
        <div>
            <a href="adimin.php">Home</a>
            <a href="vendedor.php">Vendedor</a>
            <a href="clientes.php">Clientes</a>
            <a href="gerenciar_produtos.php">Produtos</a>
            <a href="rh.php">RH</a>
            <a href="financas.php" class="active">Finanças</a>
        </div>
    </div>
    <div class="content">
        <h1 class="text-center">Painel de Finanças</h1>

        <div class="kpi-container">
            <div class="kpi-card">
                <h3>Receita Total</h3>
                <p>Kz <?= number_format($receita_total, 2, ',', '.') ?></p>
            </div>
            <div class="kpi-card">
                <h3>Total de Vendas</h3>
                <p><?= $total_vendas ?></p>
            </div>
            <div class="kpi-card">
                <h3>Produtos Vendidos</h3>
                <p><?= $total_produtos_vendidos ?></p>
            </div>
            <div class="kpi-card">
                <h3>Ticket Médio</h3>
                <p>Kz <?= number_format($ticket_medio, 2, ',', '.') ?></p>
            </div>
        </div>

        <h2 class="text-center">Histórico de Vendas</h2>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID da Venda</th>
                        <th>Produto</th>
                        <th>Quantidade</th>
                        <th>Preço Total</th>
                        <th>Vendedor (Email)</th>
                        <th>Data da Venda</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($vendas)): ?>
                        <?php foreach ($vendas as $venda): ?>
                            <tr>
                                <td><?= htmlspecialchars($venda['id']) ?></td>
                                <td><?= htmlspecialchars($venda['produto']) ?></td>
                                <td><?= htmlspecialchars($venda['quantidade']) ?></td>
                                <td>Kz <?= number_format($venda['preco_total'], 2, ',', '.') ?></td>
                                <td><?= htmlspecialchars($venda['vendedor_email']) ?></td>
                                <td><?= htmlspecialchars(date('d/m/Y H:i:s', strtotime($venda['data_venda']))) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6">Nenhuma venda registrada ainda.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function toggleMenu() {
            const sideMenu = document.getElementById('sideMenu');
            sideMenu.classList.toggle('show');
        }
    </script>
</body>
</html>
