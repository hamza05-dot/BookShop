<?php
class DashboardController {
    private DashboardModel $model;

    public function __construct() {
        $this->model = new DashboardModel();
    }

    public function index(): void {

        // requête AJAX → retourner JSON selon l'action demandée
        if ($this->isAjax()) {
            header('Content-Type: application/json');
            $action = $_GET['action'] ?? '';

            switch ($action) {
                case 'dashboard_stats':
                    echo json_encode($this->model->getStats());
                    break;
                case 'dashboard_orders':
                    echo json_encode($this->model->getRecentOrders());
                    break;
                case 'dashboard_charts':
                    echo json_encode($this->model->getChartData());
                    break;
                default:
                    http_response_code(400);
                    echo json_encode(['error' => 'Action inconnue']);
            }
            exit;
        }

        // requête normale → charger la vue
        $activePage = 'dashboard';
        require __DIR__ . '/../views/dashboard/index.php';
    }

    // vérifie si la requête vient de jQuery $.getJSON / $.post
    private function isAjax(): bool {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}
