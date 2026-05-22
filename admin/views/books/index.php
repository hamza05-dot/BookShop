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
        .toast { position:fixed; bottom:20px; right:20px; background:#333; color:#fff; padding:10px 18px; border-radius:8px; font-size:13px; z-index:999; display:none; }
    </style>
</head>
<body>
<?php include '../includes/nav.php'; ?>
<div class="main">

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
        <div id="booksTableWrap">
            <p style="padding:30px;text-align:center;color:#aaa;"><span class="spinner"></span> Loading books…</p>
        </div>
    </div>
</div>

<div class="toast" id="toast"></div>

<script>
$(document).ready(function () {

    $(".menuicn").on("click", function () {
        $(".navcontainer").toggleClass("navclose");
    });

    // affiche un message toast en bas à droite
    function showToast(msg) {
        $("#toast").text(msg).fadeIn(200).delay(2200).fadeOut(400);
    }

    // ── Charger la liste des livres ───────────────────────────────────────────
    function loadBooks() {
        $.getJSON("api.php?action=books", function (books) {

            $("#bookCount").text(books.length);

            if (!books.length) {
                $("#booksTableWrap").html('<p style="text-align:center;padding:40px;color:#bbb;">No books yet. <a href="add-book.php">Add your first →</a></p>');
                return;
            }

            var html = '<table><thead><tr><th>Cover</th><th>Title</th><th>Author</th><th>Categories</th><th>Price</th><th>Stock</th><th>Actions</th></tr></thead><tbody>';

            $.each(books, function (i, livre) {

                // badges catégories
                var catBadges = '';
                if (livre.categories) {
                    $.each(livre.categories.split(', '), function (j, cat) {
                        if (cat.trim()) catBadges += '<span class="mini-badge">'+$('<div>').text(cat.trim()).html()+'</span>';
                    });
                }

                // couverture ou emoji placeholder
                var cover = livre.image
                    ? '<img class="book-img" src="../uploads/book-covers/'+livre.image+'">'
                    : '<div style="width:45px;height:60px;background:#eef;border-radius:4px;display:flex;align-items:center;justify-content:center;font-size:20px;">📖</div>';

                var stockClass = parseInt(livre.stock) > 0 ? 'confirmee' : 'annulee';
                var search = (livre.titre+' '+(livre.auteur||'')+' '+(livre.categories||'')).toLowerCase();

                html += '<tr class="book-row" data-search="'+search+'" data-id="'+livre.idLivre+'">';
                html += '<td>'+cover+'</td>';
                html += '<td><a href="book-detail.php?id='+livre.idLivre+'" style="color:var(--secondary);font-weight:600;">'+$('<div>').text(livre.titre).html()+'</a></td>';
                html += '<td style="font-size:13px;color:#777;">'+$('<div>').text(livre.auteur||'—').html()+'</td>';
                html += '<td><div class="cat-list">'+catBadges+'</div></td>';
                html += '<td><strong>'+parseFloat(livre.prix).toFixed(2)+' DT</strong></td>';
                html += '<td><span class="badge '+stockClass+'">'+livre.stock+'</span></td>';
                html += '<td>';
                html += '<a class="btn btn-warning" href="book-detail.php?id='+livre.idLivre+'">Edit</a> ';
                // bouton delete → appelle l'API via $.post au lieu d'un lien GET
                html += '<button class="btn btn-danger btn-delete-book" data-id="'+livre.idLivre+'" data-title="'+$('<div>').text(livre.titre).html()+'">Delete</button>';
                html += '</td>';
                html += '</tr>';
            });

            html += '</tbody></table>';
            $("#booksTableWrap").html(html);

            // filtre live sur le tableau
            $("#bookSearch").on("input", function () {
                var q = $(this).val().toLowerCase();
                var visible = 0;
                $(".book-row").each(function () {
                    var match = !q || $(this).data("search").includes(q);
                    $(this).toggle(match);
                    if (match) visible++;
                });
                $("#bookCount").text(visible);
            });

        }).fail(function () {
            $("#booksTableWrap").html('<p style="color:red;padding:20px;">Failed to load books.</p>');
        });
    }

    loadBooks();

    // ── Supprimer un livre via fetch POST ─────────────────────────────────────
    $(document).on("click", ".btn-delete-book", function () {
        var id    = $(this).data("id");
        var title = $(this).data("title");

        if (!confirm('Delete "' + title + '"?')) return;

        var $row = $(this).closest("tr");

        $.post("api.php?action=delete_book", { id: id }, function (res) {
            if (res.success) {
                // on retire la ligne du tableau sans recharger la page
                $row.fadeOut(300, function () {
                    $(this).remove();
                    // met à jour le compteur
                    var count = parseInt($("#bookCount").text()) - 1;
                    $("#bookCount").text(count);
                });
                showToast("✅ Book deleted.");
            } else {
                showToast("❌ " + (res.error || "Failed to delete."));
            }
        }, "json").fail(function () {
            showToast("❌ Server error.");
        });
    });

});
</script>
</body>
</html>
