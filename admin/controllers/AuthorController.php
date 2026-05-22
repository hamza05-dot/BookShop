<?php
class AuthorController {
    private AuthorModel $model;

    public function __construct() {
        $this->model = new AuthorModel();
    }

    public function index(): void {
        if ($this->isAjax()) {
            header('Content-Type: application/json');
            $action = $_GET['action'] ?? '';

            switch ($action) {

                case 'authors':
                    echo json_encode($this->model->findAll());
                    break;

                case 'delete_author':
                    $id = (int)($_POST['id'] ?? 0);
                    if (!$id) {
                        http_response_code(400);
                        echo json_encode(['error' => 'ID manquant']);
                        break;
                    }
                    try {
                        $this->model->delete($id);
                        echo json_encode(['success' => true]);
                    } catch (Exception $e) {
                        http_response_code(500);
                        echo json_encode(['error' => 'Erreur lors de la suppression']);
                    }
                    break;

                default:
                    http_response_code(400);
                    echo json_encode(['error' => 'Action inconnue']);
            }
            exit;
        }

        $activePage = 'authors';
        require __DIR__ . '/../views/authors/index.php';
    }

    public function detail(): void {
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) { header('Location: authors.php'); exit(); }

        $message = '';

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
            $message = 'Auteur mis à jour avec succès.';
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
