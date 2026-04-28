<?php
session_start();
require_once 'includes/db.php';

 $error = '';
 $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nomUser    = trim($_POST['nomUser']);
    $prenomUser = trim($_POST['prenomUser']);
    $email      = trim($_POST['email']);
    $password   = trim($_POST['password']);
    $telephone  = trim($_POST['telephone']);
    $adresse    = trim($_POST['adresse']);
    $ville      = trim($_POST['ville']);
    $dateNaiss  = $_POST['dateNaiss'];

    $stmt = $pdo->prepare("SELECT * FROM utilisateur WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $error = "Cet email est déjà utilisé.";
    } else {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("
            INSERT INTO utilisateur (nomUser, prenomUser, email, password, createdAt)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$nomUser, $prenomUser, $email, $hashedPassword]);

        $idUser = $pdo->lastInsertId();

        $stmt2 = $pdo->prepare("
            INSERT INTO client (idUser, telephone, adresse, ville, dateNaiss)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt2->execute([$idUser, $telephone, $adresse, $ville, $dateNaiss]);

        $success = "Compte créé avec succès ! Vous pouvez vous connecter.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription — BookShop</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=Source+Sans+3:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/auth.css">
</head>
<body>

    <div class="auth-page">
        <div class="auth-card" style="position:relative;">

            <div class="auth-header">
                <div class="auth-logo">
                    <span class="auth-logo-icon">📚</span> BookShop
                </div>
                <h1 class="auth-title">Créer un compte</h1>
                <p class="auth-subtitle">Rejoignez notre communauté de lecteurs</p>
            </div>

            <div class="auth-body">

                <?php if ($error): ?>
                    <div class="auth-message auth-message--error">⚠ <?= $error ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="auth-message auth-message--success">✓ <?= $success ?></div>
                <?php endif; ?>

                <form class="auth-form" method="POST">

                    <div class="auth-row">
                        <div class="auth-field">
                            <label class="auth-label" for="nomUser">Nom</label>
                            <input class="auth-input" type="text" id="nomUser" name="nomUser" placeholder="Dupont" required>
                        </div>
                        <div class="auth-field">
                            <label class="auth-label" for="prenomUser">Prénom</label>
                            <input class="auth-input" type="text" id="prenomUser" name="prenomUser" placeholder="Jean" required>
                        </div>
                    </div>

                    <div class="auth-field">
                        <label class="auth-label" for="email">Email</label>
                        <input class="auth-input" type="email" id="email" name="email" placeholder="jean@exemple.com" required>
                    </div>

                    <div class="auth-field">
                        <label class="auth-label" for="password">Mot de passe</label>
                        <input class="auth-input" type="password" id="password" name="password" placeholder="Minimum 6 caractères" required>
                    </div>

                    <div class="auth-divider"><span>Informations complémentaires</span></div>

                    <div class="auth-row">
                        <div class="auth-field">
                            <label class="auth-label" for="telephone">Téléphone</label>
                            <input class="auth-input" type="text" id="telephone" name="telephone" placeholder="06 12 34 56 78">
                        </div>
                        <div class="auth-field">
                            <label class="auth-label" for="dateNaiss">Date de naissance</label>
                            <input class="auth-input" type="date" id="dateNaiss" name="dateNaiss">
                        </div>
                    </div>

                    <div class="auth-field">
                        <label class="auth-label" for="adresse">Adresse</label>
                        <input class="auth-input" type="text" id="adresse" name="adresse" placeholder="12 rue des Livres">
                    </div>

                    <div class="auth-field">
                        <label class="auth-label" for="ville">Ville</label>
                        <input class="auth-input" type="text" id="ville" name="ville" placeholder="Paris">
                    </div>

                    <button class="auth-submit" type="submit">Créer mon compte</button>

                </form>
            </div>

            <div class="auth-footer">
                Déjà un compte ? <a href="Login.php">Se connecter</a>
            </div>

            <span class="auth-deco">📖</span>
        </div>
    </div>

</body>
</html>