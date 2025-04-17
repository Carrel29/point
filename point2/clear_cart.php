<?php
session_start();

// Clear the cart session
$_SESSION['cart'] = [];

// Redirect back to index.php
header('Location: index.php');
exit;
?>