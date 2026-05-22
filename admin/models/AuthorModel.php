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
            SELECT l.* FROM livre l
            INNER JOIN livre_auteur la ON l.idLivre = la.idLivre
            WHERE la.idAuteur = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function update(int $id, array $data): void {
        $this->pdo->prepare("
            UPDATE auteur SET nom=?, prenom=?, description=?, status=?, dateNaiss=?, image=?
            WHERE idAuteur=?
        ")->execute([$data['nom'], $data['prenom'], $data['description'], $data['status'], $data['dateNaiss'], $data['image'], $id]);
    }

    public function delete(int $id): void {
        $this->pdo->prepare("DELETE FROM livre_auteur WHERE idAuteur=?")->execute([$id]);
        $this->pdo->prepare("DELETE FROM auteur WHERE idAuteur=?")->execute([$id]);
    }
}
