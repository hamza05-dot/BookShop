<?php
class AuthorController {
    private AuthorModel $model;

    public function __construct() {
        $this->model = new AuthorModel();
    }

    public function index(): void {
        $message = '';
        if (isset($_GET['delete'])) {
            $this->model->delete((int)$_GET['delete']);
            $message = "Author deleted.";
        }
        $authors    = $this->model->findAll();
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
            $message = "Author updated.";
        }

        $author     = $this->model->findById($id);
        $books      = $this->model->findBooksByAuthor($id);
        $activePage = 'authors';
        require __DIR__ . '/../views/authors/detail.php';
    }
}
