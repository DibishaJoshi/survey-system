<?php
session_start();
require '../config.php';
require '../functions.php';
requireLogin();

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $pdo->prepare("DELETE FROM surveys WHERE id = ?");
    $stmt->execute([$id]);
}

redirect('../dashboard.php');
?>
