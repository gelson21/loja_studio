<?php
session_start();
include 'conexao.php';

$produtos_sql = "SELECT id, nome, descricao, preco, quantidade, imagem FROM produtos WHERE quantidade > 0 ORDER BY id DESC";
$produtos_result = $conn->query($produtos_sql);
$produtos = $produtos_result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nossa Loja</title>
    <style>
        /* Força a cor verde nos botões principais da loja */
        .button, .add-to-cart-form .button {
            background-color: #2E8B57 !important; /* Verde Mar */
            border-color: #2E8B57 !important;
        }
        .button:hover, .add-to-cart-form .button:hover {
            background-color: #256d45 !important; /* Tom de verde mais escuro no hover */
        }

        /* Grade de produtos organizada */
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(270px, 1fr));
            gap: 20px;
            padding: 25px 0;
        }

        .product-card {
            background-color: #ffffff;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0,0,0,0.05);
            display: flex;
            flex-direction: column;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px rgba(0,0,0,0.1);
        }
        .product-card img {
            width: 100%;
            height: 180px;
            object-fit: cover;
        }
        .product-info {
            padding: 15px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }
        .product-info h3 {
            margin: 0 0 10px 0;
            font-size: 1.2em;
            color: #2E8B57;
        }
        .product-info .price {
            font-size: 1.3em;
            font-weight: bold;
            color: #2E8B57;
            margin: 10px 0;
        }
        .product-info .stock {
            font-size: 0.9em;
            color: #7f8c8d;
            margin-bottom: 15px;
        }
        .product-info .description {
            flex-grow: 1;
            color: #555;
            font-size: 0.95em;
        }
        .add-to-cart-form {
            background-color: #f7fcf9;
            padding: 15px;
            border-top: 1px solid #e9e9e9;
        }
        .add-to-cart-form .form-group {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .add-to-cart-form input[type="number"] {
            width: 70px;
            padding: 8px;
            border-radius: 5px;
            border: 1px solid #ddd;
        }
        .add-to-cart-form .button {
            width: auto;
            padding: 8px 15px;
        }
    </style>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
            <h1>Nossos Produtos</h1>
            <div>
                <a href="carrinho.php" class="button">Ver Carrinho</a>
                <?php if (isset($_SESSION['usuario_id']) && isset($_SESSION['nivel_acesso'])): ?>
                    <?php if ($_SESSION['nivel_acesso'] === 'cliente'): ?>
                        <a href="cliente_dashboard.php" class="button">Meu Painel</a>
                    <?php endif; ?>
                    <a href="index.php" class="button-danger">Sair</a>
                <?php else: ?>
                    <a href="login.php" class="button">Login</a>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="products-grid">
            <?php foreach ($produtos as $produto): ?>
                <div class="product-card">
                    <img src="<?= htmlspecialchars($produto['imagem']) ?>" alt="<?= htmlspecialchars($produto['nome']) ?>">
                    <div class="product-info">
                        <h3><?= htmlspecialchars($produto['nome']) ?></h3>
                        <p class="description"><?= htmlspecialchars($produto['descricao']) ?></p>
                        <p class="stock">Disponível: <?= htmlspecialchars($produto['quantidade']) ?></p>
                        <p class="price">Kz <?= number_format($produto['preco'], 2, ',', '.') ?></p>
                    </div>
                    <div class="add-to-cart-form">
                        <form action="carrinho_acoes.php" method="POST">
                            <input type="hidden" name="id_produto" value="<?= $produto['id'] ?>">
                            <div class="form-group">
                                <input type="number" name="quantidade" value="1" min="1" max="<?= $produto['quantidade'] ?>" required>
                                <button type="submit" name="adicionar" class="button">Adicionar</button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html> 