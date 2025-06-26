<?php
session_start();
include 'conexao.php';

// Garante que o carrinho está limpo de IDs inválidos (como 0) antes de processar.
if (isset($_SESSION['carrinho'])) {
    $_SESSION['carrinho'] = array_filter(
        $_SESSION['carrinho'],
        function ($key) {
            return $key > 0;
        },
        ARRAY_FILTER_USE_KEY
    );
}

// Redireciona se o carrinho estiver vazio ou o usuário não estiver logado
if (empty($_SESSION['carrinho']) || !isset($_SESSION['usuario_verificado'])) {
    header('Location: loja.php');
    exit();
}

$carrinho = $_SESSION['carrinho'];
$vendedor_email = $_SESSION['usuario_verificado']; // Assumindo que o usuário logado é o vendedor/cliente

$conn->begin_transaction();

try {
    // 1. Pega todos os produtos do carrinho para travar as linhas e verificar o estoque
    $ids = array_keys($carrinho);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    
    // FOR UPDATE trava as linhas para evitar que outro processo altere o estoque durante a transação
    $stmt = $conn->prepare("SELECT id, nome, preco, quantidade FROM produtos WHERE id IN ($placeholders) FOR UPDATE");
    $stmt->bind_param(str_repeat('i', count($ids)), ...$ids);
    $stmt->execute();
    $produtos_db = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Mapeia os produtos do DB por ID para fácil acesso
    $produtos_mapeados = [];
    foreach ($produtos_db as $p) {
        $produtos_mapeados[$p['id']] = $p;
    }

    $pedido_itens = [];
    $total_pedido = 0;

    // 2. Verifica o estoque e prepara os dados para inserção
    foreach ($carrinho as $id_produto => $quantidade_comprar) {
        if (!isset($produtos_mapeados[$id_produto]) || $produtos_mapeados[$id_produto]['quantidade'] < $quantidade_comprar) {
            // Se o produto não existe ou não tem estoque, desfaz a transação
            throw new Exception("Produto '" . ($produtos_mapeados[$id_produto]['nome'] ?? 'ID ' . $id_produto) . "' está fora de estoque ou indisponível.");
        }
        
        // Prepara os dados do item do pedido
        $preco_unitario = $produtos_mapeados[$id_produto]['preco'];
        $subtotal = $preco_unitario * $quantidade_comprar;
        $total_pedido += $subtotal;

        $pedido_itens[] = [
            'id' => $id_produto,
            'nome' => $produtos_mapeados[$id_produto]['nome'],
            'quantidade' => $quantidade_comprar,
            'preco_total' => $subtotal
        ];
    }
    
    // 3. Atualiza o estoque e insere na tabela de vendas
    $stmt_update = $conn->prepare("UPDATE produtos SET quantidade = quantidade - ? WHERE id = ?");
    $stmt_insert = $conn->prepare("INSERT INTO vendas (produto, quantidade, preco_total, vendedor_email) VALUES (?, ?, ?, ?)");

    foreach ($pedido_itens as $item) {
        // Atualiza estoque
        $stmt_update->bind_param('ii', $item['quantidade'], $item['id']);
        $stmt_update->execute();

        // Insere na tabela de vendas
        $stmt_insert->bind_param('sids', $item['nome'], $item['quantidade'], $item['preco_total'], $vendedor_email);
        $stmt_insert->execute();
    }

    // 4. Se tudo deu certo, confirma a transação
    $conn->commit();

    // 5. Limpa o carrinho e guarda o pedido finalizado na sessão para exibir na página de sucesso
    $_SESSION['pedido_finalizado'] = ['itens' => $pedido_itens, 'total' => $total_pedido];
    unset($_SESSION['carrinho']);

} catch (Exception $e) {
    // Se algo deu errado, desfaz todas as operações
    $conn->rollback();
    $_SESSION['feedback'] = ['type' => 'error', 'message' => 'Falha na compra: ' . $e->getMessage()];
    header('Location: carrinho.php');
    exit();
}

$pedido_finalizado = $_SESSION['pedido_finalizado'] ?? null;
unset($_SESSION['pedido_finalizado']);

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Compra Finalizada com Sucesso!</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .success-container { text-align: center; padding: 40px; }
        .success-icon { font-size: 5em; color: #28a745; }
        .order-summary { text-align: left; max-width: 500px; margin: 30px auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px; }
        .order-summary h3 { text-align: center; border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 20px; }
        .order-item { display: flex; justify-content: space-between; margin-bottom: 10px; }
        .order-total { font-weight: bold; font-size: 1.2em; text-align: right; margin-top: 20px; border-top: 1px solid #eee; padding-top: 10px; }
    </style>
</head>
<body>
<div class="container">
    <div class="success-container">
        <div class="success-icon">✔</div>
        <h1>Obrigado pela sua compra!</h1>
        <p>Seu pedido foi processado com sucesso.</p>

        <?php if ($pedido_finalizado): ?>
            <div class="order-summary">
                <h3>Resumo do Pedido</h3>
                <?php foreach ($pedido_finalizado['itens'] as $item): ?>
                    <div class="order-item">
                        <span><?= htmlspecialchars($item['nome']) ?> (x<?= $item['quantidade'] ?>)</span>
                        <span>Kz <?= number_format($item['preco_total'], 2, ',', '.') ?></span>
                    </div>
                <?php endforeach; ?>
                <div class="order-total">
                    <span>TOTAL</span>
                    <span>Kz <?= number_format($pedido_finalizado['total'], 2, ',', '.') ?></span>
                </div>
            </div>
            <h4>Métodos de Pagamento</h4>
            <p>Por favor, realize o pagamento via Transferência Bancária ou na entrega.</p>
        <?php endif; ?>

        <div class="back-button" style="margin-top: 30px;">
            <a href="loja.php" class="button">Voltar à Loja</a>
        </div>
    </div>
</div>
</body>
</html> 