<?php
session_start();

if (!isset($_SESSION['idUser'])) {
    header("Location: login.php");
    exit();
}

if (isset($_POST['idLivre'])) {
    $id  = $_POST['idLivre'];
    $qte = (int)($_POST['quantite'] ?? 1);

    if (!isset($_SESSION['panier'])) $_SESSION['panier'] = [];

    if (isset($_SESSION['panier'][$id])) {
        $_SESSION['panier'][$id] += $qte;
    } else {
        $_SESSION['panier'][$id] = $qte;
    }
}

// ✅ Redirect back with scroll position preserved
$referer = $_SERVER['HTTP_REFERER'] ?? 'index.php';
$scroll  = isset($_POST['scroll']) ? (int)$_POST['scroll'] : 0;

$separator = strpos($referer, '?') !== false ? '&' : '?';
header("Location: " . $referer . $separator . "_scroll=" . $scroll);
exit();
?>