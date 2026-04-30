<?php
session_start();
require_once '../includes/db.php';
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') { header('Location: ../login.php'); exit(); }

$activePage = 'authors';
$message = '';
$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: authors.php'); exit(); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom       = trim($_POST['nom']);
    $prenom    = trim($_POST['prenom']);
    $desc      = trim($_POST['description']);
    $status    = $_POST['status'];
    $dateNaiss = $_POST['dateNaiss'] ?: null;
    $image     = $_POST['current_image'];
    if (!empty($_FILES['image']['name'])) {
        $ext     = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $nomFich = time() . '_author_' . uniqid() . '.' . $ext;
        move_uploaded_file($_FILES['image']['tmp_name'], '../uploads/author/' . $nomFich);
        $image = $nomFich;
    }
    $pdo->prepare("UPDATE auteur SET nom=?, prenom=?, description=?, status=?, dateNaiss=?, image=? WHERE idAuteur=?")
        ->execute([$nom, $prenom, $desc, $status, $dateNaiss, $image, $id]);
    $message = "Author updated.";
}

$stmt = $pdo->prepare("SELECT * FROM auteur WHERE idAuteur = ?");
$stmt->execute([$id]);
$author = $stmt->fetch();
if (!$author) { header('Location: authors.php'); exit(); }

$books = $pdo->query("
    SELECT l.*, GROUP_CONCAT(DISTINCT c.nomCat SEPARATOR ', ') AS categories
    FROM livre l
    INNER JOIN livre_auteur la ON l.idLivre = la.idLivre
    LEFT JOIN livre_categorie lc ON l.idLivre = lc.idLivre
    LEFT JOIN categorie c ON lc.idCat = c.idCat
    WHERE la.idAuteur = $id
    GROUP BY l.idLivre
    ORDER BY l.createdAt DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($author['prenom'].' '.$author['nom']) ?> — Author</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .author-profile { display:flex; gap:25px; align-items:flex-start; margin-bottom:25px; padding-bottom:20px; border-bottom:1px solid #eee; }
        .author-photo { width:110px; height:110px; border-radius:50%; object-fit:cover; border:3px solid #eee; box-shadow:0 4px 15px rgba(0,0,0,0.1); }
        .author-photo-ph { width:110px; height:110px; border-radius:50%; background:#dde; display:flex; align-items:center; justify-content:center; font-size:50px; }
        .author-info h2 { font-size:20px; color:var(--primary); margin-bottom:5px; }
        .author-info p { font-size:13px; color:#888; margin-bottom:3px; }
        .back-link { display:inline-flex; align-items:center; gap:6px; color:var(--secondary); text-decoration:none; font-size:14px; margin-bottom:20px; }
        .mini-badge { background:#eef2ff; color:#5c6bc0; font-size:11px; padding:2px 8px; border-radius:10px; }
        .file-label { display:flex; align-items:center; gap:10px; padding:10px 14px; border:2px dashed #ccc; border-radius:8px; cursor:pointer; font-size:13px; color:#777; background:#fafafa; }
        .file-label input { display:none; }
        #photoPreview { width:80px; height:80px; border-radius:50%; object-fit:cover; display:none; margin-top:8px; }
    </style>
</head>
<body>
<?php include '../includes/nav.php'; ?>
<div class="main">
    <a class="back-link" href="authors.php">← Back to Authors</a>
    <?php if ($message): ?><div class="message-box success">✓ <?= $message ?></div><?php endif; ?>
    <div class="form-box">
        <div class="author-profile">
            <?php if ($author['image']): ?>
                <img class="author-photo" src="../uploads/authors/<?= htmlspecialchars($author['image']) ?>">
            <?php else: ?>
                <div class="author-photo-ph">✍️</div>
            <?php endif; ?>
            <div class="author-info">
                <h2><?= htmlspecialchars($author['prenom'].' '.$author['nom']) ?></h2>
                <p><?= $author['status'] === 'vivant' ? '🟢 Alive' : '⚫ Deceased' ?></p>
                <p>🗓 Born: <?= $author['dateNaiss'] ? date('d/m/Y', strtotime($author['dateNaiss'])) : '—' ?></p>
                <p>📚 <?= count($books) ?> book<?= count($books) != 1 ? 's' : '' ?> in the catalog</p>
                <?php if ($author['description']): ?>
                    <p style="margin-top:8px; font-style:italic; color:#555; max-width:400px;"><?= htmlspecialchars($author['description']) ?></p>
                <?php endif; ?>
            </div>
        </div>
        <h3>✏️ Edit Author</h3>
        <form method="POST" enctype="multipart/form-data" style="margin-top:16px;">
            <input type="hidden" name="current_image" value="<?= htmlspecialchars($author['image']) ?>">
            <div class="form-row">
                <div class="form-group"><label>First Name *</label><input type="text" name="prenom" value="<?= htmlspecialchars($author['prenom']) ?>" required></div>
                <div class="form-group"><label>Last Name *</label><input type="text" name="nom" value="<?= htmlspecialchars($author['nom']) ?>" required></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Date of Birth</label><input type="date" name="dateNaiss" value="<?= $author['dateNaiss'] ?>"></div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status">
                        <option value="vivant" <?= $author['status']==='vivant'?'selected':'' ?>>🟢 Alive</option>
                        <option value="decede" <?= $author['status']==='decede'?'selected':'' ?>>⚫ Deceased</option>
                    </select>
                </div>
            </div>
            <div class="form-group"><label>Biography</label><textarea name="description"><?= htmlspecialchars($author['description'] ?? '') ?></textarea></div>
            <div class="form-group">
                <label>Photo</label>
                <label class="file-label" for="photoInput">
                    <input type="file" id="photoInput" name="image" accept="image/*" onchange="previewPhoto(this)">
                    🧑 Choose a new photo…
                </label>
                <img id="photoPreview" alt="Preview">
            </div>
            <button class="btn btn-success" type="submit">💾 Save Changes</button>
        </form>
    </div>
    <div class="report-container" style="margin-top:20px;">
        <div class="report-header">
            <h2>📚 Books by <?= htmlspecialchars($author['prenom'].' '.$author['nom']) ?></h2>
            <a class="btn btn-primary" href="add-book.php">＋ Add Book</a>
        </div>
        <table>
            <thead><tr><th>Cover</th><th>Title</th><th>Categories</th><th>Price</th><th>Stock</th><th>Action</th></tr></thead>
            <tbody>
            <?php foreach ($books as $livre): ?>
            <tr>
                <td><?= $livre['image'] ? '<img class="book-img" src="../uploads/book-covers/'.htmlspecialchars($livre['image']).'">' : '📖' ?></td>
                <td><a href="book-detail.php?id=<?= $livre['idLivre'] ?>" style="color:var(--secondary);font-weight:600;"><?= htmlspecialchars($livre['titre']) ?></a></td>
                <td><?php foreach (explode(', ', $livre['categories']??'') as $c): if(trim($c)) echo '<span class="mini-badge">'.htmlspecialchars(trim($c)).'</span> '; endforeach; ?></td>
                <td><strong><?= number_format($livre['prix'],2) ?> DT</strong></td>
                <td><span class="badge <?= $livre['stock']>0?'confirmee':'annulee' ?>"><?= $livre['stock'] ?></span></td>
                <td><a class="btn btn-warning" href="book-detail.php?id=<?= $livre['idLivre'] ?>">Edit</a></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($books)): ?><tr><td colspan="6" style="text-align:center;padding:30px;color:#bbb;">No books yet for this author.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</div>
<script>
function previewPhoto(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => { const p = document.getElementById('photoPreview'); p.src = e.target.result; p.style.display = 'block'; };
        reader.readAsDataURL(input.files[0]);
    }
}
document.querySelector(".menuicn").addEventListener("click", () => { document.querySelector(".navcontainer").classList.toggle("navclose"); });
</script>
</body></html>
