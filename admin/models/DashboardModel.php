<?php
class DashboardModel {
    private PDO $pdo;

    public function __construct() {
        $this->pdo = Database::getInstance();
    }

    // Statistiques des 4 cartes en haut du dashboard
    public function getStats(): array {
        return [
            'livres'    => (int)$this->pdo->query("SELECT COUNT(*) FROM livre")->fetchColumn(),
            'clients'   => (int)$this->pdo->query("SELECT COUNT(*) FROM client")->fetchColumn(),
            'commandes' => (int)$this->pdo->query("SELECT COUNT(*) FROM commande")->fetchColumn(),
            'enAttente' => (int)$this->pdo->query("SELECT COUNT(*) FROM commande WHERE status='en attente'")->fetchColumn(),
        ];
    }

    // 5 dernières commandes pour la table du dashboard
    public function getRecentOrders(): array {
        return $this->pdo->query("
            SELECT c.idCom, c.status, c.total, c.createdAt, u.nomUser, u.prenomUser
            FROM commande c
            JOIN utilisateur u ON c.idClient = u.idUser
            ORDER BY c.createdAt DESC LIMIT 5
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    // Données pour les 3 graphiques Chart.js
    public function getChartData(): array {

        // commandes par mois sur les 6 derniers mois
        $ordersByMonth = $this->pdo->query("
            SELECT DATE_FORMAT(createdAt, '%b %Y') AS mois, COUNT(*) AS total
            FROM commande
            WHERE createdAt >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
            GROUP BY DATE_FORMAT(createdAt, '%Y-%m')
            ORDER BY MIN(createdAt) ASC
        ")->fetchAll(PDO::FETCH_ASSOC);

        // répartition par statut
        $orderStatus = $this->pdo->query("
            SELECT status, COUNT(*) AS total FROM commande GROUP BY status
        ")->fetchAll(PDO::FETCH_ASSOC);

        // top 5 catégories par nombre de livres
        $topCategories = $this->pdo->query("
            SELECT c.nomCat, COUNT(lc.idLivre) AS total
            FROM categorie c
            JOIN livre_categorie lc ON c.idCat = lc.idCat
            GROUP BY c.idCat
            ORDER BY total DESC
            LIMIT 5
        ")->fetchAll(PDO::FETCH_ASSOC);

        return [
            'ordersByMonth' => $ordersByMonth,
            'orderStatus'   => $orderStatus,
            'topCategories' => $topCategories,
        ];
    }
}
