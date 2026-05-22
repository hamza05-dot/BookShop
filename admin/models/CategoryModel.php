<?php
class CategoryModel {
    private PDO $pdo;

    public function __construct() {
        $this->pdo = Database::getInstance();
    }

    public function findAll(): array {
        return $this->pdo->query("
            SELECT c.*, COUNT(lc.idLivre) AS bookCount
            FROM categorie c
            LEFT JOIN livre_categorie lc ON c.idCat = lc.idCat
            GROUP BY c.idCat
            ORDER BY bookCount DESC
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): array|false {
        $stmt = $this->pdo->prepare("SELECT * FROM categorie WHERE idCat = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function findBooksByCategory(int $id): array {
        $stmt = $this->pdo->prepare("
            SELECT l.*, GROUP_CONCAT(DISTINCT CONCAT(a.prenom,' ',a.nom) SEPARATOR ', ') AS auteur
            FROM livre l
            INNER JOIN livre_categorie lc ON l.idLivre = lc.idLivre
            LEFT JOIN livre_auteur la ON l.idLivre = la.idLivre
            LEFT JOIN auteur a ON la.idAuteur = a.idAuteur
            WHERE lc.idCat = ?
            GROUP BY l.idLivre ORDER BY l.titre ASC
        ");
        $stmt->execute([$id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(string $nomCat): void {
        $this->pdo->prepare("INSERT INTO categorie (nomCat) VALUES (?)")->execute([$nomCat]);
    }

    public function delete(int $id): void {
        $this->pdo->prepare("DELETE FROM livre_categorie WHERE idCat=?")->execute([$id]);
        $this->pdo->prepare("DELETE FROM categorie WHERE idCat=?")->execute([$id]);
    }
}
