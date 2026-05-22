<?php
class BookController {
    private BookModel $model;

    public function __construct() {
        $this->model = new BookModel();
    }

    public function index(): void {
        if ($this->isAjax()) {
            header('Content-Type: application/json');
            $action = $_GET['action'] ?? '';

            switch ($action) {

                case 'books':
                    echo json_encode($this->model->findAll());
                    break;

                case 'delete_book':
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

        $activePage = 'books';
        require __DIR__ . '/../views/books/index.php';
    }

    public function detail(): void {
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) { header('Location: books.php'); exit(); }

        $message = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['saveBook'])) {
            $image = $_POST['current_image'];
            if (!empty($_FILES['image']['name'])) {
                $ext   = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $fname = time() . '_' . uniqid() . '.' . $ext;
                move_uploaded_file($_FILES['image']['tmp_name'], '../../uploads/book-covers/' . $fname);
                $image = $fname;
            }
            $this->model->update($id, [
                'titre'       => trim($_POST['titre']),
                'description' => trim($_POST['description']),
                'prix'        => (float)$_POST['prix'],
                'stock'       => (int)$_POST['stock'],
                'image'       => $image,
            ]);
            $this->model->updateCategories($id, $_POST['categories'] ?? []);
            $message = 'Livre mis à jour avec succès.';
        }

        $livre         = $this->model->findById($id);
        $allCategories = $this->model->getAllCategories();
        $selectedCats  = $this->model->getCategoriesOfBook($id);
        $activePage    = 'books';
        require __DIR__ . '/../views/books/detail.php';
    }

    private function isAjax(): bool {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}
