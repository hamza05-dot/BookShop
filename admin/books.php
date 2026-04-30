<?php
session_start();
require_once '../includes/db.php';
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') { header('Location: ../login.php'); exit(); }

$activePage = 'books';
$message = '';

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $pdo->prepare("DELETE FROM livre_auteur    WHERE idLivre = ?")->execute([$id]);
    $pdo->prepare("DELETE FROM livre_categorie WHERE idLivre = ?")->execute([$id]);
    $pdo->prepare("DELETE FROM ligne_commande  WHERE idLivre = ?")->execute([$id]);
    $pdo->prepare("DELETE FROM livre           WHERE idLivre = ?")->execute([$id]);
    $message = "Book deleted.";
}

$livres = $pdo->query("
    SELECT l.*,
           GROUP_CONCAT(DISTINCT c.nomCat SEPARATOR ', ') AS categories,
           CONCAT(a.prenom, ' ', a.nom) AS auteur
    FROM livre l
    LEFT JOIN livre_categorie lc ON l.idLivre  = lc.idLivre
    LEFT JOIN categorie c        ON lc.idCat   = c.idCat
    LEFT JOIN livre_auteur la    ON l.idLivre  = la.idLivre
    LEFT JOIN auteur a           ON la.idAuteur = a.idAuteur
    GROUP BY l.idLivre
    ORDER BY l.createdAt DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Books — BookShop Admin</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .search-wrap { display:flex; align-items:center; background:#f5f7fa; border:1.5px solid #e0e4ed; border-radius:25px; padding:6px 14px; gap:8px; }
        .search-wrap:focus-within { border-color:var(--secondary); background:#fff; }
        #bookSearch { border:none; background:transparent; outline:none; font-size:13px; font-family:"Poppins",sans-serif; width:220px; }
        .mini-badge { background:#eef2ff; color:#5c6bc0; font-size:11px; padding:2px 8px; border-radius:10px; white-space:nowrap; }
        .cat-list { display:flex; flex-wrap:wrap; gap:4px; }
        .clickable-title { color:var(--secondary); cursor:pointer; font-weight:600; text-decoration:underline; }
        .clickable-title:hover { color:var(--primary); }
    </style>
</head>
<body>
<?php include '../includes/nav.php'; ?>
<div class="main">
    <?php if ($message): ?><div class="message-box success">✓ <?= $message ?></div><?php endif; ?>
    <div class="report-container">
        <div class="report-header">
            <h2>📚 All Books (<span id="bookCount"><?= count($livres) ?></span>)</h2>
            <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                <div class="search-wrap">
                    <span>🔍</span>
                    <input type="text" id="bookSearch" placeholder="Search title, author…" oninput="filterBooks(this.value)">
                </div>
                <a class="btn btn-primary" href="add-book.php">＋ Add Book</a>
            </div>
        </div>
        <table id="bookTable">
            <thead><tr><th>Cover</th><th>Title</th><th>Author</th><th>Categories</th><th>Price</th><th>Stock</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($livres as $livre): ?>
            <tr class="book-row" data-search="<?= strtolower(htmlspecialchars($livre['titre'].' '.($livre['auteur']??'').' '.($livre['categories']??''))) ?>">
                <td>
                    <?php if ($livre['image']): ?>
                        <img class="book-img" src="../uploads/book-covers/<?= htmlspecialchars($livre['image']) ?>">
                    <?php else: ?>
                        <div style="width:45px;height:60px;background:#eef;border-radius:4px;display:flex;align-items:center;justify-content:center;">📖</div>
                    <?php endif; ?>
                </td>
                <td><a class="clickable-title" href="book-detail.php?id=<?= $livre['idLivre'] ?>"><?= htmlspecialchars($livre['titre']) ?></a></td>
                <td style="font-size:13px; color:#777;"><?= htmlspecialchars($livre['auteur'] ?? '—') ?></td>
                <td>
                    <div class="cat-list">
                        <?php foreach (explode(', ', $livre['categories'] ?? '') as $cat): ?>
                            <?php if (trim($cat)): ?><span class="mini-badge"><?= htmlspecialchars(trim($cat)) ?></span><?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </td>
                <td><strong><?= number_format($livre['prix'], 2) ?> DT</strong></td>
                <td><span class="badge <?= $livre['stock'] > 0 ? 'confirmee' : 'annulee' ?>"><?= $livre['stock'] ?></span></td>
                <td>
                    <a class="btn btn-warning" href="book-detail.php?id=<?= $livre['idLivre'] ?>">Edit</a>
                    <a class="btn btn-danger" href="?delete=<?= $livre['idLivre'] ?>" onclick="return confirm('Delete this book?')">Delete</a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($livres)): ?>
                <tr><td colspan="7" style="text-align:center;padding:40px;color:#bbb;">No books yet. <a href="add-book.php">Add your first →</a></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</div>
<script>
function filterBooks(q) {
    q = q.toLowerCase();
    let visible = 0;
    document.querySelectorAll('.book-row').forEach(row => {
        const match = !q || row.dataset.search.includes(q);
        row.style.display = match ? '' : 'none';
        if (match) visible++;
    });
    document.getElementById('bookCount').textContent = visible;
}
document.querySelector(".menuicn").addEventListener("click", () => { document.querySelector(".navcontainer").classList.toggle("navclose"); });
</script>
</body></html>
