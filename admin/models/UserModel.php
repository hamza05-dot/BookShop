<?php
class UserModel {
    private PDO $pdo;

    public function __construct() {
        $this->pdo = Database::getInstance();
    }

    public function findAdmins(): array {
        return $this->pdo->query("
            SELECT u.idUser, u.nomUser, u.prenomUser, u.email, u.image, u.createdAt
            FROM utilisateur u
            INNER JOIN admin a ON u.idUser = a.idUser
            ORDER BY u.nomUser ASC
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findClients(): array {
        return $this->pdo->query("
            SELECT u.idUser, u.nomUser, u.prenomUser, u.email, u.image, u.createdAt,
                   c.telephone, c.adresse, c.ville, c.dateNaiss
            FROM utilisateur u
            LEFT JOIN client c ON u.idUser = c.idUser
            WHERE u.idUser NOT IN (SELECT idUser FROM admin)
            ORDER BY u.createdAt DESC
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function isAdmin(int $id): bool {
        $stmt = $this->pdo->prepare("SELECT idUser FROM admin WHERE idUser = ?");
        $stmt->execute([$id]);
        return (bool) $stmt->fetch();
    }

    public function promoteToAdmin(int $id): void {
        $this->pdo->prepare("INSERT INTO admin (idUser) VALUES (?)")->execute([$id]);
    }

    public function removeAdmin(int $id): void {
        $this->pdo->prepare("DELETE FROM admin WHERE idUser = ?")->execute([$id]);
    }

    // Suppression atomique : tout réussit ou rien ne change
    public function delete(int $id): void {
        $this->pdo->beginTransaction();
        try {
            $this->pdo->prepare(
                "DELETE FROM avis
                 WHERE idLigneCom IN (
                     SELECT lc.idLigneCom FROM ligne_commande lc
                     JOIN commande c ON lc.idCom = c.idCom
                     WHERE c.idClient = ?
                 )"
            )->execute([$id]);
            $this->pdo->prepare(
                "DELETE FROM ligne_commande WHERE idCom IN (SELECT idCom FROM commande WHERE idClient = ?)"
            )->execute([$id]);
            $this->pdo->prepare("DELETE FROM commande    WHERE idClient = ?")->execute([$id]);
            $this->pdo->prepare("DELETE FROM client      WHERE idUser   = ?")->execute([$id]);
            $this->pdo->prepare("DELETE FROM admin       WHERE idUser   = ?")->execute([$id]);
            $this->pdo->prepare("DELETE FROM utilisateur WHERE idUser   = ?")->execute([$id]);
            $this->pdo->commit();
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
}
