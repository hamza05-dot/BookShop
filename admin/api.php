<?php
// api.php — point d'entrée unique pour tous les appels AJAX (lecture ET écriture)
// Toutes les vues utilisent $.getJSON() ou $.post() vers ce fichier

session_start();
require_once '../includes/db.php';

// Seuls les admins connectés peuvent appeler cette API
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Accès refusé']);
    exit;
}

header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);

$pdo    = Database::getInstance();
$action = $_GET['action'] ?? '';

switch ($action) {

    // ══════════════════════════════════════════════════════════════════════════
    // LECTURE (GET)
    // ══════════════════════════════════════════════════════════════════════════

    // ── Dashboard ─────────────────────────────────────────────────────────────

    case 'dashboard_stats':
        // Les 4 chiffres des cartes en haut du dashboard
        echo json_encode([
            'livres'    => (int)$pdo->query("SELECT COUNT(*) FROM livre")->fetchColumn(),
            'clients'   => (int)$pdo->query("SELECT COUNT(*) FROM client")->fetchColumn(),
            'commandes' => (int)$pdo->query("SELECT COUNT(*) FROM commande")->fetchColumn(),
            'enAttente' => (int)$pdo->query("SELECT COUNT(*) FROM commande WHERE status='en attente'")->fetchColumn(),
        ]);
        break;

    case 'dashboard_orders':
        // Les 5 dernières commandes pour la table du dashboard
        $rows = $pdo->query("
            SELECT c.idCom, c.status, c.total, c.createdAt,
                   u.nomUser, u.prenomUser
            FROM commande c
            JOIN utilisateur u ON c.idClient = u.idUser
            ORDER BY c.createdAt DESC
            LIMIT 5
        ")->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($rows);
        break;

    case 'dashboard_charts':
        // Données pour les 3 graphiques Chart.js

        // Commandes par mois sur les 6 derniers mois
        $ordersByMonth = $pdo->query("
            SELECT DATE_FORMAT(createdAt, '%b %Y') AS mois, COUNT(*) AS total
            FROM commande
            WHERE createdAt >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
            GROUP BY DATE_FORMAT(createdAt, '%Y-%m')
            ORDER BY MIN(createdAt) ASC
        ")->fetchAll(PDO::FETCH_ASSOC);

        // Répartition par statut
        $orderStatus = $pdo->query("
            SELECT status, COUNT(*) AS total FROM commande GROUP BY status
        ")->fetchAll(PDO::FETCH_ASSOC);

        // Top 5 catégories par nombre de livres
        $topCategories = $pdo->query("
            SELECT c.nomCat, COUNT(lc.idLivre) AS total
            FROM categorie c
            JOIN livre_categorie lc ON c.idCat = lc.idCat
            GROUP BY c.idCat
            ORDER BY total DESC
            LIMIT 5
        ")->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'ordersByMonth' => $ordersByMonth,
            'orderStatus'   => $orderStatus,
            'topCategories' => $topCategories,
        ]);
        break;

    // ── Livres ────────────────────────────────────────────────────────────────

    case 'books':
        // Liste complète avec auteur(s) et catégorie(s)
        $rows = $pdo->query("
            SELECT l.idLivre, l.titre, l.prix, l.stock, l.image, l.createdAt,
                   GROUP_CONCAT(DISTINCT c.nomCat SEPARATOR ', ')                      AS categories,
                   GROUP_CONCAT(DISTINCT CONCAT(a.prenom,' ',a.nom) SEPARATOR ', ')   AS auteur
            FROM livre l
            LEFT JOIN livre_categorie lc ON l.idLivre  = lc.idLivre
            LEFT JOIN categorie c        ON lc.idCat   = c.idCat
            LEFT JOIN livre_auteur la    ON l.idLivre  = la.idLivre
            LEFT JOIN auteur a           ON la.idAuteur = a.idAuteur
            GROUP BY l.idLivre
            ORDER BY l.createdAt DESC
        ")->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($rows);
        break;

    // ── Auteurs ───────────────────────────────────────────────────────────────

    case 'authors':
        // Tous les auteurs avec leur nombre de livres
        $rows = $pdo->query("
            SELECT a.idAuteur, a.nom, a.prenom, a.status, a.dateNaiss, a.image,
                   COUNT(la.idLivre) AS bookCount
            FROM auteur a
            LEFT JOIN livre_auteur la ON a.idAuteur = la.idAuteur
            GROUP BY a.idAuteur
            ORDER BY a.nom ASC
        ")->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($rows);
        break;

    // ── Commandes ─────────────────────────────────────────────────────────────

    case 'orders':
        // Liste avec filtres optionnels status et search
        $filterStatus = trim($_GET['status'] ?? '');
        $filterSearch = trim($_GET['search'] ?? '');
        $where  = [];
        $params = [];

        if ($filterStatus !== '') {
            $where[]  = "c.status = ?";
            $params[] = $filterStatus;
        }
        if ($filterSearch !== '') {
            $where[]  = "(c.idCom LIKE ? OR u.nomUser LIKE ? OR u.prenomUser LIKE ?)";
            $like     = '%' . $filterSearch . '%';
            $params   = array_merge($params, [$like, $like, $like]);
        }

        $sql  = "SELECT c.idCom, c.status, c.total, c.createdAt, u.nomUser, u.prenomUser
                 FROM commande c JOIN utilisateur u ON c.idClient = u.idUser "
              . ($where ? 'WHERE '.implode(' AND ', $where) : '')
              . " ORDER BY c.createdAt DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        break;

    // ── Utilisateurs ──────────────────────────────────────────────────────────

    case 'users':
        // Retourne admins et clients séparément
        $admins = $pdo->query("
            SELECT u.idUser, u.nomUser, u.prenomUser, u.email, u.image, u.createdAt
            FROM utilisateur u INNER JOIN admin a ON u.idUser = a.idUser
            ORDER BY u.nomUser ASC
        ")->fetchAll(PDO::FETCH_ASSOC);

        $clients = $pdo->query("
            SELECT u.idUser, u.nomUser, u.prenomUser, u.email, u.image, u.createdAt,
                   c.telephone, c.adresse, c.ville, c.dateNaiss
            FROM utilisateur u
            LEFT JOIN client c ON u.idUser = c.idUser
            WHERE u.idUser NOT IN (SELECT idUser FROM admin)
            ORDER BY u.createdAt DESC
        ")->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['admins' => $admins, 'clients' => $clients]);
        break;

    // ── Catégories ────────────────────────────────────────────────────────────

    case 'categories':
        // Toutes les catégories avec le nombre de livres
        $rows = $pdo->query("
            SELECT c.idCat, c.nomCat, COUNT(lc.idLivre) AS bookCount
            FROM categorie c
            LEFT JOIN livre_categorie lc ON c.idCat = lc.idCat
            GROUP BY c.idCat
            ORDER BY c.nomCat ASC
        ")->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($rows);
        break;

    // ── Avis ──────────────────────────────────────────────────────────────────

    case 'reviews':
        // Tous les avis avec stats (total, moyenne, 5 étoiles)
        $rows = $pdo->query("
            SELECT av.idAvis, av.note, av.commentaire, av.createdAt,
                   l.titre, l.idLivre, u.nomUser, u.prenomUser
            FROM avis av
            JOIN ligne_commande lc ON av.idLigneCom = lc.idLigneCom
            JOIN livre l           ON lc.idLivre    = l.idLivre
            JOIN commande c        ON lc.idCom      = c.idCom
            JOIN utilisateur u     ON c.idClient    = u.idUser
            ORDER BY av.createdAt DESC
        ")->fetchAll(PDO::FETCH_ASSOC);

        $total     = count($rows);
        $avg       = $total ? round(array_sum(array_column($rows, 'note')) / $total, 1) : 0;
        $fiveStars = count(array_filter($rows, fn($r) => (int)$r['note'] === 5));

        echo json_encode(['reviews' => $rows, 'total' => $total, 'avgNote' => $avg, 'fiveStars' => $fiveStars]);
        break;

    // ══════════════════════════════════════════════════════════════════════════
    // ÉCRITURE (POST)
    // ══════════════════════════════════════════════════════════════════════════

    // ── Changement statut commande ────────────────────────────────────────────

    case 'order_update_status':
        $idCom  = (int)($_POST['idCom'] ?? 0);
        $status = trim($_POST['status'] ?? '');
        $allowed = ['en attente', 'confirmee', 'livree', 'annulee'];

        if (!$idCom || !in_array($status, $allowed)) {
            http_response_code(400);
            echo json_encode(['error' => 'Données invalides']);
            break;
        }
        $pdo->prepare("UPDATE commande SET status=? WHERE idCom=?")->execute([$status, $idCom]);
        echo json_encode(['success' => true]);
        break;

    // ── Supprimer un livre ────────────────────────────────────────────────────

    case 'delete_book':
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { http_response_code(400); echo json_encode(['error' => 'ID manquant']); break; }

        // supprimer les liaisons avant le livre lui-même
        $pdo->prepare("DELETE FROM livre_categorie WHERE idLivre=?")->execute([$id]);
        $pdo->prepare("DELETE FROM livre_auteur    WHERE idLivre=?")->execute([$id]);
        $pdo->prepare("DELETE FROM livre           WHERE idLivre=?")->execute([$id]);
        echo json_encode(['success' => true]);
        break;

    // ── Supprimer un auteur ───────────────────────────────────────────────────

    case 'delete_author':
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { http_response_code(400); echo json_encode(['error' => 'ID manquant']); break; }

        $pdo->prepare("DELETE FROM livre_auteur WHERE idAuteur=?")->execute([$id]);
        $pdo->prepare("DELETE FROM auteur        WHERE idAuteur=?")->execute([$id]);
        echo json_encode(['success' => true]);
        break;

    // ── Supprimer une catégorie ───────────────────────────────────────────────

    case 'delete_category':
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { http_response_code(400); echo json_encode(['error' => 'ID manquant']); break; }

        $pdo->prepare("DELETE FROM livre_categorie WHERE idCat=?")->execute([$id]);
        $pdo->prepare("DELETE FROM categorie        WHERE idCat=?")->execute([$id]);
        echo json_encode(['success' => true]);
        break;

    // ── Ajouter une catégorie ─────────────────────────────────────────────────

    case 'add_category':
        $nom = trim($_POST['nomCat'] ?? '');
        if (!$nom) { http_response_code(400); echo json_encode(['error' => 'Nom manquant']); break; }

        // vérifier si la catégorie existe déjà
        $check = $pdo->prepare("SELECT idCat FROM categorie WHERE nomCat=?");
        $check->execute([$nom]);
        if ($check->fetch()) {
            echo json_encode(['error' => 'Cette catégorie existe déjà']);
            break;
        }

        $pdo->prepare("INSERT INTO categorie (nomCat) VALUES (?)")->execute([$nom]);
        $newId = $pdo->lastInsertId();
        echo json_encode(['success' => true, 'idCat' => $newId, 'nomCat' => $nom]);
        break;

    // ── Supprimer un avis ─────────────────────────────────────────────────────

    case 'delete_review':
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { http_response_code(400); echo json_encode(['error' => 'ID manquant']); break; }

        $pdo->prepare("DELETE FROM avis WHERE idAvis=?")->execute([$id]);
        echo json_encode(['success' => true]);
        break;

    // ── Supprimer un utilisateur ──────────────────────────────────────────────

    case 'delete_user':
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { http_response_code(400); echo json_encode(['error' => 'ID manquant']); break; }

        // on ne peut pas se supprimer soi-même
        if ($id === (int)$_SESSION['idUser']) {
            echo json_encode(['error' => 'Vous ne pouvez pas vous supprimer vous-même']);
            break;
        }

        // supprimer dans l'ordre pour respecter les clés étrangères
        $pdo->prepare("DELETE FROM ligne_commande WHERE idCom IN (SELECT idCom FROM commande WHERE idClient=?)")->execute([$id]);
        $pdo->prepare("DELETE FROM commande    WHERE idClient=?")->execute([$id]);
        $pdo->prepare("DELETE FROM client      WHERE idUser=?")->execute([$id]);
        $pdo->prepare("DELETE FROM admin       WHERE idUser=?")->execute([$id]);
        $pdo->prepare("DELETE FROM utilisateur WHERE idUser=?")->execute([$id]);
        echo json_encode(['success' => true]);
        break;

    // ── Promouvoir un client en admin ─────────────────────────────────────────

    case 'promote_admin':
        $id = (int)($_POST['idUser'] ?? 0);
        if (!$id) { http_response_code(400); echo json_encode(['error' => 'ID manquant']); break; }

        // vérifier qu'il n'est pas déjà admin
        $check = $pdo->prepare("SELECT idUser FROM admin WHERE idUser=?");
        $check->execute([$id]);
        if ($check->fetch()) {
            echo json_encode(['error' => 'Cet utilisateur est déjà admin']);
            break;
        }

        $pdo->prepare("INSERT INTO admin (idUser) VALUES (?)")->execute([$id]);
        echo json_encode(['success' => true]);
        break;

    // ── Retirer le rôle admin ─────────────────────────────────────────────────

    case 'remove_admin':
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { http_response_code(400); echo json_encode(['error' => 'ID manquant']); break; }

        // on ne peut pas se retirer le rôle admin soi-même
        if ($id === (int)$_SESSION['idUser']) {
            echo json_encode(['error' => 'Vous ne pouvez pas vous retirer le rôle admin']);
            break;
        }

        $pdo->prepare("DELETE FROM admin WHERE idUser=?")->execute([$id]);
        echo json_encode(['success' => true]);
        break;

    // ── Action inconnue ───────────────────────────────────────────────────────

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Action inconnue : ' . htmlspecialchars($action)]);
        break;
}
