<?php
session_start();
require_once '../includes/db.php';
require_once 'models/CategoryModel.php';
require_once 'controllers/CategoryController.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php'); exit();
}

$controller = new CategoryController();
$controller->detail();
