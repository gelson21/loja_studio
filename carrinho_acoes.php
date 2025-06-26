<?php
session_start();
include 'conexao.php';

// Inicializa o carrinho se não existir
if (!isset($_SESSION['carrinho'])) {
    $_SESSION['carrinho'] = [];
}

// Função para adicionar produto ao carrinho
function adicionarAoCarrinho($id_produto, $quantidade, $conn) {
    if ($quantidade <= 0) {
        return; // Não adiciona se a quantidade for zero ou negativa
    }

    // 1. Verifica o estoque atual do produto
    $stmt = $conn->prepare("SELECT quantidade FROM produtos WHERE id = ?");
    $stmt->bind_param("i", $id_produto);
    $stmt->execute();
    $result = $stmt->get_result();
    $produto = $result->fetch_assoc();

    if (!$produto) {
        $_SESSION['feedback'] = ['type' => 'error', 'message' => 'Produto não encontrado.'];
        return;
    }

    $estoque_disponivel = $produto['quantidade'];
    $quantidade_no_carrinho = 0;

    // Verifica se o produto já está no carrinho para somar a quantidade
    if (isset($_SESSION['carrinho'][$id_produto])) {
        $quantidade_no_carrinho = $_SESSION['carrinho'][$id_produto];
    }

    // 2. Verifica se a quantidade desejada + o que já está no carrinho excede o estoque
    if (($quantidade + $quantidade_no_carrinho) > $estoque_disponivel) {
        $_SESSION['feedback'] = ['type' => 'error', 'message' => 'Quantidade solicitada indisponível no estoque.'];
        return;
    }

    // 3. Adiciona ou atualiza a quantidade no carrinho
    $_SESSION['carrinho'][$id_produto] = $quantidade_no_carrinho + $quantidade;
    $_SESSION['feedback'] = ['type' => 'success', 'message' => 'Produto adicionado ao carrinho!'];
}

// Função para remover produto do carrinho
function removerDoCarrinho($id_produto) {
    if (isset($_SESSION['carrinho'][$id_produto])) {
        unset($_SESSION['carrinho'][$id_produto]);
        $_SESSION['feedback'] = ['type' => 'success', 'message' => 'Produto removido do carrinho.'];
    }
}

// Função para atualizar a quantidade de um produto no carrinho
function atualizarQuantidade($id_produto, $quantidade, $conn) {
     if ($quantidade <= 0) {
        removerDoCarrinho($id_produto);
        return;
    }

    $stmt = $conn->prepare("SELECT quantidade FROM produtos WHERE id = ?");
    $stmt->bind_param("i", $id_produto);
    $stmt->execute();
    $result = $stmt->get_result();
    $produto = $result->fetch_assoc();

    if ($produto && $quantidade > $produto['quantidade']) {
        $_SESSION['feedback'] = ['type' => 'error', 'message' => 'Estoque insuficiente para a quantidade solicitada.'];
        // Mantém a quantidade máxima disponível
        $_SESSION['carrinho'][$id_produto] = $produto['quantidade'];
    } else {
        $_SESSION['carrinho'][$id_produto] = $quantidade;
        $_SESSION['feedback'] = ['type' => 'success', 'message' => 'Carrinho atualizado.'];
    }
}


// --- Lógica do Controller ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['adicionar'])) {
        $id_produto = intval($_POST['id_produto']);
        $quantidade = intval($_POST['quantidade']);
        adicionarAoCarrinho($id_produto, $quantidade, $conn);
        header('Location: loja.php'); // Redireciona de volta para a loja
        exit();
    }

    if (isset($_POST['remover'])) {
        $id_produto = intval($_POST['id_produto']);
        removerDoCarrinho($id_produto);
        header('Location: carrinho.php'); // Redireciona para o carrinho
        exit();
    }
    
    if (isset($_POST['atualizar'])) {
        $id_produto = intval($_POST['id_produto']);
        $quantidade = intval($_POST['quantidade']);
        atualizarQuantidade($id_produto, $quantidade, $conn);
        header('Location: carrinho.php'); // Redireciona para o carrinho
        exit();
    }
}

// Se nenhuma ação for especificada, redireciona para a loja
header('Location: loja.php');
exit(); 