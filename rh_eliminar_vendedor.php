<?php
session_start();
include 'conexao.php';

// Função para definir mensagem de feedback e redirecionar
function feedback_redirect($message, $type, $location) {
    $_SESSION['feedback_message'] = $message;
    $_SESSION['feedback_type'] = $type;
    header("Location: $location");
    exit();
}

// 1. Validação de Segurança e Permissões
// Apenas usuários de RH podem acessar e deve ser uma requisição POST.
if (!isset($_SESSION['nivel_acesso']) || $_SESSION['nivel_acesso'] !== 'rh') {
    feedback_redirect('Acesso negado. Você não tem permissão para executar esta ação.', 'error', 'login.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    feedback_redirect('Método de requisição inválido.', 'error', 'rh_dashboard.php');
}

// 2. Validação dos Dados de Entrada
$id_vendedor = $_POST['id_vendedor'] ?? null;
if (empty($id_vendedor) || !is_numeric($id_vendedor)) {
    feedback_redirect('ID do vendedor inválido ou não fornecido.', 'error', 'rh_dashboard.php');
}

// 3. Lógica de Negócio (Verificação Adicional)
// Verifica se o usuário a ser deletado é realmente um vendedor para evitar exclusões acidentais.
$stmt = $conn->prepare("SELECT nivel_acesso FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $id_vendedor);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    feedback_redirect('Vendedor não encontrado no sistema.', 'error', 'rh_dashboard.php');
}

$usuario = $result->fetch_assoc();
$stmt->close();

if ($usuario['nivel_acesso'] !== 'vendedor') {
    feedback_redirect('Ação não permitida. O usuário selecionado não é um vendedor.', 'error', 'rh_dashboard.php');
}

// 4. Execução da Exclusão
// Prepara e executa o comando DELETE para remover o vendedor.
$stmt = $conn->prepare("DELETE FROM usuarios WHERE id = ? AND nivel_acesso = 'vendedor'");
$stmt->bind_param("i", $id_vendedor);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        // Sucesso na exclusão
        feedback_redirect('Vendedor eliminado com sucesso!', 'success', 'rh_dashboard.php');
    } else {
        // Nenhuma linha foi afetada, o que pode indicar que o vendedor já foi removido.
        feedback_redirect('Nenhum vendedor foi eliminado. Talvez ele já tenha sido removido.', 'info', 'rh_dashboard.php');
    }
} else {
    // Erro na execução do SQL.
    feedback_redirect('Ocorreu um erro ao tentar eliminar o vendedor.', 'error', 'rh_dashboard.php');
}

$stmt->close();
$conn->close();
?> 