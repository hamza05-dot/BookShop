<?php
session_start();

if (!isset($_SESSION['idUser'])) {
    header("Location: login.php");
    exit();
}

if (isset($_POST['idLivre'], $_POST['quantite'])) {
    $id = $_POST['idLivre'];
    $qte = (int)$_POST['quantite'];
    if (!isset($_SESSION['panier'])) $_SESSION['panier'] = [];
    if (isset($_SESSION['panier'][$id])) {
        $_SESSION['panier'][$id] += $qte;
    } else {
        $_SESSION['panier'][$id] = $qte;
    }
}

// ✅ Go back to where the user came from
$referer = $_SERVER['HTTP_REFERER'] ?? 'index.php';
header("Location: " . $referer);
exit();
?>