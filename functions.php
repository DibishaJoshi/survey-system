<?php
// functions.php

function redirect($url)
{
    header("Location: $url");
    exit;
}

function clean_input($data)
{
    return htmlspecialchars(stripslashes(trim($data)));
}

function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}

function requireLogin()
{
    if (!isLoggedIn()) {
        redirect('login.php');
    }
}

function generateToken()
{
    return bin2hex(random_bytes(32));
}

function base_url($path = '')
{
    // For local XAMPP setup, use a absolute path from the webroot if possible
    // or a simple dynamic one that handles the "survey-system" subdirectory.
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'];

    // Hardcoding 'survey-system' as the base to ensure consistency across subdirectories
    return $protocol . "://" . $host . "/survey-system/" . ltrim($path, '/');
}
