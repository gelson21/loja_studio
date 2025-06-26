<?php
session_start();
include 'conexao.php';

// Adiciona produto ao carrinho se enviado via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['produto'])) {
    $produto = $_POST['produto'];
    if (!isset($_SESSION['carrinho'])) {
        $_SESSION['carrinho'] = [];
    }
    $_SESSION['carrinho'][] = $produto;
    header('Location: carrinho.php');
    exit;
}

// Busca todos os produtos cadastrados
$stmt = $conn->prepare("SELECT id, nome, descricao, preco, imagem, categoria FROM produtos");
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Produtos da Loja</title>
    <link rel="stylesheet" href="admin-style.css">
</head>
<body>
    <div class="content">
        <h1>Produtos da Loja</h1>
        <div class="product-grid">
            <?php while ($produto = $result->fetch_assoc()): ?>
                <div class="product-card">
                    <?php if (!empty($produto['imagem']) && file_exists($produto['imagem'])): ?>
                        <img src="<?= htmlspecialchars($produto['imagem']) ?>" alt="<?= htmlspecialchars($produto['nome']) ?>">
                    <?php else: ?>
                        <img src="placeholder.jpg" alt="Sem imagem">
                    <?php endif; ?>
                    <div class="product-card-content">
                        <h3><?= htmlspecialchars($produto['nome']) ?></h3>
                        <p><?= htmlspecialchars($produto['descricao']) ?></p>
                        <p><strong>Preço:</strong> R$ <?= number_format($produto['preco'], 2, ',', '.') ?></p>
                        <form method="post" style="text-align: center;">
                            <input type="hidden" name="produto" value="<?= htmlspecialchars($produto['nome']) ?> (R$ <?= number_format($produto['preco'], 2, ',', '.') ?>)">
                            <button type="submit" class="button">Adicionar ao Carrinho</button>
                        </form>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
        <div class="back-button">
            <a href="erdi.php">Continuar Comprando</a>
        </div>
    </div>
</body>
</html>
