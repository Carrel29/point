<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// For admin pages, check if user is admin
function requireAdmin() {
    if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
        header("Location: index.php");
        exit();
    }
}

// Get current user info
$current_user = [
    'id' => $_SESSION['user_id'],
    'username' => $_SESSION['username'],
    'is_admin' => $_SESSION['is_admin'],
    'login_time' => $_SESSION['login_time']
];
?>