<?php
// filepath: c:\xampp\htdocs\PROGAMACAO\Tarefa1\usuarios.php

include 'conexao.php'; // Inclui o arquivo de conexão com o banco de dados

function buscarUsuario($email) {
    global $conn;

    // Prepara a consulta para buscar o usuário pelo email
    $stmt = $conn->prepare("SELECT senha FROM usuarios WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    // Verifica se o usuário foi encontrado
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($senha);
        $stmt->fetch();
        return $senha; // Retorna a senha do usuário
    }

    return null; // Retorna null se o usuário não for encontrado
}
?>