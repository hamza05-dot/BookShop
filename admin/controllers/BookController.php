<?php
class BookController {
    private BookModel $model;

    public function __construct() {
        $this->model = new BookModel();
    }

    public function index(): void {
        $message = '';
        if (isset($_GET['delete'])) {
            $this->model->delete((int)$_GET['delete']);
            $message = "Book deleted.";
        }
        $livres     = $this->model->findAll();
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
    $categories = $_POST['categories'] ?? [];          // ← add
    $this->model->updateCategories($id, $categories);  // ← add
    $message = "Book updated.";
}

    $livre          = $this->model->findById($id);
    $allCategories  = $this->model->getAllCategories();   // ← add
    $selectedCats   = $this->model->getCategoriesOfBook($id); // ← add
    $activePage     = 'books';
    require __DIR__ . '/../views/books/detail.php';
}

}
