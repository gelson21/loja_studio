<?php
// filepath: c:\xampp\htdocs\PROGAMACAO\Tarefa1\Index.php

include 'conexao.php'; // Inclui o arquivo de conexão com o banco de dados

session_start();

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["usuario"])) {
    $usuario = filter_var($_POST["usuario"], FILTER_SANITIZE_EMAIL);
    $senha_post = $_POST["senha"]; // Pega a senha do formulário

    if (filter_var($usuario, FILTER_VALIDATE_EMAIL)) {
        // Busca o usuário e sua senha hash no banco
        $stmt = $conn->prepare("SELECT id, nome, email, senha, nivel_acesso FROM usuarios WHERE email = ?");
        $stmt->bind_param("s", $usuario);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();

            // Verifica se a senha fornecida corresponde ao hash armazenado
            if (password_verify($senha_post, $user['senha'])) {
                $_SESSION["usuario_id"] = $user['id'];
                $_SESSION["usuario_verificado"] = $user['email'];
                $_SESSION["nivel_acesso"] = $user['nivel_acesso'];
                $_SESSION["usuario_nome"] = $user['nome']; // Armazena o nome do usuário na sessão

                if ($user['nivel_acesso'] === 'admin') {
                    $redirectPage = 'admin_dashboard.php';
                } elseif ($user['nivel_acesso'] === 'vendedor') {
                    $redirectPage = 'vendedor_dashboard.php';
                } elseif ($user['nivel_acesso'] === 'rh') {
                    $redirectPage = 'rh_dashboard.php';
                } elseif ($user['nivel_acesso'] === 'cliente') {
                    $redirectPage = 'cliente_dashboard.php';
                } else {
                    // Por padrão, se o nível de acesso não for reconhecido, vai para o login
                    $redirectPage = 'login.php';
                }
                
                header("Location: $redirectPage");
                exit();
            } else {
                echo "<script>alert('Senha incorreta!'); window.location.href = 'login.php';</script>";
                exit();
            }
        } else {
            echo "<script>alert('Usuário não registrado!'); window.location.href = 'cadastro.php';</script>";
            exit();
        }
    } else {
        echo "<script>alert('Formato de usuário inválido!');</script>";
    }
}
?>

<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Usuário</title>
    <link rel="stylesheet" href="login-style.css">
    <a href="index.php">Voltar</a>
</head>
<body>
    <div class="container-login">
        <h3>Insira seu Usuário</h3>
        <form action="" method="POST">
            <div class="mb-3">
                <label for="usuario">Usuário (Email):</label>
                <input type="text" id="usuario" name="usuario" class="form-control" required placeholder="Usuário">
            </div>
            <div class="mb-3">
                <label for="senha">Senha:</label>
                <input type="password" id="senha" name="senha" class="form-control" required placeholder="Senha">
            </div>
            <div class="mb-3">
                <button type="submit" class="btn btn-primary form-control">Continuar</button>
            </div>
        </form>
    </div>
</body>
</html>