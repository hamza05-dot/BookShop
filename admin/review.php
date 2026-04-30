<?php
session_start();
require_once '../includes/db.php';
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') { header('Location: ../login.php'); exit(); }

$activePage = 'reviews';
$message = '';

// DELETE REVIEW
if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM avis WHERE idAvis = ?")->execute([(int)$_GET['delete']]);
    $message = "Review deleted.";
}

// FETCH ALL REVIEWS
// avis → ligne_commande → livre + commande → utilisateur
$reviews = $pdo->query("
    SELECT av.idAvis, av.note, av.commentaire, av.createdAt,
           l.titre, l.idLivre,
           u.nomUser, u.prenomUser
    FROM avis av
    JOIN ligne_commande lc ON av.idLigneCom = lc.idLigneCom
    JOIN livre l           ON lc.idLivre    = l.idLivre
    JOIN commande c        ON lc.idCom      = c.idCom
    JOIN utilisateur u     ON c.idClient    = u.idUser
    ORDER BY av.createdAt DESC
")->fetchAll();

// STATS
$totalReviews = count($reviews);
$avgNote = $totalReviews > 0
    ? number_format(array_sum(array_column($reviews, 'note')) / $totalReviews, 1)
    : 0;
$fiveStars = count(array_filter($reviews, fn($r) => $r['note'] == 5));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reviews — BookShop Admin</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .stars { color: #C9A84C; font-size: 15px; letter-spacing: 1px; }
        .stars-gray { color: #ddd; }
        .review-comment { font-size: 13px; color: #666; font-style: italic; max-width: 300px; }
        .search-wrap { display:flex; align-items:center; background:#f5f7fa; border:1.5px solid #e0e4ed; border-radius:25px; padding:6px 14px; gap:8px; }
        .search-wrap:focus-within { border-color:var(--brown-light); background:#fff; }
        #reviewSearch { border:none; background:transparent; outline:none; font-size:13px; font-family:"Poppins",sans-serif; width:220px; }
        .stat-mini { background:white; border-radius:12px; padding:16px 24px; text-align:center; box-shadow:var(--shadow); border:1px solid var(--border); }
        .stat-mini .num { font-size:28px; font-weight:700; color:var(--brown-dark); }
        .stat-mini .lbl { font-size:12px; color:#999; margin-top:3px; }
        .stats-row { display:flex; gap:20px; margin-bottom:25px; flex-wrap:wrap; }
    </style>
</head>
<body>
<?php include '../includes/nav.php'; ?>

<div class="main">
    <?php if ($message): ?>
        <div class="message-box success">✓ <?= $message ?></div>
    <?php endif; ?>

    <!-- Mini stats -->
    <div class="stats-row">
        <div class="stat-mini">
            <div class="num"><?= $totalReviews ?></div>
            <div class="lbl">Total Reviews</div>
        </div>
        <div class="stat-mini">
            <div class="num" style="color:#C9A84C;">⭐ <?= $avgNote ?></div>
            <div class="lbl">Average Rating</div>
        </div>
        <div class="stat-mini">
            <div class="num" style="color:#27ae60;"><?= $fiveStars ?></div>
            <div class="lbl">5-Star Reviews</div>
        </div>
    </div>

    <div class="report-container">
        <div class="report-header">
            <h2>⭐ All Reviews (<span id="reviewCount"><?= $totalReviews ?></span>)</h2>
            <div class="search-wrap">
                <span>🔍</span>
                <input type="text" id="reviewSearch" placeholder="Search book or client…"
                       oninput="filterReviews(this.value)">
            </div>
        </div>

        <table id="reviewTable">
            <thead>
            <tr>
                <th>#</th>
                <th>Client</th>
                <th>Book</th>
                <th>Rating</th>
                <th>Comment</th>
                <th>Date</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($reviews as $r): ?>
            <tr class="review-row"
                data-search="<?= strtolower(htmlspecialchars($r['nomUser'].' '.$r['prenomUser'].' '.$r['titre'])) ?>">
                <td style="color:#aaa;"><?= $r['idAvis'] ?></td>
                <td><strong><?= htmlspecialchars($r['nomUser'].' '.$r['prenomUser']) ?></strong></td>
                <td>
                    <a href="book-detail.php?id=<?= $r['idLivre'] ?>"
                       style="color:var(--brown-mid); font-weight:600; text-decoration:underline;">
                        <?= htmlspecialchars($r['titre']) ?>
                    </a>
                </td>
                <td>
                    <span class="stars">
                        <?= str_repeat('★', (int)$r['note']) ?>
                    </span>
                    <span class="stars-gray">
                        <?= str_repeat('★', 5 - (int)$r['note']) ?>
                    </span>
                    <span style="font-size:12px; color:#aaa; margin-left:4px;">(<?= $r['note'] ?>/5)</span>
                </td>
                <td>
                    <?php if ($r['commentaire']): ?>
                        <p class="review-comment">"<?= htmlspecialchars($r['commentaire']) ?>"</p>
                    <?php else: ?>
                        <em style="color:#ccc; font-size:12px;">No comment</em>
                    <?php endif; ?>
                </td>
                <td style="font-size:12px; color:#888;">
                    <?= date('d/m/Y', strtotime($r['createdAt'])) ?>
                </td>
                <td>
                    <a class="btn btn-danger"
                       href="?delete=<?= $r['idAvis'] ?>"
                       onclick="return confirm('Delete this review?')">Delete</a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($reviews)): ?>
                <tr><td colspan="7" style="text-align:center;padding:40px;color:#bbb;">No reviews yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</div>

<script>
function filterReviews(q) {
    q = q.toLowerCase();
    let visible = 0;
    document.querySelectorAll('.review-row').forEach(row => {
        const match = !q || row.dataset.search.includes(q);
        row.style.display = match ? '' : 'none';
        if (match) visible++;
    });
    document.getElementById('reviewCount').textContent = visible;
}
document.querySelector(".menuicn").addEventListener("click", () => {
    document.querySelector(".navcontainer").classList.toggle("navclose");
});
</script>
</body>
</html>
