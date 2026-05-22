<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reviews — BookShop Admin</title>
    <link rel="stylesheet" href="../assests/css/admin.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <style>
        .spinner { display:inline-block; width:18px; height:18px; border:3px solid #ddd; border-top-color:var(--secondary,#5c6bc0); border-radius:50%; animation:spin .7s linear infinite; vertical-align:middle; margin-right:6px; }
        @keyframes spin { to { transform:rotate(360deg); } }
        .stars     { color:#C9A84C; font-size:15px; letter-spacing:1px; }
        .stars-gray { color:#ddd; }
        .review-comment { font-size:13px; color:#666; font-style:italic; max-width:300px; }
        .search-wrap { display:flex; align-items:center; background:#f5f7fa; border:1.5px solid #e0e4ed; border-radius:25px; padding:6px 14px; gap:8px; }
        .search-wrap:focus-within { border-color:var(--brown-light); background:#fff; }
        #reviewSearch { border:none; background:transparent; outline:none; font-size:13px; font-family:inherit; width:220px; }
        /* cartes statistiques en haut de la page */
        .stats-row { display:flex; gap:20px; margin-bottom:25px; flex-wrap:wrap; }
        .stat-mini { background:#fff; border-radius:12px; padding:16px 24px; text-align:center; box-shadow:var(--shadow); border:1px solid var(--border); }
        .stat-mini .num { font-size:28px; font-weight:700; color:var(--brown-dark); }
        .stat-mini .lbl { font-size:12px; color:#999; margin-top:3px; }
    </style>
</head>
<body>
<?php include '../includes/nav.php'; ?>
<div class="main">
    <?php if ($message): ?><div class="message-box success">✓ <?= $message ?></div><?php endif; ?>

    <!-- Cartes stats injectées par jQuery -->
    <div class="stats-row" id="reviewStats">
        <div class="stat-mini"><div class="num"><span class="spinner"></span></div><div class="lbl">Loading…</div></div>
    </div>

    <div class="report-container">
        <div class="report-header">
            <h2>⭐ All Reviews (<span id="reviewCount">…</span>)</h2>
            <div class="search-wrap">
                <span>🔍</span>
                <input type="text" id="reviewSearch" placeholder="Search book or client…">
            </div>
        </div>
        <!-- Table injectée par jQuery -->
        <div id="reviewsTableWrap">
            <p style="padding:30px;text-align:center;color:#aaa;"><span class="spinner"></span> Loading reviews…</p>
        </div>
    </div>
</div>

<script>
$(document).ready(function () {

    $(".menuicn").on("click", function () {
        $(".navcontainer").toggleClass("navclose");
    });

    // ── Charger les avis depuis l'API ─────────────────────────────────────────
    $.getJSON("../admin/api.php?action=reviews", function (data) {

        var reviews = data.reviews;

        // Cartes statistiques
        var statsHtml = '<div class="stat-mini"><div class="num">'+data.total+'</div><div class="lbl">Total Reviews</div></div>';
        statsHtml += '<div class="stat-mini"><div class="num" style="color:#C9A84C;">⭐ '+data.avgNote+'</div><div class="lbl">Average Rating</div></div>';
        statsHtml += '<div class="stat-mini"><div class="num" style="color:#27ae60;">'+data.fiveStars+'</div><div class="lbl">5-Star Reviews</div></div>';
        $("#reviewStats").html(statsHtml);

        $("#reviewCount").text(reviews.length);

        if (!reviews.length) {
            $("#reviewsTableWrap").html('<p style="text-align:center;padding:40px;color:#bbb;">No reviews yet.</p>');
            return;
        }

        var html = '<table><thead><tr><th>#</th><th>Client</th><th>Book</th><th>Rating</th><th>Comment</th><th>Date</th><th>Action</th></tr></thead><tbody>';

        $.each(reviews, function (i, r) {

            // étoiles pleines + étoiles grises
            var note    = parseInt(r.note) || 0;
            var stars   = '<span class="stars">' + '★'.repeat(note) + '</span>';
            stars      += '<span class="stars-gray">' + '★'.repeat(5 - note) + '</span>';
            stars      += ' <span style="font-size:12px;color:#aaa;">('+note+'/5)</span>';

            // commentaire ou placeholder
            var comment = r.commentaire
                ? '<p class="review-comment">"' + $('<div>').text(r.commentaire).html() + '"</p>'
                : '<em style="color:#ccc;font-size:12px;">No comment</em>';

            // date au format DD/MM/YYYY
            var d    = new Date(r.createdAt);
            var date = ('0'+d.getDate()).slice(-2)+'/'+('0'+(d.getMonth()+1)).slice(-2)+'/'+d.getFullYear();

            var search = (r.nomUser+' '+r.prenomUser+' '+r.titre).toLowerCase();

            html += '<tr class="review-row" data-search="'+search+'">';
            html += '<td style="color:#aaa;">'+r.idAvis+'</td>';
            html += '<td><strong>'+$('<div>').text(r.nomUser+' '+r.prenomUser).html()+'</strong></td>';
            html += '<td><a href="book-detail.php?id='+r.idLivre+'" style="color:var(--brown-mid);font-weight:600;text-decoration:underline;">'+$('<div>').text(r.titre).html()+'</a></td>';
            html += '<td>'+stars+'</td>';
            html += '<td>'+comment+'</td>';
            html += '<td style="font-size:12px;color:#888;">'+date+'</td>';
            html += '<td><a class="btn btn-danger" href="?delete='+r.idAvis+'" onclick="return confirm(\'Delete this review?\')">Delete</a></td>';
            html += '</tr>';
        });

        html += '</tbody></table>';
        $("#reviewsTableWrap").html(html);

        // ── Filtre de recherche en temps réel ─────────────────────────────────
        $("#reviewSearch").on("input", function () {
            var q = $(this).val().toLowerCase();
            var visible = 0;

            $(".review-row").each(function () {
                var match = !q || $(this).data("search").includes(q);
                $(this).toggle(match);
                if (match) visible++;
            });

            $("#reviewCount").text(visible);
        });

    }).fail(function () {
        $("#reviewsTableWrap").html('<p style="color:red;padding:20px;">Failed to load reviews.</p>');
    });

});
</script>
</body>
</html>
