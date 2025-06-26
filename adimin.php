<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adimin</title>
    <link rel="stylesheet" href="admin-style.css">
</head>
<body>
    <div class="top-menu">
        <a href="index.php">Sair</a>
        <a href="suporte.php">Suporte</a>
    </div>
    <button class="toggle-button" onclick="toggleMenu()">☰ Menu</button>
    <div class="side-menu" id="sideMenu">
        <div>
            <a href="">Home</a>
            <a href="vendedor.php">Vendedor</a>
            <a href="clientes.php">Clientes</a>
            <a href="gerenciar_produtos.php">Produtos</a>
            <a href="rh.php">RH</a>
            <a href="financas.php">Finanças</a>
        </div>
    </div>
    <div class="content">
        <h1 class="text-center"> Olá,Admin! <br>Seja bem-Vindo </h1>
    </div>
    <script>
        function toggleMenu() {
            const sideMenu = document.getElementById('sideMenu');
            sideMenu.classList.toggle('show');
        }
    </script>
</body>
</html>
