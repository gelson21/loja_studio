<?php
// filepath: c:\xampp\htdocs\PROGAMACAO\Tarefa1\conexao.php

$host = 'localhost'; // Host do banco de dados
$usuario = 'root'; // Usuário do MySQL
$senha = ''; // Senha do MySQL (deixe vazio se estiver usando XAMPP padrão)
$banco = 'sistema_estudio_musical'; // Nome do banco de dados

// Conexão com o banco de dados
$conn = new mysqli($host, $usuario, $senha, $banco);

// Verifica se houve erro na conexão
if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}

// Define o charset para evitar problemas com caracteres especiais
$conn->set_charset("utf8");
?>