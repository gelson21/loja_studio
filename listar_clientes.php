<?php
session_start();
include 'conexao.php';

// Proteção da página: Apenas vendedores podem acessar
if (!isset($_SESSION['nivel_acesso']) || !in_array($_SESSION['nivel_acesso'], ['vendedor', 'admin'])) {
    echo "<script>alert('Acesso negado!'); window.location.href = 'login.php';</script>";
    exit();
}

// Verificar se as colunas existem
$check_columns = $conn->query("SHOW COLUMNS FROM usuarios LIKE 'telefone'");
$telefone_exists = $check_columns->num_rows > 0;

$check_columns = $conn->query("SHOW COLUMNS FROM usuarios LIKE 'endereco'");
$endereco_exists = $check_columns->num_rows > 0;

// Construir a query base
$query = "SELECT id, nome, email";
if ($telefone_exists) {
    $query .= ", telefone";
}
if ($endereco_exists) {
    $query .= ", endereco";
}
$query .= " FROM usuarios WHERE nivel_acesso = 'cliente' ORDER BY nome ASC";

$result = $conn->query($query);
$clientes = $result->fetch_all(MYSQLI_ASSOC);

$feedback = $_SESSION['feedback_vendedor'] ?? null;
unset($_SESSION['feedback_vendedor']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Lista de Clientes</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="dashboard-header">
            <h1>Lista de Clientes</h1>
            <div class="header-buttons">
                <a href="cadastrar_cliente.php" class="button">Novo Cliente</a>
                <a href="vendedor_dashboard.php" class="button">Voltar ao Dashboard</a>
            </div>
        </div>

        <?php if ($feedback): ?>
            <div class="feedback <?= htmlspecialchars($feedback['type']) ?>">
                <?= htmlspecialchars($feedback['message']) ?>
            </div>
        <?php endif; ?>

        <div class="search-box">
            <input type="text" id="searchInput" placeholder="Buscar cliente..." onkeyup="searchTable()">
        </div>

        <table id="clientesTable">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Email</th>
                    <?php if ($telefone_exists): ?>
                        <th>Telefone</th>
                    <?php endif; ?>
                    <?php if ($endereco_exists): ?>
                        <th>Endereço</th>
                    <?php endif; ?>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($clientes as $cliente): ?>
                    <tr>
                        <td><?= htmlspecialchars($cliente['nome']) ?></td>
                        <td><?= htmlspecialchars($cliente['email']) ?></td>
                        <?php if ($telefone_exists): ?>
                            <td><?= htmlspecialchars($cliente['telefone']) ?></td>
                        <?php endif; ?>
                        <?php if ($endereco_exists): ?>
                            <td><?= htmlspecialchars($cliente['endereco']) ?></td>
                        <?php endif; ?>
                        <td>
                            <a href="editar_cliente.php?id=<?= $cliente['id'] ?>" class="button-edit">Editar</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script>
    function searchTable() {
        var input = document.getElementById("searchInput");
        var filter = input.value.toUpperCase();
        var table = document.getElementById("clientesTable");
        var tr = table.getElementsByTagName("tr");

        for (var i = 1; i < tr.length; i++) {
            var found = false;
            var td = tr[i].getElementsByTagName("td");
            
            for (var j = 0; j < td.length - 1; j++) { // -1 to exclude the actions column
                if (td[j]) {
                    var txtValue = td[j].textContent || td[j].innerText;
                    if (txtValue.toUpperCase().indexOf(filter) > -1) {
                        found = true;
                        break;
                    }
                }
            }
            
            tr[i].style.display = found ? "" : "none";
        }
    }
    </script>
</body>
</html> 