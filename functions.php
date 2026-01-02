<?php
// functions.php

function redirect($url) {
    header("Location: $url");
    exit;
}

function clean_input($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        redirect('login.php');
    }
}

function generateToken() {
    return bin2hex(random_bytes(32));
}
?>
