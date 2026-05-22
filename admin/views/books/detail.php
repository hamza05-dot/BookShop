<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($livre['titre']) ?> — Edit</title>
    <link rel="stylesheet" href="../assests/css/admin.css">
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

    <!-- jQuery CDN -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</head>
<body>
<?php include '../includes/nav.php'; ?>
<div class="main">
    <a class="back-link" href="books.php">← Back to All Books</a>
    <?php if ($message): ?><div class="message-box success">✓ <?= $message ?></div><?php endif; ?>

    <div class="form-box">
        <!-- Aperçu actuel du livre -->
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
                    <!-- Input file caché, déclenché par le label -->
                    <label class="file-label" for="coverInput">
                        <input type="file" id="coverInput" name="image" accept="image/*">
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
                                   <?= $checked ? 'checked' : '' ?>>
                            <?= htmlspecialchars($cat['nomCat']) ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div style="display:flex; gap:10px; margin-top:10px;">
                <button class="btn btn-success" type="submit" name="saveBook">💾 Save Changes</button>
                <a class="btn btn-warning" href="books.php">Cancel</a>
            </div>
        </form>
    </div>
</div>
</div>

<script>
    // Prévisualisation de l'image avant upload
    $("#coverInput").on("change", function () {
        var file = this.files[0];
        if (!file) return;

        var reader = new FileReader();
        reader.onload = function (e) {
            // Affiche la preview
            $("#coverPreview").attr("src", e.target.result).show();
        };
        reader.readAsDataURL(file);
    });

    // Toggle checked sur les chips catégories
    $(".cat-chip input[type='checkbox']").on("change", function () {
        $(this).closest(".cat-chip").toggleClass("checked", this.checked);
    });

    // Toggle sidebar
    $(".menuicn").on("click", function () {
        $(".navcontainer").toggleClass("navclose");
    });
</script>
</body>
</html>