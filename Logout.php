<?php
session_start();

// Supprimer toutes les données de la session
session_destroy();

// Rediriger vers la page de connexion
header('Location: login.php');
exit();
?>
