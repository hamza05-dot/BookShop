<?php
session_start();
$conn = mysqli_connect("localhost", "root", "", "bookdb");

if (isset($_GET['remove'])) {
    unset($_SESSION['panier'][$_GET['remove']]);
    header("Location: panier.php");
    exit();
}

$total_ttc = 0;
$nb_articles = 0;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mon Panier - BookShop</title>
    <link rel="stylesheet" href="assests/css/style.css">
    <link rel="stylesheet" href="assests/css/style_panier.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

<nav class="navbar">
    <div class="nav-container">
        <a href="index.php" class="logo">📚 BookShop</a>
        <div class="nav-links">
            <a href="index.php"><i class="fas fa-arrow-left"></i> Continuer mes achats</a>
        </div>
    </div>
</nav>

<div class="container" style="max-width:1200px; margin:40px auto; padding:0 20px;">
    <h1 style="margin-bottom: 30px; color: #2c3e50;">Mon Panier</h1>

    <?php if (empty($_SESSION['panier'])): ?>
        <div style="text-align:center; padding:50px; background:white; border-radius:15px;">
            <i class="fas fa-shopping-basket" style="font-size:4rem; color:#eee; margin-bottom:20px;"></i>
            <p style="font-size:1.2rem; color:#666;">Votre panier est vide pour le moment.</p>
            <a href="index.php" class="btn-green" style="display:inline-block; margin-top:20px; width:auto; padding:10px 30px; text-decoration:none;">Boutique</a>
        </div>
    <?php else: ?>
        <div class="checkout-section">
            
            <div class="left-side">
                <div class="cart-table-container">
                    <table class="cart-table">
                        <thead>
                            <tr>
                                <th>Livre</th>
                                <th>Prix Unit.</th>
                                <th>Quantité</th>
                                <th>Sous-total</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            foreach ($_SESSION['panier'] as $id => $qte): 
                                $res = mysqli_query($conn, "SELECT * FROM livre WHERE idLivre = '$id'");
                                $l = mysqli_fetch_assoc($res);
                                if ($l):
                                    $st = $l['prix'] * $qte;
                                    $total_ttc += $st;
                                    $nb_articles += $qte;
                            ?>
                            <tr>
                                <td><strong><?php echo $l['titre']; ?></strong></td>
                                <td><?php echo number_format($l['prix'], 3); ?> DT</td>
                                <td><span class="badge-qty"><?php echo $qte; ?></span></td>
                                <td style="font-weight:600;"><?php echo number_format($st, 3); ?> DT</td>
                                <td>
                                    <a href="panier.php?remove=<?php echo $id; ?>" style="color:#e74c3c;" title="Supprimer">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endif; endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="payment-methods">
                    <h3><i class="fas fa-shipping-fast"></i> Informations de livraison</h3>
                    <form id="formCaisse" action="valider.php" method="POST">
                        <div class="input-group">
                            <input type="text" name="adresse" class="input-field" placeholder="Adresse complète de livraison" required>
                        </div>
                        <div class="input-group">
                            <input type="tel" name="tel" class="input-field" placeholder="Numéro de téléphone" required>
                        </div>
                        
                        <div class="radio-group">
                            <label class="radio-item">
                                <input type="radio" name="p" value="cod" checked onclick="document.getElementById('card-info').style.display='none'">
                                Paiement à la livraison
                            </label>
                            <label class="radio-item">
                                <input type="radio" name="p" value="card" onclick="document.getElementById('card-info').style.display='block'">
                                Carte Bancaire
                            </label>
                        </div>

                        <div id="card-info" style="display:none; margin-top:15px; padding:20px; background:#f9f9f9; border-radius:10px; border:1px solid #eee;">
                            <div class="input-group">
                                <input type="text" placeholder="Numéro de carte (16 chiffres)" class="input-field">
                            </div>
                            <div style="display:flex; gap:10px;">
                                <input type="text" placeholder="MM/YY" class="input-field" style="width:50%;">
                                <input type="text" placeholder="CVV" class="input-field" style="width:50%;">
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="order-summary">
                <h3>Résumé</h3>
                <div class="summary-item">
                    <span>Articles (<?php echo $nb_articles; ?>)</span>
                    <span><?php echo number_format($total_ttc * 0.81, 3); ?> DT</span>
                </div>
                <div class="summary-item">
                    <span>TVA (19%)</span>
                    <span><?php echo number_format($total_ttc * 0.19, 3); ?> DT</span>
                </div>
                <div class="summary-item">
                    <span>Frais de livraison</span>
                    <span style="color:var(--green); font-weight:bold;">OFFERT</span>
                </div>
                
                <div class="total-row">
                    <h2>
                        <span>Total</span>
                        <span><?php echo number_format($total_ttc, 3); ?> DT</span>
                    </h2>
                </div>
<form id="formCaisse" action="valider.php" method="POST">
    </form>

<button type="submit" form="formCaisse" class="btn-pay">
    <i class="fas fa-check-circle"></i> CONFIRMER LA COMMANDE
</button>
                
                <p style="text-align:center; color:#999; font-size:0.8rem; margin-top:15px;">
                    <i class="fas fa-lock"></i> Paiement 100% sécurisé
                </p>
            </div>

        </div>
    <?php endif; ?>
</div>

</body>
</html>