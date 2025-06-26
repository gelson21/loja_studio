<?php
session_start();
include 'conexao.php';

// Proteção da página: Apenas vendedores podem acessar
if (!isset($_SESSION['nivel_acesso']) || !in_array($_SESSION['nivel_acesso'], ['vendedor', 'admin'])) {
    echo "<script>alert('Acesso negado!'); window.location.href = 'login.php';</script>";
    exit();
}

// Verificar se as colunas existem
$check_columns = $conn->query("SHOW COLUMNS FROM usuarios LIKE 'telefone'");
$telefone_exists = $check_columns->num_rows > 0;

$check_columns = $conn->query("SHOW COLUMNS FROM usuarios LIKE 'endereco'");
$endereco_exists = $check_columns->num_rows > 0;

$cliente_id = $_GET['id'] ?? null;
if (!$cliente_id) {
    header("Location: listar_clientes.php");
    exit();
}

// Construir a query base
$query = "SELECT id, nome, email";
if ($telefone_exists) {
    $query .= ", telefone";
}
if ($endereco_exists) {
    $query .= ", endereco";
}
$query .= " FROM usuarios WHERE id = ? AND nivel_acesso = 'cliente'";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $cliente_id);
$stmt->execute();
$result = $stmt->get_result();
$cliente = $result->fetch_assoc();

if (!$cliente) {
    $_SESSION['feedback_vendedor'] = ['type' => 'error', 'message' => 'Cliente não encontrado.'];
    header("Location: listar_clientes.php");
    exit();
}

$feedback = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $telefone = $telefone_exists ? trim($_POST['telefone']) : '';
    $endereco = $endereco_exists ? trim($_POST['endereco']) : '';
    $nova_senha = trim($_POST['nova_senha']);

    // Validações básicas
    if (empty($nome) || empty($email)) {
        $feedback = ['type' => 'error', 'message' => 'Nome e email são campos obrigatórios.'];
    } else {
        // Verificar se o email já existe para outro cliente
        $check_email = $conn->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
        $check_email->bind_param("si", $email, $cliente_id);
        $check_email->execute();
        $result = $check_email->get_result();

        if ($result->num_rows > 0) {
            $feedback = ['type' => 'error', 'message' => 'Este email já está em uso por outro cliente.'];
        } else {
            // Construir a query de atualização
            if (!empty($nova_senha)) {
                $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
                $query = "UPDATE usuarios SET nome = ?, email = ?, senha = ?";
                $types = "sss";
                $params = [$nome, $email, $senha_hash];
            } else {
                $query = "UPDATE usuarios SET nome = ?, email = ?";
                $types = "ss";
                $params = [$nome, $email];
            }

            if ($telefone_exists) {
                $query .= ", telefone = ?";
                $types .= "s";
                $params[] = $telefone;
            }
            if ($endereco_exists) {
                $query .= ", endereco = ?";
                $types .= "s";
                $params[] = $endereco;
            }

            $query .= " WHERE id = ?";
            $types .= "i";
            $params[] = $cliente_id;

            $stmt = $conn->prepare($query);
            $stmt->bind_param($types, ...$params);

            if ($stmt->execute()) {
                $_SESSION['feedback_vendedor'] = ['type' => 'success', 'message' => 'Dados do cliente atualizados com sucesso!'];
                header("Location: listar_clientes.php");
                exit();
            } else {
                $feedback = ['type' => 'error', 'message' => 'Erro ao atualizar dados do cliente.'];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Editar Cliente</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="dashboard-header">
            <h1>Editar Cliente</h1>
            <a href="clientes.php" class="button">Voltar à Lista</a>
        </div>

        <?php if ($feedback): ?>
            <div class="feedback <?= htmlspecialchars($feedback['type']) ?>">
                <?= htmlspecialchars($feedback['message']) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" class="form-container">
            <div class="form-group">
                <label for="nome">Nome Completo *</label>
                <input type="text" id="nome" name="nome" value="<?= htmlspecialchars($cliente['nome']) ?>" required>
            </div>

            <div class="form-group">
                <label for="email">Email *</label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($cliente['email']) ?>" required>
            </div>

            <?php if ($telefone_exists): ?>
                <div class="form-group">
                    <label for="telefone">Telefone</label>
                    <input type="tel" id="telefone" name="telefone" value="<?= htmlspecialchars($cliente['telefone']) ?>">
                </div>
            <?php endif; ?>

            <?php if ($endereco_exists): ?>
                <div class="form-group">
                    <label for="endereco">Endereço</label>
                    <textarea id="endereco" name="endereco" rows="3"><?= htmlspecialchars($cliente['endereco']) ?></textarea>
                </div>
            <?php endif; ?>

            <div class="form-group">
                <label for="nova_senha">Nova Senha (deixe em branco para manter a atual)</label>
                <input type="password" id="nova_senha" name="nova_senha">
            </div>

            <div class="form-group">
                <button type="submit" class="button">Salvar Alterações</button>
            </div>
        </form>
    </div>
</body>
</html> 