<?php
session_start();
include 'conexao.php';

$carrinho_ids = $_SESSION['carrinho'] ?? [];
$produtos_carrinho = [];
$subtotal = 0;

if (!empty($carrinho_ids)) {
    // Pega os IDs dos produtos para a consulta SQL
    $ids = array_keys($carrinho_ids);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    
    $stmt = $conn->prepare("SELECT id, nome, preco, imagem, quantidade AS estoque_disponivel FROM produtos WHERE id IN ($placeholders)");
    $stmt->bind_param(str_repeat('i', count($ids)), ...$ids);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($produto = $result->fetch_assoc()) {
        $id = $produto['id'];
        $quantidade_no_carrinho = $carrinho_ids[$id];
        $produto['quantidade_no_carrinho'] = $quantidade_no_carrinho;
        $produto['subtotal_item'] = (float)$produto['preco'] * (int)$quantidade_no_carrinho;
        $subtotal += $produto['subtotal_item'];
        $produtos_carrinho[] = $produto;
    }
}

$feedback = $_SESSION['feedback'] ?? null;
unset($_SESSION['feedback']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Meu Carrinho de Compras</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <h1>Meu Carrinho</h1>

    <?php if ($feedback): ?>
        <div class="feedback <?= htmlspecialchars($feedback['type']) ?>">
            <?= htmlspecialchars($feedback['message']) ?>
        </div>
    <?php endif; ?>

    <?php if (empty($produtos_carrinho)): ?>
        <p>Seu carrinho está vazio.</p>
    <?php else: ?>
        <?php foreach ($produtos_carrinho as $item): ?>
            <div class="cart-item">
                <img src="<?= htmlspecialchars($item['imagem']) ?>" alt="<?= htmlspecialchars($item['nome']) ?>">
                <div class="cart-item-info">
                    <h3><?= htmlspecialchars($item['nome']) ?></h3>
                    <p><strong>Preço Unitário:</strong> Kz <?= number_format($item['preco'], 2, ',', '.') ?></p>
                    <p><strong>Subtotal:</strong> Kz <?= number_format($item['subtotal_item'], 2, ',', '.') ?></p>
                </div>
                <div class="cart-item-actions">
                    <form action="carrinho_acoes.php" method="POST" style="display:flex; align-items:center;">
                        <input type="hidden" name="id_produto" value="<?= $item['id'] ?>">
                        <input type="number" name="quantidade" value="<?= $item['quantidade_no_carrinho'] ?>" min="1" max="<?= $item['estoque_disponivel'] ?>">
                        <button type="submit" name="atualizar" class="button">Atualizar</button>
                    </form>
                    <form action="carrinho_acoes.php" method="POST">
                        <input type="hidden" name="id_produto" value="<?= $item['id'] ?>">
                        <button type="submit" name="remover" class="button-danger">Remover</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
        <div class="cart-total">
            Total: Kz <?= number_format($subtotal, 2, ',', '.') ?>
        </div>
        <div class="checkout-button-container">
            <?php if (isset($_SESSION['usuario_verificado']) && $_SESSION['nivel_acesso'] === 'cliente'): ?>
                <form action="criar_pedido.php" method="POST">
                    <button type="submit" name="fazer_pedido" class="button">Fazer Pedido de Compra</button>
                </form>
            <?php elseif (isset($_SESSION['usuario_verificado'])): ?>
                <p style="text-align: right; color: var(--cor-erro);">Apenas clientes podem fazer pedidos.</p>
            <?php else: ?>
                <p style="text-align: right;">Você precisa <a href="login.php">fazer login</a> como cliente para fazer um pedido.</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="back-button" style="margin-top: 20px;">
        <a href="loja.php">Continuar Comprando</a>
    </div>
</div>
</body>
</html>
