<?php
class OrderController {
    private OrderModel $model;

    public function __construct() {
        $this->model = new OrderModel();
    }

    public function index(): void {
        $message = '';
        if (isset($_POST['updateStatus'])) {
            $this->model->updateStatus((int)$_POST['idCom'], $_POST['status']);
            $message = "Statut mis à jour.";
        }
        $filterStatus  = $_GET['status'] ?? '';
        $filterSearch  = trim($_GET['search'] ?? '');
        $commandes     = $this->model->findAll($filterStatus, $filterSearch);
        $activePage    = 'orders';
        require __DIR__ . '/../views/orders/index.php';
    }

    public function detail(): void {
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) { header('Location: orders.php'); exit(); }

    $message = '';
    if (isset($_POST['updateStatus'])) {
        $this->model->updateStatus($id, $_POST['status']);
        $message = "Statut mis à jour.";
    }

    $order = $this->model->findById($id);
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
}