<?php
session_start();
$conn = mysqli_connect("localhost", "root", "", "bookdb");

if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_SESSION['panier'])) {
    
    // Récupération des données du formulaire
    $adresse = mysqli_real_escape_string($conn, $_POST['adresse']);
    $telephone = mysqli_real_escape_string($conn, $_POST['tel']);
    $methode = mysqli_real_escape_string($conn, $_POST['p']);
    
    // Génération d'une référence de commande unique
    $order_ref = "BK-" . strtoupper(substr(md5(time()), 0, 8));
    $_SESSION['derniere_commande'] = $order_ref;

    /* Note : Tu peux insérer ici tes requêtes SQL INSERT INTO 
       pour sauvegarder la commande en base de données.
    */

    // Redirection vers la page de suivi dynamique
    header("Location: confirmation.php");
    exit();
} else {
    header("Location: panier.php");
    exit();
}
?>