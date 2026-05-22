<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — BookShop Admin</title>
    <link rel="stylesheet" href="../assests/css/admin.css">

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- Chart.js pour les graphiques -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

    <style>
        /* spinner de chargement */
        .spinner {
            display: inline-block;
            width: 18px; height: 18px;
            border: 3px solid #ddd;
            border-top-color: var(--secondary, #5c6bc0);
            border-radius: 50%;
            animation: spin 0.7s linear infinite;
            vertical-align: middle;
            margin-right: 6px;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* grille pour les 3 graphiques */
        .charts-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 24px;
        }
        /* le premier graphique (ligne) prend toute la largeur */
        .charts-grid .chart-card:first-child {
            grid-column: 1 / -1;
        }
        .chart-card {
            background: var(--bg-card, #fff);
            border: 1px solid var(--border, #e5e7eb);
            border-radius: 14px;
            padding: 22px 24px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        .chart-card h3 {
            font-size: 15px;
            font-weight: 600;
            margin: 0 0 16px 0;
        }
        /* hauteur fixe pour éviter que le canvas grossisse indéfiniment */
        .chart-wrap {
            position: relative;
            height: 220px;
        }
        .loading-msg {
            text-align: center;
            padding: 30px;
            color: #aaa;
            font-size: 14px;
        }
    </style>
</head>
<body>
<?php include '../includes/nav.php'; ?>

<div class="main">

    <!-- Cartes statistiques : injectées via fetch() -->
    <div class="box-container" id="statsContainer">
        <div class="loading-msg"><span class="spinner"></span> Loading stats…</div>
    </div>

    <!-- Zone des 3 graphiques, cachée jusqu'au chargement des données -->
    <div class="charts-grid" id="chartsGrid" style="display:none;">

        <!-- Graphique 1 : commandes par mois (courbe) -->
        <div class="chart-card">
            <h3>📈 Orders per Month (last 6 months)</h3>
            <div class="chart-wrap">
                <canvas id="chartOrders"></canvas>
            </div>
        </div>

        <!-- Graphique 2 : statuts des commandes (donut) -->
        <div class="chart-card">
            <h3>🥧 Orders by Status</h3>
            <div class="chart-wrap">
                <canvas id="chartStatus"></canvas>
            </div>
        </div>

        <!-- Graphique 3 : top 5 catégories (barres horizontales) -->
        <div class="chart-card">
            <h3>🏷️ Top 5 Book Categories</h3>
            <div class="chart-wrap">
                <canvas id="chartCats"></canvas>
            </div>
        </div>

    </div>

    <!-- Tableau des dernières commandes -->
    <div class="report-container" style="margin-top:24px;">
        <div class="report-header">
            <h2>Recent Orders</h2>
            <a class="btn btn-primary" href="orders.php">View All</a>
        </div>
        <div id="recentOrdersWrap">
            <div class="loading-msg"><span class="spinner"></span> Loading orders…</div>
        </div>
    </div>

</div><!-- /.main -->

<script>
$(document).ready(function () {

    // toggle sidebar
    $(".menuicn").on("click", function () {
        $(".navcontainer").toggleClass("navclose");
    });

    // ── 1. Cartes stats ───────────────────────────────────────────────────────
    $.getJSON("dashboard.php?action=dashboard_stats", function (data) {

        var html = `
            <a href="books.php" class="box-link">
                <div class="box box-1">
                    <div class="text"><h2>${data.livres}</h2><p>Books</p></div>
                    <span class="box-icon">📚</span>
                </div>
            </a>
            <a href="users.php" class="box-link">
                <div class="box box-2">
                    <div class="text"><h2>${data.clients}</h2><p>Clients</p></div>
                    <span class="box-icon">👥</span>
                </div>
            </a>
            <a href="orders.php" class="box-link">
                <div class="box box-3">
                    <div class="text"><h2>${data.commandes}</h2><p>Orders</p></div>
                    <span class="box-icon">📦</span>
                </div>
            </a>
            <a href="orders.php?status=en+attente" class="box-link">
                <div class="box box-4">
                    <div class="text"><h2>${data.enAttente}</h2><p>Pending</p></div>
                    <span class="box-icon">⏳</span>
                </div>
            </a>
        `;
        $("#statsContainer").html(html);

    }).fail(function () {
        $("#statsContainer").html('<p style="color:red;padding:10px;">Failed to load stats.</p>');
    });

    // ── 2. Données des graphiques ─────────────────────────────────────────────
    $.getJSON("dashboard.php?action=dashboard_charts", function (data) {

        // on affiche la grille seulement une fois les données prêtes
        $("#chartsGrid").show();

        // palette de couleurs commune aux 3 graphiques
        var colors = [
            'rgba(92,107,192,0.75)',
            'rgba(66,165,105,0.75)',
            'rgba(255,167,38,0.75)',
            'rgba(239,83,80,0.75)',
            'rgba(38,166,154,0.75)'
        ];

        // ─ Graphique 1 : commandes par mois (courbe remplie) ──────────────────
        new Chart(document.getElementById('chartOrders'), {
            type: 'line',
            data: {
                labels: data.ordersByMonth.map(function(r){ return r.mois; }),
                datasets: [{
                    label: 'Orders',
                    data: data.ordersByMonth.map(function(r){ return r.total; }),
                    borderColor: 'rgba(92,107,192,1)',
                    backgroundColor: 'rgba(92,107,192,0.1)',
                    tension: 0.35,
                    fill: true,
                    pointBackgroundColor: 'rgba(92,107,192,1)',
                    pointRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1 } }
                }
            }
        });

        // ─ Graphique 2 : statuts des commandes (donut) ───────────────────────
        var statusLabels = {
            'en attente': 'Pending',
            'confirmee':  'Confirmed',
            'livree':     'Delivered',
            'annulee':    'Cancelled'
        };

        new Chart(document.getElementById('chartStatus'), {
            type: 'doughnut',
            data: {
                labels: data.orderStatus.map(function(r){
                    return statusLabels[r.status] || r.status;
                }),
                datasets: [{
                    data: data.orderStatus.map(function(r){ return r.total; }),
                    backgroundColor: colors,
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } }
            }
        });

        // ─ Graphique 3 : top catégories (barres horizontales) ────────────────
        new Chart(document.getElementById('chartCats'), {
            type: 'bar',
            data: {
                labels: data.topCategories.map(function(r){ return r.nomCat; }),
                datasets: [{
                    label: 'Books',
                    data: data.topCategories.map(function(r){ return r.total; }),
                    backgroundColor: colors,
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: { legend: { display: false } },
                scales: {
                    x: { beginAtZero: true, ticks: { stepSize: 5 } }
                }
            }
        });

    }).fail(function () {
        $("#chartsGrid").html('<p style="color:red;padding:10px;">Failed to load chart data.</p>');
    });

    // ── 3. Dernières commandes ────────────────────────────────────────────────
    $.getJSON("dashboard.php?action=dashboard_orders", function (rows) {

        if (!rows.length) {
            $("#recentOrdersWrap").html('<p style="padding:20px;text-align:center;color:#aaa;">No orders yet.</p>');
            return;
        }

        var html = '<table><thead><tr><th>#</th><th>Client</th><th>Total</th><th>Status</th><th>Date</th></tr></thead><tbody>';

        $.each(rows, function (i, cmd) {
            // formatage de la date côté JS
            var d    = new Date(cmd.createdAt);
            var date = ('0'+d.getDate()).slice(-2)+'/'+('0'+(d.getMonth()+1)).slice(-2)+'/'+d.getFullYear();

            // badge de statut avec la même classe CSS que le PHP
            var badgeClass = cmd.status.replace(' ', '-');

            html += '<tr>';
            html += '<td><strong>' + cmd.idCom + '</strong></td>';
            html += '<td>' + $('<div>').text(cmd.nomUser+' '+cmd.prenomUser).html() + '</td>';
            html += '<td><strong>' + parseFloat(cmd.total).toFixed(2) + ' DT</strong></td>';
            html += '<td><span class="badge ' + badgeClass + '">' + cmd.status + '</span></td>';
            html += '<td>' + date + '</td>';
            html += '</tr>';
        });

        html += '</tbody></table>';
        $("#recentOrdersWrap").html(html);

    }).fail(function () {
        $("#recentOrdersWrap").html('<p style="color:red;padding:10px;">Failed to load orders.</p>');
    });

});
</script>
</body>
</html>
