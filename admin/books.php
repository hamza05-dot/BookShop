<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$message = '';

if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM livre WHERE idLivre = ?")->execute([$_GET['delete']]);
    $message = "Livre supprimé.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre       = trim($_POST['titre']);
    $description = trim($_POST['description']);
    $prix        = $_POST['prix'];
    $stock       = $_POST['stock'];
    $image = '';
    if ($_FILES['image']['name'] != '') {
        $nomFichier = time() . '_' . $_FILES['image']['name'];
        move_uploaded_file($_FILES['image']['tmp_name'], '../uploads/book-covers/' . $nomFichier);
        $image = $nomFichier;
    }
    $pdo->prepare("INSERT INTO livre (titre, description, prix, stock, image, createdAt) VALUES (?, ?, ?, ?, ?, NOW())")
        ->execute([$titre, $description, $prix, $stock, $image]);
    $message = "Livre ajouté.";
}

$livres = $pdo->query("SELECT * FROM livre ORDER BY createdAt DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Livres — BookShop Admin</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>

<header>
    <div class="logosec">
        <img class="menuicn" id="menuicn" src="https://img.icons8.com/ios-filled/50/menu--v1.png" alt="menu">
        <div class="logo">📚 BookShop Admin</div>
    </div>
    <span class="admin-info">Bonjour, <?= $_SESSION['nomUser'] ?> 👋</span>
    <a class="logout-btn" href="../logout.php">Se déconnecter</a>
</header>

<div class="main-container">
    <div class="navcontainer" id="navcontainer">
        <nav class="nav">
            <div class="nav-upper-options">
                <a class="nav-option" href="dashboard.php">
                    <img class="nav-img" src="https://img.icons8.com/?size=100&id=10245&format=png&color=000000"><h3>Dashboard</h3>
                </a>
                <a class="nav-option active" href="books.php">
                    <img class="nav-img" src="https://img.icons8.com/ios-filled/50/ffffff/book.png"><h3>Livres</h3>
                </a>
                <a class="nav-option" href="categories.php">
                    <img class="nav-img" src="https://img.icons8.com/ios-filled/50/price-tag.png"><h3>Catégories</h3>
                </a>
                <a class="nav-option" href="orders.php">
                    <img class="nav-img" src="https://img.icons8.com/ios-filled/50/package.png"><h3>Commandes</h3>
                </a>
                <a class="nav-option" href="users.php">
                    <img class="nav-img" src="https://img.icons8.com/ios-filled/50/user.png"><h3>Utilisateurs</h3>
                </a>
                <a class="nav-option logout-nav" href="../logout.php">
                    <img class="nav-img" src="https://img.icons8.com/ios-filled/50/exit.png"><h3>Déconnexion</h3>
                </a>
            </div>
        </nav>
    </div>

    <div class="main">
        <?php if ($message): ?>
            <div class="message-box success">✓ <?= $message ?></div>
        <?php endif; ?>

        <div class="form-box">
            <h3>Ajouter un livre</h3>
            <form method="POST" enctype="multipart/form-data">
                <div class="form-row">
                    <div class="form-group">
                        <label>Titre</label>
                        <input type="text" name="titre" required>
                    </div>
                    <div class="form-group">
                        <label>Prix (DT)</label>
                        <input type="number" name="prix" step="0.01" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Stock</label>
                        <input type="number" name="stock" required>
                    </div>
                    <div class="form-group">
                        <label>Image</label>
                        <input type="file" name="image" accept="image/*">
                    </div>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description"></textarea>
                </div>
                <button class="btn btn-primary" type="submit">Ajouter</button>
            </form>
        </div>

        <div class="report-container">
            <div class="report-header">
                <h2>Liste des livres</h2>
            </div>
            <table>
                <tr>
                    <th>Image</th><th>Titre</th><th>Prix</th><th>Stock</th><th>Action</th>
                </tr>
                <?php foreach ($livres as $livre): ?>
                <tr>
                    <td><?= $livre['image'] ? '<img class="book-img" src="../uploads/book-covers/'.$livre['image'].'">' : '—' ?></td>
                    <td><?= $livre['titre'] ?></td>
                    <td><?= number_format($livre['prix'], 2) ?> DT</td>
                    <td><?= $livre['stock'] ?></td>
                    <td><a class="btn btn-danger" href="?delete=<?= $livre['idLivre'] ?>" onclick="return confirm('Supprimer ?')">Supprimer</a></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>
</div>

<script>
    document.querySelector(".menuicn").addEventListener("click", () => {
        document.querySelector(".navcontainer").classList.toggle("navclose");
    });
</script>
</body>
</html>