<?php
session_start();
include 'conexao.php';

// Proteção da página: Apenas admin pode acessar
if (!isset($_SESSION['nivel_acesso']) || $_SESSION['nivel_acesso'] !== 'admin') {
    echo "<script>alert('Acesso negado!'); window.location.href = 'login.php';</script>";
    exit();
}

// Buscar lista de RH
$query = "
    SELECT 
        u.id,
        u.nome,
        u.email,
        u.telefone,
        'ativo' as status,
        'Ativo' as status_formatado
    FROM usuarios u
    WHERE u.nivel_acesso = 'rh'
    ORDER BY u.nome ASC
";

$result = $conn->query($query);
$rh_list = $result->fetch_all(MYSQLI_ASSOC);

// Calcular totais
$total_rh = count($rh_list);
$total_ativos = $total_rh; // Since all are active by default
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Lista de RH</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="dashboard-header">
            <h1>Lista de RH Cadastrados</h1>
            <div class="header-buttons">
                <a href="admin_dashboard.php" class="button">Voltar ao Dashboard</a>
                <a href="cadastrar_rh.php" class="button">Cadastrar Novo RH</a>
            </div>
        </div>

        <!-- Resumo -->
        <div class="stats-container">
            <div class="stat-box">
                <h3>Total de RH</h3>
                <p><?= $total_rh ?></p>
            </div>
            <div class="stat-box">
                <h3>RH Ativos</h3>
                <p><?= $total_ativos ?></p>
            </div>
        </div>

        <!-- Tabela de RH -->
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Email</th>
                        <th>Telefone</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rh_list as $rh): ?>
                        <tr>
                            <td><?= htmlspecialchars($rh['nome']) ?></td>
                            <td><?= htmlspecialchars($rh['email']) ?></td>
                            <td><?= htmlspecialchars($rh['telefone']) ?></td>
                            <td>
                                <span class="status-badge <?= strtolower($rh['status_formatado']) ?>">
                                    <?= htmlspecialchars($rh['status_formatado']) ?>
                                </span>
                            </td>
                            <td>
                                <a href="editar_rh.php?id=<?= $rh['id'] ?>" class="button-small">Editar</a>
                                <a href="desativar_rh.php?id=<?= $rh['id'] ?>" class="button-small button-danger" onclick="return confirm('Tem certeza que deseja desativar este RH?')">Desativar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html> 