<?php
// filepath: c:\xampp\htdocs\PROGAMACAO\Tarefa1\senha.php

include 'conexao.php'; // Inclui o arquivo de conexão com o banco de dados

session_start(); 

// Verifica se o usuário foi verificado na sessão
if (!isset($_SESSION["usuario_verificado"])) {
    header("Location: index.php"); // Redireciona para a página inicial se o usuário não estiver verificado
    exit();
}

$usuario = $_SESSION["usuario_verificado"];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST["senha"])) {
        $senha = $_POST["senha"];

        // Busca a senha e o nível de acesso do usuário no banco de dados
        $stmt = $conn->prepare("SELECT senha, nivel_acesso FROM usuarios WHERE email = ?");
        $stmt->bind_param("s", $usuario);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($senhaHash, $nivelAcesso);
            $stmt->fetch();

            // Verifica se a senha está correta
            if (password_verify($senha, $senhaHash)) {
                $_SESSION["nivel_acesso"] = $nivelAcesso; // Armazena o nível de acesso na sessão

                echo "<script>alert('Login realizado com sucesso!');</script>";

                // Redireciona com base no nível de acesso
                if ($nivelAcesso === 'admin') {
                    header("Location: adimin.php"); // Redireciona para o painel do administrador
                } elseif ($nivelAcesso === 'vendedor') {
                    header("Location: vendas.php"); // Redireciona para o painel do vendedor
                } else {
                    header("Location: erdi.php"); // Redireciona para o painel do cliente
                }
                exit();
            } else {
                echo "<script>alert('Senha incorreta!');</script>";
            }
        } else {
            echo "<script>alert('Usuário não encontrado!');</script>";
        }
    }
}
?>

<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - Senha</title>
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: linear-gradient(120deg, #43e97b 0%, #38f9d7 100%);
            color: #0a3d2c;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container-senha {
            background: rgba(255,255,255,0.97);
            border-radius: 14px;
            box-shadow: 0 4px 16px #38f9d722;
            padding: 40px 30px 30px 30px;
            max-width: 400px;
            width: 100%;
            margin: 60px auto;
        }
        h3 {
            text-align: center;
            color: #0a3d2c;
            font-size: 2rem;
            margin-bottom: 24px;
            letter-spacing: 1px;
            text-shadow: 1px 2px 8px #fff9, 0 2px 8px #38f9d7;
        }
        label {
            font-weight: bold;
            color: #0a3d2c;
            margin-bottom: 6px;
            display: block;
        }
        input, select {
            width: 100%;
            margin-bottom: 16px;
            padding: 12px;
            font-size: 1rem;
            border: 1px solid #38f9d7;
            border-radius: 8px;
            background: #f0fff0;
            color: #0a3d2c;
            box-sizing: border-box;
            transition: border 0.2s;
        }
        input:focus, select:focus {
            border: 1.5px solid #43e97b;
            outline: none;
        }
        button {
            width: 100%;
            background: linear-gradient(90deg, #43e97b 0%, #38f9d7 100%);
            color: #0a3d2c;
            padding: 14px 0;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
            box-shadow: 0 2px 8px #38f9d7aa;
            transition: background 0.2s, color 0.2s;
        }
        button:hover {
            background: #0a3d2c;
            color: #fff;
        }
        .error {
            color: #e74c3c;
            background: #fff0f0;
            border: 1px solid #e74c3c;
            border-radius: 8px;
            padding: 10px;
            margin-bottom: 18px;
            text-align: center;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container-senha">
        <h3 class="text-center">Olá, Insira sua senha</h3>
        <form action="" method="POST">
            <div class="mb-3">
                <label for="senha">Senha:</label>
                <input type="password" id="senha" name="senha" class="form-control" required placeholder="Senha">
            </div>
            <div class="mb-3">
                <button type="submit" class="btn btn-success form-control">Logar</button>
            </div>
        </form>
    </div>
</body>
</html>