<?php
session_start();
require_once '../includes/db.php';
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') { header('Location: ../login.php'); exit(); }

$activePage = 'books';
$message = '';
$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: books.php'); exit(); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre       = trim($_POST['titre']);
    $description = trim($_POST['description']);
    $prix        = (float)$_POST['prix'];
    $stock       = (int)$_POST['stock'];
    $categories  = $_POST['categories'] ?? [];
    $image = $_POST['current_image'];
    if (!empty($_FILES['image']['name'])) {
        $ext     = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $nomFich = time() . '_' . uniqid() . '.' . $ext;
        move_uploaded_file($_FILES['image']['tmp_name'], '../uploads/book-covers/' . $nomFich);
        $image = $nomFich;
    }
    $pdo->prepare("UPDATE livre SET titre=?, description=?, prix=?, stock=?, image=? WHERE idLivre=?")
        ->execute([$titre, $description, $prix, $stock, $image, $id]);
    $pdo->prepare("DELETE FROM livre_categorie WHERE idLivre=?")->execute([$id]);
    foreach ($categories as $idCat) {
        $pdo->prepare("INSERT INTO livre_categorie (idLivre, idCat) VALUES (?,?)")->execute([$id, (int)$idCat]);
    }
    $message = "Book updated successfully!";
}

$stmt = $pdo->prepare("
    SELECT l.*, GROUP_CONCAT(DISTINCT lc.idCat) AS cat_ids,
           CONCAT(a.prenom,' ',a.nom) AS auteur, a.idAuteur
    FROM livre l
    LEFT JOIN livre_categorie lc ON l.idLivre  = lc.idLivre
    LEFT JOIN livre_auteur la    ON l.idLivre  = la.idLivre
    LEFT JOIN auteur a           ON la.idAuteur = a.idAuteur
    WHERE l.idLivre = ?
    GROUP BY l.idLivre
");
$stmt->execute([$id]);
$livre = $stmt->fetch();
if (!$livre) { header('Location: books.php'); exit(); }

$allCategories = $pdo->query("SELECT * FROM categorie ORDER BY nomCat ASC")->fetchAll();
$selectedCats  = $livre['cat_ids'] ? explode(',', $livre['cat_ids']) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($livre['titre']) ?> — Edit</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .book-preview { display:flex; gap:30px; align-items:flex-start; margin-bottom:25px; padding-bottom:20px; border-bottom:1px solid #eee; }
        .book-cover-lg { width:120px; height:160px; object-fit:cover; border-radius:8px; box-shadow:0 4px 15px rgba(0,0,0,0.15); }
        .cover-placeholder-lg { width:120px; height:160px; background:#eef; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:50px; }
        .book-meta h2 { font-size:20px; color:var(--primary); margin-bottom:6px; }
        .book-meta p { font-size:13px; color:#888; margin-bottom:4px; }
        .cat-chip { display:inline-flex; align-items:center; gap:6px; padding:6px 14px; border:2px solid #ddd; border-radius:20px; cursor:pointer; font-size:13px; margin:4px; background:#fafafa; }
        .cat-chip input { display:none; }
        .cat-chip.checked { border-color:var(--secondary); background:var(--secondary); color:white; }
        .file-label { display:flex; align-items:center; gap:10px; padding:10px 14px; border:2px dashed #ccc; border-radius:8px; cursor:pointer; font-size:13px; color:#777; background:#fafafa; }
        .file-label input { display:none; }
        #coverPreview { width:80px; height:105px; object-fit:cover; border-radius:6px; display:none; margin-top:8px; }
        .back-link { display:inline-flex; align-items:center; gap:6px; color:var(--secondary); text-decoration:none; font-size:14px; margin-bottom:20px; }
    </style>
</head>
<body>
<?php include '../includes/nav.php'; ?>
<div class="main">
    <a class="back-link" href="books.php">← Back to All Books</a>
    <?php if ($message): ?><div class="message-box success">✓ <?= $message ?></div><?php endif; ?>
    <div class="form-box">
        <div class="book-preview">
            <?php if ($livre['image']): ?>
                <img class="book-cover-lg" src="../uploads/book-covers/<?= htmlspecialchars($livre['image']) ?>">
            <?php else: ?>
                <div class="cover-placeholder-lg">📖</div>
            <?php endif; ?>
            <div class="book-meta">
                <h2><?= htmlspecialchars($livre['titre']) ?></h2>
                <p>👤 Author: <strong><?= htmlspecialchars($livre['auteur'] ?? '—') ?></strong></p>
                <p>💰 Price: <strong><?= number_format($livre['prix'],2) ?> DT</strong></p>
                <p>📦 Stock: <strong><?= $livre['stock'] ?></strong></p>
                <p>🗓 Added: <?= date('d/m/Y', strtotime($livre['createdAt'])) ?></p>
            </div>
        </div>
        <h3>✏️ Edit Book</h3>
        <form method="POST" enctype="multipart/form-data" style="margin-top:16px;">
            <input type="hidden" name="current_image" value="<?= htmlspecialchars($livre['image']) ?>">
            <div class="form-row">
                <div class="form-group">
                    <label>Title *</label>
                    <input type="text" name="titre" value="<?= htmlspecialchars($livre['titre']) ?>" required>
                </div>
                <div class="form-group">
                    <label>New Cover Image</label>
                    <label class="file-label" for="coverInput">
                        <input type="file" id="coverInput" name="image" accept="image/*" onchange="previewCover(this)">
                        🖼️ Choose image…
                    </label>
                    <img id="coverPreview" alt="Preview">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Price (DT) *</label>
                    <input type="number" name="prix" step="0.01" value="<?= $livre['prix'] ?>" required>
                </div>
                <div class="form-group">
                    <label>Stock *</label>
                    <input type="number" name="stock" value="<?= $livre['stock'] ?>" required>
                </div>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description"><?= htmlspecialchars($livre['description'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label>Categories</label>
                <div>
                    <?php foreach ($allCategories as $cat): ?>
                        <?php $checked = in_array($cat['idCat'], $selectedCats); ?>
                        <label class="cat-chip <?= $checked ? 'checked' : '' ?>">
                            <input type="checkbox" name="categories[]" value="<?= $cat['idCat'] ?>"
                                   <?= $checked ? 'checked' : '' ?>
                                   onchange="this.closest('.cat-chip').classList.toggle('checked', this.checked)">
                            <?= htmlspecialchars($cat['nomCat']) ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div style="display:flex; gap:10px; margin-top:10px;">
                <button class="btn btn-success" type="submit">💾 Save Changes</button>
                <a class="btn btn-warning" href="books.php">Cancel</a>
            </div>
        </form>
    </div>
</div>
</div>
<script>
function previewCover(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => { const prev = document.getElementById('coverPreview'); prev.src = e.target.result; prev.style.display = 'block'; };
        reader.readAsDataURL(input.files[0]);
    }
}
document.querySelector(".menuicn").addEventListener("click", () => { document.querySelector(".navcontainer").classList.toggle("navclose"); });
</script>
</body></html>
