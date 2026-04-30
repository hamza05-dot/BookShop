<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

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
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — BookShop Admin</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>

<!-- HEADER -->
<header>
    <div class="logosec">
        <img class="menuicn" id="menuicn" src="https://img.icons8.com/ios-filled/50/menu--v1.png" alt="menu">
        <div class="logo">📚 BookShop Admin</div>
    </div>
    <span class="admin-info">Bonjour, <?= $_SESSION['nomUser'] ?> 👋</span>
    <a class="logout-btn" href="../logout.php">Se déconnecter</a>
</header>

<!-- MAIN -->
<div class="main-container">

    <!-- SIDEBAR -->
    <div class="navcontainer" id="navcontainer">
        <nav class="nav">
            <div class="nav-upper-options">
                <a class="nav-option active" href="dashboard.php">
                    <img class="nav-img" src="https://img.icons8.com/?size=100&id=10245&format=png&color=000000">
                    <h3>Dashboard</h3>
                </a>
                <a class="nav-option" href="books.php">
                    <img class="nav-img" src="https://img.icons8.com/ios-filled/50/book.png">
                    <h3>Livres</h3>
                </a>
                <a class="nav-option" href="categories.php">
                    <img class="nav-img" src="https://img.icons8.com/ios-filled/50/price-tag.png">
                    <h3>Catégories</h3>
                </a>
                <a class="nav-option" href="orders.php">
                    <img class="nav-img" src="https://img.icons8.com/ios-filled/50/package.png">
                    <h3>Commandes</h3>
                </a>
                <a class="nav-option" href="users.php">
                    <img class="nav-img" src="https://img.icons8.com/ios-filled/50/user.png">
                    <h3>Utilisateurs</h3>
                </a>
                <a class="nav-option logout-nav" href="../logout.php">
                    <img class="nav-img" src="https://img.icons8.com/ios-filled/50/exit.png">
                    <h3>Déconnexion</h3>
                </a>
            </div>
        </nav>
    </div>

    <!-- CONTENT -->
    <div class="main">

        <!-- Stats -->
        <div class="box-container">
            <div class="box">
                <div class="text">
                    <h2><?= $livres ?></h2>
                    <p>Livres</p>
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
                    <p>Commandes</p>
                </div>
                <span class="box-icon">📦</span>
            </div>
            <div class="box">
                <div class="text">
                    <h2><?= $enAttente ?></h2>
                    <p>En attente</p>
                </div>
                <span class="box-icon">⏳</span>
            </div>
        </div>

        <!-- Dernières commandes -->
        <div class="report-container">
            <div class="report-header">
                <h2>Dernières commandes</h2>
                <a class="btn btn-primary" href="orders.php">Voir tout</a>
            </div>
            <table>
                <tr>
                    <th>#</th>
                    <th>Client</th>
                    <th>Total</th>
                    <th>Statut</th>
                    <th>Date</th>
                </tr>
                <?php foreach ($dernieresCommandes as $cmd): ?>
                <tr>
                    <td><?= $cmd['idCom'] ?></td>
                    <td><?= $cmd['nomUser'] . ' ' . $cmd['prenomUser'] ?></td>
                    <td><?= number_format($cmd['total'], 2) ?> DT</td>
                    <td><span class="badge <?= str_replace(' ', '-', $cmd['status']) ?>"><?= $cmd['status'] ?></span></td>
                    <td><?= date('d/m/Y', strtotime($cmd['createdAt'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>

    </div>
</div>

<script>
    // Ouvrir/fermer la sidebar
    let menuicn = document.querySelector(".menuicn");
    let nav = document.querySelector(".navcontainer");
    menuicn.addEventListener("click", () => {
        nav.classList.toggle("navclose");
    });
</script>

</body>
</html>