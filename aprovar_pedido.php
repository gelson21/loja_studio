<?php
session_start();
include 'conexao.php';

// Proteção da página: Apenas vendedores podem acessar
if (!isset($_SESSION['nivel_acesso']) || !in_array($_SESSION['nivel_acesso'], ['vendedor', 'admin'])) {
    echo "<script>alert('Acesso negado!'); window.location.href = 'login.php';</script>";
    exit();
}

$pedido_id = $_GET['id'] ?? null;
$vendedor_id = $_SESSION['usuario_id'] ?? null;

if (!$pedido_id) {
    $_SESSION['feedback_vendedor'] = ['type' => 'error', 'message' => 'Pedido não encontrado.'];
    header("Location: vendedor_dashboard.php");
    exit();
}

// Iniciar transação
$conn->begin_transaction();

try {
    // Buscar informações do pedido
    $stmt = $conn->prepare("
        SELECT p.*, pd.produto_id, pd.quantidade, pd.preco_unitario
        FROM pedidos p 
        JOIN pedidos_produtos pd ON p.id = pd.pedido_id 
        WHERE p.id = ? AND p.status = 'pendente'
    ");
    $stmt->bind_param("i", $pedido_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $pedido_produtos = $result->fetch_all(MYSQLI_ASSOC);

    if (empty($pedido_produtos)) {
        throw new Exception("Pedido não encontrado ou já processado.");
    }

    // Verificar estoque e atualizar
    foreach ($pedido_produtos as $item) {
        $stmt = $conn->prepare("
            SELECT quantidade 
            FROM produtos 
            WHERE id = ? AND quantidade >= ?
        ");
        $stmt->bind_param("ii", $item['produto_id'], $item['quantidade']);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            throw new Exception("Estoque insuficiente para o produto ID: " . $item['produto_id']);
        }

        // Atualizar estoque
        $stmt = $conn->prepare("
            UPDATE produtos 
            SET quantidade = quantidade - ? 
            WHERE id = ?
        ");
        $stmt->bind_param("ii", $item['quantidade'], $item['produto_id']);
        $stmt->execute();
    }

    // Atualizar status do pedido e adicionar vendedor
    $stmt = $conn->prepare("
        UPDATE pedidos 
        SET status = 'aprovado', 
            vendedor_id = ?, 
            data_aprovacao = NOW() 
        WHERE id = ?
    ");
    $stmt->bind_param("ii", $vendedor_id, $pedido_id);
    $stmt->execute();

    // Atualizar estatísticas de vendas do vendedor
    $stmt = $conn->prepare("
        UPDATE usuarios 
        SET total_vendas = total_vendas + 1,
            valor_total_vendas = valor_total_vendas + ?
        WHERE id = ?
    ");
    $stmt->bind_param("di", $pedido_produtos[0]['total_pedido'], $vendedor_id);
    $stmt->execute();

    // Atualizar vendas do dia atual
    $hoje = date('Y-m-d');
    $stmt = $conn->prepare("
        INSERT INTO vendas_dia (data, vendedor_id, total_pedidos, total_vendas)
        VALUES (?, ?, 1, ?)
        ON DUPLICATE KEY UPDATE 
            total_pedidos = total_pedidos + 1,
            total_vendas = total_vendas + ?
    ");
    $stmt->bind_param("sidd", $hoje, $vendedor_id, $pedido_produtos[0]['total_pedido'], $pedido_produtos[0]['total_pedido']);
    $stmt->execute();

    // Atualizar histórico de vendas dos últimos 7 dias
    $stmt = $conn->prepare("
        INSERT INTO historico_vendas (
            pedido_id, 
            vendedor_id, 
            cliente_id, 
            data_pedido, 
            total_pedido, 
            data_aprovacao,
            produtos_vendidos
        )
        SELECT 
            p.id,
            p.vendedor_id,
            p.id_cliente,
            p.data_pedido,
            p.total_pedido,
            p.data_aprovacao,
            GROUP_CONCAT(
                CONCAT(
                    pr.nome, ' (', pp.quantidade, ' x Kz ', 
                    FORMAT(pp.preco_unitario, 2), ')'
                ) SEPARATOR ', '
            )
        FROM pedidos p
        JOIN pedidos_produtos pp ON p.id = pp.pedido_id
        JOIN produtos pr ON pp.produto_id = pr.id
        WHERE p.id = ?
        GROUP BY p.id
    ");
    $stmt->bind_param("i", $pedido_id);
    $stmt->execute();

    // Limpar histórico antigo (mais de 7 dias)
    $stmt = $conn->prepare("
        DELETE FROM historico_vendas 
        WHERE data_pedido < DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    ");
    $stmt->execute();

    // Confirmar transação
    $conn->commit();

    $_SESSION['feedback_vendedor'] = [
        'type' => 'success',
        'message' => 'Pedido aprovado com sucesso! Estatísticas atualizadas.'
    ];
} catch (Exception $e) {
    // Reverter transação em caso de erro
    $conn->rollback();
    $_SESSION['feedback_vendedor'] = [
        'type' => 'error',
        'message' => 'Erro ao aprovar pedido: ' . $e->getMessage()
    ];
}

header("Location: vendedor_dashboard.php");
exit(); 