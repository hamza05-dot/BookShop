<?php
// add-book.php — à placer dans le dossier admin/
session_start();
require_once '../includes/db.php';

// Sécurité : seuls les admins peuvent accéder à cette page
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$activePage = 'add-book';
$message    = '';
$msgType    = 'success'; // 'success' ou 'error'

// On récupère les noms des fichiers images téléchargés via l'API (champs cachés)
$apiCoverFile  = trim($_POST['api_cover_file']  ?? '');
$apiAuthorFile = trim($_POST['api_author_file'] ?? '');

// ════════════════════════════════════════════════════════════════
//   TRAITEMENT DU FORMULAIRE (quand l'utilisateur clique "Sauvegarder")
// ════════════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['addBook'])) {

    $titre       = trim($_POST['titre']       ?? '');
    $description = trim($_POST['description'] ?? '');
    $prix        = (float)($_POST['prix']     ?? 0);
    $stock       = (int)($_POST['stock']      ?? 0);
    $categories  = $_POST['categories']       ?? [];
    $auteurMode  = $_POST['auteur_mode']      ?? 'existing';

    // ── Validation basique côté PHP ──────────────────────────────────────
    if (!$titre) {
        $message = 'Le titre est obligatoire.';
        $msgType = 'error';
    } elseif ($prix < 0) {
        $message = 'Le prix ne peut pas être négatif.';
        $msgType = 'error';
    } else {

        // ── CORRECTION : vérifier si le livre existe déjà avant d'insérer ──
        $stmtDoubl = $pdo->prepare("SELECT idLivre FROM livre WHERE LOWER(titre) = LOWER(?) LIMIT 1");
        $stmtDoubl->execute([$titre]);
        $livreExistant = $stmtDoubl->fetch();

        if ($livreExistant) {
            // Le livre existe déjà : on n'insère rien et on avertit l'utilisateur
            $message = 'Ce livre existe déjà dans la base de données : "' . htmlspecialchars($titre) . '".';
            $msgType = 'error';

        } else {
            // ── Gérer l'image de couverture ──────────────────────────────
            $image = '';

            if (!empty($_FILES['image']['name'])) {
                // L'utilisateur a choisi une image manuellement
                $ext     = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $nomFich = time() . '_' . uniqid() . '.' . $ext;
                if (move_uploaded_file($_FILES['image']['tmp_name'], '../uploads/book-covers/' . $nomFich)) {
                    $image = $nomFich;
                }
            } elseif ($apiCoverFile) {
                // Sinon on utilise l'image téléchargée depuis l'API
                $image = $apiCoverFile;
            }

            // ── Insérer le livre dans la base ────────────────────────────
            $stmt = $pdo->prepare(
                "INSERT INTO livre (titre, description, prix, stock, image, createdAt)
                 VALUES (?, ?, ?, ?, ?, NOW())"
            );
            $stmt->execute([$titre, $description, $prix, $stock, $image]);
            $idLivre = $pdo->lastInsertId();

            // ── Associer les catégories au livre ─────────────────────────
            // On vérifie que la catégorie n'est pas déjà liée (doublon)
            foreach ($categories as $idCat) {
                $idCat = (int)$idCat;
                if ($idCat > 0) {
                    $pdo->prepare("INSERT IGNORE INTO livre_categorie (idLivre, idCat) VALUES (?, ?)")
                        ->execute([$idLivre, $idCat]);
                }
            }

            // ── Gérer l'auteur ───────────────────────────────────────────
            $idAuteur   = null;
            $authorNote = '';

            if ($auteurMode === 'existing') {
                // Auteur existant sélectionné dans le menu déroulant
                $idAuteur   = (int)($_POST['auteur_existing'] ?? 0);
                $authorNote = 'lié à un auteur existant';

            } else {
                // Nouvel auteur : on récupère les champs du formulaire
                $aNom    = trim($_POST['a_nom']         ?? '');
                $aPrenom = trim($_POST['a_prenom']      ?? '');
                $aDesc   = trim($_POST['a_description'] ?? '');
                $aStatus = trim($_POST['a_status']      ?? 'vivant');
                $aDate   = $_POST['a_dateNaiss'] ?: null;

                // Gérer la photo de l'auteur
                $aImage = '';
                if (!empty($_FILES['a_image']['name'])) {
                    // Photo uploadée manuellement
                    $ext     = pathinfo($_FILES['a_image']['name'], PATHINFO_EXTENSION);
                    $nomFich = time() . '_author_' . uniqid() . '.' . $ext;
                    if (move_uploaded_file($_FILES['a_image']['tmp_name'], '../uploads/authors/' . $nomFich)) {
                        $aImage = $nomFich;
                    }
                } elseif ($apiAuthorFile) {
                    // Photo téléchargée depuis l'API
                    $aImage = $apiAuthorFile;
                }

                // ── CORRECTION : vérifier si l'auteur existe déjà ────────
                // On compare nom + prénom sans tenir compte des majuscules
                $stmtCheck = $pdo->prepare(
                    "SELECT idAuteur FROM auteur
                     WHERE LOWER(nom) = LOWER(?) AND LOWER(prenom) = LOWER(?)
                     LIMIT 1"
                );
                $stmtCheck->execute([$aNom, $aPrenom]);
                $auteurExistant = $stmtCheck->fetch();

                if ($auteurExistant) {
                    // L'auteur existe : on réutilise son ID sans créer de doublon
                    $idAuteur   = $auteurExistant['idAuteur'];
                    $authorNote = 'auteur existant réutilisé : "' . htmlspecialchars($aPrenom . ' ' . $aNom) . '"';
                } else {
                    // L'auteur n'existe pas : on le crée
                    $stmtA = $pdo->prepare(
                        "INSERT INTO auteur (nom, prenom, description, status, dateNaiss, image)
                         VALUES (?, ?, ?, ?, ?, ?)"
                    );
                    $stmtA->execute([$aNom, $aPrenom, $aDesc, $aStatus, $aDate, $aImage]);
                    $idAuteur   = $pdo->lastInsertId();
                    $authorNote = 'nouvel auteur créé : "' . htmlspecialchars($aPrenom . ' ' . $aNom) . '"';
                }
            }

            // ── Créer la relation livre ↔ auteur ─────────────────────────
            if ($idAuteur) {
                $pdo->prepare("INSERT IGNORE INTO livre_auteur (idLivre, idAuteur) VALUES (?, ?)")
                    ->execute([$idLivre, $idAuteur]);
            }

            $message = 'Livre "' . htmlspecialchars($titre) . '" ajouté avec succès ! (' . $authorNote . ')';
            $msgType = 'success';
        }
    }
}

// ── Charger les catégories et auteurs pour afficher dans le formulaire ────
$categories = $pdo->query("SELECT * FROM categorie ORDER BY nomCat ASC")->fetchAll();
$auteurs    = $pdo->query("SELECT * FROM auteur ORDER BY nom ASC")->fetchAll();

// ── Construire une map JS des auteurs pour la détection côté client ───────
// On stocke plusieurs clés par auteur pour couvrir les variantes de formatage
// venant de l'API (ex: "Agatha Christie" splitté en prenom=Agatha, nom=Christie
// mais aussi "Christie Agatha" ou nom complet dans prenom)
$authorMapJs = [];
foreach ($auteurs as $a) {
    $p   = strtolower(trim($a['prenom']));
    $n   = strtolower(trim($a['nom']));
    $val = ['id' => $a['idAuteur'], 'label' => $a['prenom'] . ' ' . $a['nom'],
            'prenom' => $a['prenom'], 'nom' => $a['nom']];

    // Clé normale : prenom|nom
    $authorMapJs[$p . '|' . $n] = $val;

    // Clé inversée : nom|prenom (au cas où l'API retourne "Christie Agatha")
    $authorMapJs[$n . '|' . $p] = $val;

    // Clé nom complet dans prenom (si prenom = "Agatha Christie" et nom = "")
    $authorMapJs[($p . ' ' . $n) . '|'] = $val;
    $authorMapJs['|' . ($p . ' ' . $n)] = $val;

    // Clé nom complet seul (pour chercher dans le nom complet)
    $fullKey = trim($p . ' ' . $n);
    $authorMapJs['full|' . $fullKey] = $val;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter un livre — BookShop Admin</title>
    <link rel="stylesheet" href="../assests/css/admin.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <style>
        /* ── Onglets ── */
        .tabs { display:flex; gap:0; margin-bottom:0; border-bottom:2px solid #e0e0e0; }
        .tab-btn {
            padding:12px 24px; background:#f5f5f5; border:none;
            border-bottom:3px solid transparent; cursor:pointer;
            font-size:14px; font-family:"Poppins",sans-serif; font-weight:500;
            color:#777; transition:all 0.2s; margin-bottom:-2px;
        }
        .tab-btn.active { background:white; color:var(--primary); border-bottom:3px solid var(--secondary); }
        .tab-btn:hover:not(.active) { background:#eee; color:#444; }
        .tab-pane { display:none; padding-top:24px; }
        .tab-pane.active { display:block; }

        /* ── Boutons auteur existant / nouveau ── */
        .author-toggle { display:flex; gap:10px; margin-bottom:18px; }
        .toggle-btn {
            flex:1; padding:10px; border:2px solid #ddd; border-radius:8px;
            background:#f9f9f9; cursor:pointer; font-size:13px;
            font-family:"Poppins",sans-serif; font-weight:500; color:#666; transition:all 0.2s;
        }
        .toggle-btn.active { border-color:var(--secondary); background:#ebf5fb; color:var(--secondary); }
        .author-section { display:none; }
        .author-section.visible { display:block; }

        /* ── Chips de catégories ── */
        .cat-grid { display:flex; flex-wrap:wrap; gap:8px; margin-top:8px; }
        .cat-chip {
            display:flex; align-items:center; gap:6px; padding:6px 12px;
            border:2px solid #ddd; border-radius:20px; cursor:pointer;
            font-size:13px; transition:all 0.2s; user-select:none; background:#fafafa;
        }
        .cat-chip input { display:none; }
        .cat-chip:hover { border-color:var(--secondary); background:#ebf5fb; }
        .cat-chip.checked { border-color:var(--secondary); background:var(--secondary); color:white; }

        /* ── Séparateurs de section ── */
        .section-divider {
            display:flex; align-items:center; gap:12px; margin:20px 0 16px;
            color:#999; font-size:12px; font-weight:600;
            letter-spacing:1px; text-transform:uppercase;
        }
        .section-divider::before,
        .section-divider::after { content:''; flex:1; height:1px; background:#e5e5e5; }

        /* ── Bouton de sélection de fichier ── */
        .file-label {
            display:flex; align-items:center; gap:10px; padding:10px 14px;
            border:2px dashed #ccc; border-radius:8px; cursor:pointer;
            font-size:13px; color:#777; transition:border-color 0.2s; background:#fafafa;
        }
        .file-label:hover { border-color:var(--secondary); color:var(--secondary); }
        .file-label input[type="file"] { display:none; }
        .file-name { font-size:12px; color:#444; margin-top:4px; }

        /* ── Aperçus images ── */
        #bookImagePreview {
            width:80px; height:100px; object-fit:cover; border-radius:6px;
            margin-top:8px; box-shadow:0 2px 8px rgba(0,0,0,0.15);
        }
        #authorImagePreview {
            width:70px; height:70px; object-fit:cover; border-radius:50%;
            margin-top:8px; box-shadow:0 2px 8px rgba(0,0,0,0.15);
        }

        /* ── Boîte du formulaire nouvel auteur ── */
        .new-author-box {
            background:#f8f9ff; border:1px solid #d0d9f5;
            border-radius:10px; padding:20px; margin-top:5px;
        }
        .form-row-3 { display:grid; grid-template-columns:1fr 1fr 1fr; gap:15px; }
        .req { color:var(--accent); margin-left:2px; }
        @media(max-width:768px) { .form-row-3 { grid-template-columns:1fr; } }

        /* ── Notice auteur existant (auto-détecté) ── */
        .author-exists-notice {
            display:none; background:#e8f8f0; border:1.5px solid #27ae60;
            border-radius:8px; padding:10px 14px; margin-bottom:14px;
            font-size:13px; color:#1a6635;
        }

        /* ── Messages de succès / erreur ── */
        .message-box {
            padding:14px 18px; border-radius:10px; margin-bottom:18px;
            font-size:14px; font-weight:500;
        }
        .message-box.success { background:#e8f8f0; border:1.5px solid #27ae60; color:#1a6635; }
        .message-box.error   { background:#fdecea; border:1.5px solid #e74c3c; color:#922b21; }

        /* ── Zone de recherche API ── */
        .api-search-box {
            background: linear-gradient(135deg, #f0f7ff, #e8f4fd);
            border: 1.5px solid #b3d4f0; border-radius: 12px;
            padding: 16px 18px; margin-bottom: 20px;
        }
        .api-search-box .api-label {
            font-size: 12px; font-weight: 600; color: #1a5276;
            text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px;
            display: flex; align-items: center; gap: 6px;
        }
        .api-search-row { display: flex; gap: 8px; align-items: center; }
        .api-search-row input {
            flex: 1; padding: 9px 14px; border: 1.5px solid #b3d4f0;
            border-radius: 8px; font-size: 13px; font-family: "Poppins", sans-serif;
            background: white; outline: none; transition: border-color 0.2s;
        }
        .api-search-row input:focus { border-color: #2980b9; }
        .btn-api {
            padding: 9px 16px; background: #2980b9; color: white; border: none;
            border-radius: 8px; font-size: 13px; font-family: "Poppins", sans-serif;
            font-weight: 500; cursor: pointer; transition: background 0.2s; white-space: nowrap;
        }
        .btn-api:hover { background: #1a6fa3; }
        .btn-api:disabled { background: #aaa; cursor: not-allowed; }

        /* ── Liste de résultats API ── */
        #apiResults {
            display: none; margin-top: 10px; border: 1.5px solid #b3d4f0;
            border-radius: 8px; background: white; max-height: 260px; overflow-y: auto;
        }
        .api-result-item {
            display: flex; align-items: center; gap: 12px; padding: 10px 14px;
            cursor: pointer; border-bottom: 1px solid #f0f0f0; transition: background 0.15s;
        }
        .api-result-item:last-child { border-bottom: none; }
        .api-result-item:hover { background: #eaf4fd; }
        .api-result-item img { width:36px; height:50px; object-fit:cover; border-radius:3px; flex-shrink:0; border:1px solid #ddd; }
        .api-result-item .api-book-info { flex: 1; }
        .api-result-item .api-book-title  { font-size:13px; font-weight:600; color:#2c3e50; line-height:1.3; }
        .api-result-item .api-book-author { font-size:12px; color:#7f8c8d; margin-top:2px; }
        .api-result-item .api-book-year   { font-size:11px; color:#aaa; margin-top:2px; }

        .api-status { font-size:12px; color:#7f8c8d; margin-top:8px; min-height:18px; }
        .api-status.error { color:#c0392b; }
        .api-filled-badge {
            display:none; background:#d5f5e3; color:#1e8449; border:1px solid #a9dfbf;
            border-radius:20px; padding:3px 12px; font-size:12px; font-weight:600; margin-top:8px;
        }

        /* ── Overlay de chargement ── */
        .api-loading-overlay {
            display:none; position:fixed; inset:0; background:rgba(255,255,255,0.7);
            z-index:9999; align-items:center; justify-content:center; flex-direction:column; gap:12px;
        }
        .api-loading-overlay.show { display:flex; }
        .api-spinner {
            width:42px; height:42px; border:4px solid #b3d4f0;
            border-top-color:#2980b9; border-radius:50%; animation:spin 0.8s linear infinite;
        }
        @keyframes spin { to { transform:rotate(360deg); } }
        .api-loading-text { font-size:14px; color:#2980b9; font-family:"Poppins",sans-serif; font-weight:500; }
    </style>
</head>
<body>
<?php include '../includes/nav.php'; ?>

<!-- Overlay de chargement pendant l'appel API -->
<div class="api-loading-overlay" id="apiLoadingOverlay">
    <div class="api-spinner"></div>
    <div class="api-loading-text" id="apiLoadingText">Téléchargement des données…</div>
</div>

<div class="main">

    <!-- Message de succès ou d'erreur après soumission du formulaire -->
    <?php if ($message): ?>
        <div class="message-box <?= $msgType ?>">
            <?= $msgType === 'success' ? '✓' : '⚠' ?> <?= htmlspecialchars($message) ?>
            <?php if ($msgType === 'success'): ?>
                &nbsp;·&nbsp; <a href="books.php" style="color:#1e8449; font-weight:600;">Voir tous les livres →</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="form-box">
        <h3>📖 Ajouter un nouveau livre</h3>

        <!-- Onglets de navigation -->
        <div class="tabs" style="margin-top:16px;">
            <button class="tab-btn active" data-tab="book-info">① Infos du livre</button>
            <button class="tab-btn"        data-tab="categories">② Catégories</button>
            <button class="tab-btn"        data-tab="author">③ Auteur</button>
        </div>

        <form method="POST" enctype="multipart/form-data" id="bookForm">
            <!-- Champs cachés pour passer les données au PHP -->
            <input type="hidden" name="addBook"         value="1">
            <input type="hidden" name="auteur_mode"     id="auteurMode"    value="existing">
            <input type="hidden" name="api_cover_file"  id="apiCoverFile"  value="">
            <input type="hidden" name="api_author_file" id="apiAuthorFile" value="">

            <!-- ════ ONGLET 1 : INFOS DU LIVRE ════ -->
            <div class="tab-pane active" id="tab-book-info">

                <!-- Zone de recherche API -->
                <div class="api-search-box" style="margin-top:16px;">
                    <div class="api-label">🔍 Remplissage automatique via Open Library</div>
                    <div class="api-search-row">
                        <input type="text" id="apiSearchInput" placeholder="Chercher un titre de livre pour remplir automatiquement…">
                        <button type="button" class="btn-api" id="btnApiSearch">Rechercher</button>
                    </div>
                    <div id="apiResults"></div>
                    <div class="api-status" id="apiStatus"></div>
                    <div class="api-filled-badge" id="apiFilledBadge">✓ Formulaire rempli depuis l'API Open Library</div>
                </div>

                <!-- Titre + Image -->
                <div class="form-row" style="margin-top:8px;">
                    <div class="form-group">
                        <label>Titre <span class="req">*</span></label>
                        <input type="text" name="titre" id="fieldTitre" placeholder="ex: Le Petit Prince" required>
                    </div>
                    <div class="form-group">
                        <label>Image de couverture</label>
                        <label class="file-label" for="bookImageInput">
                            <input type="file" id="bookImageInput" name="image" accept="image/*">
                            🖼️ Choisir une image…
                        </label>
                        <div class="file-name" id="bookFileName">Aucun fichier choisi</div>
                        <img id="bookImagePreview" src="" alt="Aperçu couverture" style="display:none;">
                    </div>
                </div>

                <!-- Prix + Stock -->
                <div class="form-row-3">
                    <div class="form-group">
                        <label>Prix (DT) <span class="req">*</span></label>
                        <input type="number" name="prix" id="fieldPrix" step="0.01" min="0" placeholder="0.00" required>
                    </div>
                    <div class="form-group">
                        <label>Stock <span class="req">*</span></label>
                        <input type="number" name="stock" id="fieldStock" min="0" placeholder="0" required>
                    </div>
                </div>

                <!-- Description -->
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" id="fieldDescription" placeholder="Résumé du livre…"></textarea>
                </div>

                <div style="text-align:right; margin-top:10px;">
                    <button type="button" class="btn btn-primary btn-nav" data-target="categories">Suivant → Catégories</button>
                </div>
            </div>

            <!-- ════ ONGLET 2 : CATÉGORIES ════ -->
            <div class="tab-pane" id="tab-categories">
                <div class="section-divider">Sélectionner une ou plusieurs catégories</div>

                <?php if (empty($categories)): ?>
                    <p style="color:#999; font-size:14px;">Aucune catégorie disponible. <a href="categories.php">Créer une catégorie →</a></p>
                <?php else: ?>
                    <div class="cat-grid" id="catGrid">
                        <?php foreach ($categories as $cat): ?>
                            <label class="cat-chip"
                                   id="chip-<?= $cat['idCat'] ?>"
                                   data-name="<?= strtolower(htmlspecialchars($cat['nomCat'])) ?>">
                                <input type="checkbox" name="categories[]" value="<?= $cat['idCat'] ?>">
                                <?= htmlspecialchars($cat['nomCat']) ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <p style="font-size:12px; color:#aaa; margin-top:12px;">Cliquer sur les chips pour sélectionner les catégories.</p>
                <?php endif; ?>

                <div style="display:flex; justify-content:space-between; margin-top:20px;">
                    <button type="button" class="btn btn-warning btn-nav" data-target="book-info">← Retour</button>
                    <button type="button" class="btn btn-primary btn-nav" data-target="author">Suivant → Auteur</button>
                </div>
            </div>

            <!-- ════ ONGLET 3 : AUTEUR ════ -->
            <div class="tab-pane" id="tab-author">
                <div class="section-divider">Choisir ou créer un auteur</div>

                <!-- Boutons de basculement -->
                <div class="author-toggle">
                    <button type="button" class="toggle-btn active" id="btnExisting" data-mode="existing">👤 Auteur existant</button>
                    <button type="button" class="toggle-btn"        id="btnNew"      data-mode="new">✏️ Nouvel auteur</button>
                </div>

                <!-- Section auteur existant -->
                <div class="author-section visible" id="existingAuthorSection">
                    <div class="author-exists-notice" id="authorExistsNotice">
                        ✅ Cet auteur existe déjà dans la base — sélectionné automatiquement ci-dessous.
                    </div>
                    <div class="form-group">
                        <label>Sélectionner un auteur <span class="req">*</span></label>
                        <?php if (empty($auteurs)): ?>
                            <p style="color:#e74c3c; font-size:13px;">Aucun auteur trouvé. Utiliser "Nouvel auteur" ci-dessus.</p>
                        <?php else: ?>
                            <select name="auteur_existing" id="auteurSelect" style="width:100%;">
                                <option value="">— Choisir un auteur —</option>
                                <?php foreach ($auteurs as $a): ?>
                                    <option value="<?= $a['idAuteur'] ?>">
                                        <?= htmlspecialchars($a['prenom'] . ' ' . $a['nom']) ?>
                                        <?php
                                        // CORRECTION : on compare avec 'decede' (cohérent avec la BDD)
                                        echo $a['status'] === 'decede' ? ' — ⚫ Décédé' : ' — 🟢 Vivant';
                                        ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Section nouvel auteur -->
                <div class="author-section" id="newAuthorSection">
                    <div class="new-author-box">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Prénom <span class="req">*</span></label>
                                <input type="text" name="a_prenom" id="fieldAuteurPrenom" placeholder="ex: Antoine">
                            </div>
                            <div class="form-group">
                                <label>Nom <span class="req">*</span></label>
                                <input type="text" name="a_nom" id="fieldAuteurNom" placeholder="ex: de Saint-Exupéry">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Date de naissance</label>
                                <input type="date" name="a_dateNaiss" id="fieldAuteurDate">
                            </div>
                            <div class="form-group">
                                <label>Statut</label>
                                <select name="a_status" id="fieldAuteurStatus">
                                    <option value="vivant">🟢 Vivant</option>
                                    <option value="decede">⚫ Décédé</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Biographie</label>
                            <textarea name="a_description" id="fieldAuteurDesc" placeholder="Courte biographie de l'auteur…"></textarea>
                        </div>
                        <div class="form-group">
                            <label>Photo de l'auteur</label>
                            <label class="file-label" for="authorImageInput">
                                <input type="file" id="authorImageInput" name="a_image" accept="image/*">
                                🧑 Choisir une photo…
                            </label>
                            <div class="file-name" id="authorFileName">Aucun fichier choisi</div>
                            <img id="authorImagePreview" src="" alt="Photo auteur" style="display:none;">
                        </div>
                    </div>
                </div>

                <div style="display:flex; justify-content:space-between; margin-top:24px;">
                    <button type="button" class="btn btn-warning btn-nav" data-target="categories">← Retour</button>
                    <button type="submit" class="btn btn-success" id="btnSave">✓ Sauvegarder le livre</button>
                </div>
            </div>

        </form>
    </div>
</div>

<script>
// Map des auteurs existants : "prenom|nom" => { id, label }
// Générée côté PHP pour éviter une requête AJAX supplémentaire
var authorMap = <?= json_encode($authorMapJs) ?>;

$(document).ready(function () {

    // Ouverture / fermeture du menu de navigation
    $(".menuicn").on("click", function () {
        $(".navcontainer").toggleClass("navclose");
    });

    // ════ Navigation entre onglets ════════════════════════════════════════

    function switchTab(id) {
        $(".tab-pane").removeClass("active");
        $(".tab-btn").removeClass("active");
        $("#tab-" + id).addClass("active");
        $(".tab-btn[data-tab='" + id + "']").addClass("active");
    }

    $(".tab-btn").on("click", function () { switchTab($(this).data("tab")); });
    $(".btn-nav").on("click", function () { switchTab($(this).data("target")); });

    // ════ Chips de catégories ══════════════════════════════════════════════

    // Quand on clique sur un chip, on coche / décoche la case à cocher cachée
    $(".cat-chip input").on("change", function () {
        $(this).closest(".cat-chip").toggleClass("checked", this.checked);
    });

    // ════ Basculement auteur existant / nouvel auteur ══════════════════════

    function setAuthorMode(mode) {
        $("#auteurMode").val(mode);

        // Afficher la bonne section
        $("#existingAuthorSection").toggleClass("visible", mode === "existing");
        $("#newAuthorSection").toggleClass("visible",      mode === "new");

        // Mettre le bouton actif en surbrillance
        $("#btnExisting, #btnNew").removeClass("active");
        if (mode === "existing") {
            $("#btnExisting").addClass("active");
        } else {
            $("#btnNew").addClass("active");
        }

        // Gérer les champs obligatoires selon le mode
        $("#auteurSelect").prop("required", mode === "existing");
        $("[name='a_nom'], [name='a_prenom']").prop("required", mode === "new");
    }

    $(".toggle-btn").on("click", function () {
        setAuthorMode($(this).data("mode"));
        $("#authorExistsNotice").hide();
    });

    // ════ Aperçu des images sélectionnées manuellement ════════════════════

    // Image de couverture du livre
    $("#bookImageInput").on("change", function () {
        var file = this.files[0];
        if (!file) return;
        var reader = new FileReader();
        reader.onload = function (e) {
            $("#bookImagePreview").attr("src", e.target.result).show();
        };
        reader.readAsDataURL(file);
        $("#bookFileName").text(file.name);
    });

    // Photo de l'auteur
    $("#authorImageInput").on("change", function () {
        var file = this.files[0];
        if (!file) return;
        var reader = new FileReader();
        reader.onload = function (e) {
            $("#authorImagePreview").attr("src", e.target.result).show();
        };
        reader.readAsDataURL(file);
        $("#authorFileName").text(file.name);
    });

    // ════ Vérification si l'auteur existe déjà (dans la map JS) ══════════

    // Essaie plusieurs variantes de clé pour être robuste face aux différences
    // de formatage entre l'API Open Library et notre base de données.
    function verifierAuteurExistant(prenom, nom) {
        var p    = (prenom || '').trim().toLowerCase();
        var n    = (nom    || '').trim().toLowerCase();
        var full = (p + ' ' + n).trim();

        var keysToTry = [
            p + '|' + n,
            n + '|' + p,
            'full|' + full,
            'full|' + n + ' ' + p,
        ];

        var found = null;
        for (var i = 0; i < keysToTry.length; i++) {
            if (authorMap[keysToTry[i]]) { found = authorMap[keysToTry[i]]; break; }
        }

        // Recherche par sous-chaîne si pas trouvé par clé exacte
        if (!found && full.length > 2) {
            $.each(authorMap, function (key, val) {
                if (!found && key.indexOf('full|') === 0) {
                    var sf = key.replace('full|', '');
                    if (sf.indexOf(p) !== -1 || sf.indexOf(n) !== -1 || full.indexOf(sf) !== -1) {
                        found = val;
                    }
                }
            });
        }

        if (found) {
            setAuthorMode("existing");
            $("#auteurSelect").val(found.id);
            $("#authorExistsNotice").show();
        } else {
            setAuthorMode("new");
            $("#authorExistsNotice").hide();
        }
    }

    // ════ Validation du formulaire avant soumission ════════════════════════

    $("#auteurSelect").prop("required", true);

    $("#btnSave").on("click", function (e) {
        var titre = $("[name='titre']").val().trim();
        if (!titre) {
            alert("Veuillez entrer le titre du livre.");
            switchTab("book-info");
            e.preventDefault();
            return;
        }

        var mode = $("#auteurMode").val();
        if (mode === "existing") {
            if (!$("#auteurSelect").val()) {
                alert("Veuillez sélectionner un auteur.");
                switchTab("author");
                e.preventDefault();
                return;
            }
        } else {
            if (!$("[name='a_nom']").val().trim() || !$("[name='a_prenom']").val().trim()) {
                alert("Veuillez entrer le prénom et le nom du nouvel auteur.");
                switchTab("author");
                e.preventDefault();
                return;
            }
        }
    });

    // ════════════════════════════════════════════════════════════════════════
    //   RECHERCHE API OPEN LIBRARY + REMPLISSAGE AUTOMATIQUE DU FORMULAIRE
    // ════════════════════════════════════════════════════════════════════════

    // Lancer la recherche au clic ou avec la touche Entrée
    $("#btnApiSearch").on("click", function () { doApiSearch(); });
    $("#apiSearchInput").on("keypress", function (e) {
        if (e.which === 13) { e.preventDefault(); doApiSearch(); }
    });

    // ── Étape 1 : Rechercher des livres via api_book_fetch.php ────────────
    function doApiSearch() {
        var query = $("#apiSearchInput").val().trim();
        if (!query) {
            $("#apiStatus").text("Veuillez entrer un titre de livre.").removeClass("error");
            return;
        }

        $("#apiStatus").text("Recherche en cours…").removeClass("error");
        $("#btnApiSearch").prop("disabled", true).text("Chargement…");
        $("#apiResults").hide().empty();
        $("#apiFilledBadge").hide();

        $.ajax({
            url: "api_book_fetch.php",
            data: { q: query },
            dataType: "json",
            success: function (data) {
                $("#btnApiSearch").prop("disabled", false).text("Rechercher");

                if (data.error || !data.results || data.results.length === 0) {
                    $("#apiStatus").text("Aucun résultat. Essayez un autre titre.").addClass("error");
                    return;
                }

                $("#apiStatus").text(data.results.length + " résultat(s) — cliquer pour remplir le formulaire.");
                $("#apiResults").empty().show();

                // Afficher chaque résultat dans la liste
                $.each(data.results, function (i, doc) {
                    var imgHtml = doc.thumbUrl
                        ? '<img src="' + doc.thumbUrl + '" alt="couverture">'
                        : '<div style="width:36px;height:50px;background:#eee;border-radius:3px;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0;">📖</div>';

                    var $item = $('<div class="api-result-item"></div>').html(
                        imgHtml +
                        '<div class="api-book-info">' +
                            '<div class="api-book-title">'  + $('<span>').text(doc.title).html()              + '</div>' +
                            '<div class="api-book-author">' + $('<span>').text((doc.authors || []).join(', ')).html() + '</div>' +
                            (doc.year ? '<div class="api-book-year">' + doc.year + '</div>' : '') +
                        '</div>'
                    );

                    // On passe le doc en paramètre pour éviter le problème de closure
                    (function (d) {
                        $item.on("click", function () { fillFormFromApi(d); });
                    })(doc);

                    $("#apiResults").append($item);
                });
            },
            error: function () {
                $("#btnApiSearch").prop("disabled", false).text("Rechercher");
                $("#apiStatus").text("Impossible de contacter api_book_fetch.php.").addClass("error");
            }
        });
    }

    // ── Étape 2 : Remplir le formulaire avec les données du livre choisi ──
    function fillFormFromApi(doc) {
        $("#apiResults").hide();
        $("#apiStatus").text("").removeClass("error");
        $("#apiLoadingOverlay").addClass("show");
        $("#apiLoadingText").text("Téléchargement de la couverture et des infos auteur…");

        // On prépare les paramètres pour api_author_fetch.php
        var params = {};
        if (doc.coverId)                         params.cover = doc.coverId;
        if (doc.authorKeys && doc.authorKeys[0]) params.key   = doc.authorKeys[0];
        // CORRECTION : on envoie workKey (qui était absent dans l'original)
        if (doc.workKey)                         params.work  = doc.workKey;

        $.ajax({
            url: "api_author_fetch.php",
            data: params,
            dataType: "text", // On reçoit du texte pour gérer les éventuels avertissements PHP
            success: function (raw) {
                $("#apiLoadingOverlay").removeClass("show");

                // On parse le JSON en cherchant le premier "{"
                // (au cas où PHP aurait affiché un warning avant le JSON)
                var res = null;
                try {
                    var jsonStart = raw.indexOf('{');
                    res = JSON.parse(jsonStart >= 0 ? raw.slice(jsonStart) : raw);
                } catch (e) {
                    // Si le JSON est invalide, on fait un remplissage minimal
                    applyBasicFill(doc);
                    $("#apiStatus").text("api_author_fetch.php a retourné un JSON invalide.").addClass("error");
                    return;
                }

                // ── Remplir le titre ──────────────────────────────────────
                $("#fieldTitre").val(doc.title);

                // ── Remplir la description ────────────────────────────────
                // On prend la description de l'API, sinon les sujets du livre
                var desc = (res.description && res.description.trim())
                    ? res.description
                    : (doc.subjects && doc.subjects.length ? doc.subjects.join(', ') : '');
                if (desc) $("#fieldDescription").val(desc);

                // ── Afficher l'aperçu de la couverture ───────────────────
                if (res.coverFile) {
                    $("#apiCoverFile").val(res.coverFile);
                    $("#bookImagePreview").attr("src", res.coverPreview).show();
                    $("#bookFileName").text(res.coverFile);
                }

                // ── Cocher automatiquement les catégories correspondantes ─
                if (doc.subjects && doc.subjects.length > 0) {
                    var subjectsLower = doc.subjects.map(function (s) {
                        return s.toLowerCase();
                    });

                    $(".cat-chip").each(function () {
                        var chipName = $(this).data("name") || "";
                        // On cherche si le nom du chip est dans les sujets du livre
                        var match = subjectsLower.some(function (s) {
                            return s.indexOf(chipName) !== -1 || chipName.indexOf(s) !== -1;
                        });
                        if (match) {
                            $(this).find("input").prop("checked", true).trigger("change");
                        }
                    });
                }

                // ── Remplir les infos de l'auteur ─────────────────────────
                var prenom = '', nom = '';

                if (res.author) {
                    prenom = res.author.prenom || '';
                    nom    = res.author.nom    || '';
                    $("#fieldAuteurPrenom").val(prenom);
                    $("#fieldAuteurNom").val(nom);
                    if (res.author.bio && res.author.bio.trim()) {
                        $("#fieldAuteurDesc").val(res.author.bio);
                    }
                    if (res.author.birthDate) {
                        $("#fieldAuteurDate").val(res.author.birthDate);
                    }
                    // CORRECTION : status = 'decede' ou 'vivant' (cohérent avec la BDD)
                    $("#fieldAuteurStatus").val(res.author.status || 'vivant');

                    if (res.author.photoFile) {
                        $("#apiAuthorFile").val(res.author.photoFile);
                        $("#authorImagePreview").attr("src", res.author.photoPreview).show();
                        $("#authorFileName").text("📷 " + res.author.photoFile);
                    }
                } else {
                    // Pas de données auteur depuis l'API : on split le nom complet
                    var parts = ((doc.authors && doc.authors[0]) || '').split(' ');
                    prenom = parts[0] || '';
                    nom    = parts.slice(1).join(' ') || '';
                    $("#fieldAuteurPrenom").val(prenom);
                    $("#fieldAuteurNom").val(nom);
                }

                // ── Vérifier si l'auteur existe déjà dans notre base ──────
                verifierAuteurExistant(prenom, nom);

                // ── Badge de confirmation + effet visuel ──────────────────
                $("#apiFilledBadge").show();

                var champs = ["#fieldTitre", "#fieldDescription", "#fieldAuteurPrenom", "#fieldAuteurNom", "#fieldAuteurDesc"];
                $.each(champs, function (i, sel) {
                    $(sel).css({ "border-color": "#27ae60", "background": "#f0fff4" });
                    setTimeout(function () {
                        $(sel).css({ "border-color": "", "background": "" });
                    }, 2000);
                });
            },
            error: function (xhr) {
                $("#apiLoadingOverlay").removeClass("show");
                applyBasicFill(doc);
                $("#apiStatus").text("Erreur HTTP " + xhr.status + " — vérifier que api_author_fetch.php est dans admin/.").addClass("error");
            }
        });
    }

    // ── Remplissage minimal si api_author_fetch.php est inaccessible ──────
    function applyBasicFill(doc) {
        $("#fieldTitre").val(doc.title);
        var parts  = ((doc.authors && doc.authors[0]) || '').split(' ');
        var prenom = parts[0] || '';
        var nom    = parts.slice(1).join(' ') || '';
        $("#fieldAuteurPrenom").val(prenom);
        $("#fieldAuteurNom").val(nom);
        verifierAuteurExistant(prenom, nom);
        $("#apiFilledBadge").show();
    }

});
</script>
</body>
</html>