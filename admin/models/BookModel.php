<?php
class BookModel {
    private PDO $pdo;

    public function __construct() {
        $this->pdo = Database::getInstance();
    }

    public function findAll(): array {
        return $this->pdo->query("
            SELECT l.*,
                   GROUP_CONCAT(DISTINCT c.nomCat SEPARATOR ', ') AS categories,
                   CONCAT(a.prenom, ' ', a.nom) AS auteur
            FROM livre l
            LEFT JOIN livre_categorie lc ON l.idLivre  = lc.idLivre
            LEFT JOIN categorie c        ON lc.idCat   = c.idCat
            LEFT JOIN livre_auteur la    ON l.idLivre  = la.idLivre
            LEFT JOIN auteur a           ON la.idAuteur = a.idAuteur
            GROUP BY l.idLivre
            ORDER BY l.createdAt DESC
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): array|false {
        $stmt = $this->pdo->prepare("
            SELECT l.*, a.idAuteur, a.nom AS auteurNom, a.prenom AS auteurPrenom,
                   a.description AS auteurDesc, a.status AS auteurStatus,
                   a.dateNaiss AS auteurDate, a.image AS auteurImage
            FROM livre l
            LEFT JOIN livre_auteur la ON l.idLivre = la.idLivre
            LEFT JOIN auteur a ON la.idAuteur = a.idAuteur
            WHERE l.idLivre = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function update(int $id, array $data): void {
        $this->pdo->prepare("
            UPDATE livre SET titre=?, description=?, prix=?, stock=?, image=?
            WHERE idLivre=?
        ")->execute([$data['titre'], $data['description'], $data['prix'], $data['stock'], $data['image'], $id]);
    }
    public function updateCategories(int $id, array $catIds): void {
    // Delete existing categories for this book
    $this->pdo->prepare("DELETE FROM livre_categorie WHERE idLivre = ?")->execute([$id]);

    // Insert new ones
    $stmt = $this->pdo->prepare("INSERT INTO livre_categorie (idLivre, idCat) VALUES (?, ?)");
    foreach ($catIds as $catId) {
        $stmt->execute([$id, (int)$catId]);
    }
}

    public function delete(int $id): void {
        $this->pdo->prepare("DELETE FROM livre_auteur    WHERE idLivre=?")->execute([$id]);
        $this->pdo->prepare("DELETE FROM livre_categorie WHERE idLivre=?")->execute([$id]);
        $this->pdo->prepare("DELETE FROM ligne_commande  WHERE idLivre=?")->execute([$id]);
        $this->pdo->prepare("DELETE FROM livre           WHERE idLivre=?")->execute([$id]);
    }
    public function getAllCategories(): array {
    return $this->pdo
        ->query("SELECT idCat, nomCat FROM categorie ORDER BY nomCat")
        ->fetchAll(PDO::FETCH_ASSOC);
}

public function getCategoriesOfBook(int $id): array {
    $stmt = $this->pdo->prepare("
        SELECT idCat FROM livre_categorie WHERE idLivre = ?
    ");
    $stmt->execute([$id]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN); // returns flat array of IDs [1, 3, 5]
}
}
