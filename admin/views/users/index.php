<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users — BookShop Admin</title>
    <link rel="stylesheet" href="../assests/css/admin.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <style>
        .spinner { display:inline-block; width:18px; height:18px; border:3px solid #ddd; border-top-color:var(--secondary,#5c6bc0); border-radius:50%; animation:spin .7s linear infinite; vertical-align:middle; margin-right:6px; }
        @keyframes spin { to { transform:rotate(360deg); } }
        .section-title { font-size:18px; font-weight:600; color:var(--primary); margin:30px 0 15px; }
        .search-wrap { display:flex; align-items:center; background:#f5f7fa; border:1.5px solid #e0e4ed; border-radius:25px; padding:6px 14px; gap:8px; }
        .search-wrap:focus-within { border-color:var(--secondary); background:#fff; }
        #clientSearch { border:none; background:transparent; outline:none; font-size:13px; font-family:inherit; width:220px; }
        .user-avatar { width:38px; height:38px; border-radius:50%; object-fit:cover; }
        .avatar-placeholder { width:38px; height:38px; border-radius:50%; background:#dde; display:inline-flex; align-items:center; justify-content:center; font-size:18px; }
        /* modal de détail client */
        .modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.45); z-index:200; align-items:center; justify-content:center; }
        .modal-overlay.open { display:flex; }
        .modal { background:#fff; border-radius:16px; padding:30px; max-width:480px; width:90%; position:relative; }
        .modal-close { position:absolute; top:14px; right:18px; background:none; border:none; font-size:20px; cursor:pointer; color:#888; }
        .modal-avatar { width:90px; height:90px; border-radius:50%; object-fit:cover; border:3px solid #eee; }
        .modal-avatar-ph { width:90px; height:90px; border-radius:50%; background:#dde; display:flex; align-items:center; justify-content:center; font-size:40px; }
        .modal-header { display:flex; align-items:center; gap:20px; margin-bottom:20px; }
        .modal-name { font-size:18px; font-weight:600; }
        .modal-email { font-size:13px; color:#888; }
        .info-grid { display:grid; grid-template-columns:1fr 1fr; gap:10px; }
        .info-item label { font-size:11px; color:#aaa; text-transform:uppercase; font-weight:600; }
        .info-item p { font-size:14px; color:#333; margin-top:2px; }
        .add-admin-form { display:flex; gap:10px; align-items:center; flex-wrap:wrap; background:#f8f9ff; padding:15px 20px; border-radius:10px; margin-bottom:20px; border:1px solid #d0d9f5; }
        .add-admin-form label { font-size:14px; font-weight:500; }
        .add-admin-form select { padding:8px 12px; border:1px solid #ddd; border-radius:8px; font-size:14px; font-family:inherit; }
        .clickable-row { cursor:pointer; }
        .clickable-row:hover td { background:#f0f6ff !important; }
    </style>
</head>
<body>
<?php include '../includes/nav.php'; ?>
<div class="main">
    <?php if ($message): ?>
        <div class="message-box success">✓ <?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <!-- Section admins : gardée en PHP car les actions (remove/add) sont des POST -->
    <p class="section-title" id="adminSectionTitle">👑 Admins <span style="font-size:14px;color:#aaa;">(<span id="adminCount">…</span>)</span></p>
    <div class="report-container" id="adminsTableWrap">
        <p style="padding:20px;text-align:center;color:#aaa;"><span class="spinner"></span> Loading admins…</p>
    </div>

    <p class="section-title">➕ Promote Client to Admin</p>
    <div class="add-admin-form" id="promoteFormWrap">
        <p style="color:#aaa;font-size:13px;"><span class="spinner"></span> Loading clients…</p>
    </div>

    <!-- Section clients -->
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:15px;">
        <p class="section-title" style="margin:0;">👥 Clients (<span id="clientCount">…</span>)</p>
        <div class="search-wrap">
            <span>🔍</span>
            <input type="text" id="clientSearch" placeholder="Search name or email…">
        </div>
    </div>
    <div class="report-container" id="clientsTableWrap">
        <p style="padding:20px;text-align:center;color:#aaa;"><span class="spinner"></span> Loading clients…</p>
    </div>
</div>

<!-- Modal de détail d'un client -->
<div class="modal-overlay" id="clientModal">
    <div class="modal">
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

    $(".menuicn").on("click", function () {
        $(".navcontainer").toggleClass("navclose");
    });

    // On garde sessionUserId pour masquer le bouton "Remove" sur soi-même
    var sessionUserId = <?= (int)$_SESSION['idUser'] ?>;

    // ── Charger admins et clients depuis l'API ────────────────────────────────
    $.getJSON("../admin/api.php?action=users", function (data) {

        // ─ Tableau des admins ─────────────────────────────────────────────────
        $("#adminCount").text(data.admins.length);

        var adminHtml = '<table><thead><tr><th>Avatar</th><th>Name</th><th>Email</th><th>Action</th></tr></thead><tbody>';
        $.each(data.admins, function (i, a) {
            var avatar = a.image
                ? '<img class="user-avatar" src="../uploads/users/'+a.image+'">'
                : '<span class="avatar-placeholder">👤</span>';

            var action = (parseInt(a.idUser) !== sessionUserId)
                ? '<a class="btn btn-warning" href="?removeAdmin='+a.idUser+'" onclick="return confirm(\'Remove admin role?\')">Remove Admin</a>'
                : '<span style="color:#aaa;font-size:13px;">You</span>';

            adminHtml += '<tr>';
            adminHtml += '<td>'+avatar+'</td>';
            adminHtml += '<td><strong>'+$('<div>').text(a.nomUser+' '+a.prenomUser).html()+'</strong></td>';
            adminHtml += '<td>'+$('<div>').text(a.email).html()+'</td>';
            adminHtml += '<td>'+action+'</td>';
            adminHtml += '</tr>';
        });
        adminHtml += '</tbody></table>';
        $("#adminsTableWrap").html(adminHtml);

        // ─ Formulaire de promotion ────────────────────────────────────────────
        var formHtml = '<form method="POST">';
        formHtml += '<label>Select a client:</label>';
        formHtml += '<select name="idUser" required><option value="">— Choose a client —</option>';
        $.each(data.clients, function (i, c) {
            formHtml += '<option value="'+c.idUser+'">'+$('<div>').text(c.nomUser+' '+c.prenomUser+' — '+c.email).html()+'</option>';
        });
        formHtml += '</select>';
        formHtml += '<button class="btn btn-primary" name="addAdmin">Make Admin</button></form>';
        $("#promoteFormWrap").html(formHtml);

        // ─ Tableau des clients ────────────────────────────────────────────────
        $("#clientCount").text(data.clients.length);

        var clientHtml = '<table id="clientTable"><thead><tr><th>Avatar</th><th>Name</th><th>Email</th><th>City</th><th>Joined</th><th>Action</th></tr></thead><tbody>';

        $.each(data.clients, function (i, c) {
            var avatar = c.image
                ? '<img class="user-avatar" src="../uploads/users/'+c.image+'">'
                : '<span class="avatar-placeholder">👤</span>';

            // date d'inscription
            var joined = c.createdAt ? c.createdAt.substring(0, 10) : '—';

            // attribut data-search pour le filtre live
            var search = (c.nomUser+' '+c.prenomUser+' '+c.email).toLowerCase();

            // on stocke les infos dans data-client pour le modal
            var clientData = JSON.stringify(c).replace(/'/g, "&#39;");

            clientHtml += '<tr class="client-row clickable-row" data-search="'+search+'" data-client=\''+clientData+'\'>';
            clientHtml += '<td>'+avatar+'</td>';
            clientHtml += '<td><strong>'+$('<div>').text(c.nomUser+' '+c.prenomUser).html()+'</strong></td>';
            clientHtml += '<td>'+$('<div>').text(c.email).html()+'</td>';
            clientHtml += '<td>'+$('<div>').text(c.ville||'—').html()+'</td>';
            clientHtml += '<td style="font-size:12px;">'+joined+'</td>';
            clientHtml += '<td class="stop-prop"><a class="btn btn-danger" href="?delete='+c.idUser+'" onclick="return confirm(\'Delete this user?\')">Delete</a></td>';
            clientHtml += '</tr>';
        });

        clientHtml += '</tbody></table>';
        $("#clientsTableWrap").html(clientHtml);

        // ── Filtre de recherche en temps réel sur les clients ─────────────────
        $("#clientSearch").on("input", function () {
            var q = $(this).val().toLowerCase();
            var visible = 0;

            $(".client-row").each(function () {
                var match = !q || $(this).data("search").includes(q);
                $(this).toggle(match);
                if (match) visible++;
            });

            $("#clientCount").text(visible);
        });

        // ── Clic sur une ligne → ouvre le modal de détail ─────────────────────
        $(document).on("click", ".client-row", function () {
            var c = $(this).data("client");

            $("#modalName").text(c.nomUser + ' ' + c.prenomUser);
            $("#modalEmail").text(c.email);
            $("#modalPhone").text(c.telephone || '—');
            $("#modalCity").text(c.ville || '—');
            $("#modalAddress").text(c.adresse || '—');
            $("#modalDob").text(c.dateNaiss || '—');
            $("#modalJoined").text(c.createdAt ? c.createdAt.substring(0, 10) : '—');

            var avatar = c.image
                ? '<img class="modal-avatar" src="../uploads/users/'+c.image+'">'
                : '<div class="modal-avatar-ph">👤</div>';
            $("#modalAvatar").html(avatar);

            $("#clientModal").addClass("open");
        });

        // empêche le clic sur "Delete" d'ouvrir le modal
        $(document).on("click", ".stop-prop", function (e) {
            e.stopPropagation();
        });

    }).fail(function () {
        $("#adminsTableWrap, #clientsTableWrap").html('<p style="color:red;padding:20px;">Failed to load users.</p>');
    });

    // ── Fermer le modal ───────────────────────────────────────────────────────
    $("#btnCloseModal").on("click", function () {
        $("#clientModal").removeClass("open");
    });

    $("#clientModal").on("click", function (e) {
        if ($(e.target).is("#clientModal")) $(this).removeClass("open");
    });

});
</script>
</body>
</html>
