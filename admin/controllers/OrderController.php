<?php
class OrderController {
    private OrderModel $model;

    public function __construct() {
        $this->model = new OrderModel();
    }

    public function index(): void {

        // requête AJAX → retourner JSON
        if ($this->isAjax()) {
            header('Content-Type: application/json');
            $action = $_GET['action'] ?? '';

            switch ($action) {

                case 'orders':
                    // liste avec filtres optionnels
                    $status = trim($_GET['status'] ?? '');
                    $search = trim($_GET['search'] ?? '');
                    echo json_encode($this->model->findAll($status, $search));
                    break;

                case 'order_update_status':
                    // changer le statut d'une commande
                    $idCom   = (int)($_POST['idCom'] ?? 0);
                    $status  = trim($_POST['status'] ?? '');
                    $allowed = ['en attente', 'confirmee', 'livree', 'annulee'];

                    if (!$idCom || !in_array($status, $allowed)) {
                        http_response_code(400);
                        echo json_encode(['error' => 'Données invalides']);
                        break;
                    }
                    $this->model->updateStatus($idCom, $status);
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
        $activePage = 'orders';
        require __DIR__ . '/../views/orders/index.php';
    }

    public function detail(): void {
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) { header('Location: orders.php'); exit(); }

        // requête AJAX pour mise à jour du statut depuis la page de détail
        if ($this->isAjax()) {
            header('Content-Type: application/json');
            $action  = $_GET['action'] ?? '';
            $status  = trim($_POST['status'] ?? '');
            $allowed = ['en attente', 'confirmee', 'livree', 'annulee'];

            if ($action === 'order_update_status' && in_array($status, $allowed)) {
                $this->model->updateStatus($id, $status);
                echo json_encode(['success' => true]);
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Données invalides']);
            }
            exit;
        }

        // requête normale → charger la vue
        $message = '';
        $order   = $this->model->findById($id);
        if (!$order) { header('Location: orders.php'); exit(); }

        $items  = $this->model->findLines($id);
        $idCom  = $order['idCom'];

        $statusMap = [
            'en attente' => ['label' => 'En attente', 'bg' => '#fff7e6', 'color' => '#d97706'],
            'confirmee'  => ['label' => 'Confirmée',  'bg' => '#ecfdf5', 'color' => '#059669'],
            'livree'     => ['label' => 'Livrée',     'bg' => '#eff6ff', 'color' => '#2563eb'],
            'annulee'    => ['label' => 'Annulée',    'bg' => '#fff1f2', 'color' => '#e11d48'],
        ];
        $currentStatus = $statusMap[$order['status']] ?? ['label' => $order['status'], 'bg' => '#f0f0f0', 'color' => '#666'];

        $activePage = 'orders';
        require __DIR__ . '/../views/orders/detail.php';
    }

    private function isAjax(): bool {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}
