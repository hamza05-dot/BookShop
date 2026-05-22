<?php
session_start();
require_once '../includes/db.php';
require_once 'models/ReviewModel.php';
require_once 'controllers/ReviewController.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php'); exit();
}

$controller = new ReviewController();
$controller->index();
