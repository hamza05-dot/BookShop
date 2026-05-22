<?php
class CategoryModel {
    private PDO $pdo;

    public function __construct() {
        $this->pdo = Database::getInstance();
    }

    // Toutes les catégories avec le nombre de livres
    public function findAll(): array {
        return $this->pdo->query("
            SELECT c.idCat, c.nomCat, COUNT(lc.idLivre) AS bookCount
            FROM categorie c
            LEFT JOIN livre_categorie lc ON c.idCat = lc.idCat
            GROUP BY c.idCat
            ORDER BY c.nomCat ASC
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    // Une catégorie par ID (pour la page de détail)
    public function findById(int $id): array|false {
        $stmt = $this->pdo->prepare("SELECT * FROM categorie WHERE idCat=?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Livres d'une catégorie (pour la page de détail)
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

    // Ajouter une catégorie
    public function create(string $nomCat): int {
        $this->pdo->prepare("INSERT INTO categorie (nomCat) VALUES (?)")->execute([$nomCat]);
        return (int)$this->pdo->lastInsertId();
    }

    // Vérifier si une catégorie existe déjà
    public function existsByName(string $nomCat): bool {
        $stmt = $this->pdo->prepare("SELECT idCat FROM categorie WHERE nomCat=?");
        $stmt->execute([$nomCat]);
        return (bool)$stmt->fetch();
    }

    // Supprimer une catégorie et ses liaisons avec les livres
    public function delete(int $id): void {
        $this->pdo->prepare("DELETE FROM livre_categorie WHERE idCat=?")->execute([$id]);
        $this->pdo->prepare("DELETE FROM categorie        WHERE idCat=?")->execute([$id]);
    }
}
