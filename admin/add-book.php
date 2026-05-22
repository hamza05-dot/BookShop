<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$activePage = 'add-book';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['addBook'])) {
    $titre       = trim($_POST['titre']);
    $description = trim($_POST['description']);
    $prix        = (float)$_POST['prix'];
    $stock       = (int)$_POST['stock'];
    $categories  = $_POST['categories'] ?? [];
    $auteurMode  = $_POST['auteur_mode'];

    $image = '';
    if (!empty($_FILES['image']['name'])) {
        $ext     = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $nomFich = time() . '_' . uniqid() . '.' . $ext;
        if (move_uploaded_file($_FILES['image']['tmp_name'], '../uploads/book-covers/' . $nomFich)) {
            $image = $nomFich;
        }
    }

    $stmt = $pdo->prepare("INSERT INTO livre (titre, description, prix, stock, image, createdAt) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->execute([$titre, $description, $prix, $stock, $image]);
    $idLivre = $pdo->lastInsertId();

    foreach ($categories as $idCat) {
        $pdo->prepare("INSERT INTO livre_categorie (idLivre, idCat) VALUES (?, ?)")->execute([$idLivre, (int)$idCat]);
    }

    $idAuteur = null;
    if ($auteurMode === 'existing') {
        $idAuteur = (int)$_POST['auteur_existing'];
    } else {
        $aNom    = trim($_POST['a_nom']);
        $aPrenom = trim($_POST['a_prenom']);
        $aDesc   = trim($_POST['a_description']);
        $aStatus = trim($_POST['a_status']);
        $aDate   = $_POST['a_dateNaiss'] ?: null;
        $aImage  = '';
        if (!empty($_FILES['a_image']['name'])) {
            $ext     = pathinfo($_FILES['a_image']['name'], PATHINFO_EXTENSION);
            $nomFich = time() . '_author_' . uniqid() . '.' . $ext;
            if (move_uploaded_file($_FILES['a_image']['tmp_name'], '../uploads/authors/' . $nomFich)) {
                $aImage = $nomFich;
            }
        }
        $stmtA = $pdo->prepare("INSERT INTO auteur (nom, prenom, description, status, dateNaiss, image) VALUES (?, ?, ?, ?, ?, ?)");
        $stmtA->execute([$aNom, $aPrenom, $aDesc, $aStatus, $aDate, $aImage]);
        $idAuteur = $pdo->lastInsertId();
    }

    if ($idAuteur) {
        $pdo->prepare("INSERT INTO livre_auteur (idLivre, idAuteur) VALUES (?, ?)")->execute([$idLivre, $idAuteur]);
    }

    $message = "Book \"" . htmlspecialchars($titre) . "\" added successfully!";
}

$categories = $pdo->query("SELECT * FROM categorie ORDER BY nomCat ASC")->fetchAll();
$auteurs    = $pdo->query("SELECT * FROM auteur ORDER BY nom ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Book — BookShop Admin</title>
    <link rel="stylesheet" href="../assests/css/admin.css">
    <!-- jQuery CDN -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <style>
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

        .author-toggle { display:flex; gap:10px; margin-bottom:18px; }
        .toggle-btn {
            flex:1; padding:10px; border:2px solid #ddd; border-radius:8px;
            background:#f9f9f9; cursor:pointer; font-size:13px;
            font-family:"Poppins",sans-serif; font-weight:500; color:#666; transition:all 0.2s;
        }
        .toggle-btn.active { border-color:var(--secondary); background:#ebf5fb; color:var(--secondary); }
        .author-section { display:none; }
        .author-section.visible { display:block; }

        .cat-grid { display:flex; flex-wrap:wrap; gap:8px; margin-top:8px; }
        .cat-chip {
            display:flex; align-items:center; gap:6px; padding:6px 12px;
            border:2px solid #ddd; border-radius:20px; cursor:pointer;
            font-size:13px; transition:all 0.2s; user-select:none; background:#fafafa;
        }
        .cat-chip input { display:none; }
        .cat-chip:hover { border-color:var(--secondary); background:#ebf5fb; }
        .cat-chip.checked { border-color:var(--secondary); background:var(--secondary); color:white; }

        .section-divider {
            display:flex; align-items:center; gap:12px; margin:20px 0 16px;
            color:#999; font-size:12px; font-weight:600;
            letter-spacing:1px; text-transform:uppercase;
        }
        .section-divider::before,
        .section-divider::after { content:''; flex:1; height:1px; background:#e5e5e5; }

        .file-label {
            display:flex; align-items:center; gap:10px; padding:10px 14px;
            border:2px dashed #ccc; border-radius:8px; cursor:pointer;
            font-size:13px; color:#777; transition:border-color 0.2s; background:#fafafa;
        }
        .file-label:hover { border-color:var(--secondary); color:var(--secondary); }
        .file-label input[type="file"] { display:none; }
        .file-name { font-size:12px; color:#444; margin-top:4px; }

        #bookImagePreview {
            width:80px; height:100px; object-fit:cover; border-radius:6px;
            display:none; margin-top:8px; box-shadow:0 2px 8px rgba(0,0,0,0.15);
        }
        #authorImagePreview {
            width:70px; height:70px; object-fit:cover; border-radius:50%;
            display:none; margin-top:8px; box-shadow:0 2px 8px rgba(0,0,0,0.15);
        }
        .new-author-box {
            background:#f8f9ff; border:1px solid #d0d9f5;
            border-radius:10px; padding:20px; margin-top:5px;
        }
        .form-row-3 { display:grid; grid-template-columns:1fr 1fr 1fr; gap:15px; }
        .req { color:var(--accent); margin-left:2px; }
        @media(max-width:768px) { .form-row-3 { grid-template-columns:1fr; } }
    </style>
</head>
<body>
<?php include '../includes/nav.php'; ?>

<div class="main">

    <?php if ($message): ?>
        <div class="message-box success">
            ✓ <?= htmlspecialchars($message) ?>
            &nbsp;·&nbsp; <a href="books.php" style="color:#1e8449; font-weight:600;">View all books →</a>
        </div>
    <?php endif; ?>

    <div class="form-box">
        <h3>📖 Add a New Book</h3>

        <div class="tabs" style="margin-top:16px;">
            <!-- onclick retiré → géré par jQuery avec data-tab -->
            <button class="tab-btn active" data-tab="book-info">① Book Info</button>
            <button class="tab-btn"        data-tab="categories">② Categories</button>
            <button class="tab-btn"        data-tab="author">③ Author</button>
        </div>

        <form method="POST" enctype="multipart/form-data" id="bookForm">
            <input type="hidden" name="addBook" value="1">
            <input type="hidden" name="auteur_mode" id="auteurMode" value="existing">

            <!-- TAB 1: BOOK INFO -->
            <div class="tab-pane active" id="tab-book-info">
                <div class="form-row" style="margin-top:8px;">
                    <div class="form-group">
                        <label>Title <span class="req">*</span></label>
                        <input type="text" name="titre" placeholder="e.g. The Little Prince" required>
                    </div>
                    <div class="form-group">
                        <label>Cover Image</label>
                        <label class="file-label" for="bookImageInput">
                            <!-- onchange retiré → géré par jQuery -->
                            <input type="file" id="bookImageInput" name="image" accept="image/*">
                            🖼️ Choose an image…
                        </label>
                        <div class="file-name" id="bookFileName">No file chosen</div>
                        <img id="bookImagePreview" alt="Cover preview">
                    </div>
                </div>
                <div class="form-row-3">
                    <div class="form-group">
                        <label>Price (DT) <span class="req">*</span></label>
                        <input type="number" name="prix" step="0.01" min="0" placeholder="0.00" required>
                    </div>
                    <div class="form-group">
                        <label>Stock <span class="req">*</span></label>
                        <input type="number" name="stock" min="0" placeholder="0" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" placeholder="Book summary…"></textarea>
                </div>
                <div style="text-align:right; margin-top:10px;">
                    <!-- bouton navigation tab avec data-target -->
                    <button type="button" class="btn btn-primary btn-nav" data-target="categories">Next → Categories</button>
                </div>
            </div>

            <!-- TAB 2: CATEGORIES -->
            <div class="tab-pane" id="tab-categories">
                <div class="section-divider">Select one or more categories</div>
                <?php if (empty($categories)): ?>
                    <p style="color:#999; font-size:14px;">No categories available. <a href="categories.php">Create one first →</a></p>
                <?php else: ?>
                    <div class="cat-grid" id="catGrid">
                        <?php foreach ($categories as $cat): ?>
                            <label class="cat-chip" id="chip-<?= $cat['idCat'] ?>">
                                <!-- onchange retiré → géré par jQuery -->
                                <input type="checkbox" name="categories[]" value="<?= $cat['idCat'] ?>">
                                <?= htmlspecialchars($cat['nomCat']) ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <p style="font-size:12px; color:#aaa; margin-top:12px;">Click chips to select the book's categories.</p>
                <?php endif; ?>
                <div style="display:flex; justify-content:space-between; margin-top:20px;">
                    <button type="button" class="btn btn-warning btn-nav" data-target="book-info">← Back</button>
                    <button type="button" class="btn btn-primary btn-nav" data-target="author">Next → Author</button>
                </div>
            </div>

            <!-- TAB 3: AUTHOR -->
            <div class="tab-pane" id="tab-author">
                <div class="section-divider">Choose or create an author</div>
                <div class="author-toggle">
                    <!-- onclick retiré → géré par jQuery avec data-mode -->
                    <button type="button" class="toggle-btn active" id="btnExisting" data-mode="existing">
                        👤 Existing Author
                    </button>
                    <button type="button" class="toggle-btn" id="btnNew" data-mode="new">
                        ✏️ New Author
                    </button>
                </div>

                <!-- SELECT EXISTING -->
                <div class="author-section visible" id="existingAuthorSection">
                    <div class="form-group">
                        <label>Select an author <span class="req">*</span></label>
                        <?php if (empty($auteurs)): ?>
                            <p style="color:#e74c3c; font-size:13px;">No authors found. Use "New Author" above.</p>
                        <?php else: ?>
                            <select name="auteur_existing" id="auteurSelect" style="width:100%;">
                                <option value="">— Choose an author —</option>
                                <?php foreach ($auteurs as $a): ?>
                                    <option value="<?= $a['idAuteur'] ?>">
                                        <?= htmlspecialchars($a['prenom'] . ' ' . $a['nom']) ?>
                                        <?= $a['status'] === 'Dead' ? ' — ⚫ Deceased' : ' — 🟢 Alive' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- CREATE NEW AUTHOR -->
                <div class="author-section" id="newAuthorSection">
                    <div class="new-author-box">
                        <div class="form-row">
                            <div class="form-group">
                                <label>First Name <span class="req">*</span></label>
                                <input type="text" name="a_prenom" placeholder="e.g. Antoine">
                            </div>
                            <div class="form-group">
                                <label>Last Name <span class="req">*</span></label>
                                <input type="text" name="a_nom" placeholder="e.g. de Saint-Exupéry">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Date of Birth</label>
                                <input type="date" name="a_dateNaiss">
                            </div>
                            <div class="form-group">
                                <label>Status</label>
                                <select name="a_status">
                                    <option value="vivant">🟢 Alive</option>
                                    <option value="decede">⚫ Deceased</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Biography</label>
                            <textarea name="a_description" placeholder="Short author biography…"></textarea>
                        </div>
                        <div class="form-group">
                            <label>Author Photo</label>
                            <label class="file-label" for="authorImageInput">
                                <!-- onchange retiré → géré par jQuery -->
                                <input type="file" id="authorImageInput" name="a_image" accept="image/*">
                                🧑 Choose a photo…
                            </label>
                            <div class="file-name" id="authorFileName">No file chosen</div>
                            <img id="authorImagePreview" alt="Author photo">
                        </div>
                    </div>
                </div>

                <div style="display:flex; justify-content:space-between; margin-top:24px;">
                    <button type="button" class="btn btn-warning btn-nav" data-target="categories">← Back</button>
                    <button type="submit" class="btn btn-success" id="btnSave">✓ Save Book</button>
                </div>
            </div>

        </form>
    </div>

</div>
</div>

<script>
$(document).ready(function () {

    // ── Sidebar toggle ──
    $(".menuicn").on("click", function () {
        $(".navcontainer").toggleClass("navclose");
    });

    // ── Fonction switchTab ──
    function switchTab(id) {
        $(".tab-pane").removeClass("active");
        $(".tab-btn").removeClass("active");
        $("#tab-" + id).addClass("active");
        $(".tab-btn[data-tab='" + id + "']").addClass("active");
    }

    // ── Clics sur les boutons de tab (header) ──
    $(".tab-btn").on("click", function () {
        switchTab($(this).data("tab"));
    });

    // ── Boutons de navigation entre tabs ──
    $(".btn-nav").on("click", function () {
        switchTab($(this).data("target"));
    });

    // ── Chip catégorie : toggle classe checked ──
    $(".cat-chip input").on("change", function () {
        $(this).closest(".cat-chip").toggleClass("checked", this.checked);
    });

    // ── Mode auteur (existing / new) ──
    $(".toggle-btn").on("click", function () {
        const mode = $(this).data("mode");
        $("#auteurMode").val(mode);

        // affiche/cache les sections
        $("#existingAuthorSection").toggleClass("visible", mode === "existing");
        $("#newAuthorSection").toggleClass("visible", mode === "new");

        // active le bon bouton
        $("#btnExisting, #btnNew").removeClass("active");
        $(this).addClass("active");

        // required dynamique
        $("#auteurSelect").prop("required", mode === "existing");
        $("[name='a_nom'], [name='a_prenom']").prop("required", mode === "new");
    });

    // ── Preview image générique (couverture + photo auteur) ──
    function previewImage(input, previewId, fileNameId) {
        const file = input.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function (e) {
                // affiche l'image et son nom
                $("#" + previewId).attr("src", e.target.result).show();
                $("#" + fileNameId).text(file.name);
            };
            reader.readAsDataURL(file);
        }
    }

    // ── Preview couverture du livre ──
    $("#bookImageInput").on("change", function () {
        previewImage(this, "bookImagePreview", "bookFileName");
    });

    // ── Preview photo auteur ──
    $("#authorImageInput").on("change", function () {
        previewImage(this, "authorImagePreview", "authorFileName");
    });

    // ── Validation avant soumission ──
    $("#btnSave").on("click", function (e) {
        const titre = $("[name='titre']").val().trim();
        if (!titre) {
            alert("Please enter the book title.");
            switchTab("book-info");
            e.preventDefault();
            return;
        }
        const mode = $("#auteurMode").val();
        if (mode === "existing") {
            if ($("#auteurSelect").length && !$("#auteurSelect").val()) {
                alert("Please select an author.");
                e.preventDefault();
                return;
            }
        } else {
            const nom    = $("[name='a_nom']").val().trim();
            const prenom = $("[name='a_prenom']").val().trim();
            if (!nom || !prenom) {
                alert("Please enter the first and last name of the new author.");
                e.preventDefault();
            }
        }
    });

    // ── Init : mode existing au chargement ──
    $("#auteurSelect").prop("required", true);
});
</script>
</body>
</html>