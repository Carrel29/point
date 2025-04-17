<?php
$servername = "localhost";
$username = "root";  // your database username
$password = "";      // your database password
$dbname = "cafe_pos";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset
$conn->set_charset("utf8mb4");
?>