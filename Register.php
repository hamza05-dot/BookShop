<?php
session_start();
require_once 'includes/db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. Récupérer les données du formulaire
    $nomUser    = trim($_POST['nomUser']);
    $prenomUser = trim($_POST['prenomUser']);
    $email      = trim($_POST['email']);
    $password   = trim($_POST['password']);
    $telephone  = trim($_POST['telephone']);
    $adresse    = trim($_POST['adresse']);
    $ville      = trim($_POST['ville']);
    $dateNaiss  = $_POST['dateNaiss'];

    // 2. Vérifier si l'email existe déjà
    $stmt = $pdo->prepare("SELECT * FROM utilisateur WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $error = "Cet email est déjà utilisé.";
    } else {

        // 3. Hasher le mot de passe (sécurité)
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // 4. Insérer dans la table utilisateur
        $stmt = $pdo->prepare("
            INSERT INTO utilisateur (nomUser, prenomUser, email, password, createdAt)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$nomUser, $prenomUser, $email, $hashedPassword]);

        // 5. Récupérer l'id du nouvel utilisateur
        $idUser = $pdo->lastInsertId();

        // 6. Insérer dans la table client
        $stmt2 = $pdo->prepare("
            INSERT INTO client (idUser, telephone, adresse, ville)
            VALUES (?, ?, ?, ?)
        ");
        $stmt2->execute([$idUser, $telephone, $adresse, $ville]);

        $success = "Compte créé avec succès ! Vous pouvez vous connecter.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Inscription — BookShop</title>
</head>
<body>

    <h2>Créer un compte</h2>

    <!-- Messages -->
    <?php if ($error): ?>
        <p style="color:red;"><?= $error ?></p>
    <?php endif; ?>
    <?php if ($success): ?>
        <p style="color:green;"><?= $success ?></p>
    <?php endif; ?>

    <form method="POST">

        <label>Nom :</label><br>
        <input type="text" name="nomUser" required><br><br>

        <label>Prénom :</label><br>
        <input type="text" name="prenomUser" required><br><br>

        <label>Email :</label><br>
        <input type="email" name="email" required><br><br>

        <label>Mot de passe :</label><br>
        <input type="password" name="password" required><br><br>

        <label>Téléphone :</label><br>
        <input type="text" name="telephone"><br><br>

        <label>Adresse :</label><br>
        <input type="text" name="adresse"><br><br>

        <label>Ville :</label><br>
        <input type="text" name="ville"><br><br>

        <label>Date de naissance :</label><br>
        <input type="date" name="dateNaiss"><br><br>

        <button type="submit">S'inscrire</button>
    </form>

    <p>Déjà un compte ? <a href="Login.php">Se connecter</a></p>

</body>
</html>
