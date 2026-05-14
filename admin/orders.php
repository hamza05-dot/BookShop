<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$message = '';

// Handle status update
if (isset($_POST['updateStatus'])) {
    $pdo->prepare("UPDATE commande SET status = ? WHERE idCom = ?")
        ->execute([$_POST['status'], $_POST['idCom']]);
    $message = "Statut mis à jour.";
}

// --- Filters ---
$filterStatus = $_GET['status'] ?? '';
$filterSearch = trim($_GET['search'] ?? '');

$where  = [];
$params = [];

if ($filterStatus !== '') {
    $where[]  = "c.status = ?";
    $params[] = $filterStatus;
}

if ($filterSearch !== '') {
    $where[]  = "(c.idCom LIKE ? OR u.nomUser LIKE ? OR u.prenomUser LIKE ? OR CONCAT(u.nomUser,' ',u.prenomUser) LIKE ?)";
    $like     = '%' . $filterSearch . '%';
    $params   = array_merge($params, [$like, $like, $like, $like]);
}

$sql = "
    SELECT c.idCom, c.status, c.total, c.createdAt, u.nomUser, u.prenomUser
    FROM commande c
    JOIN utilisateur u ON c.idClient = u.idUser
" . ($where ? 'WHERE ' . implode(' AND ', $where) : '') . "
    ORDER BY c.createdAt DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$commandes = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commandes — BookShop Admin</title>
    <link rel="stylesheet" href="../assests/css/admin.css">
    <style>
        /* Filter bar */
        .filter-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
            margin-bottom: 20px;
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 14px 18px;
            box-shadow: var(--shadow);
        }
        .filter-bar input[type="text"] {
            flex: 1;
            min-width: 200px;
            padding: 9px 14px;
            border: 1.5px solid var(--border);
            border-radius: 8px;
            font-size: 14px;
            font-family: "Poppins", sans-serif;
            background: rgba(245,240,232,0.7);
            color: var(--brown-dark);
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .filter-bar input[type="text"]:focus {
            border-color: var(--brown-light);
            box-shadow: 0 0 0 3px rgba(139,111,71,0.12);
            background: var(--white);
        }
        .filter-bar select {
            padding: 9px 14px;
            border: 1.5px solid var(--border);
            border-radius: 8px;
            font-size: 14px;
            font-family: "Poppins", sans-serif;
            background: rgba(245,240,232,0.7);
            color: var(--brown-dark);
            outline: none;
            cursor: pointer;
            transition: border-color 0.2s;
        }
        .filter-bar select:focus {
            border-color: var(--brown-light);
            box-shadow: 0 0 0 3px rgba(139,111,71,0.12);
        }
        .filter-bar .btn-reset {
            background: transparent;
            border: 1.5px solid var(--border);
            color: var(--brown-mid);
            padding: 8px 14px;
            border-radius: 8px;
            font-size: 13px;
            font-family: "Poppins", sans-serif;
            cursor: pointer;
            text-decoration: none;
            transition: background 0.2s, color 0.2s;
        }
        .filter-bar .btn-reset:hover {
            background: var(--bg-page);
            color: var(--brown-dark);
        }
        .results-count {
            font-size: 13px;
            color: var(--brown-light);
            margin-bottom: 10px;
        }
        /* Active filter pill */
        .active-filter {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(201,168,76,0.15);
            border: 1px solid rgba(201,168,76,0.35);
            color: var(--brown-dark);
            border-radius: 20px;
            padding: 3px 10px;
            font-size: 12px;
            font-weight: 600;
        }
    </style>
</head>
<body>
<?php include '../includes/nav.php'; ?>

<div class="main">

    <?php if ($message): ?>
        <div class="message-box success">✓ <?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <!-- Filter Bar -->
    <form method="GET" action="orders.php">
        <div class="filter-bar">
            <input
                type="text"
                name="search"
                placeholder="🔍  Search by client name or order ID…"
                value="<?= htmlspecialchars($filterSearch) ?>"
            >
            <select name="status">
                <option value="">All statuses</option>
                <option value="en attente" <?= $filterStatus === 'en attente' ? 'selected' : '' ?>>⏳ En attente</option>
                <option value="confirmee"  <?= $filterStatus === 'confirmee'  ? 'selected' : '' ?>>✅ Confirmée</option>
                <option value="livree"     <?= $filterStatus === 'livree'     ? 'selected' : '' ?>>📦 Livrée</option>
                <option value="annulee"    <?= $filterStatus === 'annulee'    ? 'selected' : '' ?>>❌ Annulée</option>
            </select>
            <button class="btn btn-primary" type="submit">Filter</button>
            <?php if ($filterStatus !== '' || $filterSearch !== ''): ?>
                <a href="orders.php" class="btn-reset">✕ Reset</a>
            <?php endif; ?>
        </div>
    </form>

    <!-- Active filters display -->
    <?php if ($filterStatus !== '' || $filterSearch !== ''): ?>
    <p class="results-count">
        <?= count($commandes) ?> result<?= count($commandes) !== 1 ? 's' : '' ?> found
        <?php if ($filterStatus !== ''): ?>
            &nbsp;<span class="active-filter"><?= htmlspecialchars($filterStatus) ?></span>
        <?php endif; ?>
        <?php if ($filterSearch !== ''): ?>
            &nbsp;<span class="active-filter">"<?= htmlspecialchars($filterSearch) ?>"</span>
        <?php endif; ?>
    </p>
    <?php endif; ?>

    <div class="report-container">
        <div class="report-header">
            <h2>Liste des commandes</h2>
        </div>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Client</th>
                    <th>Total</th>
                    <th>Date</th>
                    <th>Statut</th>
                    <th>Changer</th>
                    <th>Détails</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($commandes)): ?>
                <tr>
                    <td colspan="7" style="text-align:center; padding:30px; color:var(--brown-light);">
                        No orders found for the selected filters.
                    </td>
                </tr>
            <?php else: ?>
            <?php foreach ($commandes as $cmd): ?>
            <tr>
                <td><strong><?= $cmd['idCom'] ?></strong></td>
                <td><?= htmlspecialchars($cmd['nomUser'] . ' ' . $cmd['prenomUser']) ?></td>
                <td><?= number_format($cmd['total'], 2) ?> DT</td>
                <td><?= date('d/m/Y', strtotime($cmd['createdAt'])) ?></td>
                <td>
                    <span class="badge <?= str_replace(' ', '-', $cmd['status']) ?>">
                        <?= htmlspecialchars($cmd['status']) ?>
                    </span>
                </td>
                <td>
                    <form method="POST" style="display:flex; gap:5px;">
                        <input type="hidden" name="idCom" value="<?= $cmd['idCom'] ?>">
                        <!-- Preserve filters after POST -->
                        <input type="hidden" name="_redirect_status" value="<?= htmlspecialchars($filterStatus) ?>">
                        <input type="hidden" name="_redirect_search" value="<?= htmlspecialchars($filterSearch) ?>">
                        <select name="status">
                            <option value="en attente" <?= $cmd['status']==='en attente'?'selected':'' ?>>En attente</option>
                            <option value="confirmee"  <?= $cmd['status']==='confirmee' ?'selected':'' ?>>Confirmée</option>
                            <option value="livree"     <?= $cmd['status']==='livree'    ?'selected':'' ?>>Livrée</option>
                            <option value="annulee"    <?= $cmd['status']==='annulee'   ?'selected':'' ?>>Annulée</option>
                        </select>
                        <button class="btn btn-warning" name="updateStatus">OK</button>
                    </form>
                </td>
                <td>
                    <a class="btn btn-primary" href="commande_detail.php?id=<?= $cmd['idCom'] ?>">
                        👁 Voir
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.querySelector(".menuicn").addEventListener("click", () => {
    document.querySelector(".navcontainer").classList.toggle("navclose");
});
</script>
</body>
</html>