<?php
class CategoryController {
    private CategoryModel $model;

    public function __construct() {
        $this->model = new CategoryModel();
    }

    public function index(): void {

        // requête AJAX → retourner JSON
        if ($this->isAjax()) {
            header('Content-Type: application/json');
            $action = $_GET['action'] ?? '';

            switch ($action) {

                case 'categories':
                    // liste complète des catégories
                    echo json_encode($this->model->findAll());
                    break;

                case 'add_category':
                    // ajouter une nouvelle catégorie
                    $nom = trim($_POST['nomCat'] ?? '');
                    if (!$nom) {
                        http_response_code(400);
                        echo json_encode(['error' => 'Nom manquant']);
                        break;
                    }
                    if ($this->model->existsByName($nom)) {
                        echo json_encode(['error' => 'Cette catégorie existe déjà']);
                        break;
                    }
                    $newId = $this->model->create($nom);
                    echo json_encode(['success' => true, 'idCat' => $newId, 'nomCat' => $nom]);
                    break;

                case 'delete_category':
                    // supprimer une catégorie
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
        $activePage = 'categories';
        require __DIR__ . '/../views/categories/index.php';
    }

    public function detail(): void {
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) { header('Location: categories.php'); exit(); }

        $category = $this->model->findById($id);
        if (!$category) { header('Location: categories.php'); exit(); }

        $books      = $this->model->findBooksByCategory($id);
        $activePage = 'categories';
        require __DIR__ . '/../views/categories/detail.php';
    }

    private function isAjax(): bool {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}
