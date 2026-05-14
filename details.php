<?php
// 1. Connexion à la base de données
$conn = mysqli_connect("localhost", "root", "", "bookdb");

if (!$conn) {
    die("Connexion échouée: " . mysqli_connect_error());
}

$book = null;
if (isset($_GET['id'])) {
    $idLivre = mysqli_real_escape_string($conn, $_GET['id']);
    
    // On récupère les infos du livre et de l'auteur
    $query = "SELECT l.*, a.nom, a.prenom, a.description AS desc_auteur, a.image AS img_auteur, a.status 
              FROM livre l
              INNER JOIN livre_auteur la ON l.idLivre = la.idLivre 
              INNER JOIN auteur a ON la.idAuteur = a.idAuteur 
              WHERE l.idLivre = '$idLivre'";
              
    $result = mysqli_query($conn, $query);
    if (!$result) {
        die("Erreur SQL Détails : " . mysqli_error($conn));
    }
    $book = mysqli_fetch_assoc($result);
}

if (!$book) {
    die("Livre introuvable. Vérifiez que l'ID $idLivre existe et qu'il est lié à un auteur.");
}

// 2. Gestion de l'envoi des avis
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_avis'])) {
    $note = (int)$_POST['note'];
    $commentaire = mysqli_real_escape_string($conn, $_POST['commentaire']);
    
    // Tentative d'insertion avec débogage
    $sql_avis = "INSERT INTO avis (note, commentaire, idLigneCom, createdAt) VALUES ($note, '$commentaire', '$idLivre', NOW())";
    
    if(mysqli_query($conn, $sql_avis)) {
        header("Location: details.php?id=$idLivre#avis-list");
        exit();
    } else {
        // Cela affichera l'erreur exacte (ex: colonne inconnue, problème de type...)
        die("Erreur lors de l'enregistrement de l'avis : " . mysqli_error($conn));
    }
}

// 3. Récupération des avis (Version sécurisée contre le crash)
$query_avis = "SELECT * FROM avis WHERE idLigneCom = '$idLivre' ORDER BY createdAt DESC";
$res_avis = mysqli_query($conn, $query_avis);

if (!$res_avis) {
    // Si cette partie s'affiche, c'est que 'idLigneCom' n'est peut-être pas le bon nom de colonne
    $db_error = "Erreur lecture avis : " . mysqli_error($conn);
    $count_avis = 0;
} else {
    $count_avis = mysqli_num_rows($res_avis);
}
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

    <?php if(isset($db_error)): ?>
        <div style="background:#ffebee; color:#c62828; padding:15px; border-radius:8px; margin-bottom:20px; border:1px solid #ef9a9a;">
            <strong>Attention :</strong> <?php echo $db_error; ?>
        </div>
    <?php endif; ?>

    <div class="product-card">
        <div class="book-image">
            <img src="assests/uploads/book-covers/<?php echo $book['image']; ?>" alt="Couverture">
        </div>
        <div class="book-info">
            <h1><?php echo htmlspecialchars($book['titre']); ?></h1>
            <div class="price"><?php echo number_format($book['prix'], 3); ?> DT</div>
            <p><?php echo nl2br(htmlspecialchars($book['description'])); ?></p>
            <form action="ajouter_panier.php" method="POST">
                <input type="hidden" name="idLivre" value="<?php echo $book['idLivre']; ?>">
                <button type="submit" class="btn" style="background:#27ae60; color:white; padding:10px 20px; border:none; border-radius:5px; cursor:pointer;">Ajouter au panier</button>
            </form>
        </div>
    </div>

    <div class="author-section" style="background:white; padding:20px; border-radius:15px; margin-top:30px; box-shadow:0 2px 10px rgba(0,0,0,0.05);">
        <div class="author-header" style="display:flex; align-items:center; gap:20px;">
            <img src="assests/uploads/authors/<?php echo $book['img_auteur']; ?>" style="width:80px; height:80px; border-radius:50%; object-fit:cover;">
            <div>
                <h2 style="margin:0;">Auteur : <?php echo htmlspecialchars($book['prenom']." ".$book['nom']); ?></h2>
                <p style="margin:0; font-size:0.9rem; color:#7f8c8d;"><?php echo htmlspecialchars($book['status']); ?></p>
            </div>
        </div>
        <p style="margin-top:15px; color:#666;"><?php echo nl2br(htmlspecialchars($book['desc_auteur'])); ?></p>
    </div>

    <div id="avis" style="margin-top:40px; background:white; padding:20px; border-radius:15px;">
        <h2>Donnez votre avis</h2>
        <form method="POST">
            <select name="note" style="width:100%; padding:10px; margin-bottom:10px;">
                <option value="5">⭐⭐⭐⭐⭐ Excellent</option>
                <option value="4">⭐⭐⭐⭐ Très bon</option>
                <option value="3">⭐⭐⭐ Moyen</option>
                <option value="2">⭐⭐ Décevant</option>
                <option value="1">⭐ Mauvais</option>
            </select>
            <textarea name="commentaire" rows="4" required style="width:100%; padding:10px;" placeholder="Votre avis..."></textarea>
            <button type="submit" name="submit_avis" class="btn" style="margin-top:10px; background:#2c3e50; color:white; padding:10px 20px; border:none; border-radius:5px; cursor:pointer;">Publier</button>
        </form>
    </div>

    <div id="avis-list" style="margin-top:40px; padding-bottom:50px;">
        <h3>Avis des lecteurs (<?php echo $count_avis; ?>)</h3>
        <hr>
        <?php if($count_avis > 0): ?>
            <?php while($a = mysqli_fetch_assoc($res_avis)): ?>
                <div style="background:white; padding:15px; border-radius:10px; margin-bottom:15px; box-shadow:0 2px 5px rgba(0,0,0,0.05);">
                    <div style="color:#f1c40f;">
                        <?php for($i=1; $i<=5; $i++) echo ($i <= $a['note']) ? '⭐' : '☆'; ?>
                    </div>
                    <p style="margin:10px 0;">"<?php echo htmlspecialchars($a['commentaire']); ?>"</p>
                    <small style="color:#999;">Le <?php echo date('d/m/Y', strtotime($a['createdAt'])); ?></small>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p style="text-align:center; color:#999;">Aucun avis pour le moment.</p>
        <?php endif; ?>
    </div>
</div>

</body>
</html>