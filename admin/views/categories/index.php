<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories — BookShop Admin</title>
    <link rel="stylesheet" href="../assests/css/admin.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <style>
        .spinner { display:inline-block; width:18px; height:18px; border:3px solid #ddd; border-top-color:var(--secondary,#5c6bc0); border-radius:50%; animation:spin .7s linear infinite; vertical-align:middle; margin-right:6px; }
        @keyframes spin { to { transform:rotate(360deg); } }
        .toast { position:fixed; bottom:20px; right:20px; background:#333; color:#fff; padding:10px 18px; border-radius:8px; font-size:13px; z-index:999; display:none; }
        .error-msg { color:red; font-size:13px; margin-top:6px; display:none; }
    </style>
</head>
<body>
<?php include '../includes/nav.php'; ?>
<div class="main">

    <!-- Formulaire d'ajout — soumis via fetch, pas en PHP -->
    <div class="form-box">
        <h3>➕ Add Category</h3>
        <div style="display:flex;gap:10px;align-items:flex-end;margin-top:12px;">
            <div class="form-group" style="margin:0;flex:1;">
                <label>Category name</label>
                <input type="text" id="newCatName" placeholder="e.g. Romance, Sci-Fi…">
            </div>
            <button class="btn btn-primary" id="btnAddCat">Add</button>
        </div>
        <p class="error-msg" id="addCatError"></p>
    </div>

    <div class="report-container">
        <div class="report-header">
            <h2>🏷️ All Categories (<span id="catCount">…</span>)</h2>
        </div>
        <div id="catsTableWrap">
            <p style="padding:30px;text-align:center;color:#aaa;"><span class="spinner"></span> Loading categories…</p>
        </div>
    </div>
</div>

<div class="toast" id="toast"></div>

<script>
$(document).ready(function () {

    $(".menuicn").on("click", function () {
        $(".navcontainer").toggleClass("navclose");
    });

    function showToast(msg) {
        $("#toast").text(msg).fadeIn(200).delay(2200).fadeOut(400);
    }

    // ── Charger les catégories ────────────────────────────────────────────────
    function loadCategories() {
        $.getJSON("categories.php?action=categories", function (cats) {

            $("#catCount").text(cats.length);

            if (!cats.length) {
                $("#catsTableWrap").html('<p style="text-align:center;padding:40px;color:#bbb;">No categories yet.</p>');
                return;
            }

            var html = '<table><thead><tr><th>Name</th><th>Books</th><th>Actions</th></tr></thead><tbody>';

            $.each(cats, function (i, cat) {
                html += '<tr data-id="'+cat.idCat+'">';
                html += '<td><a href="category-detail.php?id='+cat.idCat+'" style="color:var(--secondary);font-weight:600;text-decoration:underline;">'+$('<div>').text(cat.nomCat).html()+'</a></td>';
                html += '<td><strong style="color:var(--secondary);">'+cat.bookCount+' book'+(cat.bookCount!=1?'s':'')+'</strong></td>';
                html += '<td>';
                html += '<a class="btn btn-warning" href="category-detail.php?id='+cat.idCat+'">View</a> ';
                html += '<button class="btn btn-danger btn-delete-cat" data-id="'+cat.idCat+'" data-name="'+$('<div>').text(cat.nomCat).html()+'">Delete</button>';
                html += '</td>';
                html += '</tr>';
            });

            html += '</tbody></table>';
            $("#catsTableWrap").html(html);

        }).fail(function () {
            $("#catsTableWrap").html('<p style="color:red;padding:20px;">Failed to load categories.</p>');
        });
    }

    loadCategories();

    // ── Ajouter une catégorie via fetch POST ──────────────────────────────────
    $("#btnAddCat").on("click", function () {
        var nom = $("#newCatName").val().trim();

        if (!nom) {
            $("#addCatError").text("Please enter a category name.").show();
            return;
        }
        $("#addCatError").hide();

        $.post("categories.php?action=add_category", { nomCat: nom }, function (res) {
            if (res.success) {
                $("#newCatName").val('');
                showToast("✅ Category \"" + res.nomCat + "\" added.");
                // recharger la liste pour afficher la nouvelle catégorie
                loadCategories();
            } else {
                $("#addCatError").text(res.error || "Failed to add category.").show();
            }
        }, "json").fail(function () {
            showToast("❌ Server error.");
        });
    });

    // permettre l'ajout avec la touche Entrée
    $("#newCatName").on("keyup", function (e) {
        if (e.key === "Enter") $("#btnAddCat").trigger("click");
    });

    // ── Supprimer une catégorie via fetch POST ────────────────────────────────
    $(document).on("click", ".btn-delete-cat", function () {
        var id   = $(this).data("id");
        var name = $(this).data("name");

        if (!confirm('Delete category "' + name + '"? Books in this category will be unlinked.')) return;

        var $row = $(this).closest("tr");

        $.post("categories.php?action=delete_category", { id: id }, function (res) {
            if (res.success) {
                $row.fadeOut(300, function () {
                    $(this).remove();
                    var count = parseInt($("#catCount").text()) - 1;
                    $("#catCount").text(count);
                });
                showToast("✅ Category deleted.");
            } else {
                showToast("❌ " + (res.error || "Failed to delete."));
            }
        }, "json").fail(function () {
            showToast("❌ Server error.");
        });
    });

});
</script>
</body>
</html>
