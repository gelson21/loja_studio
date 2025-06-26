<?php
include 'conexao.php';
session_start();

// Proteção da página: Apenas administradores podem acessar
if (!isset($_SESSION['nivel_acesso']) || $_SESSION['nivel_acesso'] !== 'admin') {
    $_SESSION['feedback'] = ['type' => 'error', 'message' => 'Acesso negado! Apenas administradores podem acessar esta página.'];
    header("Location: login.php");
    exit();
}

$feedback = $_SESSION['feedback'] ?? null;
unset($_SESSION['feedback']);

$edit_user = null;
$edit_mode = false;

// Modo de edição: busca os dados do usuário a ser editado
if (isset($_GET['edit_id'])) {
    $edit_mode = true;
    $id = intval($_GET['edit_id']);
    $stmt = $conn->prepare("SELECT id, nome, email FROM usuarios WHERE id = ? AND nivel_acesso = 'rh'");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $edit_user = $result->fetch_assoc();
}

// Lógica para Adicionar ou Atualizar usuário de RH
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['adicionar']) || isset($_POST['atualizar']))) {
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $senha = trim($_POST['senha']);
    $id_usuario = isset($_POST['id_usuario']) ? intval($_POST['id_usuario']) : 0;

    $stmt = null;
    $redirect = true;

    if (empty($nome) || empty($email)) {
        $_SESSION['feedback'] = ['type' => 'error', 'message' => 'Nome e Email são obrigatórios!'];
        $redirect = false;
    } else {
        if ($id_usuario > 0) { // Atualizar usuário
            if (!empty($senha)) {
                $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE usuarios SET nome = ?, email = ?, senha = ? WHERE id = ?");
                $stmt->bind_param("sssi", $nome, $email, $senhaHash, $id_usuario);
            } else {
                $stmt = $conn->prepare("UPDATE usuarios SET nome = ?, email = ? WHERE id = ?");
                $stmt->bind_param("ssi", $nome, $email, $id_usuario);
            }
            $_SESSION['feedback'] = ['type' => 'success', 'message' => 'Usuário de RH atualizado com sucesso!'];
        } else { // Adicionar novo usuário
            if (empty($senha)) {
                $_SESSION['feedback'] = ['type' => 'error', 'message' => 'A senha é obrigatória para novos usuários!'];
                $redirect = false;
            } else {
                $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
                $nivelAcesso = 'rh';
                $stmt = $conn->prepare("INSERT INTO usuarios (nome, email, senha, nivel_acesso) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssss", $nome, $email, $senhaHash, $nivelAcesso);
                $_SESSION['feedback'] = ['type' => 'success', 'message' => 'Usuário de RH cadastrado com sucesso!'];
            }
        }

        if ($stmt && !$stmt->execute()) {
            $_SESSION['feedback'] = ['type' => 'error', 'message' => 'Erro ao salvar usuário.'];
        }
    }
    if($redirect) {
        header("Location: rh.php");
        exit();
    }
}

// Remover usuário de RH
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remover'])) {
    $id = intval($_POST['id']);
    $stmt = $conn->prepare("DELETE FROM usuarios WHERE id = ? AND nivel_acesso = 'rh'");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        $_SESSION['feedback'] = ['type' => 'success', 'message' => 'Usuário de RH removido com sucesso!'];
    } else {
        $_SESSION['feedback'] = ['type' => 'error', 'message' => 'Erro ao remover o usuário.'];
    }
    header("Location: rh.php");
    exit();
}

// Buscar todos os usuários de RH
$stmt = $conn->prepare("SELECT id, nome, email, criado_em FROM usuarios WHERE nivel_acesso = 'rh'");
$stmt->execute();
$result = $stmt->get_result();
$usuarios_rh = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar RH</title>
    <link rel="stylesheet" href="vendedor-style.css">
    <style>
        .form-container {
            background-color: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .feedback {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            color: #fff;
            text-align: center;
        }
        .feedback.success {
            background-color: #28a745;
        }
        .feedback.error {
            background-color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Gerenciar Usuários de RH</h1>
        
        <?php if ($feedback): ?>
            <div class="feedback <?= htmlspecialchars($feedback['type']) ?>">
                <?= htmlspecialchars($feedback['message']) ?>
            </div>
        <?php endif; ?>

        <div class="form-container">
            <h2><?= $edit_mode ? 'Editar Usuário de RH' : 'Adicionar Novo Usuário de RH' ?></h2>
            <form method="POST" action="rh.php">
                <?php if ($edit_mode): ?>
                    <input type="hidden" name="id_usuario" value="<?= $edit_user['id'] ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label for="nome">Nome</label>
                    <input type="text" id="nome" name="nome" value="<?= htmlspecialchars($edit_user['nome'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?= htmlspecialchars($edit_user['email'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label for="senha">Senha</label>
                    <input type="password" id="senha" name="senha" <?= !$edit_mode ? 'required' : '' ?> placeholder="<?= $edit_mode ? 'Deixe em branco para não alterar' : '' ?>">
                </div>
                
                <?php if ($edit_mode): ?>
                    <button type="submit" name="atualizar" class="button">Salvar Alterações</button>
                    <a href="rh.php" class="button button-edit">Cancelar Edição</a>
                <?php else: ?>
                    <button type="submit" name="adicionar" class="button">Adicionar Usuário</button>
                <?php endif; ?>
            </form>
        </div>

        <h2>Usuários de RH Cadastrados</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>Email</th>
                    <th>Data de Cadastro</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($usuarios_rh as $usuario): ?>
                    <tr>
                        <td><?= htmlspecialchars($usuario['id']) ?></td>
                        <td><?= htmlspecialchars($usuario['nome']) ?></td>
                        <td><?= htmlspecialchars($usuario['email']) ?></td>
                        <td><?= htmlspecialchars(date('d/m/Y', strtotime($usuario['criado_em']))) ?></td>
                        <td class="actions">
                            <a href="rh.php?edit_id=<?= $usuario['id'] ?>" class="button button-edit">Editar</a>
                            <form method="POST" action="rh.php" style="display: inline;" onsubmit="return confirm('Tem certeza que deseja remover este usuário?');">
                                <input type="hidden" name="id" value="<?= $usuario['id'] ?>">
                                <button type="submit" name="remover" class="button-danger">Remover</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="back-button">
            <a href="adimin.php">Voltar</a>
        </div>
    </div>
</body>
</html> 