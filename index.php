<?php
require 'functions.php';

// Check if user is logged in using the existing function from functions.php
if (isLoggedIn()) {
    redirect('dashboard.php');
}
else {
    redirect('login.php');
}
?>
