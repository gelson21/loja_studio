<?php
include 'conexao.php';
session_start();

if (!isset($_SESSION['nivel_acesso']) || $_SESSION['nivel_acesso'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$edit_mode = false;
$edit_product = null;

if (isset($_GET['edit_id'])) {
    $edit_mode = true;
    $id = intval($_GET['edit_id']);
    $stmt = $conn->prepare("SELECT id, nome, descricao, preco, categoria, quantidade, imagem FROM produtos WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $edit_product = $result->fetch_assoc();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar_produto'])) {
    $nome = trim($_POST['nome']);
    $descricao = trim($_POST['descricao']);
    $preco = floatval($_POST['preco']);
    $categoria_id = intval($_POST['categoria']);
    $quantidade = intval($_POST['quantidade']);
    $id_produto = isset($_POST['id_produto']) ? intval($_POST['id_produto']) : 0;
    
    $imagemDestino = $_POST['imagem_existente'] ?? null;
    $mensagem = '';

    // Busca o nome da categoria a partir do ID
    $categoria_nome = '';
    if ($categoria_id > 0) {
        $stmt_cat = $conn->prepare("SELECT nome FROM categorias WHERE id = ?");
        $stmt_cat->bind_param("i", $categoria_id);
        $stmt_cat->execute();
        $result_cat = $stmt_cat->get_result();
        if($row = $result_cat->fetch_assoc()) {
            $categoria_nome = $row['nome'];
        }
    }

    if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = "uploads/";
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        $imagemNome = uniqid() . "_" . basename($_FILES['imagem']['name']);
        $imagemDestino = $uploadDir . $imagemNome;
        if (!move_uploaded_file($_FILES['imagem']['tmp_name'], $imagemDestino)) {
            $mensagem = 'Erro ao fazer upload da nova imagem.';
            $imagemDestino = $_POST['imagem_existente'] ?? null;
        }
    }

    if (empty($mensagem) && !empty($categoria_nome)) {
        if ($id_produto > 0) {
            $stmt = $conn->prepare("UPDATE produtos SET nome = ?, descricao = ?, preco = ?, categoria = ?, quantidade = ?, imagem = ? WHERE id = ?");
            $stmt->bind_param("ssdsisi", $nome, $descricao, $preco, $categoria_nome, $quantidade, $imagemDestino, $id_produto);
            $mensagem = 'Produto atualizado com sucesso!';
        } else {
            $stmt = $conn->prepare("INSERT INTO produtos (nome, descricao, preco, categoria, quantidade, imagem) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssdsis", $nome, $descricao, $preco, $categoria_nome, $quantidade, $imagemDestino);
            $mensagem = 'Produto adicionado com sucesso!';
        }

        if ($stmt && $stmt->execute()) {
            $_SESSION['feedback'] = ['type' => 'success', 'message' => $mensagem];
        } else {
            $_SESSION['feedback'] = ['type' => 'error', 'message' => 'Erro ao salvar o produto.'];
        }
    } else {
        $msg_final = !empty($mensagem) ? $mensagem : 'Erro: Categoria inválida.';
        $_SESSION['feedback'] = ['type' => 'error', 'message' => $msg_final];
    }
    
    header("Location: gerenciar_produtos.php");
    exit();
}

if (isset($_POST['remover_produto'])) {
    $id = intval($_POST['id']);
    $stmt = $conn->prepare("DELETE FROM produtos WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $_SESSION['feedback'] = ['type' => 'success', 'message' => 'Produto removido com sucesso!'];
    } else {
        $_SESSION['feedback'] = ['type' => 'error', 'message' => 'Erro ao remover o produto.'];
    }
    header("Location: gerenciar_produtos.php");
    exit();
}

$produtos_sql = "SELECT id, nome, descricao, preco, quantidade, imagem, categoria FROM produtos ORDER BY id DESC";
$produtos_result = $conn->query($produtos_sql);
$produtos = $produtos_result->fetch_all(MYSQLI_ASSOC);

$categorias_result = $conn->query("SELECT * FROM categorias");
$categorias = $categorias_result->fetch_all(MYSQLI_ASSOC);

$feedback = $_SESSION['feedback'] ?? null;
unset($_SESSION['feedback']);

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Produtos</title>
    <link rel="stylesheet" href="vendedor-style.css">
    <style>
        .feedback {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            color: #fff;
            text-align: center;
        }
        .feedback.success { background-color: #28a745; }
        .feedback.error { background-color: #dc3545; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Gerenciar Produtos</h1>

        <?php if ($feedback): ?>
            <div class="feedback <?= htmlspecialchars($feedback['type']) ?>">
                <?= htmlspecialchars($feedback['message']) ?>
            </div>
        <?php endif; ?>

        <div class="form-container">
            <h2><?= $edit_mode ? 'Editar Produto' : 'Adicionar Novo Produto' ?></h2>
            <form method="POST" action="gerenciar_produtos.php" enctype="multipart/form-data">
                <?php if ($edit_mode && $edit_product): ?>
                    <input type="hidden" name="id_produto" value="<?= $edit_product['id'] ?>">
                    <input type="hidden" name="imagem_existente" value="<?= htmlspecialchars($edit_product['imagem']) ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label for="nome">Nome do Produto</label>
                    <input type="text" id="nome" name="nome" value="<?= htmlspecialchars($edit_product['nome'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label for="descricao">Descrição</label>
                    <textarea id="descricao" name="descricao" required><?= htmlspecialchars($edit_product['descricao'] ?? '') ?></textarea>
                </div>
                <div class="form-group">
                    <label for="preco">Preço (Kz)</label>
                    <input type="number" step="0.01" id="preco" name="preco" value="<?= htmlspecialchars($edit_product['preco'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label for="quantidade">Quantidade em Estoque</label>
                    <input type="number" id="quantidade" name="quantidade" value="<?= htmlspecialchars($edit_product['quantidade'] ?? '0') ?>" required>
                </div>
                <div class="form-group">
                    <label for="categoria">Categoria</label>
                    <select id="categoria" name="categoria" required>
                        <option value="">Selecione uma categoria</option>
                        <?php foreach ($categorias as $categoria): ?>
                            <option value="<?= $categoria['id'] ?>" <?= isset($edit_product) && $edit_product['categoria'] == $categoria['nome'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($categoria['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="imagem">Imagem do Produto</label>
                    <input type="file" id="imagem" name="imagem" accept="image/*">
                    <?php if ($edit_mode && !empty($edit_product['imagem'])): ?>
                        <p>Imagem atual: <img src="<?= htmlspecialchars($edit_product['imagem']) ?>" alt="Imagem atual" width="50"></p>
                    <?php endif; ?>
                </div>

                <button type="submit" name="salvar_produto" class="button"><?= $edit_mode ? 'Salvar Alterações' : 'Adicionar Produto' ?></button>
                <?php if ($edit_mode): ?>
                    <a href="gerenciar_produtos.php" class="button button-edit">Cancelar Edição</a>
                <?php endif; ?>
            </form>
        </div>

        <h2>Produtos Cadastrados</h2>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Imagem</th>
                        <th>Nome</th>
                        <th>Descrição</th>
                        <th>Preço</th>
                        <th>Qtd. em Estoque</th>
                        <th>Categoria</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($produtos as $produto): ?>
                        <tr>
                            <td><?= htmlspecialchars($produto['id']) ?></td>
                            <td><img src="<?= htmlspecialchars($produto['imagem']) ?>" alt="Imagem do Produto" width="50"></td>
                            <td><?= htmlspecialchars($produto['nome']) ?></td>
                            <td><?= htmlspecialchars($produto['descricao']) ?></td>
                            <td>Kz <?= number_format($produto['preco'], 2, ',', '.') ?></td>
                            <td><?= htmlspecialchars($produto['quantidade']) ?></td>
                            <td><?= htmlspecialchars($produto['categoria']) ?></td>
                            <td class="actions">
                                <a href="gerenciar_produtos.php?edit_id=<?= $produto['id'] ?>" class="button button-edit">Editar</a>
                                <form method="POST" action="gerenciar_produtos.php" style="display: inline;" onsubmit="return confirm('Tem certeza que deseja remover este produto?');">
                                    <input type="hidden" name="id" value="<?= $produto['id'] ?>">
                                    <button type="submit" name="remover_produto" class="button-danger">Remover</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="back-button">
            <a href="admin_dashboard.php">Voltar</a>
        </div>
    </div>
</body>
</html>