<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories — BookShop Admin</title>
    <link rel="stylesheet" href="../assests/css/admin.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <style>
        .spinner { display:inline-block; width:18px; height:18px; border:3px solid #ddd; border-top-color:var(--secondary,#5c6bc0); border-radius:50%; animation:spin .7s linear infinite; vertical-align:middle; margin-right:6px; }
        @keyframes spin { to { transform:rotate(360deg); } }
    </style>
</head>
<body>
<?php include '../includes/nav.php'; ?>
<div class="main">
    <?php if ($message): ?><div class="message-box success">✓ <?= $message ?></div><?php endif; ?>

    <!-- Formulaire d'ajout de catégorie (soumission PHP normale) -->
    <div class="form-box">
        <h3>➕ Add Category</h3>
        <form method="POST" style="display:flex;gap:10px;align-items:flex-end;margin-top:12px;">
            <div class="form-group" style="margin:0;flex:1;">
                <label>Category name</label>
                <input type="text" name="nomCat" placeholder="e.g. Romance, Sci-Fi…" required>
            </div>
            <button class="btn btn-primary" type="submit">Add</button>
        </form>
    </div>

    <div class="report-container">
        <div class="report-header">
            <h2>🏷️ All Categories (<span id="catCount">…</span>)</h2>
        </div>
        <!-- Table injectée par jQuery -->
        <div id="catsTableWrap">
            <p style="padding:30px;text-align:center;color:#aaa;"><span class="spinner"></span> Loading categories…</p>
        </div>
    </div>
</div>

<script>
$(document).ready(function () {

    $(".menuicn").on("click", function () {
        $(".navcontainer").toggleClass("navclose");
    });

    // ── Charger les catégories depuis l'API ───────────────────────────────────
    $.getJSON("../admin/api.php?action=categories", function (cats) {

        $("#catCount").text(cats.length);

        if (!cats.length) {
            $("#catsTableWrap").html('<p style="text-align:center;padding:40px;color:#bbb;">No categories yet.</p>');
            return;
        }

        var html = '<table><thead><tr><th>Name</th><th>Books</th><th>Actions</th></tr></thead><tbody>';

        $.each(cats, function (i, cat) {
            html += '<tr>';
            html += '<td><a href="category-detail.php?id='+cat.idCat+'" style="color:var(--secondary);font-weight:600;text-decoration:underline;">'+$('<div>').text(cat.nomCat).html()+'</a></td>';
            html += '<td><strong style="color:var(--secondary);">'+cat.bookCount+' book'+(cat.bookCount!=1?'s':'')+'</strong></td>';
            html += '<td><a class="btn btn-warning" href="category-detail.php?id='+cat.idCat+'">View</a> <a class="btn btn-danger" href="?delete='+cat.idCat+'" onclick="return confirm(\'Delete this category?\')">Delete</a></td>';
            html += '</tr>';
        });

        html += '</tbody></table>';
        $("#catsTableWrap").html(html);

    }).fail(function () {
        $("#catsTableWrap").html('<p style="color:red;padding:20px;">Failed to load categories.</p>');
    });

});
</script>
</body>
</html>
