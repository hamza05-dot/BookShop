<?php
session_start();
require_once 'includes/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. Récupérer ce que l'utilisateur a tapé
    $email    = trim($_POST['email']);
    $password = trim($_POST['password']);

    // 2. Chercher l'utilisateur dans la table utilisateur
    $stmt = $pdo->prepare("SELECT * FROM utilisateur WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // 3. Vérifier si le mot de passe est correct
    if ($user && password_verify($password, $user['password'])) {

        // 4. Créer la session
        $_SESSION['idUser']   = $user['idUser'];
        $_SESSION['nomUser']  = $user['nomUser'];
        $_SESSION['email']    = $user['email'];

        // 5. Vérifier si c'est un admin
        $stmt2 = $pdo->prepare("SELECT * FROM admin WHERE idUser = ?");
        $stmt2->execute([$user['idUser']]);
        $isAdmin = $stmt2->fetch();

        if ($isAdmin) {
            $_SESSION['role'] = 'admin';
            header('Location: admin/dashboard.php'); // → aller au panel admin
        } else {
            $_SESSION['role'] = 'client';
            header('Location: index.php'); // → aller à l'accueil
        }
        exit();

    } else {
        $error = "Email ou mot de passe incorrect.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Connexion — BookShop</title>
</head>
<body>

    <h2>Connexion</h2>

    <!-- Afficher l'erreur si elle existe -->
    <?php if ($error): ?>
        <p style="color:red;"><?= $error ?></p>
    <?php endif; ?>

    <form method="POST">
        <label>Email :</label><br>
        <input type="email" name="email" required><br><br>

        <label>Mot de passe :</label><br>
        <input type="password" name="password" required><br><br>

        <button type="submit">Se connecter</button>
    </form>

    <p>Pas encore de compte ? <a href="register.php">S'inscrire</a></p>

</body>
</html>
