<?php
class BookModel {
    private PDO $pdo;

    public function __construct() {
        $this->pdo = Database::getInstance();
    }

    // Liste complète des livres avec auteur(s) et catégorie(s)
    public function findAll(): array {
        return $this->pdo->query("
            SELECT l.idLivre, l.titre, l.prix, l.stock, l.image, l.createdAt,
                   GROUP_CONCAT(DISTINCT c.nomCat SEPARATOR ', ')                      AS categories,
                   GROUP_CONCAT(DISTINCT CONCAT(a.prenom,' ',a.nom) SEPARATOR ', ')   AS auteur
            FROM livre l
            LEFT JOIN livre_categorie lc ON l.idLivre  = lc.idLivre
            LEFT JOIN categorie c        ON lc.idCat   = c.idCat
            LEFT JOIN livre_auteur la    ON l.idLivre  = la.idLivre
            LEFT JOIN auteur a           ON la.idAuteur = a.idAuteur
            GROUP BY l.idLivre
            ORDER BY l.createdAt DESC
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    // Un seul livre par son ID (pour la page de détail)
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

    // Mettre à jour les infos d'un livre (sans image upload)
    public function update(int $id, array $data): void {
        $this->pdo->prepare("
            UPDATE livre SET titre=?, description=?, prix=?, stock=?, image=?
            WHERE idLivre=?
        ")->execute([$data['titre'], $data['description'], $data['prix'], $data['stock'], $data['image'], $id]);
    }

    // Mettre à jour les catégories d'un livre
    public function updateCategories(int $id, array $catIds): void {
        $this->pdo->prepare("DELETE FROM livre_categorie WHERE idLivre=?")->execute([$id]);
        $stmt = $this->pdo->prepare("INSERT INTO livre_categorie (idLivre, idCat) VALUES (?, ?)");
        foreach ($catIds as $catId) {
            $stmt->execute([$id, (int)$catId]);
        }
    }

    // Supprimer un livre et toutes ses liaisons
    public function delete(int $id): void {
        $this->pdo->prepare("DELETE FROM livre_auteur    WHERE idLivre=?")->execute([$id]);
        $this->pdo->prepare("DELETE FROM livre_categorie WHERE idLivre=?")->execute([$id]);
        $this->pdo->prepare("DELETE FROM ligne_commande  WHERE idLivre=?")->execute([$id]);
        $this->pdo->prepare("DELETE FROM livre           WHERE idLivre=?")->execute([$id]);
    }

    // Toutes les catégories (pour le formulaire d'édition)
    public function getAllCategories(): array {
        return $this->pdo->query("SELECT idCat, nomCat FROM categorie ORDER BY nomCat")->fetchAll(PDO::FETCH_ASSOC);
    }

    // Catégories d'un livre donné (pour pré-cocher les cases)
    public function getCategoriesOfBook(int $id): array {
        $stmt = $this->pdo->prepare("SELECT idCat FROM livre_categorie WHERE idLivre=?");
        $stmt->execute([$id]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}
