<?php
session_start();
include 'conexao.php';

// Apenas clientes logados podem criar pedidos
if (!isset($_SESSION['usuario_id']) || $_SESSION['nivel_acesso'] !== 'cliente') {
    header('Location: login.php');
    exit();
}

// Garante que o carrinho não está vazio
if (empty($_SESSION['carrinho'])) {
    header('Location: carrinho.php');
    exit();
}

$carrinho = $_SESSION['carrinho'];
$id_cliente = $_SESSION['usuario_id'];
$total_pedido = 0;

$conn->begin_transaction();

try {
    // 1. Pega os produtos do carrinho para calcular o total e verificar os dados
    $ids = array_keys($carrinho);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    
    $stmt_produtos = $conn->prepare("SELECT id, preco FROM produtos WHERE id IN ($placeholders)");
    $stmt_produtos->bind_param(str_repeat('i', count($ids)), ...$ids);
    $stmt_produtos->execute();
    $result = $stmt_produtos->get_result();
    
    if ($result->num_rows !== count($ids)) {
        throw new Exception("Um ou mais produtos no seu carrinho não foram encontrados no banco de dados.");
    }
    $produtos_db = $result->fetch_all(MYSQLI_ASSOC);

    // Mapeia para fácil acesso e calcula o total
    $produtos_mapeados = [];
    foreach ($produtos_db as $p) {
        $produtos_mapeados[$p['id']] = $p;
    }

    $itens_do_pedido = [];
    foreach ($carrinho as $id_produto => $quantidade) {
        if (!isset($produtos_mapeados[$id_produto])) {
            throw new Exception("Produto com ID $id_produto não foi encontrado.");
        }
        $preco_unitario = (float)$produtos_mapeados[$id_produto]['preco'];
        $total_pedido += $preco_unitario * (int)$quantidade;
        $itens_do_pedido[] = [
            'id_produto' => $id_produto,
            'quantidade' => $quantidade,
            'preco_unitario' => $preco_unitario
        ];
    }

    // 2. Insere o pedido na tabela `pedidos`
    $stmt_pedido = $conn->prepare("INSERT INTO pedidos (id_cliente, total_pedido, status) VALUES (?, ?, 'pendente')");
    $stmt_pedido->bind_param('id', $id_cliente, $total_pedido);
    $stmt_pedido->execute();
    $id_pedido = $conn->insert_id;

    // 3. Insere os itens na tabela `pedido_itens`
    $stmt_itens = $conn->prepare("INSERT INTO pedido_itens (id_pedido, id_produto, quantidade, preco_unitario) VALUES (?, ?, ?, ?)");
    foreach ($itens_do_pedido as $item) {
        $stmt_itens->bind_param('iiid', $id_pedido, $item['id_produto'], $item['quantidade'], $item['preco_unitario']);
        $stmt_itens->execute();
    }

    // 4. Confirma a transação
    $conn->commit();

    // 5. Limpa o carrinho e redireciona
    unset($_SESSION['carrinho']);
    $_SESSION['feedback'] = ['type' => 'success', 'message' => 'Seu pedido foi enviado com sucesso e aguarda aprovação do vendedor!'];
    header('Location: loja.php');
    exit();

} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['feedback'] = ['type' => 'error', 'message' => 'Falha ao criar o pedido: ' . $e->getMessage()];
    header('Location: carrinho.php');
    exit();
}
?> 