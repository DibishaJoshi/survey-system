<?php
if (session_status() === PHP_SESSION_NONE) {
    // Set session lifetime to 1 day (86400 seconds)
    ini_set('session.gc_maxlifetime', 86400);
    session_set_cookie_params(['lifetime' => 86400]);
    session_start();
}
require_once __DIR__ . '/../functions.php';
requireLogin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Survey System Admin</title>
    <link rel="stylesheet" href="assets/style.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include __DIR__ . '/navbar.php'; ?>
    <div class="container">
