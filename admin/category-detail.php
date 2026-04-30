<?php
session_start();
require_once '../includes/db.php';
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') { header('Location: ../login.php'); exit(); }

$activePage = 'categories';
$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: categories.php'); exit(); }

$stmt = $pdo->prepare("SELECT * FROM categorie WHERE idCat = ?");
$stmt->execute([$id]);
$category = $stmt->fetch();
if (!$category) { header('Location: categories.php'); exit(); }

$books = $pdo->query("
    SELECT l.*, GROUP_CONCAT(DISTINCT CONCAT(a.prenom,' ',a.nom) SEPARATOR ', ') AS auteur
    FROM livre l
    INNER JOIN livre_categorie lc ON l.idLivre = lc.idLivre
    LEFT JOIN livre_auteur la ON l.idLivre = la.idLivre
    LEFT JOIN auteur a ON la.idAuteur = a.idAuteur
    WHERE lc.idCat = $id
    GROUP BY l.idLivre
    ORDER BY l.titre ASC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($category['nomCat']) ?> — Category</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .back-link { display:inline-flex; align-items:center; gap:6px; color:var(--secondary); text-decoration:none; font-size:14px; margin-bottom:20px; }
        .cat-header { background:var(--primary); color:white; padding:25px 30px; border-radius:12px; margin-bottom:25px; }
        .cat-header h2 { font-size:24px; margin-bottom:5px; }
        .cat-header p { opacity:0.8; font-size:14px; }
    </style>
</head>
<body>
<?php include '../includes/nav.php'; ?>
<div class="main">
    <a class="back-link" href="categories.php">← Back to Categories</a>
    <div class="cat-header">
        <h2>🏷️ <?= htmlspecialchars($category['nomCat']) ?></h2>
        <p><?= count($books) ?> book<?= count($books) != 1 ? 's' : '' ?> in this category</p>
    </div>
    <div class="report-container">
        <div class="report-header">
            <h2>All Books in "<?= htmlspecialchars($category['nomCat']) ?>"</h2>
            <a class="btn btn-primary" href="add-book.php">＋ Add Book</a>
        </div>
        <table>
            <thead><tr><th>Cover</th><th>Title</th><th>Author</th><th>Price</th><th>Stock</th><th>Action</th></tr></thead>
            <tbody>
            <?php foreach ($books as $livre): ?>
            <tr>
                <td><?= $livre['image'] ? '<img class="book-img" src="../uploads/book-covers/'.htmlspecialchars($livre['image']).'">' : '<div style="width:45px;height:60px;background:#eef;border-radius:4px;display:flex;align-items:center;justify-content:center;">📖</div>' ?></td>
                <td><a href="book-detail.php?id=<?= $livre['idLivre'] ?>" style="color:var(--secondary);font-weight:600;"><?= htmlspecialchars($livre['titre']) ?></a></td>
                <td style="font-size:13px;color:#777;"><?= htmlspecialchars($livre['auteur'] ?? '—') ?></td>
                <td><strong><?= number_format($livre['prix'],2) ?> DT</strong></td>
                <td><span class="badge <?= $livre['stock']>0?'confirmee':'annulee' ?>"><?= $livre['stock'] ?></span></td>
                <td><a class="btn btn-warning" href="book-detail.php?id=<?= $livre['idLivre'] ?>">Edit</a></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($books)): ?><tr><td colspan="6" style="text-align:center;padding:40px;color:#bbb;">No books in this category yet.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</div>
<script>document.querySelector(".menuicn").addEventListener("click", () => { document.querySelector(".navcontainer").classList.toggle("navclose"); });</script>
</body></html>
