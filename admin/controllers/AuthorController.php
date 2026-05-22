<?php
class AuthorController {
    private AuthorModel $model;

    public function __construct() {
        $this->model = new AuthorModel();
    }

    public function index(): void {

        // requête AJAX → retourner JSON
        if ($this->isAjax()) {
            header('Content-Type: application/json');
            $action = $_GET['action'] ?? '';

            switch ($action) {

                case 'authors':
                    // liste complète des auteurs
                    echo json_encode($this->model->findAll());
                    break;

                case 'delete_author':
                    // supprimer un auteur par son ID
                    $id = (int)($_POST['id'] ?? 0);
                    if (!$id) {
                        http_response_code(400);
                        echo json_encode(['error' => 'ID manquant']);
                        break;
                    }
                    $this->model->delete($id);
                    echo json_encode(['success' => true]);
                    break;

                default:
                    http_response_code(400);
                    echo json_encode(['error' => 'Action inconnue']);
            }
            exit;
        }

        // requête normale → charger la vue
        $message    = '';
        $activePage = 'authors';
        require __DIR__ . '/../views/authors/index.php';
    }

    public function detail(): void {
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) { header('Location: authors.php'); exit(); }

        $message = '';

        // sauvegarde de l'auteur (formulaire avec upload image)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $image = $_POST['current_image'];
            if (!empty($_FILES['image']['name'])) {
                $ext   = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $fname = time() . '_author_' . uniqid() . '.' . $ext;
                move_uploaded_file($_FILES['image']['tmp_name'], '../../uploads/authors/' . $fname);
                $image = $fname;
            }
            $this->model->update($id, [
                'nom'         => trim($_POST['nom']),
                'prenom'      => trim($_POST['prenom']),
                'description' => trim($_POST['description']),
                'status'      => $_POST['status'],
                'dateNaiss'   => $_POST['dateNaiss'] ?: null,
                'image'       => $image,
            ]);
            $message = "Author updated.";
        }

        $author     = $this->model->findById($id);
        $books      = $this->model->findBooksByAuthor($id);
        $activePage = 'authors';
        require __DIR__ . '/../views/authors/detail.php';
    }

    private function isAjax(): bool {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}
