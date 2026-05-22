<?php
class CategoryController {
    private CategoryModel $model;

    public function __construct() {
        $this->model = new CategoryModel();
    }

    public function index(): void {
        $message = '';
        if (isset($_GET['delete'])) {
            $this->model->delete((int)$_GET['delete']);
            $message = "Category deleted.";
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nom = trim($_POST['nomCat'] ?? '');
            if ($nom) {
                $this->model->create($nom);
                $message = "Category added.";
            }
        }
        $categories = $this->model->findAll();
        $activePage = 'categories';
        require __DIR__ . '/../views/categories/index.php';
    }

    public function detail(): void {
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) { header('Location: categories.php'); exit(); }
        $category   = $this->model->findById($id);
        if (!$category) { header('Location: categories.php'); exit(); }
        $books      = $this->model->findBooksByCategory($id);
        $activePage = 'categories';
        require __DIR__ . '/../views/categories/detail.php';
    }
}
