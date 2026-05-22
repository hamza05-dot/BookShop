<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/webp" href="../../../assests/img/logo.webp">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($author['prenom'].' '.$author['nom']) ?> — Author</title>
    <link rel="stylesheet" href="../assests/css/admin.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
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
                <!-- FIX: was 'ALive' (typo) -->
                <p><?= $author['status'] === 'Alive' ? '🟢 Alive' : '⚫ Deceased' ?></p>
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
                        <!-- FIX: values were 'vivant'/'decede', now match DB values 'Alive'/'Dead' -->
                        <option value="Alive" <?= $author['status'] === 'Alive' ? 'selected' : '' ?>>🟢 Alive</option>
                        <option value="Dead"  <?= $author['status'] === 'Dead'  ? 'selected' : '' ?>>⚫ Deceased</option>
                    </select>
                </div>
            </div>
            <div class="form-group"><label>Biography</label><textarea name="description"><?= htmlspecialchars($author['description'] ?? '') ?></textarea></div>
            <div class="form-group">
                <label>Photo</label>
                <label class="file-label" for="photoInput">
                    <input type="file" id="photoInput" name="image" accept="image/*">
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
<script>
$(document).ready(function () {
    $(".menuicn").on("click", function () {
        $(".navcontainer").toggleClass("navclose");
    });
    $("#photoInput").on("change", function () {
        const file = this.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = function (e) {
            $("#photoPreview").attr("src", e.target.result).show();
        };
        reader.readAsDataURL(file);
    });
});
</script>
</body>
</html>