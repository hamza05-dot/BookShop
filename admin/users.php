<?php
session_start();
require_once '../includes/db.php';
require_once 'models/UserModel.php';
require_once 'controllers/UserController.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php'); exit();
}

$controller = new UserController();
$controller->index();
