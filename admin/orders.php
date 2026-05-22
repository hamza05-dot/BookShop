<?php
session_start();
require_once '../includes/db.php';
require_once 'models/OrderModel.php';
require_once 'controllers/OrderController.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php'); exit();
}

$controller = new OrderController();
$controller->index();
