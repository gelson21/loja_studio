<?php
// filepath: c:\xampp\htdocs\PROGAMACAO\Tarefa1\cadastro.php

include 'conexao.php'; // Inclui o arquivo de conexão com o banco de dados

$erro = ""; // Variável para armazenar mensagens de erro

// Senha de confirmação para cadastro de administradores
$senhaAdmin = "wibom"; // Substitua por uma senha segura

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = trim($_POST["nome"]);
    $email = trim($_POST["email"]);
    $senha = trim($_POST["senha"]);
    $confirmaSenha = trim($_POST["confirmaSenha"]);
    $nivelAcesso = $_POST["nivelAcesso"] ?? 'cliente'; // Define o nível de acesso (cliente por padrão)
    $senhaConfirmacao = trim($_POST["senhaConfirmacao"]); // Senha de confirmação para admin

    // Verifica se as senhas coincidem
    if ($senha !== $confirmaSenha) {
        $erro = "As senhas não coincidem!";
    } elseif ($nivelAcesso === 'admin' && $senhaConfirmacao !== $senhaAdmin) {
        $erro = "Senha de confirmação para administrador incorreta!";
    } elseif ($nivelAcesso === 'vendedor' && $senhaConfirmacao !== $senhaAdmin) {
        $erro = "Senha de confirmação para vendedor incorreta!";
    } else {
        // Verifica se o email já está cadastrado
        $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $erro = "Email já cadastrado!";
        } else {
            // Insere o novo cliente no banco de dados
            $senhaHash = password_hash($senha, PASSWORD_DEFAULT); // Criptografa a senha
            $stmt = $conn->prepare("INSERT INTO usuarios (nome, email, senha, nivel_acesso) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $nome, $email, $senhaHash, $nivelAcesso);

            if ($stmt->execute()) {
                if ($nivelAcesso === 'vendedor') {
                    echo "<script>alert('Cadastro realizado com sucesso! Redirecionando para a página de vendas.');</script>";
                    echo "<script>window.location.href = 'vendas.php';</script>";
                } else {
                    echo "<script>alert('Cadastro realizado com sucesso!');</script>";
                    echo "<script>window.location.href = 'index.php';</script>";
                }
                exit();
            } else {
                $erro = "Erro ao cadastrar o cliente. Tente novamente.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Cliente</title>
    <link rel="stylesheet" href="cadastro-style.css">
<body>
    <div class="container">
        <h1>Cadastrar usuario</h1>
        <?php if (!empty($erro)): ?>
            <p class="error"><?= htmlspecialchars($erro) ?></p>
        <?php endif; ?>
        <form method="POST" action="">
            <label for="nome">Nome:</label>
            <input type="text" id="nome" name="nome" required>

            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>

            <label for="senha">Senha:</label>
            <input type="password" id="senha" name="senha" required>

            <label for="confirmaSenha">Confirme a Senha:</label>
            <input type="password" id="confirmaSenha" name="confirmaSenha" required>

            <label for="nivelAcesso">Nível de Acesso:</label>
            <select id="nivelAcesso" name="nivelAcesso" required>
                <option value="cliente">Cliente</option>
                <option value="admin">Administrador</option>
            </select>

            <div id="senhaConfirmacaoGroup" style="display: none;">
                <label for="senhaConfirmacao">Senha de Confirmação para Administrador</label>
                <input type="password" id="senhaConfirmacao" name="senhaConfirmacao">
            </div>

            <button type="submit">Cadastrar</button>
        </form>
    </div>
    <script>
        const nivelAcessoSelect = document.getElementById('nivelAcesso');
        const senhaConfirmacaoGroup = document.getElementById('senhaConfirmacaoGroup');

        nivelAcessoSelect.addEventListener('change', function() {
            if (this.value === 'admin') {
                senhaConfirmacaoGroup.style.display = 'block';
            } else {
                senhaConfirmacaoGroup.style.display = 'none';
            }
        });
    </script>
    <div class="voltar-container">
        <a href="index.php">
            <button>
                Voltar
            </button>
        </a>
    </div>
</body>
</html>
