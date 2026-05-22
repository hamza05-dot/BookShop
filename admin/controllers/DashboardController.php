<?php
class DashboardController {
    public function index(): void {
        // Les données viennent maintenant de api.php via fetch() côté JS
        // Le controller se contente d'afficher la vue
        $activePage = 'dashboard';
        require __DIR__ . '/../views/dashboard/index.php';
    }
}
