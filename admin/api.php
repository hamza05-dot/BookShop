<?php
// api.php — point d'entrée unique pour tous les appels AJAX du dashboard admin
// Toutes les pages utilisent fetch() vers ce fichier au lieu d'avoir du PHP dans les vues

session_start();
require_once '../includes/db.php';

// Vérifier que l'utilisateur est connecté comme admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Accès refusé']);
    exit;
}

// On retourne toujours du JSON
header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);

$pdo = Database::getInstance();

// Action demandée dans l'URL : api.php?action=dashboard_stats
$action = $_GET['action'] ?? '';

switch ($action) {

    // ── DASHBOARD ─────────────────────────────────────────────────────────────

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

        // Graphique 1 : nombre de commandes par mois (6 derniers mois)
        $ordersByMonth = $pdo->query("
            SELECT DATE_FORMAT(createdAt, '%b %Y') AS mois,
                   COUNT(*) AS total
            FROM commande
            WHERE createdAt >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
            GROUP BY DATE_FORMAT(createdAt, '%Y-%m')
            ORDER BY MIN(createdAt) ASC
        ")->fetchAll(PDO::FETCH_ASSOC);

        // Graphique 2 : répartition des commandes par statut
        $orderStatus = $pdo->query("
            SELECT status, COUNT(*) AS total
            FROM commande
            GROUP BY status
        ")->fetchAll(PDO::FETCH_ASSOC);

        // Graphique 3 : top 5 catégories par nombre de livres
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

    // ── LIVRES ────────────────────────────────────────────────────────────────

    case 'books':
        // Liste complète avec auteur(s) et catégorie(s)
        $rows = $pdo->query("
            SELECT l.idLivre, l.titre, l.prix, l.stock, l.image, l.createdAt,
                   GROUP_CONCAT(DISTINCT c.nomCat SEPARATOR ', ')                   AS categories,
                   GROUP_CONCAT(DISTINCT CONCAT(a.prenom, ' ', a.nom) SEPARATOR ', ') AS auteur
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

    // ── AUTEURS ───────────────────────────────────────────────────────────────

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

    // ── COMMANDES ─────────────────────────────────────────────────────────────

    case 'orders':
        // Liste des commandes avec filtres optionnels (status et search)
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

        $sql = "
            SELECT c.idCom, c.status, c.total, c.createdAt,
                   u.nomUser, u.prenomUser
            FROM commande c
            JOIN utilisateur u ON c.idClient = u.idUser
            " . ($where ? 'WHERE ' . implode(' AND ', $where) : '') . "
            ORDER BY c.createdAt DESC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        break;

    case 'order_update_status':
        // Changer le statut d'une commande (appelé en POST)
        $idCom  = (int)($_POST['idCom']  ?? 0);
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

    // ── UTILISATEURS ──────────────────────────────────────────────────────────

    case 'users':
        // Retourne admins et clients séparément
        $admins = $pdo->query("
            SELECT u.idUser, u.nomUser, u.prenomUser, u.email, u.image, u.createdAt
            FROM utilisateur u
            INNER JOIN admin a ON u.idUser = a.idUser
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

    // ── CATEGORIES ────────────────────────────────────────────────────────────

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

    // ── AVIS ──────────────────────────────────────────────────────────────────

    case 'reviews':
        // Tous les avis avec infos livre et client
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

        // Stats supplémentaires pour les cartes en haut de la page
        $total    = count($rows);
        $avg      = $total ? round(array_sum(array_column($rows, 'note')) / $total, 1) : 0;
        $fiveStars = count(array_filter($rows, fn($r) => (int)$r['note'] === 5));

        echo json_encode([
            'reviews'   => $rows,
            'total'     => $total,
            'avgNote'   => $avg,
            'fiveStars' => $fiveStars,
        ]);
        break;

    // ── ACTION INCONNUE ───────────────────────────────────────────────────────

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Action inconnue : ' . htmlspecialchars($action)]);
        break;
}
