<?php
require_once __DIR__ . '/initialize.php';
use BO_System\Controllers\AuthController;

$controller = new AuthController();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $controller->login();
} else {
    $controller->index();
}