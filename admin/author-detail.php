<?php
session_start();
require_once '../includes/db.php';
require_once 'models/AuthorModel.php';
require_once 'controllers/AuthorController.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php'); exit();
}

$controller = new AuthorController();
$controller->detail();
