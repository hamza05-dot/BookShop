<?php
session_start();
require_once '../includes/db.php';
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') { header('Location: ../login.php'); exit(); }

$activePage = 'users';
$message = '';

// ADD ADMIN
if (isset($_POST['addAdmin'])) {
    $idUser = (int)$_POST['idUser'];
    $check = $pdo->prepare("SELECT * FROM admin WHERE idUser = ?");
    $check->execute([$idUser]);
    if (!$check->fetch()) {
        $pdo->prepare("INSERT INTO admin (idUser) VALUES (?)")->execute([$idUser]);
        $message = "User promoted to admin.";
    } else {
        $message = "This user is already an admin.";
    }
}

// DELETE USER
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $pdo->prepare("DELETE FROM utilisateur WHERE idUser = ?")->execute([$id]);
    $message = "User deleted.";
}

// REMOVE ADMIN
if (isset($_GET['removeAdmin'])) {
    $id = (int)$_GET['removeAdmin'];
    $pdo->prepare("DELETE FROM admin WHERE idUser = ?")->execute([$id]);
    $message = "Admin role removed.";
}

// GET ADMINS
$admins = $pdo->query("
    SELECT u.* FROM utilisateur u
    INNER JOIN admin a ON u.idUser = a.idUser
    ORDER BY u.nomUser ASC
")->fetchAll();

// GET CLIENTS
$clients = $pdo->query("
    SELECT u.*, c.telephone, c.adresse, c.ville, c.dateNaiss
    FROM utilisateur u
    LEFT JOIN client c ON u.idUser = c.idUser
    WHERE u.idUser NOT IN (SELECT idUser FROM admin)
    ORDER BY u.createdAt DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users — BookShop Admin</title>
    <link rel="stylesheet" href="../assests/css/admin.css">
    <!-- jQuery CDN -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <style>
        .section-title { font-size:18px; font-weight:600; color:var(--primary); margin:30px 0 15px; }
        .search-wrap { display:flex; align-items:center; background:#f5f7fa; border:1.5px solid #e0e4ed; border-radius:25px; padding:6px 14px; gap:8px; }
        .search-wrap:focus-within { border-color:var(--secondary); background:#fff; }
        #clientSearch { border:none; background:transparent; outline:none; font-size:13px; font-family:"Poppins",sans-serif; width:220px; }
        .user-avatar { width:38px; height:38px; border-radius:50%; object-fit:cover; }
        .avatar-placeholder { width:38px; height:38px; border-radius:50%; background:#dde; display:inline-flex; align-items:center; justify-content:center; font-size:18px; }
        .clickable-row { cursor:pointer; }
        .clickable-row:hover td { background:#f0f6ff !important; }
        .modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.45); z-index:200; align-items:center; justify-content:center; }
        .modal-overlay.open { display:flex; }
        .modal { background:white; border-radius:16px; padding:30px; max-width:480px; width:90%; position:relative; }
        .modal-close { position:absolute; top:14px; right:18px; background:none; border:none; font-size:20px; cursor:pointer; color:#888; }
        .modal-avatar { width:90px; height:90px; border-radius:50%; object-fit:cover; border:3px solid #eee; }
        .modal-avatar-placeholder { width:90px; height:90px; border-radius:50%; background:#dde; display:flex; align-items:center; justify-content:center; font-size:40px; }
        .modal-header { display:flex; align-items:center; gap:20px; margin-bottom:20px; }
        .modal-name { font-size:18px; font-weight:600; }
        .modal-email { font-size:13px; color:#888; }
        .info-grid { display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-top:15px; }
        .info-item label { font-size:11px; color:#aaa; text-transform:uppercase; font-weight:600; }
        .info-item p { font-size:14px; color:#333; margin-top:2px; }
        .add-admin-form { display:flex; gap:10px; align-items:center; flex-wrap:wrap; background:#f8f9ff; padding:15px 20px; border-radius:10px; margin-bottom:20px; border:1px solid #d0d9f5; }
        .add-admin-form label { font-size:14px; font-weight:500; }
        .add-admin-form select { padding:8px 12px; border:1px solid #ddd; border-radius:8px; font-size:14px; font-family:"Poppins",sans-serif; }
    </style>
</head>
<body>
<?php include '../includes/nav.php'; ?>

<div class="main">
    <?php if ($message): ?>
        <div class="message-box success">✓ <?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <p class="section-title">👑 Admins</p>
    <div class="report-container">
        <table>
            <thead><tr><th>Avatar</th><th>Name</th><th>Email</th><th>Action</th></tr></thead>
            <tbody>
            <?php foreach ($admins as $a): ?>
            <tr>
                <td><?= $a['image'] ? '<img class="user-avatar" src="../uploads/users/'.$a['image'].'">' : '<span class="avatar-placeholder">👤</span>' ?></td>
                <td><strong><?= htmlspecialchars($a['nomUser'].' '.$a['prenomUser']) ?></strong></td>
                <td><?= htmlspecialchars($a['email']) ?></td>
                <td>
                    <?php if ($a['idUser'] != $_SESSION['idUser']): ?>
                        <a class="btn btn-warning" href="?removeAdmin=<?= $a['idUser'] ?>" onclick="return confirm('Remove admin role?')">Remove Admin</a>
                    <?php else: ?>
                        <span style="color:#aaa; font-size:13px;">You</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <p class="section-title">➕ Promote Client to Admin</p>
    <form method="POST" class="add-admin-form">
        <label>Select a client:</label>
        <select name="idUser" required>
            <option value="">— Choose a client —</option>
            <?php foreach ($clients as $c): ?>
                <option value="<?= $c['idUser'] ?>"><?= htmlspecialchars($c['nomUser'].' '.$c['prenomUser']) ?> — <?= htmlspecialchars($c['email']) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn-primary" name="addAdmin">Make Admin</button>
    </form>

    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
        <p class="section-title" style="margin:0;">👥 Clients (<span id="clientCount"><?= count($clients) ?></span>)</p>
        <div class="search-wrap">
            <span>🔍</span>
            <!-- oninput retiré → géré par jQuery -->
            <input type="text" id="clientSearch" placeholder="Search name or email…">
        </div>
    </div>
    <div class="report-container">
        <table id="clientTable">
            <thead><tr><th>Avatar</th><th>Name</th><th>Email</th><th>City</th><th>Joined</th><th>Action</th></tr></thead>
            <tbody>
            <?php foreach ($clients as $c): ?>
            <tr class="client-row clickable-row"
                data-search="<?= strtolower($c['nomUser'].' '.$c['prenomUser'].' '.$c['email']) ?>"
                data-client="<?= htmlspecialchars(json_encode($c)) ?>">
                <td><?= $c['image'] ? '<img class="user-avatar" src="../uploads/users/'.htmlspecialchars($c['image']).'">' : '<span class="avatar-placeholder">👤</span>' ?></td>
                <td><strong><?= htmlspecialchars($c['nomUser'].' '.$c['prenomUser']) ?></strong></td>
                <td><?= htmlspecialchars($c['email']) ?></td>
                <td><?= htmlspecialchars($c['ville'] ?? '—') ?></td>
                <td style="font-size:12px;"><?= date('d/m/Y', strtotime($c['createdAt'])) ?></td>
                <td class="stop-prop">
                    <a class="btn btn-danger" href="?delete=<?= $c['idUser'] ?>" onclick="return confirm('Delete this user?')">Delete</a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</div>

<div class="modal-overlay" id="clientModal">
    <div class="modal">
        <!-- bouton fermeture -->
        <button class="modal-close" id="btnCloseModal">✕</button>
        <div class="modal-header">
            <div id="modalAvatar"></div>
            <div>
                <div class="modal-name" id="modalName"></div>
                <div class="modal-email" id="modalEmail"></div>
            </div>
        </div>
        <div class="info-grid">
            <div class="info-item"><label>Phone</label><p id="modalPhone"></p></div>
            <div class="info-item"><label>City</label><p id="modalCity"></p></div>
            <div class="info-item"><label>Address</label><p id="modalAddress"></p></div>
            <div class="info-item"><label>Date of Birth</label><p id="modalDob"></p></div>
            <div class="info-item"><label>Member since</label><p id="modalJoined"></p></div>
        </div>
    </div>
</div>

<script>
$(document).ready(function () {

    // ── Sidebar toggle ──
    $(".menuicn").on("click", function () {
        $(".navcontainer").toggleClass("navclose");
    });

    // ── Filtre recherche client ──
    $("#clientSearch").on("input", function () {
        const q = $(this).val().toLowerCase();
        let visible = 0;

        $(".client-row").each(function () {
            // vérifie si la ligne correspond à la recherche
            const match = !q || $(this).data("search").includes(q);
            $(this).toggle(match);
            if (match) visible++;
        });

        // met à jour le compteur
        $("#clientCount").text(visible);
    });

    // ── Ouvrir modal au clic sur une ligne ──
    $(".client-row").on("click", function () {
        const c = $(this).data("client");

        // rempli les champs du modal
        $("#modalName").text(c.nomUser + ' ' + c.prenomUser);
        $("#modalEmail").text(c.email);
        $("#modalPhone").text(c.telephone || '—');
        $("#modalCity").text(c.ville || '—');
        $("#modalAddress").text(c.adresse || '—');
        $("#modalDob").text(c.dateNaiss || '—');
        $("#modalJoined").text(c.createdAt ? c.createdAt.substring(0, 10) : '—');

        // avatar : image ou emoji
        const avatar = c.image
            ? `<img class="modal-avatar" src="../uploads/users/${c.image}">`
            : `<div class="modal-avatar-placeholder">👤</div>`;
        $("#modalAvatar").html(avatar);

        $("#clientModal").addClass("open");
    });

    // ── Empêcher le clic sur "Delete" d'ouvrir le modal ──
    $(".stop-prop").on("click", function (e) {
        e.stopPropagation();
    });

    // ── Fermer modal (bouton ✕) ──
    $("#btnCloseModal").on("click", function () {
        $("#clientModal").removeClass("open");
    });

    // ── Fermer modal (clic en dehors) ──
    $("#clientModal").on("click", function (e) {
        if ($(e.target).is("#clientModal")) {
            $(this).removeClass("open");
        }
    });

});
</script>
</body></html>