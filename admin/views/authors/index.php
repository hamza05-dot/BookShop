<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Authors — BookShop Admin</title>
    <link rel="stylesheet" href="../assests/css/admin.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <style>
        .spinner { display:inline-block; width:18px; height:18px; border:3px solid #ddd; border-top-color:var(--secondary,#5c6bc0); border-radius:50%; animation:spin .7s linear infinite; vertical-align:middle; margin-right:6px; }
        @keyframes spin { to { transform:rotate(360deg); } }
        .search-wrap { display:flex; align-items:center; background:#f5f7fa; border:1.5px solid #e0e4ed; border-radius:25px; padding:6px 14px; gap:8px; }
        .search-wrap:focus-within { border-color:var(--secondary); background:#fff; }
        #authorSearch { border:none; background:transparent; outline:none; font-size:13px; font-family:inherit; width:220px; }
        .author-avatar { width:42px; height:42px; border-radius:50%; object-fit:cover; }
        .avatar-ph { width:42px; height:42px; border-radius:50%; background:#dde; display:inline-flex; align-items:center; justify-content:center; font-size:20px; }
        .status-Alive { background:#d5f5e3; color:#1e8449; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600; }
        .status-Dead  { background:#eee;    color:#888;    padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600; }
    </style>
</head>
<body>
<?php include '../includes/nav.php'; ?>
<div class="main">
    <?php if ($message): ?><div class="message-box success">✓ <?= $message ?></div><?php endif; ?>

    <div class="report-container">
        <div class="report-header">
            <h2>✍️ Authors (<span id="authorCount">…</span>)</h2>
            <div class="search-wrap">
                <span>🔍</span>
                <input type="text" id="authorSearch" placeholder="Search name or status…">
            </div>
        </div>

        <!-- Table injectée par jQuery après le fetch -->
        <div id="authorsTableWrap">
            <p style="padding:30px;text-align:center;color:#aaa;"><span class="spinner"></span> Loading authors…</p>
        </div>
    </div>
</div>

<script>
$(document).ready(function () {

    $(".menuicn").on("click", function () {
        $(".navcontainer").toggleClass("navclose");
    });

    // ── Charger les auteurs depuis l'API ──────────────────────────────────────
    $.getJSON("../admin/api.php?action=authors", function (authors) {

        $("#authorCount").text(authors.length);

        if (!authors.length) {
            $("#authorsTableWrap").html('<p style="text-align:center;padding:40px;color:#bbb;">No authors yet.</p>');
            return;
        }

        var html = '<table><thead><tr><th>Photo</th><th>Name</th><th>Status</th><th>Date of Birth</th><th>Books</th><th>Actions</th></tr></thead><tbody>';

        $.each(authors, function (i, a) {

            // photo ou placeholder
            var photo = a.image
                ? '<img class="author-avatar" src="../uploads/authors/'+a.image+'">'
                : '<span class="avatar-ph">✍️</span>';

            // badge statut : vivant ou décédé
            var statusHtml = '<span class="status-'+(a.status||'')+'">'+
                (a.status === 'Alive' ? '🟢 Alive' : '⚫ Deceased') + '</span>';

            // date de naissance au format DD/MM/YYYY
            var dob = '—';
            if (a.dateNaiss) {
                var parts = a.dateNaiss.split('-');
                dob = parts[2]+'/'+parts[1]+'/'+parts[0];
            }

            var search = (a.prenom+' '+a.nom+' '+(a.status||'')).toLowerCase();

            html += '<tr class="author-row" data-search="'+search+'">';
            html += '<td>'+photo+'</td>';
            html += '<td><a href="author-detail.php?id='+a.idAuteur+'" style="color:var(--secondary);font-weight:600;text-decoration:underline;">'+$('<div>').text(a.prenom+' '+a.nom).html()+'</a></td>';
            html += '<td>'+statusHtml+'</td>';
            html += '<td style="font-size:13px;">'+dob+'</td>';
            html += '<td><strong style="color:var(--secondary);">'+a.bookCount+' book'+(a.bookCount!=1?'s':'')+'</strong></td>';
            html += '<td><a class="btn btn-warning" href="author-detail.php?id='+a.idAuteur+'">View</a> <a class="btn btn-danger" href="?delete='+a.idAuteur+'" onclick="return confirm(\'Delete this author?\')">Delete</a></td>';
            html += '</tr>';
        });

        html += '</tbody></table>';
        $("#authorsTableWrap").html(html);

        // ── Filtre de recherche en temps réel ─────────────────────────────────
        $("#authorSearch").on("input", function () {
            var q = $(this).val().toLowerCase();
            var visible = 0;

            $(".author-row").each(function () {
                var match = !q || $(this).data("search").includes(q);
                $(this).toggle(match);
                if (match) visible++;
            });

            $("#authorCount").text(visible);
        });

    }).fail(function () {
        $("#authorsTableWrap").html('<p style="color:red;padding:20px;">Failed to load authors.</p>');
    });

});
</script>
</body>
</html>
