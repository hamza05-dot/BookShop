<?php
class OrderModel {
    private PDO $pdo;

    public function __construct() {
        $this->pdo = Database::getInstance();
    }

    public function findAll(string $filterStatus = '', string $filterSearch = ''): array {
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
            SELECT c.idCom, c.status, c.total, c.createdAt, u.nomUser, u.prenomUser
            FROM commande c
            JOIN utilisateur u ON c.idClient = u.idUser
            " . ($where ? 'WHERE ' . implode(' AND ', $where) : '') . "
            ORDER BY c.createdAt DESC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): array|false {
    $stmt = $this->pdo->prepare("
        SELECT c.idCom, c.status, c.total, c.createdAt,
               u.idUser, u.nomUser, u.prenomUser, u.email, u.createdAt AS memberSince,
               u.image AS userImage,
               cl.telephone, cl.adresse, cl.ville, cl.dateNaiss
        FROM commande c
        JOIN utilisateur u ON c.idClient = u.idUser
        LEFT JOIN client cl ON u.idUser = cl.idUser
        WHERE c.idCom = ?
    ");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

    public function findLines(int $id): array {
        $stmt = $this->pdo->prepare("
            SELECT lc.quantite, lc.prixUnit, l.idLivre, l.titre, l.image AS bookImage
            FROM ligne_commande lc
            JOIN livre l ON lc.idLivre = l.idLivre
            WHERE lc.idCom = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateStatus(int $id, string $status): void {
        $this->pdo->prepare("UPDATE commande SET status=? WHERE idCom=?")->execute([$status, $id]);
    }
}