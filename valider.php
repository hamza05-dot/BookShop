<?php
session_start();
require_once 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_SESSION['panier'])) {

    $idClient = $_SESSION['idUser'];
    $total    = $_POST['total']; // ✅ envoyé depuis panier.php

    // 1. Insérer dans commande
    $stmt = $pdo->prepare("
        INSERT INTO commande (idClient, total, status, createdAt)
        VALUES (?, ?, 'en attente', NOW())
    ");
    $stmt->execute([$idClient, $total]);

    $idCom = $pdo->lastInsertId();

    // 2. Insérer chaque ligne
    foreach ($_SESSION['panier'] as $idLivre => $qte) {

        $stmtLivre = $pdo->prepare("SELECT prix FROM livre WHERE idLivre = ?");
        $stmtLivre->execute([$idLivre]);
        $livre = $stmtLivre->fetch();

        if ($livre) {
            $pdo->prepare("
                INSERT INTO ligne_commande (idCom, idLivre, quantite, prixUnit)
                VALUES (?, ?, ?, ?)
            ")->execute([$idCom, $idLivre, $qte, $livre['prix']]);
        }
    }

    // 3. Vider panier
    unset($_SESSION['panier']);

    header("Location: confirmation.php");
    exit();

} else {
    header("Location: panier.php");
    exit();
}
?>