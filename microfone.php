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

// Busca apenas produtos da categoria "microfone"
$stmt = $conn->prepare("SELECT id, nome, descricao, preco, imagem, categoria FROM produtos WHERE categoria = ?");
$categoria = 'microfones';
$stmt->bind_param("s", $categoria);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Produtos da Loja</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(120deg, #43e97b 0%, #38f9d7 100%);
            color: #0a3d2c;
            margin: 0;
            min-height: 100vh;
        }
        h1 {
            text-align: center;
            color: #0a3d2c;
            font-size: 2.2rem;
            margin-top: 40px;
            letter-spacing: 1px;
            text-shadow: 1px 2px 8px #fff9, 0 2px 8px #38f9d7;
        }
        ul {
            list-style-type: none;
            padding: 0;
            max-width: 1100px;
            margin: 40px auto 0 auto;
            display: flex;
            flex-wrap: wrap;
            gap: 30px;
            justify-content: center;
        }
        li {
            background: rgba(255,255,255,0.95);
            border: 1px solid #38f9d7;
            border-radius: 14px;
            margin: 0;
            padding: 25px 30px 20px 30px;
            box-shadow: 0 4px 16px rgba(56,249,215,0.10);
            width: 270px;
            display: flex;
            flex-direction: column;
            align-items: center;
            transition: box-shadow 0.2s, transform 0.2s;
        }
        li:hover {
            box-shadow: 0 8px 32px #38f9d7aa;
            transform: translateY(-4px) scale(1.03);
        }
        li h2 {
            color: #0a3d2c;
            font-size: 1.2rem;
            margin-bottom: 10px;
            text-align: center;
        }
        li img {
            display: block;
            margin: 0 auto 10px;
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 10px;
            box-shadow: 0 2px 8px #38f9d7aa;
        }
        li p {
            font-size: 1.08rem;
            margin: 10px 0 8px 0;
            color: #228B22;
            font-weight: bold;
        }
        li .desc {
            font-size: 0.98rem;
            color: #444;
            margin-bottom: 10px;
            font-weight: normal;
        }
        li .cat {
            font-size: 0.92rem;
            color: #0a3d2c;
            margin-bottom: 10px;
        }
        li form {
            width: 100%;
            display: flex;
            justify-content: center;
        }
        li button {
            background: linear-gradient(90deg, #43e97b 0%, #38f9d7 100%);
            color: #0a3d2c;
            border: none;
            padding: 10px 22px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: bold;
            box-shadow: 0 2px 8px #38f9d7aa;
            transition: background 0.2s, color 0.2s;
        }
        li button:hover {
            background: #0a3d2c;
            color: #fff;
        }
    </style>
</head>
<body>
    <h1>Produtos da Loja</h1>
    <ul>
        <?php while ($produto = $result->fetch_assoc()): ?>
            <li>
                <h2><?= htmlspecialchars($produto['nome']) ?></h2>
                <?php if (!empty($produto['imagem'])): ?>
                    <img src="<?= htmlspecialchars($produto['imagem']) ?>" alt="<?= htmlspecialchars($produto['nome']) ?>">
                <?php else: ?>
                    <img src="sem-imagem.png" alt="Sem imagem">
                <?php endif; ?>
                <div class="desc"><?= htmlspecialchars($produto['descricao']) ?></div>
                <div class="cat">Categoria: <?= htmlspecialchars($produto['categoria']) ?></div>
                <p>Preço: kz <?= number_format($produto['preco'], 2, ',', '.') ?></p>
                <form method="post" style="display:inline;">
                    <input type="hidden" name="produto" value="<?= htmlspecialchars($produto['nome']) ?> (R$ <?= number_format($produto['preco'], 2, ',', '.') ?>)">
                    <button type="submit">Adicionar ao Carrinho</button>
                </form>
            </li>
        <?php endwhile; ?>
    </ul>
    <div class="voltar-container" style="text-align:center; margin: 30px;">
        <a href="erdi.php" style="text-decoration:none;">
            <button style="background: linear-gradient(90deg, #43e97b 0%, #38f9d7 100%); color: #0a3d2c; border: none; padding: 14px 40px; border-radius: 8px; font-size: 1.1rem; font-weight: bold; cursor: pointer; box-shadow: 0 2px 8px #38f9d7aa; transition: background 0.2s, color 0.2s;">
                Voltar
            </button>
</body>
</html>