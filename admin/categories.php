<?php
session_start();
require_once '../includes/db.php';
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') { header('Location: ../login.php'); exit(); }

$activePage = 'categories';
$message = '';

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $pdo->prepare("DELETE FROM livre_categorie WHERE idCat=?")->execute([$id]);
    $pdo->prepare("DELETE FROM categorie WHERE idCat=?")->execute([$id]);
    $message = "Category deleted.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nomCat = trim($_POST['nomCat']);
    if (!empty($nomCat)) {
        $pdo->prepare("INSERT INTO categorie (nomCat) VALUES (?)")->execute([$nomCat]);
        $message = "Category added.";
    }
}

$categories = $pdo->query("
    SELECT c.*, COUNT(lc.idLivre) AS bookCount
    FROM categorie c
    LEFT JOIN livre_categorie lc ON c.idCat = lc.idCat
    GROUP BY c.idCat
    ORDER BY bookCount DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories — BookShop Admin</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>.clickable-cat { color:var(--secondary); cursor:pointer; font-weight:600; text-decoration:underline; }</style>
</head>
<body>
<?php include '../includes/nav.php'; ?>
<div class="main">
    <?php if ($message): ?><div class="message-box success">✓ <?= $message ?></div><?php endif; ?>
    <div class="form-box">
        <h3>➕ Add Category</h3>
        <form method="POST" style="display:flex; gap:10px; align-items:flex-end; margin-top:12px;">
            <div class="form-group" style="margin:0; flex:1;">
                <label>Category name</label>
                <input type="text" name="nomCat" placeholder="e.g. Romance, Sci-Fi…" required>
            </div>
            <button class="btn btn-primary" type="submit" style="margin-bottom:0;">Add</button>
        </form>
    </div>
    <div class="report-container">
        <div class="report-header"><h2>🏷️ All Categories (<?= count($categories) ?>)</h2></div>
        <table>
            <thead><tr><th>Name</th><th>Books</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($categories as $cat): ?>
            <tr>
                <td><a class="clickable-cat" href="category-detail.php?id=<?= $cat['idCat'] ?>"><?= htmlspecialchars($cat['nomCat']) ?></a></td>
                <td><a href="category-detail.php?id=<?= $cat['idCat'] ?>" style="font-weight:700; color:var(--secondary);"><?= $cat['bookCount'] ?> book<?= $cat['bookCount'] != 1 ? 's' : '' ?></a></td>
                <td>
                    <a class="btn btn-warning" href="category-detail.php?id=<?= $cat['idCat'] ?>">View</a>
                    <a class="btn btn-danger" href="?delete=<?= $cat['idCat'] ?>" onclick="return confirm('Delete this category?')">Delete</a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($categories)): ?><tr><td colspan="4" style="text-align:center;padding:40px;color:#bbb;">No categories yet.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</div>
<script>document.querySelector(".menuicn").addEventListener("click", () => { document.querySelector(".navcontainer").classList.toggle("navclose"); });</script>
</body></html>
