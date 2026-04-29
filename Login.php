<?php
session_start();
require_once 'includes/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']);
    $password = trim($_POST['password']);

    // ✅ CONDITION 1 : Champs vides
    if (empty($email) || empty($password)) {
        $error = "Veuillez remplir tous les champs.";

    // ✅ CONDITION 2 : Format email valide
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format email invalide.";

    } else {
        $stmt = $pdo->prepare("SELECT * FROM utilisateur WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // ✅ CONDITION 3 : Email ou mot de passe incorrect
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['idUser']  = $user['idUser'];
            $_SESSION['nomUser'] = $user['nomUser'];
            $_SESSION['email']   = $user['email'];

            $stmt2 = $pdo->prepare("SELECT * FROM admin WHERE idUser = ?");
            $stmt2->execute([$user['idUser']]);
            $isAdmin = $stmt2->fetch();

            if ($isAdmin) {
                $_SESSION['role'] = 'admin';
                header('Location: admin/dashboard.php');
            } else {
                $_SESSION['role'] = 'client';
                header('Location: index.php');
            }
            exit();

        } else {
            $error = "Email ou mot de passe incorrect.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion — BookShop</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=Source+Sans+3:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/auth.css">
</head>
<body>

    <div class="auth-page">
        <div class="auth-card">

            <div class="auth-header">
                <div class="auth-logo">
                    <span class="auth-logo-icon">📚</span> BookShop
                </div>
                <h1 class="auth-title">Connexion</h1>
                <p class="auth-subtitle">Bon retour parmi nos lecteurs</p>
            </div>

            <div class="auth-body">

                <?php if ($error): ?>
                    <div class="auth-message auth-message--error">⚠ <?= $error ?></div>
                <?php endif; ?>

                <form class="auth-form" method="POST">

                    <div class="auth-field">
                        <label class="auth-label" for="email">Email</label>
                        <input class="auth-input" type="email" id="email" name="email" placeholder="jean@exemple.com" required>
                    </div>

                    <div class="auth-field">
                        <label class="auth-label" for="password">Mot de passe</label>
                        <input class="auth-input" type="password" id="password" name="password" placeholder="Votre mot de passe" required>
                    </div>

                    <button class="auth-submit" type="submit">Se connecter</button>

                </form>
            </div>

            <div class="auth-footer">
                Pas encore de compte ? <a href="register.php">S'inscrire</a>
            </div>

        </div>
    </div>

</body>
</html>