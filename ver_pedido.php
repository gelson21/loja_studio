<?php
session_start();
include 'conexao.php';

// Proteção da página: Apenas vendedores ou admins podem acessar
if (!isset($_SESSION['nivel_acesso']) || !in_array($_SESSION['nivel_acesso'], ['vendedor', 'admin'])) {
    echo "<script>alert('Acesso negado!'); window.location.href = 'login.php';</script>";
    exit();
}

if (!isset($_GET['id'])) {
    header('Location: vendedor_dashboard.php');
    exit();
}
$id_pedido = intval($_GET['id']);

// Lógica para aprovar ou rejeitar o pedido
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao_pedido'])) {
    $acao = $_POST['acao_pedido'];

    if ($acao === 'aprovar') {
        $conn->begin_transaction();
        try {
            $stmt_itens = $conn->prepare("SELECT pi.id_produto, pi.quantidade, p.nome, p.quantidade as estoque_disponivel FROM pedido_itens pi JOIN produtos p ON pi.id_produto = p.id WHERE pi.id_pedido = ? FOR UPDATE");
            $stmt_itens->bind_param('i', $id_pedido);
            $stmt_itens->execute();
            $itens = $stmt_itens->get_result()->fetch_all(MYSQLI_ASSOC);

            if (empty($itens)) {
                throw new Exception("Pedido não encontrado ou já processado.");
            }

            foreach ($itens as $item) {
                if ($item['quantidade'] > $item['estoque_disponivel']) {
                    throw new Exception("Estoque insuficiente para o produto: " . htmlspecialchars($item['nome']));
                }
            }
            
            $stmt_update_estoque = $conn->prepare("UPDATE produtos SET quantidade = quantidade - ? WHERE id = ?");
            foreach ($itens as $item) {
                $stmt_update_estoque->bind_param('ii', $item['quantidade'], $item['id_produto']);
                $stmt_update_estoque->execute();
            }

            $stmt_status = $conn->prepare("UPDATE pedidos SET status = 'aprovado' WHERE id = ?");
            $stmt_status->bind_param('i', $id_pedido);
            $stmt_status->execute();
            
            // Inserir os itens na tabela de vendas para o registro financeiro
            $stmt_venda = $conn->prepare("INSERT INTO vendas (id_pedido, produto, quantidade, preco_total, vendedor_email) VALUES (?, ?, ?, ?, ?)");
            $vendedor_email = $_SESSION['usuario_verificado']; // Email do vendedor/admin que aprovou

            foreach ($itens as $item) {
                $subtotal_item = $item['quantidade'] * $item['preco_unitario'];
                $stmt_venda->bind_param('isids', $id_pedido, $item['nome'], $item['quantidade'], $subtotal_item, $vendedor_email);
                $stmt_venda->execute();
            }

            $stmt_cliente = $conn->prepare("SELECT id_cliente FROM pedidos WHERE id = ?");
            $stmt_cliente->bind_param('i', $id_pedido);
            $stmt_cliente->execute();
            $id_cliente = $stmt_cliente->get_result()->fetch_assoc()['id_cliente'];
            
            $mensagem = "Seu pedido #" . $id_pedido . " foi aprovado e está sendo preparado para envio!";
            $stmt_notify = $conn->prepare("INSERT INTO notificacoes (id_cliente, mensagem) VALUES (?, ?)");
            $stmt_notify->bind_param('is', $id_cliente, $mensagem);
            $stmt_notify->execute();
            
            $conn->commit();
            $_SESSION['feedback_vendedor'] = ['type' => 'success', 'message' => "Pedido #" . $id_pedido . " aprovado com sucesso."];
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['feedback_vendedor'] = ['type' => 'error', 'message' => "Erro ao aprovar pedido #" . $id_pedido . ": " . $e->getMessage()];
        }
    } elseif ($acao === 'rejeitar') {
        $motivo = $_POST['motivo_rejeicao'] ?? 'Não especificado';
        $conn->query("UPDATE pedidos SET status = 'rejeitado' WHERE id = $id_pedido");
        
        $stmt_cliente = $conn->prepare("SELECT id_cliente FROM pedidos WHERE id = ?");
        $stmt_cliente->bind_param('i', $id_pedido);
        $stmt_cliente->execute();
        $id_cliente = $stmt_cliente->get_result()->fetch_assoc()['id_cliente'];
        
        $mensagem = "Seu pedido #" . $id_pedido . " foi rejeitado. Motivo: " . htmlspecialchars($motivo);
        $stmt_notify = $conn->prepare("INSERT INTO notificacoes (id_cliente, mensagem) VALUES (?, ?)");
        $stmt_notify->bind_param('is', $id_cliente, $mensagem);
        $stmt_notify->execute();
        
        $_SESSION['feedback_vendedor'] = ['type' => 'success', 'message' => "Pedido #" . $id_pedido . " rejeitado."];
    }
    header('Location: vendedor_dashboard.php');
    exit();
}


// Buscar detalhes do pedido
$stmt_pedido = $conn->prepare("SELECT p.*, u.nome as cliente_nome, u.email as cliente_email FROM pedidos p JOIN usuarios u ON p.id_cliente = u.id WHERE p.id = ?");
$stmt_pedido->bind_param('i', $id_pedido);
$stmt_pedido->execute();
$pedido = $stmt_pedido->get_result()->fetch_assoc();

if (!$pedido) {
    die("Pedido não encontrado.");
}

// Buscar itens do pedido
$stmt_itens = $conn->prepare("SELECT pi.*, p.nome as produto_nome, p.imagem as produto_imagem FROM pedido_itens pi JOIN produtos p ON pi.id_produto = p.id WHERE pi.id_pedido = ?");
$stmt_itens->bind_param('i', $id_pedido);
$stmt_itens->execute();
$itens_pedido = $stmt_itens->get_result()->fetch_all(MYSQLI_ASSOC);

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Detalhes do Pedido #<?= $id_pedido ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <h1>Detalhes do Pedido #<?= $id_pedido ?></h1>
    
    <div>
        <h3>Informações do Cliente</h3>
        <p><strong>Nome:</strong> <?= htmlspecialchars($pedido['cliente_nome']) ?></p>
        <p><strong>Email:</strong> <?= htmlspecialchars($pedido['cliente_email']) ?></p>
    </div>

    <div>
        <h3>Itens do Pedido</h3>
        <table>
            <thead>
                <tr>
                    <th>Produto</th>
                    <th>Imagem</th>
                    <th>Quantidade</th>
                    <th>Preço Unitário</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($itens_pedido as $item): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['produto_nome']) ?></td>
                        <td><img src="<?= htmlspecialchars($item['produto_imagem']) ?>" width="50"></td>
                        <td><?= $item['quantidade'] ?></td>
                        <td>Kz <?= number_format($item['preco_unitario'], 2, ',', '.') ?></td>
                        <td>Kz <?= number_format($item['preco_unitario'] * $item['quantidade'], 2, ',', '.') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div class="cart-total" style="text-align: right; font-size: 1.2em; margin-top: 10px;">
            <strong>Total do Pedido: Kz <?= number_format($pedido['total_pedido'], 2, ',', '.') ?></strong>
        </div>
    </div>
    
    <?php if ($pedido['status'] === 'pendente'): ?>
    <div style="margin-top: 30px; display: flex; justify-content: space-between; align-items: flex-start;">
        <form method="POST" action="">
            <input type="hidden" name="acao_pedido" value="aprovar">
            <button type="submit" class="button">Aprovar Pedido</button>
        </form>
        <form method="POST" action="">
            <input type="hidden" name="acao_pedido" value="rejeitar">
            <textarea name="motivo_rejeicao" placeholder="Motivo da rejeição (opcional)" rows="3" style="width: 250px;"></textarea><br>
            <button type="submit" class="button-danger" style="margin-top: 10px;">Rejeitar Pedido</button>
        </form>
    </div>
    <?php else: ?>
        <p style="margin-top: 30px; font-weight: bold;">Este pedido já foi processado. Status: <?= ucfirst($pedido['status']) ?></p>
    <?php endif; ?>

    <div class="back-button" style="margin-top: 20px;">
        <a href="vendedor_dashboard.php">Voltar para a Lista de Pedidos</a>
    </div>
</div>
</body>
</html> 