<?php
$mensagem_enviada = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['mensagem'])) {
    // Aqui você pode salvar a mensagem no banco de dados ou enviar por email
    $mensagem_enviada = true;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Suporte</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(120deg, #43e97b 0%, #38f9d7 100%);
            color: #0a3d2c;
            margin: 0;
            min-height: 100vh;
        }
        .container {
            max-width: 500px;
            margin: 60px auto;
            background: rgba(255,255,255,0.97);
            border-radius: 14px;
            box-shadow: 0 4px 16px #38f9d722;
            padding: 40px 30px 30px 30px;
        }
        h1 {
            text-align: center;
            color: #0a3d2c;
            font-size: 2.2rem;
            margin-bottom: 30px;
            letter-spacing: 1px;
            text-shadow: 1px 2px 8px #fff9, 0 2px 8px #38f9d7;
        }
        label {
            font-weight: bold;
            color: #0a3d2c;
            margin-bottom: 8px;
            display: block;
        }
        textarea {
            width: 100%;
            height: 120px;
            border-radius: 8px;
            border: 1px solid #38f9d7;
            padding: 12px;
            font-size: 1rem;
            margin-bottom: 18px;
            background: #f0fff0;
            color: #0a3d2c;
            resize: vertical;
        }
        button, .voltar-btn {
            background: linear-gradient(90deg, #43e97b 0%, #38f9d7 100%);
            color: #0a3d2c;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
            box-shadow: 0 2px 8px #38f9d7aa;
            transition: background 0.2s, color 0.2s;
            margin-top: 10px;
        }
        button:hover, .voltar-btn:hover {
            background: #0a3d2c;
            color: #fff;
        }
        .voltar-btn {
            display: block;
            margin: 30px auto 0 auto;
            width: 200px;
            text-align: center;
            text-decoration: none;
        }
        .msg-sucesso {
            color: #228B22;
            text-align: center;
            margin-bottom: 18px;
            font-weight: bold;
            font-size: 1.1rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Suporte</h1>
        <?php if ($mensagem_enviada): ?>
            <div class="msg-sucesso">Mensagem enviada com sucesso! Em breve entraremos em contato.</div>
        <?php else: ?>
            <form method="post">
                <label for="mensagem">Digite sua mensagem:</label>
                <textarea id="mensagem" name="mensagem" required></textarea>
                <button type="submit">Enviar</button>
            </form>
        <?php endif; ?>
        <a class="voltar-btn" href="cliente_dashboard.php">Voltar</a>
    </div>
</body>
</html>
