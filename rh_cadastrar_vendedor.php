<?php
session_start();
include 'conexao.php';

// Proteção: Apenas RH ou Admins podem acessar
if (!isset($_SESSION['nivel_acesso']) || !in_array($_SESSION['nivel_acesso'], ['rh', 'admin'])) {
    echo "<script>alert('Acesso negado!'); window.location.href = 'login.php';</script>";
    exit();
}

$feedback = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cadastrar_vendedor'])) {
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $senha = $_POST['senha'];

    if (empty($nome) || empty($email) || empty($senha)) {
        $feedback = ['type' => 'error', 'message' => 'Todos os campos são obrigatórios.'];
    } else {
        $stmt_check = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt_check->bind_param("s", $email);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            $feedback = ['type' => 'error', 'message' => 'Este e-mail já está cadastrado.'];
        } else {
            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
            $nivel_acesso = 'vendedor';

            $stmt_insert = $conn->prepare("INSERT INTO usuarios (nome, email, senha, nivel_acesso) VALUES (?, ?, ?, ?)");
            $stmt_insert->bind_param("ssss", $nome, $email, $senha_hash, $nivel_acesso);

            if ($stmt_insert->execute()) {
                $feedback = ['type' => 'success', 'message' => 'Novo vendedor cadastrado com sucesso!'];
            } else {
                $feedback = ['type' => 'error', 'message' => 'Erro ao cadastrar o vendedor.'];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Cadastrar Novo Vendedor</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <h1>Cadastrar Novo Vendedor</h1>
    <p>Use o formulário abaixo para adicionar um novo vendedor ao sistema.</p>

    <?php if (!empty($feedback)): ?>
        <div class="feedback <?= htmlspecialchars($feedback['type']) ?>">
            <?= htmlspecialchars($feedback['message']) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <div style="margin-bottom: 15px;">
            <label for="nome" style="display: block; margin-bottom: 5px;">Nome Completo:</label>
            <input type="text" id="nome" name="nome" required style="width: 100%; padding: 10px; border-radius: 5px; border: 1px solid var(--cor-borda);">
        </div>
        <div style="margin-bottom: 15px;">
            <label for="email" style="display: block; margin-bottom: 5px;">E-mail de Acesso:</label>
            <input type="email" id="email" name="email" required style="width: 100%; padding: 10px; border-radius: 5px; border: 1px solid var(--cor-borda);">
        </div>
        <div style="margin-bottom: 20px;">
            <label for="senha" style="display: block; margin-bottom: 5px;">Senha Provisória:</label>
            <input type="password" id="senha" name="senha" required style="width: 100%; padding: 10px; border-radius: 5px; border: 1px solid var(--cor-borda);">
        </div>
        <div>
            <button type="submit" name="cadastrar_vendedor" class="button">Cadastrar Vendedor</button>
        </div>
    </form>
    <div class="back-button" style="margin-top: 20px;">
        <a href="rh_dashboard.php">Voltar ao Painel de RH</a>
    </div>
</div>
</body>
</html> 