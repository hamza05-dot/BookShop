<?php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'client') {
    header('Location: Login.php');
    exit();
}
// Récupération des livres
$query = $pdo->query("SELECT * FROM livre");
$books = $query->fetchAll(PDO::FETCH_ASSOC);

$username = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : "Invité";
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
                <a href="panier.php" class="cart-link">
                    <i class="fas fa-shopping-basket"></i> 
                    <span>Panier (<?php echo isset($_SESSION['panier']) ? array_sum($_SESSION['panier']) : 0; ?>)</span>
                </a>
                <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i></a>
            </div>
        </div>
    </div>
</nav>

<header class="welcome-section">
    <h1>Bienvenue! 👋</h1>
    <p>Cliquez sur une couverture pour voir les détails du livre.</p>
</header>
<?php if (isset($_GET['error']) && $_GET['error'] == 'notfound'): ?>
    <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin: 20px auto; max-width: 1200px; text-align: center;">
        <i class="fas fa-exclamation-circle"></i> Désolé, aucun livre ne correspond à votre recherche.
    </div>
<?php endif; ?>

<div id="resultats">
    <?php foreach ($books as $book): ?>
        <div class="book-card">
            <a href="details.php?id=<?php echo $book['idLivre']; ?>" class="book-img-link">
                <img src="uploads/book-covers/<?php echo $book['image']; ?>" alt="Couverture">
            </a>

            <div class="book-info">
                <a href="details.php?id=<?php echo $book['idLivre']; ?>" class="book-title-link">
                    <h3><?php echo htmlspecialchars($book['titre']); ?></h3>
                </a>
                
                <div class="rating">
                    <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star-half-alt"></i>
                </div>

                <p class="price"><?php echo number_format($book['prix'], 3); ?> DT</p>

                <form action="ajouter_panier.php" method="POST">
                    <input type="hidden" name="idLivre" value="<?php echo $book['idLivre']; ?>">
                    <div class="qty-container">
                        <button type="button" class="qty-btn" onclick="updateQty(this, -1)">-</button>
                        <input type="text" name="quantite" class="qty-input" value="1" readonly>
                        <button type="button" class="qty-btn" onclick="updateQty(this, 1)">+</button>
                    </div>
                    <button type="submit" class="btn-green-add">
                        <i class="fas fa-cart-plus"></i> Ajouter au panier
                    </button>
                </form>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<script>
function updateQty(btn, delta) {
    const input = btn.parentElement.querySelector('.qty-input');
    let val = parseInt(input.value) + delta;
    if (val < 1) val = 1;
    input.value = val;
}
</script>
</body>
</html>