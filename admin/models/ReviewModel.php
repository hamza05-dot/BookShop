<?php
class ReviewModel {
    private PDO $pdo;

    public function __construct() {
        $this->pdo = Database::getInstance();
    }

    public function findAll(): array {
        return $this->pdo->query("
            SELECT av.idAvis, av.note, av.commentaire, av.createdAt,
                   l.titre, l.idLivre, u.nomUser, u.prenomUser
            FROM avis av
            JOIN ligne_commande lc ON av.idLigneCom = lc.idLigneCom
            JOIN livre l           ON lc.idLivre    = l.idLivre
            JOIN commande c        ON lc.idCom      = c.idCom
            JOIN utilisateur u     ON c.idClient    = u.idUser
            ORDER BY av.createdAt DESC
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function delete(int $id): void {
        $this->pdo->prepare("DELETE FROM avis WHERE idAvis=?")->execute([$id]);
    }
}
