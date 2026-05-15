<?php
session_start();
require_once 'includes/db.php';

$isLoggedIn = isset($_SESSION['idUser']);

$book = null;
if (isset($_GET['id'])) {
    $idLivre = (int)$_GET['id'];

    $stmt = $pdo->prepare("
        SELECT l.*, a.nom, a.prenom, a.description AS desc_auteur, a.image AS img_auteur, a.status
        FROM livre l
        INNER JOIN livre_auteur la ON l.idLivre = la.idLivre
        INNER JOIN auteur a ON la.idAuteur = a.idAuteur
        WHERE l.idLivre = ?
    ");
    $stmt->execute([$idLivre]);
    $book = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (!$book) {
    die("Livre introuvable.");
}

// ✅ Vérifier si le client a acheté ce livre
$aAchete    = false;
$idLigneCom = null;

if ($isLoggedIn) {
    $stmtCheck = $pdo->prepare("
        SELECT lc.idLigneCom
        FROM ligne_commande lc
        INNER JOIN commande c ON lc.idCom = c.idCom
        WHERE c.idClient = ? AND lc.idLivre = ?
        LIMIT 1
    ");
    $stmtCheck->execute([$_SESSION['idUser'], $idLivre]);
    $achat = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if ($achat) {
        $aAchete    = true;
        $idLigneCom = $achat['idLigneCom'];
    }
}

// ✅ Enregistrement avis — seulement si acheté
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_avis'])) {

    if (!$isLoggedIn) {
        header("Location: login.php");
        exit();
    }

    if (!$aAchete) {
        die("Vous devez acheter ce livre pour laisser un avis.");
    }

    $note        = (int)$_POST['note'];
    $commentaire = $_POST['commentaire'];

    $stmt = $pdo->prepare("
        INSERT INTO avis (note, commentaire, idLigneCom, createdAt)
        VALUES (?, ?, ?, NOW())
    ");
    $stmt->execute([$note, $commentaire, $idLigneCom]);

    header("Location: details.php?id=$idLivre#avis-list");
    exit();
}

// ✅ Récupération des avis
$stmtAvis   = $pdo->prepare("SELECT * FROM avis WHERE idLigneCom = ? ORDER BY createdAt DESC");
$stmtAvis->execute([$idLivre]);
$avis_list  = $stmtAvis->fetchAll(PDO::FETCH_ASSOC);
$count_avis = count($avis_list);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($book['titre']); ?></title>
    <link rel="stylesheet" href="assests/css/style_details.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

<div class="container">
    <header style="margin-bottom: 20px;">
        <a href="index.php">← Retour</a>
    </header>

    <div class="product-card">
        <div class="book-image">
            <img src="uploads/book-covers/<?php echo $book['image']; ?>" alt="Couverture">
        </div>
        <div class="book-info">
            <h1><?php echo htmlspecialchars($book['titre']); ?></h1>
            <div class="price"><?php echo number_format($book['prix'], 3); ?> DT</div>
            <p><?php echo nl2br(htmlspecialchars($book['description'])); ?></p>
            <form action="ajouter_panier.php" method="POST">
                <input type="hidden" name="idLivre" value="<?php echo $book['idLivre']; ?>">
                <button type="submit" class="btn" style="background:#27ae60; color:white; padding:10px 20px; border:none; border-radius:5px; cursor:pointer;">
                    Ajouter au panier
                </button>
            </form>
        </div>
    </div>

    <div class="author-section" style="background:white; padding:20px; border-radius:15px; margin-top:30px; box-shadow:0 2px 10px rgba(0,0,0,0.05);">
        <div class="author-header" style="display:flex; align-items:center; gap:20px;">
            <img src="uploads/authors/<?php echo $book['img_auteur']; ?>" style="width:80px; height:80px; border-radius:50%; object-fit:cover;">
            <div>
                <h2 style="margin:0;">Auteur : <?php echo htmlspecialchars($book['prenom']." ".$book['nom']); ?></h2>
                <p style="margin:0; font-size:0.9rem; color:#7f8c8d;"><?php echo htmlspecialchars($book['status']); ?></p>
            </div>
        </div>
        <p style="margin-top:15px; color:#666;"><?php echo nl2br(htmlspecialchars($book['desc_auteur'])); ?></p>
    </div>

    <!-- ✅ Formulaire avis — conditionnel -->
    <div id="avis" style="margin-top:40px; background:white; padding:20px; border-radius:15px;">
        <h2>Donnez votre avis</h2>

        <?php if (!$isLoggedIn): ?>
            <p style="color:#e74c3c;">
                <a href="login.php">Connectez-vous</a> pour laisser un avis.
            </p>

        <?php elseif (!$aAchete): ?>
            <p style="color:#e74c3c;">
                ⚠ Vous devez avoir acheté ce livre pour laisser un avis.
            </p>

        <?php else: ?>
            <form method="POST">
                <select name="note" style="width:100%; padding:10px; margin-bottom:10px;">
                    <option value="5">⭐⭐⭐⭐⭐ Excellent</option>
                    <option value="4">⭐⭐⭐⭐ Très bon</option>
                    <option value="3">⭐⭐⭐ Moyen</option>
                    <option value="2">⭐⭐ Décevant</option>
                    <option value="1">⭐ Mauvais</option>
                </select>
                <textarea name="commentaire" rows="4" required style="width:100%; padding:10px;" placeholder="Votre avis..."></textarea>
                <button type="submit" name="submit_avis" class="btn" style="margin-top:10px; background:#2c3e50; color:white; padding:10px 20px; border:none; border-radius:5px; cursor:pointer;">
                    Publier
                </button>
            </form>
        <?php endif; ?>
    </div>

    <div id="avis-list" style="margin-top:40px; padding-bottom:50px;">
        <h3>Avis des lecteurs (<?php echo $count_avis; ?>)</h3>
        <hr>
        <?php if($count_avis > 0): ?>
            <?php foreach($avis_list as $a): ?>
                <div style="background:white; padding:15px; border-radius:10px; margin-bottom:15px; box-shadow:0 2px 5px rgba(0,0,0,0.05);">
                    <div style="color:#f1c40f;">
                        <?php for($i=1; $i<=5; $i++) echo ($i <= $a['note']) ? '⭐' : '☆'; ?>
                    </div>
                    <p style="margin:10px 0;">"<?php echo htmlspecialchars($a['commentaire']); ?>"</p>
                    <small style="color:#999;">Le <?php echo date('d/m/Y', strtotime($a['createdAt'])); ?></small>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="text-align:center; color:#999;">Aucun avis pour le moment.</p>
        <?php endif; ?>
    </div>

</div>

</body>
</html>