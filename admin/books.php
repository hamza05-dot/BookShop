<?php
session_start();
require_once '../includes/db.php';
require_once 'models/BookModel.php';
require_once 'controllers/BookController.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php'); exit();
}

$controller = new BookController();
$controller->index();
