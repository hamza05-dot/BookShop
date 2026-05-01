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
    <link rel="stylesheet" href="../assets/css/admin.css">
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
            <button class="tab-btn active" onclick="switchTab('book-info', this)">① Book Info</button>
            <button class="tab-btn"        onclick="switchTab('categories', this)">② Categories</button>
            <button class="tab-btn"        onclick="switchTab('author', this)">③ Author</button>
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
                            <input type="file" id="bookImageInput" name="image" accept="image/*"
                                   onchange="previewImage(this,'bookImagePreview','bookFileName')">
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
                    <button type="button" class="btn btn-primary"
                            onclick="switchTab('categories', document.querySelectorAll('.tab-btn')[1])">
                        Next → Categories
                    </button>
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
                                <input type="checkbox" name="categories[]" value="<?= $cat['idCat'] ?>"
                                       onchange="toggleChip(this, 'chip-<?= $cat['idCat'] ?>')">
                                <?= htmlspecialchars($cat['nomCat']) ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <p style="font-size:12px; color:#aaa; margin-top:12px;">Click chips to select the book's categories.</p>
                <?php endif; ?>
                <div style="display:flex; justify-content:space-between; margin-top:20px;">
                    <button type="button" class="btn btn-warning"
                            onclick="switchTab('book-info', document.querySelectorAll('.tab-btn')[0])">← Back</button>
                    <button type="button" class="btn btn-primary"
                            onclick="switchTab('author', document.querySelectorAll('.tab-btn')[2])">Next → Author</button>
                </div>
            </div>

            <!-- TAB 3: AUTHOR -->
            <div class="tab-pane" id="tab-author">
                <div class="section-divider">Choose or create an author</div>
                <div class="author-toggle">
                    <button type="button" class="toggle-btn active" id="btnExisting" onclick="setAuthorMode('existing')">
                        👤 Existing Author
                    </button>
                    <button type="button" class="toggle-btn" id="btnNew" onclick="setAuthorMode('new')">
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
                                <input type="file" id="authorImageInput" name="a_image" accept="image/*"
                                       onchange="previewImage(this,'authorImagePreview','authorFileName')">
                                🧑 Choose a photo…
                            </label>
                            <div class="file-name" id="authorFileName">No file chosen</div>
                            <img id="authorImagePreview" alt="Author photo">
                        </div>
                    </div>
                </div>

                <div style="display:flex; justify-content:space-between; margin-top:24px;">
                    <button type="button" class="btn btn-warning"
                            onclick="switchTab('categories', document.querySelectorAll('.tab-btn')[1])">← Back</button>
                    <button type="submit" class="btn btn-success" onclick="return validateForm()">✓ Save Book</button>
                </div>
            </div>

        </form>
    </div>

</div>
</div><!-- /.main-container -->

<script>
function switchTab(id, btn) {
    document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + id).classList.add('active');
    btn.classList.add('active');
}
function toggleChip(checkbox, chipId) {
    document.getElementById(chipId).classList.toggle('checked', checkbox.checked);
}
function setAuthorMode(mode) {
    document.getElementById('auteurMode').value = mode;
    document.getElementById('existingAuthorSection').classList.toggle('visible', mode === 'existing');
    document.getElementById('newAuthorSection').classList.toggle('visible', mode === 'new');
    document.getElementById('btnExisting').classList.toggle('active', mode === 'existing');
    document.getElementById('btnNew').classList.toggle('active', mode === 'new');
    const sel = document.getElementById('auteurSelect');
    if (sel) sel.required = (mode === 'existing');
    document.querySelectorAll('[name="a_nom"],[name="a_prenom"]').forEach(el => {
        el.required = (mode === 'new');
    });
}
function previewImage(input, previewId, fileNameId) {
    const preview = document.getElementById(previewId);
    const label   = document.getElementById(fileNameId);
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => { preview.src = e.target.result; preview.style.display = 'block'; };
        reader.readAsDataURL(input.files[0]);
        label.textContent = input.files[0].name;
    }
}
function validateForm() {
    const titre = document.querySelector('[name="titre"]').value.trim();
    if (!titre) {
        alert('Please enter the book title.');
        switchTab('book-info', document.querySelectorAll('.tab-btn')[0]);
        return false;
    }
    const mode = document.getElementById('auteurMode').value;
    if (mode === 'existing') {
        const sel = document.getElementById('auteurSelect');
        if (sel && !sel.value) { alert('Please select an author.'); return false; }
    } else {
        const nom    = document.querySelector('[name="a_nom"]').value.trim();
        const prenom = document.querySelector('[name="a_prenom"]').value.trim();
        if (!nom || !prenom) { alert('Please enter the first and last name of the new author.'); return false; }
    }
    return true;
}
document.querySelector(".menuicn").addEventListener("click", () => {
    document.querySelector(".navcontainer").classList.toggle("navclose");
});
setAuthorMode('existing');
</script>
</body>
</html>