<?php
// filepath: c:\xampp\htdocs\PROGAMACAO\Tarefa1\vendas.php

include 'conexao.php'; // Inclui o arquivo de conexão com o banco de dados
session_start();

// Verifica se o usuário é um vendedor
if (!isset($_SESSION['nivel_acesso']) || $_SESSION['nivel_acesso'] !== 'vendedor') {
    echo "<script>alert('Acesso negado! Apenas vendedores podem acessar esta página.');</script>";
    exit();
}

// Busca as vendas associadas ao vendedor
$vendedorEmail = $_SESSION['usuario_verificado'];
$stmt = $conn->prepare("SELECT * FROM vendas WHERE vendedor_email = ?");
$stmt->bind_param("s", $vendedorEmail);
$stmt->execute();
$result = $stmt->get_result();
$vendas = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Vendas</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f9;
        }
        .container {
            width: 80%;
            margin: 20px auto;
            padding: 20px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        h1 {
            text-align: center;
            color: #374e74;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        table th, table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: center;
        }
        table th {
            background-color: #374e74;
            color: white;
        }
        .back-button {
            margin-top: 20px;
            text-align: center;
        }
        .back-button a {
            text-decoration: none;
            background-color: #374e74;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            font-size: 16px;
        }
        .back-button a:hover {
            background-color: #2c3e5c;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Gerenciar Vendas</h1>
        <?php if (!empty($vendas)): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Produto</th>
                        <th>Quantidade</th>
                        <th>Preço Total</th>
                        <th>Data da Venda</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($vendas as $venda): ?>
                        <tr>
                            <td><?= htmlspecialchars($venda['id']) ?></td>
                            <td><?= htmlspecialchars($venda['produto']) ?></td>
                            <td><?= htmlspecialchars($venda['quantidade']) ?></td>
                            <td><?= number_format($venda['preco_total'], 2, ',', '.') ?> Kz</td>
                            <td><?= htmlspecialchars($venda['data_venda']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="text-align: center;">Nenhuma venda encontrada.</p>
        <?php endif; ?>
        <div class="back-button">
            <a href="admin_dashboard.php">Voltar</a>
        </div>
    </div>
</body>
</html>