<?php
// filepath: c:\xampp\htdocs\PROGAMACAO\Tarefa1\metpagamento.php

include 'conexao.php'; // Inclui o arquivo de conexão com o banco de dados
session_start();

// Verifica se os dados do carrinho e do cliente estão disponíveis na sessão
if (!isset($_SESSION['usuario_verificado'])) {
    echo "<script>alert('Você precisa estar logado para acessar esta página!');</script>";
    echo "<script>window.location.href = 'index.php';</script>";
    exit();
}

$usuario = $_SESSION['usuario_verificado'];

// Busca os itens do carrinho no banco de dados
function buscarCarrinho($conn, $usuario) {
    $stmt = $conn->prepare("SELECT p.nome, c.quantidade, p.preco 
                            FROM carrinho c 
                            INNER JOIN produtos p ON c.produto_id = p.id 
                            WHERE c.usuario_email = ?");
    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Calcula o total do carrinho
$carrinho = buscarCarrinho($conn, $usuario);
$total = 0;
foreach ($carrinho as $item) {
    $total += $item['quantidade'] * $item['preco'];
}

// Gera os dados da fatura
$fatura = [
    'numero' => rand(1000, 9999), // Número aleatório para a fatura
    'data' => date('Y-m-d H:i:s'), // Data e hora da fatura
    'cliente' => $usuario, // Nome do cliente
    'total' => $total, // Valor total da fatura
    'metodoPagamento' => '', // Método de pagamento será definido pelo usuário
    'referencia' => 'REF-' . rand(100000, 999999) // Referência de pagamento
];

// Processa o método de pagamento selecionado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $metodoPagamento = $_POST['metodoPagamento'] ?? 'Referência Bancária';
    $fatura['metodoPagamento'] = $metodoPagamento;

    // Salva a fatura no banco de dados
    $stmt = $conn->prepare("INSERT INTO faturas (numero, data, cliente_email, total, metodo_pagamento, referencia) 
                            VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param(
        "ssssss",
        $fatura['numero'],
        $fatura['data'],
        $fatura['cliente'],
        $fatura['total'],
        $fatura['metodoPagamento'],
        $fatura['referencia']
    );

    if ($stmt->execute()) {
        echo "<script>alert('Fatura gerada com sucesso!');</script>";
        echo "<script>window.location.href = 'confirmacao.php';</script>";
        exit();
    } else {
        echo "<script>alert('Erro ao salvar a fatura. Tente novamente.');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fatura</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: rgb(240, 240, 240);
        }
        .container {
            width: 80%;
            margin: 20px auto;
            padding: 20px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        h1, h2 {
            text-align: center;
            color: rgb(55, 86, 118);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        table th, table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        table th {
            background-color: rgb(55, 86, 118);
            color: white;
        }
        .total {
            text-align: right;
            font-size: 18px;
            margin-top: 20px;
        }
        .back-button {
            display: block;
            text-align: center;
            margin: 20px 0;
        }
        .back-button a {
            text-decoration: none;
            background-color: rgb(55, 86, 118);
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            font-size: 16px;
        }
        .back-button a:hover {
            background-color: rgb(35, 66, 98);
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Fatura</h1>
        <h2>Detalhes da Fatura</h2>
        <table>
            <tr>
                <th>Número da Fatura</th>
                <td><?= htmlspecialchars($fatura['numero']) ?></td>
            </tr>
            <tr>
                <th>Data</th>
                <td><?= htmlspecialchars($fatura['data']) ?></td>
            </tr>
            <tr>
                <th>Cliente</th>
                <td><?= htmlspecialchars($fatura['cliente']) ?></td>
            </tr>
            <tr>
                <th>Total</th>
                <td><?= number_format($fatura['total'], 2, ',', '.') ?> Kz</td>
            </tr>
            <tr>
                <th>Referência</th>
                <td><?= htmlspecialchars($fatura['referencia']) ?></td>
            </tr>
        </table>
        <form method="POST" action="">
            <h2>Escolha o Método de Pagamento</h2>
            <div>
                <input type="radio" id="referencia" name="metodoPagamento" value="Referência Bancária" checked>
                <label for="referencia">Referência Bancária</label>
            </div>
            <div>
                <input type="radio" id="paypal" name="metodoPagamento" value="PayPal">
                <label for="paypal">PayPal</label>
            </div>
            <div style="margin-top: 20px;">
                <button type="submit" style="background-color: rgb(55, 86, 118); color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; font-size: 16px;">
                    Confirmar Pagamento
                </button>
            </div>
        </form>
        <div class="back-button">
            <a href="produto.php">Voltar aos Produtos</a>
        </div>
    </div>
</body>
</html>