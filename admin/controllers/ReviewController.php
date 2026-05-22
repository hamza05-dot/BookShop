<?php
class ReviewController {
    private ReviewModel $model;

    public function __construct() {
        $this->model = new ReviewModel();
    }

    public function index(): void {
        $message = '';
        if (isset($_GET['delete'])) {
            $this->model->delete((int)$_GET['delete']);
            $message = "Review deleted.";
        }
        $reviews      = $this->model->findAll();
        $totalReviews = count($reviews);
        $avgNote      = $totalReviews > 0
            ? number_format(array_sum(array_column($reviews, 'note')) / $totalReviews, 1)
            : 0;
        $fiveStars    = count(array_filter($reviews, fn($r) => $r['note'] == 5));
        $activePage   = 'reviews';
        require __DIR__ . '/../views/reviews/index.php';
    }
}
