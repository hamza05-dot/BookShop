<?php
class AuthorModel {
    private PDO $pdo;

    public function __construct() {
        $this->pdo = Database::getInstance();
    }

    public function findAll(): array {
        return $this->pdo->query("
            SELECT a.*, COUNT(la.idLivre) AS bookCount
            FROM auteur a
            LEFT JOIN livre_auteur la ON a.idAuteur = la.idAuteur
            GROUP BY a.idAuteur
            ORDER BY a.nom ASC
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): array|false {
        $stmt = $this->pdo->prepare("SELECT * FROM auteur WHERE idAuteur = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function findBooksByAuthor(int $id): array {
        $stmt = $this->pdo->prepare("
            SELECT l.*,
                   GROUP_CONCAT(DISTINCT c.nomCat SEPARATOR ', ') AS categories
            FROM livre l
            INNER JOIN livre_auteur la    ON l.idLivre = la.idLivre
            LEFT  JOIN livre_categorie lc ON l.idLivre = lc.idLivre
            LEFT  JOIN categorie c        ON lc.idCat  = c.idCat
            WHERE la.idAuteur = ?
            GROUP BY l.idLivre
            ORDER BY l.titre ASC
        ");
        $stmt->execute([$id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function update(int $id, array $data): void {
        $this->pdo->prepare("
            UPDATE auteur
            SET nom=?, prenom=?, description=?, status=?, dateNaiss=?, image=?
            WHERE idAuteur = ?
        ")->execute([
            $data['nom'], $data['prenom'], $data['description'],
            $data['status'], $data['dateNaiss'], $data['image'], $id,
        ]);
    }

    // Suppression atomique
    public function delete(int $id): void {
        $this->pdo->beginTransaction();
        try {
            $this->pdo->prepare("DELETE FROM livre_auteur WHERE idAuteur = ?")->execute([$id]);
            $this->pdo->prepare("DELETE FROM auteur        WHERE idAuteur = ?")->execute([$id]);
            $this->pdo->commit();
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
}
