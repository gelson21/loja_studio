<?php
session_start();
include 'conexao.php';

// Proteção da página: Apenas admin pode acessar
if (!isset($_SESSION['nivel_acesso']) || $_SESSION['nivel_acesso'] !== 'admin') {
    echo "<script>alert('Acesso negado!'); window.location.href = 'login.php';</script>";
    exit();
}

// Verificar se ID foi fornecido
if (!isset($_GET['id'])) {
    echo "<script>alert('ID do RH não fornecido!'); window.location.href = 'lista_rh.php';</script>";
    exit();
}

$id = $_GET['id'];

// Processar formulário de edição
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $telefone = $_POST['telefone'];
    $senha = $_POST['senha'];

    try {
        // Se uma nova senha foi fornecida, atualiza a senha também
        if (!empty($senha)) {
            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
            $query = "UPDATE usuarios SET nome = ?, email = ?, telefone = ?, senha = ? WHERE id = ? AND nivel_acesso = 'rh'";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ssssi", $nome, $email, $telefone, $senha_hash, $id);
        } else {
            $query = "UPDATE usuarios SET nome = ?, email = ?, telefone = ? WHERE id = ? AND nivel_acesso = 'rh'";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("sssi", $nome, $email, $telefone, $id);
        }

        if ($stmt->execute()) {
            echo "<script>alert('Dados do RH atualizados com sucesso!'); window.location.href = 'lista_rh.php';</script>";
            exit();
        } else {
            throw new Exception("Erro ao atualizar dados");
        }
    } catch (Exception $e) {
        $erro = "Erro ao atualizar dados: " . $e->getMessage();
    }
}

// Buscar dados do RH
$query = "SELECT nome, email, telefone FROM usuarios WHERE id = ? AND nivel_acesso = 'rh'";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$rh = $result->fetch_assoc();

if (!$rh) {
    echo "<script>alert('RH não encontrado!'); window.location.href = 'lista_rh.php';</script>";
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Editar RH</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="dashboard-header">
            <h1>Editar RH</h1>
            <div class="header-buttons">
                <a href="lista_rh.php" class="button">Voltar à Lista</a>
            </div>
        </div>

        <?php if (isset($erro)): ?>
            <div class="feedback error">
                <?= htmlspecialchars($erro) ?>
            </div>
        <?php endif; ?>

        <div class="form-container">
            <form method="POST" action="">
                <div class="form-group">
                    <label for="nome">Nome:</label>
                    <input type="text" id="nome" name="nome" value="<?= htmlspecialchars($rh['nome']) ?>" required>
                </div>

                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" value="<?= htmlspecialchars($rh['email']) ?>" required>
                </div>

                <div class="form-group">
                    <label for="telefone">Telefone:</label>
                    <input type="tel" id="telefone" name="telefone" value="<?= htmlspecialchars($rh['telefone']) ?>" required>
                </div>

                <div class="form-group">
                    <label for="senha">Nova Senha (deixe em branco para manter a atual):</label>
                    <input type="password" id="senha" name="senha">
                </div>

                <div class="form-buttons">
                    <button type="submit" class="button">Salvar Alterações</button>
                    <a href="lista_rh.php" class="button button-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html> 