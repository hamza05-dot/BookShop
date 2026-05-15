<?php
session_start();

if (isset($_POST['idLivre'], $_POST['quantite'])) {
    $id = $_POST['idLivre'];
    $qte = (int)$_POST['quantite'];

    if (!isset($_SESSION['panier'])) $_SESSION['panier'] = [];

    // On additionne si déjà présent
    if (isset($_SESSION['panier'][$id])) {
        $_SESSION['panier'][$id] += $qte;
    } else {
        $_SESSION['panier'][$id] = $qte;
    }
}

header("Location: panier.php");
exit();