<?php
session_start();
require_once 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_SESSION['panier'])) {

    $idClient = $_SESSION['idUser'];

    // ✅ Calculer le total
    $total = 0;
    foreach ($_SESSION['panier'] as $idLivre => $qte) {
        $stmtLivre = $pdo->prepare("SELECT prix FROM livre WHERE idLivre = ?");
        $stmtLivre->execute([$idLivre]);
        $livre = $stmtLivre->fetch(PDO::FETCH_ASSOC);
        if ($livre) $total += $livre['prix'] * $qte;
    }

    // ✅ 1. Insérer dans commande
    $stmt = $pdo->prepare("
        INSERT INTO commande (idClient, total, status, createdAt)
        VALUES (?, ?, 'en attente', NOW())
    ");
    $stmt->execute([$idClient, $total]);

    $idCom = $pdo->lastInsertId();

    // ✅ 2. Insérer dans ligne_commande
    foreach ($_SESSION['panier'] as $idLivre => $qte) {

        $stmtLivre = $pdo->prepare("SELECT prix FROM livre WHERE idLivre = ?");
        $stmtLivre->execute([$idLivre]);
        $livre = $stmtLivre->fetch(PDO::FETCH_ASSOC);

        if ($livre) {
            $stmtLigne = $pdo->prepare("
                INSERT INTO ligne_commande (idCom, idLivre, quantite, prixUnit)
                VALUES (?, ?, ?, ?)
            ");
            $stmtLigne->execute([$idCom, $idLivre, $qte, $livre['prix']]);
        }
    }

    // ✅ 3. Vider le panier
    unset($_SESSION['panier']);

    header("Location: confirmation.php");
    exit();

} else {
    header("Location: panier.php");
    exit();
}
?>