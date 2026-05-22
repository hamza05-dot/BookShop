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
        /* formulaire de promotion */
        .promote-form { display:flex; gap:10px; align-items:center; flex-wrap:wrap; background:#f8f9ff; padding:15px 20px; border-radius:10px; margin-bottom:20px; border:1px solid #d0d9f5; }
        .promote-form label { font-size:14px; font-weight:500; }
        .promote-form select { padding:8px 12px; border:1px solid #ddd; border-radius:8px; font-size:14px; font-family:inherit; }
        .clickable-row { cursor:pointer; }
        .clickable-row:hover td { background:#f0f6ff !important; }
        .toast { position:fixed; bottom:20px; right:20px; background:#333; color:#fff; padding:10px 18px; border-radius:8px; font-size:13px; z-index:999; display:none; }
    </style>
</head>
<body>
<?php include '../includes/nav.php'; ?>
<div class="main">

    <p class="section-title">👑 Admins (<span id="adminCount">…</span>)</p>
    <div class="report-container" id="adminsTableWrap">
        <p style="padding:20px;text-align:center;color:#aaa;"><span class="spinner"></span> Loading admins…</p>
    </div>

    <p class="section-title">➕ Promote Client to Admin</p>
    <div class="promote-form" id="promoteFormWrap">
        <p style="color:#aaa;font-size:13px;"><span class="spinner"></span> Loading clients…</p>
    </div>

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
                <div class="modal-name"  id="modalName"></div>
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

<div class="toast" id="toast"></div>

<script>
$(document).ready(function () {

    $(".menuicn").on("click", function () {
        $(".navcontainer").toggleClass("navclose");
    });

    function showToast(msg) {
        $("#toast").text(msg).fadeIn(200).delay(2200).fadeOut(400);
    }

    // id de la session courante pour bloquer "se supprimer / se retirer admin"
    var sessionUserId = <?= (int)$_SESSION['idUser'] ?>;

    // ── Charger admins et clients ─────────────────────────────────────────────
    function loadUsers() {
        $.getJSON("users.php?action=users", function (data) {

            // ─ Tableau des admins ─────────────────────────────────────────────
            $("#adminCount").text(data.admins.length);
            var adminHtml = '<table><thead><tr><th>Avatar</th><th>Name</th><th>Email</th><th>Action</th></tr></thead><tbody>';

            $.each(data.admins, function (i, a) {
                var avatar = a.image
                    ? '<img class="user-avatar" src="../uploads/users/'+a.image+'">'
                    : '<span class="avatar-placeholder">👤</span>';

                // bouton "Remove Admin" via fetch ; désactivé pour soi-même
                var action = (parseInt(a.idUser) !== sessionUserId)
                    ? '<button class="btn btn-warning btn-remove-admin" data-id="'+a.idUser+'" data-name="'+$('<div>').text(a.nomUser+' '+a.prenomUser).html()+'">Remove Admin</button>'
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

            // ─ Formulaire de promotion ─────────────────────────────────────────
            var sel = '<select id="promoteSelect"><option value="">— Choose a client —</option>';
            $.each(data.clients, function (i, c) {
                sel += '<option value="'+c.idUser+'">'+$('<div>').text(c.nomUser+' '+c.prenomUser+' — '+c.email).html()+'</option>';
            });
            sel += '</select>';
            $("#promoteFormWrap").html('<label>Select a client:</label> '+sel+' <button class="btn btn-primary" id="btnPromote">Make Admin</button>');

            // ─ Tableau des clients ─────────────────────────────────────────────
            $("#clientCount").text(data.clients.length);
            var clientHtml = '<table><thead><tr><th>Avatar</th><th>Name</th><th>Email</th><th>City</th><th>Joined</th><th>Action</th></tr></thead><tbody>';

            $.each(data.clients, function (i, c) {
                var avatar = c.image
                    ? '<img class="user-avatar" src="../uploads/users/'+c.image+'">'
                    : '<span class="avatar-placeholder">👤</span>';

                var joined = c.createdAt ? c.createdAt.substring(0, 10) : '—';
                var search = (c.nomUser+' '+c.prenomUser+' '+c.email).toLowerCase();
                var clientData = JSON.stringify(c).replace(/'/g, "&#39;");

                clientHtml += '<tr class="client-row clickable-row" data-search="'+search+'" data-client=\''+clientData+'\'>';
                clientHtml += '<td>'+avatar+'</td>';
                clientHtml += '<td><strong>'+$('<div>').text(c.nomUser+' '+c.prenomUser).html()+'</strong></td>';
                clientHtml += '<td>'+$('<div>').text(c.email).html()+'</td>';
                clientHtml += '<td>'+$('<div>').text(c.ville||'—').html()+'</td>';
                clientHtml += '<td style="font-size:12px;">'+joined+'</td>';
                // bouton delete via fetch ; stop-prop pour ne pas ouvrir le modal
                clientHtml += '<td class="stop-prop"><button class="btn btn-danger btn-delete-user" data-id="'+c.idUser+'" data-name="'+$('<div>').text(c.nomUser+' '+c.prenomUser).html()+'">Delete</button></td>';
                clientHtml += '</tr>';
            });

            clientHtml += '</tbody></table>';
            $("#clientsTableWrap").html(clientHtml);

            // filtre live sur les clients
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

        }).fail(function () {
            $("#adminsTableWrap, #clientsTableWrap").html('<p style="color:red;padding:20px;">Failed to load users.</p>');
        });
    }

    loadUsers();

    // ── Promouvoir un client en admin ─────────────────────────────────────────
    $(document).on("click", "#btnPromote", function () {
        var idUser = $("#promoteSelect").val();
        if (!idUser) { showToast("⚠️ Please select a client first."); return; }

        $.post("users.php?action=promote_admin", { idUser: idUser }, function (res) {
            if (res.success) {
                showToast("✅ User promoted to admin.");
                loadUsers(); // recharge les deux tableaux
            } else {
                showToast("❌ " + (res.error || "Failed to promote."));
            }
        }, "json").fail(function () {
            showToast("❌ Server error.");
        });
    });

    // ── Retirer le rôle admin ─────────────────────────────────────────────────
    $(document).on("click", ".btn-remove-admin", function () {
        var id   = $(this).data("id");
        var name = $(this).data("name");

        if (!confirm('Remove admin role from "' + name + '"?')) return;

        $.post("users.php?action=remove_admin", { id: id }, function (res) {
            if (res.success) {
                showToast("✅ Admin role removed.");
                loadUsers();
            } else {
                showToast("❌ " + (res.error || "Failed."));
            }
        }, "json").fail(function () {
            showToast("❌ Server error.");
        });
    });

    // ── Supprimer un client ───────────────────────────────────────────────────
    $(document).on("click", ".btn-delete-user", function () {
        var id   = $(this).data("id");
        var name = $(this).data("name");

        if (!confirm('Delete user "' + name + '"? This will also delete their orders.')) return;

        var $row = $(this).closest("tr");

        $.post("users.php?action=delete_user", { id: id }, function (res) {
            if (res.success) {
                $row.fadeOut(300, function () {
                    $(this).remove();
                    var count = parseInt($("#clientCount").text()) - 1;
                    $("#clientCount").text(count);
                    // recharger aussi la liste de promotion
                    loadUsers();
                });
                showToast("✅ User deleted.");
            } else {
                showToast("❌ " + (res.error || "Failed to delete."));
            }
        }, "json").fail(function () {
            showToast("❌ Server error.");
        });
    });

    // ── Clic sur une ligne client → modal ─────────────────────────────────────
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

    // empêche le clic sur le bouton Delete d'ouvrir le modal
    $(document).on("click", ".stop-prop", function (e) {
        e.stopPropagation();
    });

    // fermer le modal
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
