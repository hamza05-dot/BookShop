<?php
class ReviewController {
    private ReviewModel $model;

    public function __construct() {
        $this->model = new ReviewModel();
    }

    public function index(): void {

        // requête AJAX → retourner JSON
        if ($this->isAjax()) {
            header('Content-Type: application/json');
            $action = $_GET['action'] ?? '';

            switch ($action) {

                case 'reviews':
                    // liste des avis + stats (total, moyenne, 5 étoiles)
                    $rows      = $this->model->findAll();
                    $total     = count($rows);
                    $avg       = $total ? round(array_sum(array_column($rows, 'note')) / $total, 1) : 0;
                    $fiveStars = count(array_filter($rows, fn($r) => (int)$r['note'] === 5));
                    echo json_encode([
                        'reviews'   => $rows,
                        'total'     => $total,
                        'avgNote'   => $avg,
                        'fiveStars' => $fiveStars,
                    ]);
                    break;

                case 'delete_review':
                    // supprimer un avis
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
        $activePage = 'reviews';
        require __DIR__ . '/../views/reviews/index.php';
    }

    private function isAjax(): bool {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}
