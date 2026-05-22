<?php
class UserModel {
    private PDO $pdo;

    public function __construct() {
        $this->pdo = Database::getInstance();
    }

    public function findAdmins(): array {
        return $this->pdo->query("
            SELECT u.* FROM utilisateur u
            INNER JOIN admin a ON u.idUser = a.idUser
            ORDER BY u.nomUser ASC
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findClients(): array {
        return $this->pdo->query("
            SELECT u.*, c.telephone, c.adresse, c.ville, c.dateNaiss
            FROM utilisateur u
            LEFT JOIN client c ON u.idUser = c.idUser
            WHERE u.idUser NOT IN (SELECT idUser FROM admin)
            ORDER BY u.createdAt DESC
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function promoteToAdmin(int $id): string {
        $check = $this->pdo->prepare("SELECT * FROM admin WHERE idUser = ?");
        $check->execute([$id]);
        if ($check->fetch()) return "This user is already an admin.";
        $this->pdo->prepare("INSERT INTO admin (idUser) VALUES (?)")->execute([$id]);
        return "User promoted to admin.";
    }

    public function removeAdmin(int $id): void {
        $this->pdo->prepare("DELETE FROM admin WHERE idUser=?")->execute([$id]);
    }

public function delete(int $id): void {
    // 1. Delete order items linked to this user's orders
    $this->pdo->prepare("
        DELETE FROM ligne_commande 
        WHERE idCom IN (SELECT idCom FROM commande WHERE idClient = ?)
    ")->execute([$id]);

    // 2. Delete orders
    $this->pdo->prepare("
        DELETE FROM commande WHERE idClient = ?
    ")->execute([$id]);

    // 3. Delete from client table
    $this->pdo->prepare("
        DELETE FROM client WHERE idUser = ?
    ")->execute([$id]);

    // 4. Delete from admin table (if promoted)
    $this->pdo->prepare("
        DELETE FROM admin WHERE idUser = ?
    ")->execute([$id]);

    // 5. Finally delete the user
    $this->pdo->prepare("
        DELETE FROM utilisateur WHERE idUser = ?
    ")->execute([$id]);
}
}
