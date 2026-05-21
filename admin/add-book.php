<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$activePage = 'add-book';
$message = '';

// Hidden fields to carry downloaded filenames from API
$apiCoverFile  = trim($_POST['api_cover_file']  ?? '');
$apiAuthorFile = trim($_POST['api_author_file'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['addBook'])) {
    $titre       = trim($_POST['titre']);
    $description = trim($_POST['description']);
    $prix        = (float)$_POST['prix'];
    $stock       = (int)$_POST['stock'];
    $categories  = $_POST['categories'] ?? [];
    $auteurMode  = $_POST['auteur_mode'];

    // Book cover: prefer uploaded file, then API-downloaded file
    $image = '';
    if (!empty($_FILES['image']['name'])) {
        $ext     = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $nomFich = time() . '_' . uniqid() . '.' . $ext;
        if (move_uploaded_file($_FILES['image']['tmp_name'], '../uploads/book-covers/' . $nomFich)) {
            $image = $nomFich;
        }
    } elseif ($apiCoverFile) {
        $image = $apiCoverFile;
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
        // Author photo: prefer uploaded file, then API-downloaded file
        $aImage  = '';
        if (!empty($_FILES['a_image']['name'])) {
            $ext     = pathinfo($_FILES['a_image']['name'], PATHINFO_EXTENSION);
            $nomFich = time() . '_author_' . uniqid() . '.' . $ext;
            if (move_uploaded_file($_FILES['a_image']['tmp_name'], '../uploads/authors/' . $nomFich)) {
                $aImage = $nomFich;
            }
        } elseif ($apiAuthorFile) {
            $aImage = $apiAuthorFile;
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

        /* ── API Search Box ── */
        .api-search-box {
            background: linear-gradient(135deg, #f0f7ff, #e8f4fd);
            border: 1.5px solid #b3d4f0;
            border-radius: 12px;
            padding: 16px 18px;
            margin-bottom: 20px;
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
        .api-result-item .api-book-title { font-size:13px; font-weight:600; color:#2c3e50; line-height:1.3; }
        .api-result-item .api-book-author { font-size:12px; color:#7f8c8d; margin-top:2px; }
        .api-result-item .api-book-year { font-size:11px; color:#aaa; margin-top:2px; }
        .api-status { font-size:12px; color:#7f8c8d; margin-top:8px; min-height:18px; }
        .api-status.success { color:#1e8449; }
        .api-status.error   { color:#c0392b; }
        .api-filled-badge {
            display:none; background:#d5f5e3; color:#1e8449; border:1px solid #a9dfbf;
            border-radius:20px; padding:3px 12px; font-size:12px; font-weight:600; margin-top:8px;
        }
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

<!-- Loading overlay shown while API downloads images -->
<div class="api-loading-overlay" id="apiLoadingOverlay">
    <div class="api-spinner"></div>
    <div class="api-loading-text" id="apiLoadingText">Downloading book data…</div>
</div>

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
            <button class="tab-btn active" data-tab="book-info">① Book Info</button>
            <button class="tab-btn"        data-tab="categories">② Categories</button>
            <button class="tab-btn"        data-tab="author">③ Author</button>
        </div>

        <form method="POST" enctype="multipart/form-data" id="bookForm">
            <input type="hidden" name="addBook"          value="1">
            <input type="hidden" name="auteur_mode"      id="auteurMode"     value="existing">
            <!-- Filenames saved by PHP proxy (used if no manual upload) -->
            <input type="hidden" name="api_cover_file"   id="apiCoverFile"   value="">
            <input type="hidden" name="api_author_file"  id="apiAuthorFile"  value="">

            <!-- TAB 1: BOOK INFO -->
            <div class="tab-pane active" id="tab-book-info">

                <div class="api-search-box" style="margin-top:16px;">
                    <div class="api-label">🔍 Auto-fill from Open Library</div>
                    <div class="api-search-row">
                        <input type="text" id="apiSearchInput" placeholder="Search a book title to auto-fill the form…">
                        <button type="button" class="btn-api" id="btnApiSearch">Search</button>
                    </div>
                    <div id="apiResults"></div>
                    <div class="api-status" id="apiStatus"></div>
                    <div class="api-filled-badge" id="apiFilledBadge">✓ Form filled from Open Library API</div>
                </div>

                <div class="form-row" style="margin-top:8px;">
                    <div class="form-group">
                        <label>Title <span class="req">*</span></label>
                        <input type="text" name="titre" id="fieldTitre" placeholder="e.g. The Little Prince" required>
                    </div>
                    <div class="form-group">
                        <label>Cover Image</label>
                        <label class="file-label" for="bookImageInput">
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
                        <input type="number" name="prix" id="fieldPrix" step="0.01" min="0" placeholder="0.00" required>
                    </div>
                    <div class="form-group">
                        <label>Stock <span class="req">*</span></label>
                        <input type="number" name="stock" id="fieldStock" min="0" placeholder="0" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" id="fieldDescription" placeholder="Book summary…"></textarea>
                </div>
                <div style="text-align:right; margin-top:10px;">
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
                            <label class="cat-chip" id="chip-<?= $cat['idCat'] ?>" data-name="<?= strtolower(htmlspecialchars($cat['nomCat'])) ?>">
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
                    <button type="button" class="toggle-btn active" id="btnExisting" data-mode="existing">👤 Existing Author</button>
                    <button type="button" class="toggle-btn"        id="btnNew"      data-mode="new">✏️ New Author</button>
                </div>

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

                <div class="author-section" id="newAuthorSection">
                    <div class="new-author-box">
                        <div class="form-row">
                            <div class="form-group">
                                <label>First Name <span class="req">*</span></label>
                                <input type="text" name="a_prenom" id="fieldAuteurPrenom" placeholder="e.g. Antoine">
                            </div>
                            <div class="form-group">
                                <label>Last Name <span class="req">*</span></label>
                                <input type="text" name="a_nom" id="fieldAuteurNom" placeholder="e.g. de Saint-Exupéry">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Date of Birth</label>
                                <input type="date" name="a_dateNaiss" id="fieldAuteurDate">
                            </div>
                            <div class="form-group">
                                <label>Status</label>
                                <select name="a_status" id="fieldAuteurStatus">
                                    <option value="vivant">🟢 Alive</option>
                                    <option value="decede">⚫ Deceased</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Biography</label>
                            <textarea name="a_description" id="fieldAuteurDesc" placeholder="Short author biography…"></textarea>
                        </div>
                        <div class="form-group">
                            <label>Author Photo</label>
                            <label class="file-label" for="authorImageInput">
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

<script>
$(document).ready(function () {

    $(".menuicn").on("click", function () {
        $(".navcontainer").toggleClass("navclose");
    });

    // ── Tab switch ──
    function switchTab(id) {
        $(".tab-pane").removeClass("active");
        $(".tab-btn").removeClass("active");
        $("#tab-" + id).addClass("active");
        $(".tab-btn[data-tab='" + id + "']").addClass("active");
    }
    $(".tab-btn").on("click", function () { switchTab($(this).data("tab")); });
    $(".btn-nav").on("click", function () { switchTab($(this).data("target")); });

    // ── Cat chip toggle ──
    $(".cat-chip input").on("change", function () {
        $(this).closest(".cat-chip").toggleClass("checked", this.checked);
    });

    // ── Author mode toggle ──
    $(".toggle-btn").on("click", function () {
        var mode = $(this).data("mode");
        $("#auteurMode").val(mode);
        $("#existingAuthorSection").toggleClass("visible", mode === "existing");
        $("#newAuthorSection").toggleClass("visible", mode === "new");
        $("#btnExisting, #btnNew").removeClass("active");
        $(this).addClass("active");
        $("#auteurSelect").prop("required", mode === "existing");
        $("[name='a_nom'], [name='a_prenom']").prop("required", mode === "new");
    });

    // ── Image preview ──
    function previewImage(input, previewId, fileNameId) {
        var file = input.files[0];
        if (file) {
            var reader = new FileReader();
            reader.onload = function (e) {
                $("#" + previewId).attr("src", e.target.result).show();
                $("#" + fileNameId).text(file.name);
            };
            reader.readAsDataURL(file);
        }
    }
    $("#bookImageInput").on("change",   function () { previewImage(this, "bookImagePreview",   "bookFileName");   });
    $("#authorImageInput").on("change", function () { previewImage(this, "authorImagePreview", "authorFileName"); });

    // ── Form validation ──
    $("#auteurSelect").prop("required", true);
    $("#btnSave").on("click", function (e) {
        var titre = $("[name='titre']").val().trim();
        if (!titre) { alert("Please enter the book title."); switchTab("book-info"); e.preventDefault(); return; }
        var mode = $("#auteurMode").val();
        if (mode === "existing") {
            if ($("#auteurSelect").length && !$("#auteurSelect").val()) {
                alert("Please select an author."); e.preventDefault(); return;
            }
        } else {
            if (!$("[name='a_nom']").val().trim() || !$("[name='a_prenom']").val().trim()) {
                alert("Please enter the first and last name of the new author."); e.preventDefault();
            }
        }
    });

    // ════════════════════════════════════════════════════
    //   OPEN LIBRARY API — Search + Auto-fill
    // ════════════════════════════════════════════════════

    $("#btnApiSearch").on("click", function () { doApiSearch(); });
    $("#apiSearchInput").on("keypress", function (e) {
        if (e.which === 13) { e.preventDefault(); doApiSearch(); }
    });

    function doApiSearch() {
        var query = $("#apiSearchInput").val().trim();
        if (!query) { $("#apiStatus").text("Please enter a book title.").removeClass("success error"); return; }

        $("#apiStatus").text("Searching…").removeClass("success error");
        $("#btnApiSearch").prop("disabled", true).text("Loading…");
        $("#apiResults").hide().empty();
        $("#apiFilledBadge").hide();

        $.ajax({
            url: "api_book_fetch.php",
            data: { q: query },
            dataType: "json",
            success: function (data) {
                $("#btnApiSearch").prop("disabled", false).text("Search");
                if (data.error || !data.results || data.results.length === 0) {
                    $("#apiStatus").text("No results found. Try a different title.").addClass("error");
                    return;
                }
                $("#apiStatus").text(data.results.length + " result(s) — click one to fill the form.");
                $("#apiResults").empty().show();

                $.each(data.results, function (i, doc) {
                    var imgHtml = doc.thumbUrl
                        ? '<img src="' + doc.thumbUrl + '" alt="cover">'
                        : '<div style="width:36px;height:50px;background:#eee;border-radius:3px;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0;">📖</div>';

                    var $item = $('<div class="api-result-item"></div>').html(
                        imgHtml +
                        '<div class="api-book-info">' +
                            '<div class="api-book-title">'  + $('<span>').text(doc.title).html()                    + '</div>' +
                            '<div class="api-book-author">' + $('<span>').text(doc.authors.join(', ')).html()       + '</div>' +
                            (doc.year ? '<div class="api-book-year">' + doc.year + '</div>' : '') +
                        '</div>'
                    );

                    (function (d) {
                        $item.on("click", function () { fillFormFromApi(d); });
                    })(doc);

                    $("#apiResults").append($item);
                });
            },
            error: function () {
                $("#btnApiSearch").prop("disabled", false).text("Search");
                $("#apiStatus").text("Could not reach api_book_fetch.php — make sure the file is in your admin/ folder.").addClass("error");
            }
        });
    }

    function fillFormFromApi(doc) {
        $("#apiResults").hide();
        $("#apiStatus").text("").removeClass("error");
        $("#apiLoadingOverlay").addClass("show");
        $("#apiLoadingText").text("Downloading cover & author info…");

        // Build request params
        var params = {};
        if (doc.coverId)                  params.cover = doc.coverId;
        if (doc.authorKeys && doc.authorKeys[0]) params.key = doc.authorKeys[0];

        $.ajax({
            url: "api_author_fetch.php",
            data: params,
            dataType: "json",
            success: function (res) {
                $("#apiLoadingOverlay").removeClass("show");

                // ── Fill book title ──
                $("#fieldTitre").val(doc.title);

                // ── Fill description: prefer full summary, fallback to subjects ──
                if (doc.description && doc.description.trim() !== '') {
                    $("#fieldDescription").val(doc.description);
                } else if (doc.subjects && doc.subjects.length > 0) {
                    $("#fieldDescription").val(doc.subjects.join(', '));
                }

                // ── Book cover ──
                if (res.coverFile) {
                    $("#apiCoverFile").val(res.coverFile);
                    $("#bookImagePreview").attr("src", res.coverPreview).show();
                    $("#bookFileName").text(res.coverFile);
                }

                // ── Auto-check matching categories ──
                if (doc.subjects && doc.subjects.length > 0) {
                    var subjectsLower = doc.subjects.map(function(s){ return s.toLowerCase(); });
                    $(".cat-chip").each(function () {
                        var chipName = $(this).data("name") || "";
                        var match = subjectsLower.some(function(s){ return s.indexOf(chipName) !== -1 || chipName.indexOf(s) !== -1; });
                        if (match) {
                            $(this).find("input").prop("checked", true);
                            $(this).addClass("checked");
                        }
                    });
                }

                // ── Fill author ──
                if (res.author) {
                    var a = res.author;
                    $("#fieldAuteurPrenom").val(a.prenom);
                    $("#fieldAuteurNom").val(a.nom);

                    // Biography — already fetched from Open Library author page
                    if (a.bio && a.bio.trim() !== '') {
                        $("#fieldAuteurDesc").val(a.bio);
                    }

                    // Status alive/deceased
                    $("#fieldAuteurStatus").val(a.status);

                    // Birth date — PHP already formatted it as YYYY-MM-DD
                    if (a.birthDate) {
                        $("#fieldAuteurDate").val(a.birthDate);
                    }

                    // Author photo downloaded by PHP proxy
                    if (a.photoFile) {
                        $("#apiAuthorFile").val(a.photoFile);
                        $("#authorImagePreview").attr("src", a.photoPreview).show();
                        $("#authorFileName").text("📷 " + a.photoFile + " (from API)");
                    }
                } else {
                    // No author details from API — fill name only from search result
                    var parts = (doc.authors[0] || '').split(' ');
                    $("#fieldAuteurPrenom").val(parts[0] || '');
                    $("#fieldAuteurNom").val(parts.slice(1).join(' ') || '');
                }

                // Switch to New Author tab so filled data is visible
                $("#btnNew").trigger("click");

                // Show success badge
                $("#apiFilledBadge").show();

                // Highlight filled fields briefly
                var fields = ["#fieldTitre","#fieldDescription","#fieldAuteurPrenom","#fieldAuteurNom","#fieldAuteurDesc"];
                fields.forEach(function (sel) {
                    $(sel).css({ "border-color":"#27ae60","background":"#f0fff4" });
                    setTimeout(function () { $(sel).css({ "border-color":"","background":"" }); }, 2000);
                });
            },
            error: function () {
                $("#apiLoadingOverlay").removeClass("show");
                // Fallback: fill what we have from search results
                $("#fieldTitre").val(doc.title);
                var parts = (doc.authors[0] || '').split(' ');
                $("#fieldAuteurPrenom").val(parts[0] || '');
                $("#fieldAuteurNom").val(parts.slice(1).join(' ') || '');
                $("#btnNew").trigger("click");
                $("#apiFilledBadge").show();
                $("#apiStatus").text("Partial fill — could not reach api_author_fetch.php.").addClass("error");
            }
        });
    }

    // ════════════════════════════════════════════════════
});
</script>
</body>
</html>