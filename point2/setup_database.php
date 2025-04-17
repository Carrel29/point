<?php
include 'config.php';

// Create users table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    is_admin BOOLEAN DEFAULT false,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "Users table created successfully<br>";
} else {
    echo "Error creating table: " . $conn->error . "<br>";
}

// Clear existing users if they exist
$conn->query("DELETE FROM users WHERE username IN ('Carrel29', 'Admin', 'Cashier')");

// Define the users
$users = [
    [
        'username' => 'Carrel29',
        'password' => 'Carrel29',
        'is_admin' => 1,
        'created_at' => '2025-04-10 14:00:52'
    ],
    [
        'username' => 'Admin',
        'password' => 'Admin123',
        'is_admin' => 1,
        'created_at' => '2025-04-10 14:00:52'
    ],
    [
        'username' => 'Cashier',
        'password' => 'Cashier123',
        'is_admin' => 0,  // Not an admin
        'created_at' => '2025-04-10 14:00:52'
    ]
];

// Insert the users
$stmt = $conn->prepare("INSERT INTO users (username, password, is_admin, created_at) VALUES (?, ?, ?, ?)");

foreach ($users as $user) {
    $hashed_password = password_hash($user['password'], PASSWORD_DEFAULT);
    $stmt->bind_param("ssis", 
        $user['username'], 
        $hashed_password, 
        $user['is_admin'],
        $user['created_at']
    );
    
    if ($stmt->execute()) {
        echo "<p>Account created successfully:</p>";
        echo "<ul>";
        echo "<li>Username: " . htmlspecialchars($user['username']) . "</li>";
        echo "<li>Password: " . htmlspecialchars($user['password']) . "</li>";
        echo "<li>Is Admin: " . ($user['is_admin'] ? 'Yes' : 'No') . "</li>";
        echo "<li>Created at: " . htmlspecialchars($user['created_at']) . "</li>";
        echo "</ul>";
    } else {
        echo "Error creating user " . htmlspecialchars($user['username']) . ": " . $conn->error . "<br>";
    }
}

$stmt->close();
$conn->close();
?>