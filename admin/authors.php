<?php
session_start();
require_once '../includes/db.php';
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') { header('Location: ../login.php'); exit(); }

$activePage = 'authors';
$message = '';

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $pdo->prepare("DELETE FROM livre_auteur WHERE idAuteur=?")->execute([$id]);
    $pdo->prepare("DELETE FROM auteur WHERE idAuteur=?")->execute([$id]);
    $message = "Author deleted.";
}

$authors = $pdo->query("
    SELECT a.*, COUNT(la.idLivre) AS bookCount
    FROM auteur a
    LEFT JOIN livre_auteur la ON a.idAuteur = la.idAuteur
    GROUP BY a.idAuteur
    ORDER BY a.nom ASC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Authors — BookShop Admin</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .author-avatar { width:42px; height:42px; border-radius:50%; object-fit:cover; }
        .avatar-ph { width:42px; height:42px; border-radius:50%; background:#dde; display:inline-flex; align-items:center; justify-content:center; font-size:20px; }
        .clickable-name { color:var(--secondary); cursor:pointer; font-weight:600; text-decoration:underline; }
        .status-badge { padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600; }
        .status-vivant { background:#d5f5e3; color:#1e8449; }
        .status-decede { background:#eee; color:#888; }
    </style>
</head>
<body>
<?php include '../includes/nav.php'; ?>
<div class="main">
    <?php if ($message): ?><div class="message-box success">✓ <?= $message ?></div><?php endif; ?>
    <div class="report-container">
        <div class="report-header"><h2>✍️ Authors (<?= count($authors) ?>)</h2></div>
        <table>
            <thead><tr><th>Photo</th><th>Name</th><th>Status</th><th>Date of Birth</th><th>Books</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($authors as $a): ?>
            <tr>
                <td><?= $a['image'] ? '<img class="author-avatar" src="../uploads/authors/'.htmlspecialchars($a['image']).'">' : '<span class="avatar-ph">✍️</span>' ?></td>
                <td><a class="clickable-name" href="author-detail.php?id=<?= $a['idAuteur'] ?>"><?= htmlspecialchars($a['prenom'].' '.$a['nom']) ?></a></td>
                <td><span class="status-badge status-<?= $a['status'] ?>"><?= $a['status'] === 'Alive' ? '🟢 Alive' : '⚫ Deceased' ?></span></td>
                <td style="font-size:13px;"><?= $a['dateNaiss'] ? date('d/m/Y', strtotime($a['dateNaiss'])) : '—' ?></td>
                <td><a href="author-detail.php?id=<?= $a['idAuteur'] ?>" style="font-weight:700; color:var(--secondary);"><?= $a['bookCount'] ?> book<?= $a['bookCount'] != 1 ? 's' : '' ?></a></td>
                <td>
                    <a class="btn btn-warning" href="author-detail.php?id=<?= $a['idAuteur'] ?>">View</a>
                    <a class="btn btn-danger" href="?delete=<?= $a['idAuteur'] ?>" onclick="return confirm('Delete this author?')">Delete</a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($authors)): ?><tr><td colspan="6" style="text-align:center;padding:40px;color:#bbb;">No authors yet.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</div>
<script>document.querySelector(".menuicn").addEventListener("click", () => { document.querySelector(".navcontainer").classList.toggle("navclose"); });</script>
</body></html>
