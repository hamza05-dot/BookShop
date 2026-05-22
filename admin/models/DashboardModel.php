<?php
class DashboardModel {
    private PDO $pdo;

    public function __construct() {
        $this->pdo = Database::getInstance();
    }

    public function getStats(): array {
        return [
            'livres'    => $this->pdo->query("SELECT COUNT(*) FROM livre")->fetchColumn(),
            'clients'   => $this->pdo->query("SELECT COUNT(*) FROM client")->fetchColumn(),
            'commandes' => $this->pdo->query("SELECT COUNT(*) FROM commande")->fetchColumn(),
            'enAttente' => $this->pdo->query("SELECT COUNT(*) FROM commande WHERE status='en attente'")->fetchColumn(),
        ];
    }

    public function getRecentOrders(): array {
        return $this->pdo->query("
            SELECT c.idCom, c.status, c.total, c.createdAt, u.nomUser, u.prenomUser
            FROM commande c
            JOIN utilisateur u ON c.idClient = u.idUser
            ORDER BY c.createdAt DESC LIMIT 5
        ")->fetchAll(PDO::FETCH_ASSOC);
    }
}
