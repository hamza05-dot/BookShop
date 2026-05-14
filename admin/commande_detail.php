<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$idCom = (int)($_GET['id'] ?? 0);
if (!$idCom) { header('Location: orders.php'); exit(); }

$message = '';

// UPDATE STATUS
if (isset($_POST['updateStatus'])) {
    $pdo->prepare("UPDATE commande SET status = ? WHERE idCom = ?")
        ->execute([$_POST['status'], $idCom]);
    $message = "Statut mis à jour.";
}

// GET ORDER + CLIENT INFO
$stmt = $pdo->prepare("
    SELECT c.idCom, c.status, c.total, c.createdAt,
           u.idUser, u.nomUser, u.prenomUser, u.email, u.image AS userImage,
           cl.telephone, cl.adresse, cl.ville, cl.dateNaiss
    FROM commande c
    JOIN utilisateur u ON c.idClient = u.idUser
    LEFT JOIN client cl ON u.idUser = cl.idUser
    WHERE c.idCom = ?
");
$stmt->execute([$idCom]);
$order = $stmt->fetch();

if (!$order) { header('Location: orders.php'); exit(); }

// GET ORDER LINES
$lines = $pdo->prepare("
    SELECT lc.quantite, lc.prixUnit,
           l.idLivre, l.titre, l.image AS bookImage
    FROM ligne_commande lc
    JOIN livre l ON lc.idLivre = l.idLivre
    WHERE lc.idCom = ?
");
$lines->execute([$idCom]);
$items = $lines->fetchAll();

$statusLabels = [
    'en attente' => ['label' => 'En attente',  'color' => '#f59e0b', 'bg' => '#fef3c7'],
    'confirmee'  => ['label' => 'Confirmée',   'color' => '#3b82f6', 'bg' => '#dbeafe'],
    'livree'     => ['label' => 'Livrée',      'color' => '#10b981', 'bg' => '#d1fae5'],
    'annulee'    => ['label' => 'Annulée',     'color' => '#ef4444', 'bg' => '#fee2e2'],
];
$currentStatus = $statusLabels[$order['status']] ?? ['label' => $order['status'], 'color' => '#6b7280', 'bg' => '#f3f4f6'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commande #<?= $idCom ?> — BookShop Admin</title>
    <link rel="stylesheet" href="../assests/css/admin.css">
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
        .card-body { padding: 22px; }

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
        .client-name { font-size: 17px; font-weight: 700; color: #1a202c; margin-bottom: 3px; }
        .client-email { font-size: 13px; color: #718096; }
        .client-info-list { padding: 18px 22px; display: flex; flex-direction: column; gap: 14px; }
        .info-row { display: flex; align-items: center; gap: 12px; }
        .info-icon { width: 32px; height: 32px; border-radius: 8px; background: #f0f4ff; display: flex; align-items: center; justify-content: center; font-size: 15px; flex-shrink: 0; }
        .info-label { font-size: 11px; color: #a0aec0; text-transform: uppercase; font-weight: 600; letter-spacing: .5px; }
        .info-value { font-size: 14px; color: #2d3748; font-weight: 500; }

        /* ── Order Meta ── */
        .meta-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0;
        }
        .meta-cell {
            padding: 18px 20px;
            border-right: 1px solid #f0f2f8;
        }
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
        .book-row { display: flex; align-items: center; gap: 12px; }
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
        .amount { font-weight: 700; color: #2d3748; }
        .subtotal { color: var(--primary); font-weight: 700; }

        /* ── Total Row ── */
        .total-bar {
            display: flex; justify-content: flex-end; align-items: center;
            padding: 16px 22px; border-top: 2px solid #e8ecf4;
            gap: 12px;
        }
        .total-label { font-size: 14px; color: #718096; font-weight: 600; }
        .total-amount { font-size: 22px; font-weight: 800; color: var(--primary); }

        /* ── Status Update ── */
        .status-form {
            display: flex; gap: 10px; align-items: center; flex-wrap: wrap;
        }
        .status-form select {
            padding: 9px 14px; border: 1.5px solid #e2e8f0; border-radius: 10px;
            font-size: 14px; font-family: "Poppins", sans-serif;
            background: #f7f9fc; color: #2d3748; outline: none;
            cursor: pointer; transition: border-color .2s;
        }
        .status-form select:focus { border-color: var(--secondary); }

        /* ── Back link ── */
        .back-btn {
            display: inline-flex; align-items: center; gap: 7px;
            padding: 8px 16px; border-radius: 10px;
            background: #f0f4ff; color: var(--primary);
            font-size: 13px; font-weight: 600; text-decoration: none;
            transition: background .2s;
        }
        .back-btn:hover { background: #dce5ff; }

        /* ── Page header ── */
        .page-header {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 24px; flex-wrap: wrap; gap: 12px;
        }
        .page-title { font-size: 22px; font-weight: 700; color: var(--primary); margin: 0; }
        .order-id-badge {
            font-size: 13px; color: #718096; background: #f7f9fc;
            border: 1px solid #e2e8f0; padding: 4px 12px; border-radius: 20px;
        }
    </style>
</head>
<body>
<?php include '../includes/nav.php'; ?>

<div class="main">

    <?php if ($message): ?>
        <div class="message-box success">✓ <?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div class="page-header">
        <div style="display:flex; align-items:center; gap:14px; flex-wrap:wrap;">
            <a href="orders.php" class="back-btn">← Retour</a>
            <h2 class="page-title">Détails de la commande</h2>
            <span class="order-id-badge">#<?= $idCom ?></span>
        </div>
        <!-- Status update form in header -->
        <form method="POST" class="status-form">
            <select name="status">
                <option value="en attente" <?= $order['status']==='en attente'?'selected':'' ?>>⏳ En attente</option>
                <option value="confirmee"  <?= $order['status']==='confirmee' ?'selected':'' ?>>✅ Confirmée</option>
                <option value="livree"     <?= $order['status']==='livree'    ?'selected':'' ?>>📦 Livrée</option>
                <option value="annulee"    <?= $order['status']==='annulee'   ?'selected':'' ?>>❌ Annulée</option>
            </select>
            <button class="btn btn-warning" name="updateStatus">Mettre à jour</button>
        </form>
    </div>

    <div class="detail-grid">

        <!-- LEFT: Client Card -->
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

        <!-- RIGHT: Order Details -->
        <div style="display:flex; flex-direction:column; gap:20px;">

            <!-- Order Meta -->
            <div class="card">
                <div class="meta-row">
                    <div class="meta-cell">
                        <label>Date commande</label>
                        <p><?= date('d/m/Y à H:i', strtotime($order['createdAt'])) ?></p>
                    </div>
                    <div class="meta-cell">
                        <label>Statut</label>
                        <p>
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

            <!-- Items -->
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

<script>
    document.querySelector(".menuicn").addEventListener("click", () => {
        document.querySelector(".navcontainer").classList.toggle("navclose");
    });
</script>
</body>
</html>