<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commande #<?= $idCom ?> — BookShop Admin</title>
    <link rel="stylesheet" href="../assests/css/admin.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <style>
        /* ── Layout ── */
        .detail-grid {
            display: grid;
            grid-template-columns: 320px 1fr;
            gap: 24px;
            align-items: start;
        }
        @media (max-width: 900px) {
            .detail-grid { grid-template-columns: 1fr; }
        }

        /* ── Cards ── */
        .card {
            background: #fff;
            border-radius: 16px;
            border: 1px solid #e8ecf4;
            overflow: hidden;
            box-shadow: 0 2px 12px rgba(0,0,0,.05);
        }
        .card-header {
            padding: 18px 22px;
            border-bottom: 1px solid #f0f2f8;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .card-header h3 {
            font-size: 15px;
            font-weight: 600;
            color: var(--primary);
            margin: 0;
        }

        /* ── Client Card ── */
        .client-hero {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            padding: 28px 22px 22px;
            border-bottom: 1px solid #f0f2f8;
        }
        .client-avatar {
            width: 80px; height: 80px;
            border-radius: 50%; object-fit: cover;
            border: 3px solid #e8ecf4;
            margin-bottom: 12px;
        }
        .client-avatar-placeholder {
            width: 80px; height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex; align-items: center; justify-content: center;
            font-size: 32px;
            margin-bottom: 12px;
        }
        .client-name  { font-size: 17px; font-weight: 700; color: #1a202c; margin-bottom: 3px; }
        .client-email { font-size: 13px; color: #718096; }
        .client-info-list { padding: 18px 22px; display: flex; flex-direction: column; gap: 14px; }
        .info-row   { display: flex; align-items: center; gap: 12px; }
        .info-icon  { width: 32px; height: 32px; border-radius: 8px; background: #f0f4ff; display: flex; align-items: center; justify-content: center; font-size: 15px; flex-shrink: 0; }
        .info-label { font-size: 11px; color: #a0aec0; text-transform: uppercase; font-weight: 600; letter-spacing: .5px; }
        .info-value { font-size: 14px; color: #2d3748; font-weight: 500; }

        /* ── Order Meta ── */
        .meta-row { display: grid; grid-template-columns: repeat(3, 1fr); }
        .meta-cell { padding: 18px 20px; border-right: 1px solid #f0f2f8; }
        .meta-cell:last-child { border-right: none; }
        .meta-cell label { font-size: 11px; color: #a0aec0; text-transform: uppercase; font-weight: 600; display: block; margin-bottom: 6px; }
        .meta-cell p { font-size: 14px; color: #2d3748; font-weight: 600; margin: 0; }
        .status-pill {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 4px 12px; border-radius: 20px; font-size: 13px; font-weight: 600;
        }
        .status-dot { width: 7px; height: 7px; border-radius: 50%; }

        /* ── Items Table ── */
        .items-table { width: 100%; border-collapse: collapse; }
        .items-table thead tr { background: #f7f9fc; }
        .items-table th {
            font-size: 11px; color: #718096; font-weight: 600;
            text-transform: uppercase; letter-spacing: .5px;
            padding: 12px 16px; text-align: left;
        }
        .items-table td { padding: 14px 16px; border-top: 1px solid #f0f2f8; vertical-align: middle; }
        .book-row   { display: flex; align-items: center; gap: 12px; }
        .book-cover {
            width: 42px; height: 58px; object-fit: cover;
            border-radius: 4px; border: 1px solid #e2e8f0; flex-shrink: 0;
        }
        .book-cover-placeholder {
            width: 42px; height: 58px; border-radius: 4px;
            background: linear-gradient(135deg, #e8ecf4, #c9d3e8);
            display: flex; align-items: center; justify-content: center; font-size: 20px;
            flex-shrink: 0;
        }
        .book-title { font-size: 13px; font-weight: 600; color: #2d3748; line-height: 1.4; max-width: 280px; }
        .amount     { font-weight: 700; color: #2d3748; }
        .subtotal   { color: var(--primary); font-weight: 700; }

        /* ── Total Row ── */
        .total-bar {
            display: flex; justify-content: flex-end; align-items: center;
            padding: 16px 22px; border-top: 2px solid #e8ecf4;
            gap: 12px;
        }
        .total-label  { font-size: 14px; color: #718096; font-weight: 600; }
        .total-amount { font-size: 22px; font-weight: 800; color: var(--primary); }

        /* ── Status Update ── */
        .status-form { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
        .status-form select {
            padding: 9px 14px; border: 1.5px solid #e2e8f0; border-radius: 10px;
            font-size: 13px; font-family: inherit; background: #fff; cursor: pointer;
        }

        .back-btn {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 8px 16px; background: #eef2ff; border-radius: 10px; color: var(--primary);
            font-size: 13px; font-weight: 600; text-decoration: none;
            transition: background .2s;
        }
        .back-btn:hover { background: #dce5ff; }

        .page-header {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 24px; flex-wrap: wrap; gap: 12px;
        }
        .page-title    { font-size: 22px; font-weight: 700; color: var(--primary); margin: 0; }
        .order-id-badge {
            font-size: 13px; color: #718096; background: #f7f9fc;
            border: 1px solid #e2e8f0; padding: 4px 12px; border-radius: 20px;
        }

        /* toast de confirmation */
        .toast { position:fixed; bottom:20px; right:20px; background:#333; color:#fff; padding:10px 18px; border-radius:8px; font-size:13px; z-index:999; display:none; }
    </style>
</head>
<body>
<?php include '../includes/nav.php'; ?>

<div class="main">

    <div class="page-header">
        <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;">
            <a href="orders.php" class="back-btn">← Retour</a>
            <h2 class="page-title">Détails de la commande</h2>
            <span class="order-id-badge">#<?= $idCom ?></span>
        </div>

        <!-- Formulaire de changement de statut — soumis via fetch, pas PHP -->
        <div class="status-form">
            <select id="statusSelect">
                <option value="en attente" <?= $order['status']==='en attente'?'selected':'' ?>>⏳ En attente</option>
                <option value="confirmee"  <?= $order['status']==='confirmee' ?'selected':'' ?>>✅ Confirmée</option>
                <option value="livree"     <?= $order['status']==='livree'    ?'selected':'' ?>>📦 Livrée</option>
                <option value="annulee"    <?= $order['status']==='annulee'   ?'selected':'' ?>>❌ Annulée</option>
            </select>
            <button class="btn btn-warning" id="btnUpdateStatus">Mettre à jour</button>
        </div>
    </div>

    <div class="detail-grid">

        <!-- LEFT: Infos client -->
        <div class="card">
            <div class="client-hero">
                <?php if ($order['userImage']): ?>
                    <img class="client-avatar" src="../uploads/users<?= htmlspecialchars($order['userImage']) ?>" alt="">
                <?php else: ?>
                    <div class="client-avatar-placeholder">👤</div>
                <?php endif; ?>
                <div class="client-name"><?= htmlspecialchars($order['nomUser'].' '.$order['prenomUser']) ?></div>
                <div class="client-email"><?= htmlspecialchars($order['email']) ?></div>
            </div>
            <div class="client-info-list">
                <div class="info-row">
                    <div class="info-icon">📞</div>
                    <div>
                        <div class="info-label">Téléphone</div>
                        <div class="info-value"><?= htmlspecialchars($order['telephone'] ?? '—') ?></div>
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-icon">🏙️</div>
                    <div>
                        <div class="info-label">Ville</div>
                        <div class="info-value"><?= htmlspecialchars($order['ville'] ?? '—') ?></div>
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-icon">📍</div>
                    <div>
                        <div class="info-label">Adresse</div>
                        <div class="info-value"><?= htmlspecialchars($order['adresse'] ?? '—') ?></div>
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-icon">🎂</div>
                    <div>
                        <div class="info-label">Date de naissance</div>
                        <div class="info-value">
                            <?= $order['dateNaiss'] ? date('d/m/Y', strtotime($order['dateNaiss'])) : '—' ?>
                        </div>
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-icon">📅</div>
                    <div>
                        <div class="info-label">Membre depuis</div>
                        <div class="info-value"><?= date('d/m/Y', strtotime($order['createdAt'])) ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- RIGHT: Détails commande -->
        <div style="display:flex;flex-direction:column;gap:20px;">

            <!-- Méta commande : date, statut, nb articles -->
            <div class="card">
                <div class="meta-row">
                    <div class="meta-cell">
                        <label>Date commande</label>
                        <p><?= date('d/m/Y à H:i', strtotime($order['createdAt'])) ?></p>
                    </div>
                    <div class="meta-cell">
                        <label>Statut</label>
                        <!-- Le badge de statut est mis à jour dynamiquement par jQuery -->
                        <p id="statusBadge">
                            <span class="status-pill"
                                  style="background:<?= $currentStatus['bg'] ?>;color:<?= $currentStatus['color'] ?>">
                                <span class="status-dot" style="background:<?= $currentStatus['color'] ?>"></span>
                                <?= $currentStatus['label'] ?>
                            </span>
                        </p>
                    </div>
                    <div class="meta-cell">
                        <label>Articles</label>
                        <p><?= count($items) ?> article<?= count($items)>1?'s':'' ?></p>
                    </div>
                </div>
            </div>

            <!-- Liste des articles -->
            <div class="card">
                <div class="card-header">
                    <span>📚</span>
                    <h3>Articles commandés</h3>
                </div>
                <div style="overflow-x:auto;">
                    <table class="items-table">
                        <thead>
                            <tr>
                                <th>Livre</th>
                                <th>Prix unitaire</th>
                                <th>Quantité</th>
                                <th>Sous-total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                            <tr>
                                <td>
                                    <div class="book-row">
                                        <?php if ($item['bookImage']): ?>
                                            <img class="book-cover"
                                                 src="../uploads/book-covers/<?= htmlspecialchars($item['bookImage']) ?>"
                                                 alt="">
                                        <?php else: ?>
                                            <div class="book-cover-placeholder">📖</div>
                                        <?php endif; ?>
                                        <div class="book-title"><?= htmlspecialchars($item['titre']) ?></div>
                                    </div>
                                </td>
                                <td class="amount"><?= number_format($item['prixUnit'], 2) ?> DT</td>
                                <td>
                                    <span style="background:#f0f4ff;color:var(--primary);padding:4px 10px;border-radius:20px;font-weight:700;font-size:13px;">
                                        ×<?= $item['quantite'] ?>
                                    </span>
                                </td>
                                <td class="subtotal"><?= number_format($item['prixUnit'] * $item['quantite'], 2) ?> DT</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="total-bar">
                    <span class="total-label">Total commande :</span>
                    <span class="total-amount"><?= number_format($order['total'], 2) ?> DT</span>
                </div>
            </div>

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

    // couleurs des badges selon le statut
    var statusStyles = {
        'en attente': { bg: '#fff7e6', color: '#d97706', label: 'En attente' },
        'confirmee':  { bg: '#ecfdf5', color: '#059669', label: 'Confirmée'  },
        'livree':     { bg: '#eff6ff', color: '#2563eb', label: 'Livrée'     },
        'annulee':    { bg: '#fff1f2', color: '#e11d48', label: 'Annulée'    }
    };

    // ── Mettre à jour le statut via fetch POST ────────────────────────────────
    $("#btnUpdateStatus").on("click", function () {
        var newStatus = $("#statusSelect").val();

        $.post("orders.php?action=order_update_status", {
            idCom:  <?= $idCom ?>,
            status: newStatus
        }, function (res) {

            if (res.success) {
                // mettre à jour le badge de statut sans recharger la page
                var s = statusStyles[newStatus] || { bg: '#f0f0f0', color: '#666', label: newStatus };
                $("#statusBadge").html(
                    '<span class="status-pill" style="background:'+s.bg+';color:'+s.color+'">' +
                    '<span class="status-dot" style="background:'+s.color+'"></span>' +
                    s.label +
                    '</span>'
                );
                showToast("✅ Statut mis à jour : " + s.label);
            } else {
                showToast("❌ " + (res.error || "Échec de la mise à jour."));
            }

        }, "json").fail(function () {
            showToast("❌ Erreur serveur.");
        });
    });

});
</script>
</body>
</html>
