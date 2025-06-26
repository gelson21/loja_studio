<?php
session_start();
include 'conexao.php';

// Proteção da página: Apenas vendedores podem acessar
if (!isset($_SESSION['nivel_acesso']) || !in_array($_SESSION['nivel_acesso'], ['vendedor', 'admin'])) {
    echo "<script>alert('Acesso negado!'); window.location.href = 'login.php';</script>";
    exit();
}

$feedback = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $senha = trim($_POST['senha']);
    $telefone = trim($_POST['telefone']);
    $endereco = trim($_POST['endereco']);

    // Validações básicas
    if (empty($nome) || empty($email) || empty($senha)) {
        $feedback = ['type' => 'error', 'message' => 'Por favor, preencha todos os campos obrigatórios.'];
    } else {
        // Verificar se o email já existe
        $check_email = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
        $check_email->bind_param("s", $email);
        $check_email->execute();
        $result = $check_email->get_result();

        if ($result->num_rows > 0) {
            $feedback = ['type' => 'error', 'message' => 'Este email já está cadastrado.'];
        } else {
            // Hash da senha
            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

            // Inserir novo cliente
            $stmt = $conn->prepare("INSERT INTO usuarios (nome, email, senha, telefone, endereco, nivel_acesso) VALUES (?, ?, ?, ?, ?, 'cliente')");
            $stmt->bind_param("sssss", $nome, $email, $senha_hash, $telefone, $endereco);

            if ($stmt->execute()) {
                $_SESSION['feedback_vendedor'] = ['type' => 'success', 'message' => 'Cliente cadastrado com sucesso!'];
                header("Location: vendedor_dashboard.php");
                exit();
            } else {
                $feedback = ['type' => 'error', 'message' => 'Erro ao cadastrar cliente. Tente novamente.'];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Cadastrar Novo Cliente</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="dashboard-header">
            <h1>Cadastrar Novo Cliente</h1>
            <a href="vendedor_dashboard.php" class="button">Voltar ao Dashboard</a>
        </div>

        <?php if ($feedback): ?>
            <div class="feedback <?= htmlspecialchars($feedback['type']) ?>">
                <?= htmlspecialchars($feedback['message']) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" class="form-container">
            <div class="form-group">
                <label for="nome">Nome Completo *</label>
                <input type="text" id="nome" name="nome" required>
            </div>

            <div class="form-group">
                <label for="email">Email *</label>
                <input type="email" id="email" name="email" required>
            </div>

            <div class="form-group">
                <label for="senha">Senha *</label>
                <input type="password" id="senha" name="senha" required>
            </div>

            <div class="form-group">
                <label for="telefone">Telefone</label>
                <input type="tel" id="telefone" name="telefone">
            </div>

            <div class="form-group">
                <label for="endereco">Endereço</label>
                <textarea id="endereco" name="endereco" rows="3"></textarea>
            </div>

            <div class="form-group">
                <button type="submit" class="button">Cadastrar Cliente</button>
            </div>
        </form>
    </div>
</body>
</html> 