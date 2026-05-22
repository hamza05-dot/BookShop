<?php
class UserController {
    private UserModel $model;

    public function __construct() {
        $this->model = new UserModel();
    }

    public function index(): void {
        $message = '';
        if (isset($_POST['addAdmin'])) {
            $message = $this->model->promoteToAdmin((int)$_POST['idUser']);
        }
        if (isset($_GET['delete'])) {
            $this->model->delete((int)$_GET['delete']);
            $message = "User deleted.";
        }
        if (isset($_GET['removeAdmin'])) {
            $this->model->removeAdmin((int)$_GET['removeAdmin']);
            $message = "Admin role removed.";
        }
        $admins     = $this->model->findAdmins();
        $clients    = $this->model->findClients();
        $activePage = 'users';
        require __DIR__ . '/../views/users/index.php';
    }
}
