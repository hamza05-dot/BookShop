<?php
session_start();
require_once 'includes/db.php';

// ✅ Noms corrects selon login.php
$isLoggedIn = isset($_SESSION['idUser']);
$username   = $_SESSION['nomUser'] ?? "Invité";

// Récupération livres
$query = $pdo->query("SELECT * FROM livre");
$books = $query->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>BookShop - Accueil</title>
    <link rel="stylesheet" href="assests/css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>

<nav class="navbar">
    <div class="nav-container">

        <div class="nav-left">
            <a href="index.php" class="logo">📚 BookShop</a>
        </div>

        <div class="nav-center">
            <form action="recherche.php" method="GET" class="search-form">
                <input type="text" name="q" placeholder="Entrez le nom du livre..." required>
                <button type="submit"><i class="fas fa-search"></i></button>
            </form>
        </div>

        <div class="nav-right">
            <div class="nav-links">

                <a href="index.php"><i class="fas fa-home"></i> Accueil</a>

                <?php if ($isLoggedIn): ?>

                    <a href="panier.php" class="cart-link">
                        <i class="fas fa-shopping-basket"></i>
                        Panier (<?php echo isset($_SESSION['panier']) ? array_sum($_SESSION['panier']) : 0; ?>)
                    </a>

                    <span class="welcome-user">
                        👋 Bonjour <?php echo htmlspecialchars($username); ?>
                    </span>

                    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>

                <?php else: ?>

                    <a href="login.php" class="cart-link">
                        <i class="fas fa-shopping-basket"></i>
                        Panier
                    </a>

                    <a href="login.php"><i class="fas fa-sign-in-alt"></i> Connexion</a>
                    <a href="register.php"><i class="fas fa-user-plus"></i> Créer compte</a>

                <?php endif; ?>

            </div>
        </div>

    </div>
</nav>

<header class="welcome-section">
    <h1>Bienvenue <?php echo htmlspecialchars($username); ?> 👋</h1>
    <p>Cliquez sur une couverture pour voir les détails du livre.</p>
</header>

<div id="resultats">

<?php foreach ($books as $book): ?>

    <div class="book-card">

        <a href="details.php?id=<?php echo $book['idLivre']; ?>">
            <img src="uploads/book-covers/<?php echo htmlspecialchars($book['image']); ?>"
                 alt="<?php echo htmlspecialchars($book['titre']); ?>">
        </a>

        <div class="book-info">

            <h3><?php echo htmlspecialchars($book['titre']); ?></h3>

            <p class="price">
                <?php echo number_format($book['prix'], 3); ?> DT
            </p>

            <form action="<?php echo $isLoggedIn ? 'ajouter_panier.php' : '#'; ?>" method="POST">

                <input type="hidden" name="idLivre" value="<?php echo $book['idLivre']; ?>">

                <div class="qty-container">
                    <button type="button" onclick="updateQty(this, -1)">-</button>
                    <input type="text" name="quantite" value="1" readonly>
                    <button type="button" onclick="updateQty(this, 1)">+</button>
                </div>

                <?php if ($isLoggedIn): ?>

                    <button type="submit" class="btn-green-add">
                        Ajouter au panier
                    </button>

                <?php else: ?>

                    <button type="button" onclick="showLoginAlert()" class="btn-green-add">
                        Ajouter au panier
                    </button>

                <?php endif; ?>

            </form>

        </div>

    </div>

<?php endforeach; ?>

</div>

<script>
function updateQty(btn, delta) {
    const input = btn.parentElement.querySelector('input');
    let val = parseInt(input.value) + delta;
    if (val < 1) val = 1;
    input.value = val;
}

function showLoginAlert() {
    if (confirm("Vous devez vous connecter pour ajouter au panier. Aller à la page de connexion ?")) {
        window.location.href = "login.php";
    }
}
</script>

</body>
</html>