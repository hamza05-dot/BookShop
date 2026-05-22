<?php
class UserController {
    private UserModel $model;

    public function __construct() {
        $this->model = new UserModel();
    }

    public function index(): void {

        // requête AJAX → retourner JSON
        if ($this->isAjax()) {
            header('Content-Type: application/json');
            $action = $_GET['action'] ?? '';

            switch ($action) {

                case 'users':
                    // liste des admins et des clients
                    echo json_encode([
                        'admins'  => $this->model->findAdmins(),
                        'clients' => $this->model->findClients(),
                    ]);
                    break;

                case 'promote_admin':
                    // promouvoir un client en admin
                    $id = (int)($_POST['idUser'] ?? 0);
                    if (!$id) {
                        http_response_code(400);
                        echo json_encode(['error' => 'ID manquant']);
                        break;
                    }
                    if ($this->model->isAdmin($id)) {
                        echo json_encode(['error' => 'Cet utilisateur est déjà admin']);
                        break;
                    }
                    $this->model->promoteToAdmin($id);
                    echo json_encode(['success' => true]);
                    break;

                case 'remove_admin':
                    // retirer le rôle admin
                    $id = (int)($_POST['id'] ?? 0);
                    if (!$id) {
                        http_response_code(400);
                        echo json_encode(['error' => 'ID manquant']);
                        break;
                    }
                    if ($id === (int)$_SESSION['idUser']) {
                        echo json_encode(['error' => 'Vous ne pouvez pas vous retirer le rôle admin']);
                        break;
                    }
                    $this->model->removeAdmin($id);
                    echo json_encode(['success' => true]);
                    break;

                case 'delete_user':
                    // supprimer un utilisateur
                    $id = (int)($_POST['id'] ?? 0);
                    if (!$id) {
                        http_response_code(400);
                        echo json_encode(['error' => 'ID manquant']);
                        break;
                    }
                    if ($id === (int)$_SESSION['idUser']) {
                        echo json_encode(['error' => 'Vous ne pouvez pas vous supprimer vous-même']);
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
        $activePage = 'users';
        require __DIR__ . '/../views/users/index.php';
    }

    private function isAjax(): bool {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}
