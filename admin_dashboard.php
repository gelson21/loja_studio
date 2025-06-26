<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard do Administrador</title>
    <link rel="stylesheet" href="admin-style.css">
</head>
<body>
    <div class="top-menu">
    <a href="index.php">Sair</a>
    
    </div>
    <button class="toggle-button" onclick="toggleMenu()">☰ Menu</button>
    <div class="side-menu" id="sideMenu">
        <div>
            <a href="admin_dashboard.php">Home</a>
            <a href="vendedor.php">Vendedor</a>
            <a href="clientes.php">Clientes</a>
            <a href="gerenciar_produtos.php">Produtos</a>
            <a href="cadastrar_rh.php">Cadastrar RH</a>
            <a href="lista_rh.php">Lista de RH</a>
            <a href="historico_vendas.php">Histórico de Vendas</a>
        </div>
    </div>
    <div class="content">
        <h1 class="text-center"> Olá, Admin! <br>Seja bem-Vindo ao sistema de venda de material de studio musical </h1>
    </div>
    <script>
        function toggleMenu() {
            const sideMenu = document.getElementById('sideMenu');
            sideMenu.classList.toggle('show');
        }
    </script>
</body>
</html> 