<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Vendas</title>
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background-color:rgb(0, 0, 0);
            color: red;
        }
        
        .top-menu {
            background: linear-gradient(90deg, #43e97b 0%, #38f9d7 100%);
            color: #fff;
            padding: 18px 30px 12px 30px;
            text-align: center;
            box-shadow: 0px 4px 16px rgba(0, 0, 0, 0.25);
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1000;
            border-bottom-left-radius: 18px;
            border-bottom-right-radius: 18px;
            letter-spacing: 1px;
        }
        .top-menu h2 {
            margin: 0 0 5px 0;
            font-size: 2.2rem;
            letter-spacing: 2px;
            color: #0a3d2c;
            text-shadow: 1px 2px 8px #fff9, 0 2px 8px #38f9d7;
        }
        .top-menu a {
            color: #0a3d2c;
            background: rgba(255,255,255,0.8);
            border-radius: 6px;
            padding: 7px 18px;
            text-decoration: none;
            margin: 0 10px;
            font-weight: bold;
            font-size: 1.08rem;
            transition: background 0.2s, color 0.2s, box-shadow 0.2s;
            box-shadow: 0 2px 8px rgba(67,233,123,0.08);
            display: inline-block;
        }
        .top-menu a:hover {
            background: #0a3d2c;
            color: #fff;
            box-shadow: 0 4px 16px #38f9d7aa;
            text-decoration: underline;
        }

        .side-menu {
            width: 230px;
            height: 100vh;
            background: linear-gradient(180deg, #38f9d7 0%, #43e97b 100%);
            position: fixed;
            top: 0;
            left: -250px;
            padding: 70px 30px 30px 30px;
            box-sizing: border-box;
            transition: left 0.3s cubic-bezier(.77,0,.18,1);
            box-shadow: 2px 0 16px rgba(56,249,215,0.12);
            border-top-right-radius: 18px;
            border-bottom-right-radius: 18px;
        }
        .side-menu.show {
            left: 0;
        }
        .side-menu h3 {
            color: #0a3d2c;
            margin-bottom: 25px;
            font-size: 1.2rem;
            letter-spacing: 1px;
        }
        .side-menu a {
            display: block;
            color: #0a3d2c;
            background: rgba(255,255,255,0.7);
            text-decoration: none;
            margin: 12px 0;
            padding: 10px 18px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 1.05rem;
            transition: background 0.2s, color 0.2s;
        }
        .side-menu a:hover {
            background: #0a3d2c;
            color: #fff;
            text-decoration: underline;
        }
        
        .toggle-button {
            position: fixed;
            top: 40px;
            left: 18px;
            background: linear-gradient(90deg, #43e97b 0%, #38f9d7 100%);
            color: #0a3d2c;
            border: none;
            padding: 12px 18px;
            cursor: pointer;
            border-radius: 50%;
            font-size: 1.3rem;
            box-shadow: 0 2px 8px #38f9d7aa;
            z-index: 1100;
            transition: background 0.2s, color 0.2s;
        }
        .toggle-button:hover {
            background: #0a3d2c;
            color: #fff;
        }
        
        .content {
            margin-left: 20px;
            padding: 20px;
            height: 100vh;
            background-image: url('m2.jpeg');
            background-size: cover;
            background-repeat: no-repeat;
            background-position: center;
            animation: moveBackground 20s linear infinite;
        }
        @keyframes moveBackground {
            0% {
                background-position: 0% 0%;
            }
            100% {
                background-position: 100% 100%;
            }
        }
        h1 {
            text-align: center; 
            color:rgb(85, 255, 232); 
            font-size: 3rem; 
            font-weight: bold; 
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5); 
            margin-top: 300px; 
        }
    </style>
</head>
<body>
   
    <div class="  top-menu">
        <h2>STUDIO SHOP</h2>
        <a href="index.php">Sair</a>
        <a href="suporte.php">Suporte</a>
        <a href="sobre.php">Sobre</a>
    </div>

    <button class="toggle-button" onclick="toggleMenu()">☰ Menu</button>

    <div class="side-menu" id="sideMenu">
        <h3>
        <br>
        <br>
        <a href="">Home</a>
        <a href="placa de som.php">plca de som </a>
        <a href="microfone.php">microfone</a>
        <a href="criancas.php">caixa de som</a>
    </h3>
    </div>

    <div class="content">
        <h1>Bem-vindo A Nossa Loja <br> Material de Stúdio</h1>
    </div>

    <script>
        function toggleMenu() {
            const sideMenu = document.getElementById('sideMenu');
            sideMenu.classList.toggle('show');
        }
    </script>
</body>
</html>
