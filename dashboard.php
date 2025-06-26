<?php
session_start();
// Proteção de página, exemplo para RH
if (!isset($_SESSION['nivel_acesso']) || $_SESSION['nivel_acesso'] !== 'rh') {
    echo "<script>alert('Acesso negado!'); window.location.href = 'login.php';</script>";
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>Dashboard Geral</h1>
        <p>Bem-vindo ao painel principal.</p>
        <a href="rh_dashboard.php">Ir para o painel de RH</a>
    </div>
</body>
</html> 