<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders — BookShop Admin</title>
    <link rel="stylesheet" href="../assests/css/admin.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <style>
        .spinner { display:inline-block; width:18px; height:18px; border:3px solid #ddd; border-top-color:var(--secondary,#5c6bc0); border-radius:50%; animation:spin .7s linear infinite; vertical-align:middle; margin-right:6px; }
        @keyframes spin { to { transform:rotate(360deg); } }
        .filter-bar { display:flex; flex-wrap:wrap; gap:10px; align-items:center; margin-bottom:20px; background:var(--bg-card); border:1px solid var(--border); border-radius:12px; padding:14px 18px; box-shadow:var(--shadow); }
        .filter-bar input { flex:1; min-width:200px; padding:9px 14px; border:1.5px solid var(--border); border-radius:8px; font-size:14px; font-family:inherit; background:rgba(245,240,232,0.7); outline:none; }
        .filter-bar input:focus { border-color:var(--brown-light); background:#fff; }
        .filter-bar select { padding:9px 14px; border:1.5px solid var(--border); border-radius:8px; font-size:14px; font-family:inherit; background:rgba(245,240,232,0.7); outline:none; cursor:pointer; }
        .results-count { font-size:13px; color:var(--brown-light); margin-bottom:10px; }
        /* select de changement de statut dans le tableau */
        .status-select { padding:5px 8px; border-radius:6px; border:1px solid #ddd; font-size:13px; font-family:inherit; cursor:pointer; }
        .save-status { padding:5px 10px; font-size:12px; }
        .toast { position:fixed; bottom:20px; right:20px; background:#333; color:#fff; padding:10px 18px; border-radius:8px; font-size:13px; z-index:999; display:none; }
    </style>
</head>
<body>
<?php include '../includes/nav.php'; ?>
<div class="main">
    <?php if ($message): ?><div class="message-box success">✓ <?= htmlspecialchars($message) ?></div><?php endif; ?>

    <!-- Barre de filtres — les changements déclenchent un re-fetch -->
    <div class="filter-bar">
        <input type="text" id="filterSearch" placeholder="🔍 Search by client name or order ID…">
        <select id="filterStatus">
            <option value="">All statuses</option>
            <option value="en attente">⏳ En attente</option>
            <option value="confirmee">✅ Confirmée</option>
            <option value="livree">📦 Livrée</option>
            <option value="annulee">❌ Annulée</option>
        </select>
        <button class="btn btn-primary" id="btnFilter">Filter</button>
        <a href="orders.php" class="btn" style="background:transparent;border:1.5px solid var(--border);color:var(--brown-mid);">✕ Reset</a>
    </div>

    <p class="results-count" id="resultsCount"></p>

    <div class="report-container">
        <div class="report-header"><h2>📦 Orders</h2></div>
        <div id="ordersTableWrap">
            <p style="padding:30px;text-align:center;color:#aaa;"><span class="spinner"></span> Loading orders…</p>
        </div>
    </div>
</div>

<!-- Message de confirmation après changement de statut -->
<div class="toast" id="toast"></div>

<script>
$(document).ready(function () {

    $(".menuicn").on("click", function () {
        $(".navcontainer").toggleClass("navclose");
    });

    // ── Charger les commandes (avec filtres éventuels) ────────────────────────
    function loadOrders() {
        var search = $("#filterSearch").val().trim();
        var status = $("#filterStatus").val();

        var url = "orders.php?action=orders";
        if (status) url += "&status=" + encodeURIComponent(status);
        if (search) url += "&search=" + encodeURIComponent(search);

        $("#ordersTableWrap").html('<p style="padding:30px;text-align:center;color:#aaa;"><span class="spinner"></span> Loading…</p>');

        $.getJSON(url, function (rows) {

            // compteur de résultats
            var txt = rows.length + " order" + (rows.length !== 1 ? "s" : "") + " found";
            if (status) txt += ' — <strong>' + $('<div>').text(status).html() + '</strong>';
            if (search) txt += ' — <strong>"' + $('<div>').text(search).html() + '"</strong>';
            $("#resultsCount").html(rows.length ? txt : "No orders found.");

            if (!rows.length) {
                $("#ordersTableWrap").html('<p style="text-align:center;padding:40px;color:#bbb;">No orders for these filters.</p>');
                return;
            }

            var html = '<table><thead><tr><th>#</th><th>Client</th><th>Total</th><th>Date</th><th>Status</th><th>Change</th><th>Details</th></tr></thead><tbody>';

            $.each(rows, function (i, cmd) {

                // formatage date
                var d    = new Date(cmd.createdAt);
                var date = ('0'+d.getDate()).slice(-2)+'/'+('0'+(d.getMonth()+1)).slice(-2)+'/'+d.getFullYear();

                var badgeClass = cmd.status.replace(' ', '-');

                // select de changement de statut
                var sel = '<select class="status-select" data-id="'+cmd.idCom+'">';
                $.each(['en attente','confirmee','livree','annulee'], function(j, s){
                    sel += '<option value="'+s+'"'+(cmd.status===s?' selected':'')+'>'+s+'</option>';
                });
                sel += '</select>';

                html += '<tr>';
                html += '<td><strong>'+cmd.idCom+'</strong></td>';
                html += '<td>'+$('<div>').text(cmd.nomUser+' '+cmd.prenomUser).html()+'</td>';
                html += '<td><strong>'+parseFloat(cmd.total).toFixed(2)+' DT</strong></td>';
                html += '<td style="font-size:12px;">'+date+'</td>';
                html += '<td><span class="badge '+badgeClass+'">'+cmd.status+'</span></td>';
                html += '<td>'+sel+' <button class="btn btn-warning save-status" data-id="'+cmd.idCom+'">OK</button></td>';
                html += '<td><a class="btn btn-primary" href="commande_detail.php?id='+cmd.idCom+'">👁 View</a></td>';
                html += '</tr>';
            });

            html += '</tbody></table>';
            $("#ordersTableWrap").html(html);

            // ── Bouton OK : envoi du nouveau statut via fetch ─────────────────
            $(".save-status").on("click", function () {
                var id     = $(this).data("id");
                var status = $(".status-select[data-id='" + id + "']").val();

                // on envoie un POST à l'API
                $.post("orders.php?action=order_update_status", { idCom: id, status: status }, function (res) {
                    if (res.success) {
                        showToast("✅ Status updated!");
                        // on recharge le tableau pour refléter le changement
                        loadOrders();
                    }
                }, "json").fail(function () {
                    showToast("❌ Failed to update status.");
                });
            });

        }).fail(function () {
            $("#ordersTableWrap").html('<p style="color:red;padding:20px;">Failed to load orders.</p>');
        });
    }

    // ── Afficher un toast de confirmation ─────────────────────────────────────
    function showToast(msg) {
        $("#toast").text(msg).fadeIn(200).delay(2000).fadeOut(400);
    }

    // Charger au démarrage + au clic sur Filter
    loadOrders();
    $("#btnFilter").on("click", loadOrders);

    // Charger aussi si on appuie sur Entrée dans la recherche
    $("#filterSearch").on("keyup", function (e) {
        if (e.key === "Enter") loadOrders();
    });

});
</script>
</body>
</html>
