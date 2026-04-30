<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$activePage = 'dashboard';

$livres    = $pdo->query("SELECT COUNT(*) FROM livre")->fetchColumn();
$clients   = $pdo->query("SELECT COUNT(*) FROM client")->fetchColumn();
$commandes = $pdo->query("SELECT COUNT(*) FROM commande")->fetchColumn();
$enAttente = $pdo->query("SELECT COUNT(*) FROM commande WHERE status = 'en attente'")->fetchColumn();

$dernieresCommandes = $pdo->query("
    SELECT c.idCom, c.status, c.total, c.createdAt, u.nomUser, u.prenomUser
    FROM commande c
    JOIN utilisateur u ON c.idClient = u.idUser
    ORDER BY c.createdAt DESC LIMIT 5
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — BookShop Admin</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
<?php include '../includes/nav.php'; ?>

<div class="main">

    <!-- Stats -->
    <div class="box-container">
        <div class="box">
            <div class="text">
                <h2><?= $livres ?></h2>
                <p>Books</p>
            </div>
            <span class="box-icon">📚</span>
        </div>
        <div class="box">
            <div class="text">
                <h2><?= $clients ?></h2>
                <p>Clients</p>
            </div>
            <span class="box-icon">👥</span>
        </div>
        <div class="box">
            <div class="text">
                <h2><?= $commandes ?></h2>
                <p>Orders</p>
            </div>
            <span class="box-icon">📦</span>
        </div>
        <div class="box">
            <div class="text">
                <h2><?= $enAttente ?></h2>
                <p>Pending</p>
            </div>
            <span class="box-icon">⏳</span>
        </div>
    </div>

    <!-- Recent Orders -->
    <div class="report-container">
        <div class="report-header">
            <h2>Recent Orders</h2>
            <a class="btn btn-primary" href="orders.php">View All</a>
        </div>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Client</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($dernieresCommandes as $cmd): ?>
            <tr>
                <td><?= $cmd['idCom'] ?></td>
                <td><?= htmlspecialchars($cmd['nomUser'] . ' ' . $cmd['prenomUser']) ?></td>
                <td><?= number_format($cmd['total'], 2) ?> DT</td>
                <td><span class="badge <?= str_replace(' ', '-', $cmd['status']) ?>"><?= $cmd['status'] ?></span></td>
                <td><?= date('d/m/Y', strtotime($cmd['createdAt'])) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

</div>
</div><!-- /.main-container -->

<script>
document.querySelector(".menuicn").addEventListener("click", () => {
    document.querySelector(".navcontainer").classList.toggle("navclose");
});
</script>
</body>
</html>