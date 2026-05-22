<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Books — BookShop Admin</title>
    <link rel="stylesheet" href="../assests/css/admin.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <style>
        .spinner { display:inline-block; width:18px; height:18px; border:3px solid #ddd; border-top-color:var(--secondary,#5c6bc0); border-radius:50%; animation:spin .7s linear infinite; vertical-align:middle; margin-right:6px; }
        @keyframes spin { to { transform:rotate(360deg); } }
        .search-wrap { display:flex; align-items:center; background:#f5f7fa; border:1.5px solid #e0e4ed; border-radius:25px; padding:6px 14px; gap:8px; }
        .search-wrap:focus-within { border-color:var(--secondary); background:#fff; }
        #bookSearch { border:none; background:transparent; outline:none; font-size:13px; font-family:inherit; width:220px; }
        .mini-badge { background:#eef2ff; color:#5c6bc0; font-size:11px; padding:2px 8px; border-radius:10px; white-space:nowrap; }
        .cat-list { display:flex; flex-wrap:wrap; gap:4px; }
    </style>
</head>
<body>
<?php include '../includes/nav.php'; ?>
<div class="main">
    <?php if ($message): ?><div class="message-box success">✓ <?= $message ?></div><?php endif; ?>

    <div class="report-container">
        <div class="report-header">
            <h2>📚 All Books (<span id="bookCount">…</span>)</h2>
            <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
                <div class="search-wrap">
                    <span>🔍</span>
                    <input type="text" id="bookSearch" placeholder="Search title, author, category…">
                </div>
                <a class="btn btn-primary" href="add-book.php">＋ Add Book</a>
            </div>
        </div>

        <!-- Le tableau est construit ici par jQuery après le fetch -->
        <div id="booksTableWrap">
            <p style="padding:30px;text-align:center;color:#aaa;"><span class="spinner"></span> Loading books…</p>
        </div>
    </div>
</div>

<script>
$(document).ready(function () {

    $(".menuicn").on("click", function () {
        $(".navcontainer").toggleClass("navclose");
    });

    // ── Charger les livres depuis l'API ───────────────────────────────────────
    $.getJSON("../admin/api.php?action=books", function (books) {

        $("#bookCount").text(books.length);

        if (!books.length) {
            $("#booksTableWrap").html('<p style="text-align:center;padding:40px;color:#bbb;">No books yet. <a href="add-book.php">Add your first →</a></p>');
            return;
        }

        var html = '<table id="bookTable"><thead><tr><th>Cover</th><th>Title</th><th>Author</th><th>Categories</th><th>Price</th><th>Stock</th><th>Actions</th></tr></thead><tbody>';

        $.each(books, function (i, livre) {

            // badges de catégories
            var catBadges = '';
            if (livre.categories) {
                $.each(livre.categories.split(', '), function (j, cat) {
                    if (cat.trim()) catBadges += '<span class="mini-badge">'+$('<div>').text(cat.trim()).html()+'</span>';
                });
            }

            // couverture ou placeholder
            var cover = livre.image
                ? '<img class="book-img" src="../uploads/book-covers/'+livre.image+'">'
                : '<div style="width:45px;height:60px;background:#eef;border-radius:4px;display:flex;align-items:center;justify-content:center;font-size:20px;">📖</div>';

            // badge de stock : vert si > 0, rouge sinon
            var stockClass = parseInt(livre.stock) > 0 ? 'confirmee' : 'annulee';

            // attribut data pour le filtre live
            var search = (livre.titre+' '+(livre.auteur||'')+' '+(livre.categories||'')).toLowerCase();

            html += '<tr class="book-row" data-search="'+search+'">';
            html += '<td>'+cover+'</td>';
            html += '<td><a href="book-detail.php?id='+livre.idLivre+'" style="color:var(--secondary);font-weight:600;">'+$('<div>').text(livre.titre).html()+'</a></td>';
            html += '<td style="font-size:13px;color:#777;">'+$('<div>').text(livre.auteur||'—').html()+'</td>';
            html += '<td><div class="cat-list">'+catBadges+'</div></td>';
            html += '<td><strong>'+parseFloat(livre.prix).toFixed(2)+' DT</strong></td>';
            html += '<td><span class="badge '+stockClass+'">'+livre.stock+'</span></td>';
            html += '<td><a class="btn btn-warning" href="book-detail.php?id='+livre.idLivre+'">Edit</a> <a class="btn btn-danger" href="?delete='+livre.idLivre+'" onclick="return confirm(\'Delete this book?\')">Delete</a></td>';
            html += '</tr>';
        });

        html += '</tbody></table>';
        $("#booksTableWrap").html(html);

        // ── Filtre de recherche en temps réel ─────────────────────────────────
        $("#bookSearch").on("input", function () {
            var q = $(this).val().toLowerCase();
            var visible = 0;

            $(".book-row").each(function () {
                var match = !q || $(this).data("search").includes(q);
                $(this).toggle(match);
                if (match) visible++;
            });

            // met à jour le compteur en entête
            $("#bookCount").text(visible);
        });

    }).fail(function () {
        $("#booksTableWrap").html('<p style="color:red;padding:20px;">Failed to load books.</p>');
    });

});
</script>
</body>
</html>
