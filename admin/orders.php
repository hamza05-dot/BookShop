<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$message = '';

if (isset($_POST['updateStatus'])) {
    $pdo->prepare("UPDATE commande SET status = ? WHERE idCom = ?")
        ->execute([$_POST['status'], $_POST['idCom']]);
    $message = "Statut mis à jour.";
}

$commandes = $pdo->query("
    SELECT c.idCom, c.status, c.total, c.createdAt, u.nomUser, u.prenomUser
    FROM commande c
    JOIN utilisateur u ON c.idClient = u.idUser
    ORDER BY c.createdAt DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commandes — BookShop Admin</title>
    <link rel="stylesheet" href="../assests/css/admin.css">
    <style>
        .badge { padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .en-attente { background: #fef3c7; color: #d97706; }
        .confirmee  { background: #dbeafe; color: #2563eb; }
        .livree     { background: #d1fae5; color: #059669; }
        .annulee    { background: #fee2e2; color: #dc2626; }
    </style>
</head>
<body>
<?php include '../includes/nav.php'; ?>

<div class="main">
    <?php if ($message): ?>
        <div class="message-box success">✓ <?= $message ?></div>
    <?php endif; ?>

    <div class="report-container">
        <div class="report-header">
            <h2>Liste des commandes</h2>
        </div>
        <table>
            <tr>
                <th>#</th>
                <th>Client</th>
                <th>Total</th>
                <th>Date</th>
                <th>Statut</th>
                <th>Changer</th>
                <th>Détails</th>
            </tr>
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