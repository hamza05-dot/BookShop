<?php
session_start();
require_once '../includes/db.php';
require_once 'models/DashboardModel.php';
require_once 'controllers/DashboardController.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php'); exit();
}

$controller = new DashboardController();
$controller->index();
