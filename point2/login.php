<?php
session_start();
include 'config.php';
date_default_timezone_set('Asia/Manila');
// If user is already logged in, redirect to appropriate page
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header('Location: index.php');
    exit;
}

// Set timezone to UTC
date_default_timezone_set('UTC');
$current_datetime = '2025-04-10 14:00:52';

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // Query the database for the user
    $stmt = $conn->prepare("SELECT id, username, password, is_admin FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($user = $result->fetch_assoc()) {
        if (password_verify($password, $user['password'])) {
            $_SESSION['logged_in'] = true;
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['is_admin'] = $user['is_admin'];
            header('Location: index.php');
            exit;
        } else {
            $error_message = "Invalid username or password";
        }
    } else {
        $error_message = "Invalid username or password";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>BTONE POS System Login</title>
    <style>
        * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Arial', sans-serif;
}

body {
    min-height: 100vh;
    background-color: #f4f4f4;
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 20px;
}

.container {
    position: relative;
    width: 100%;
    max-width: 400px;
    background: #fff;
    padding: 40px;
    border-radius: 10px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.header {
    text-align: center;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 1px solid #ddd;
}

.header h2 {
    color: #333;
    font-size: 2em;
    margin-bottom: 15px;
}

.datetime {
    color: #666;
    font-size: 0.9em;
    margin-top: 10px;
    background: #f9f9f9;
    padding: 8px;
    border-radius: 5px;
    display: inline-block;
    border: 1px solid #ddd;
}

.user-info {
    color: #666;
    font-size: 0.9em;
    margin-top: 10px;
}

.form-group {
    margin-bottom: 25px;
}

.form-group label {
    display: block;
    color: #333;
    margin-bottom: 8px;
    font-weight: 500;
}

.input-group {
    position: relative;
}

.input-group input {
    width: 100%;
    padding: 12px 15px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 1em;
    color: #333;
    transition: all 0.3s ease;
    padding-right: 40px;
}

.input-group input:focus {
    border-color: #6c5ce7;
    outline: none;
    box-shadow: 0 0 5px rgba(108, 92, 231, 0.1);
}

.toggle-password {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    cursor: pointer;
    color: #666;
    font-size: 0.9em;
    padding: 5px;
}

.toggle-password:hover {
    color: #6c5ce7;
}

.login-button {
    width: 100%;
    padding: 12px;
    background-color: #6c5ce7;
    color: #fff;
    border: none;
    border-radius: 5px;
    font-size: 1em;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.login-button:hover {
    background-color: #5a4dcc;
}

.error-message {
    background: #ff7675;
    color: white;
    padding: 12px;
    border-radius: 5px;
    margin-bottom: 20px;
    text-align: center;
    font-size: 0.9em;
    animation: shake 0.5s ease-in-out;
}

@keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-5px); }
    75% { transform: translateX(5px); }
}

.logo {
    text-align: center;
    margin-bottom: 30px;
}

.logo h1 {
    color: #333;
    font-size: 2.5em;
    font-weight: 700;
    letter-spacing: 2px;
}

@media (max-width: 480px) {
    .container {
        padding: 20px;
    }
}
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <h1><span>BT</span>ONE</h1>
        </div>
        <div class="header">
            <h2>Welcome Back</h2>
            <div class="datetime" id="current-datetime">
                Loading time...
            </div>
            <div class="datetime-label" style="color: #666; font-size: 0.8em; margin-top: 5px;">
                Philippine Time (UTC+8)
            </div>
            <?php if (isset($_SESSION['username'])): ?>
            <div class="user-info">
                Current User: <?php echo htmlspecialchars($_SESSION['username']); ?>
            </div>
            <?php endif; ?>
        </div>
        
        <?php if (isset($error_message)): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Username</label>
                <div class="input-group">
                    <input type="text" id="username" name="username" required 
                           placeholder="Enter your username">
                </div>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-group">
                    <input type="password" id="password" name="password" required
                           placeholder="Enter your password">
                    <button type="button" class="toggle-password" onclick="togglePassword()">
                        Show
                    </button>
                </div>
            </div>
            <button type="submit" class="login-button">
                Login
            </button>
        </form>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleButton = document.querySelector('.toggle-password');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleButton.textContent = 'Hide';
            } else {
                passwordInput.type = 'password';
                toggleButton.textContent = 'Show';
            }
        }
        function updateDateTime() {
            const now = new Date();
            // Add 8 hours for Philippines time
            const phTime = new Date(now.getTime() + (8 * 60 * 60 * 1000));
            
            const year = phTime.getUTCFullYear();
            const month = String(phTime.getUTCMonth() + 1).padStart(2, '0');
            const day = String(phTime.getUTCDate()).padStart(2, '0');
            const hours = String(phTime.getUTCHours()).padStart(2, '0');
            const minutes = String(phTime.getUTCMinutes()).padStart(2, '0');
            const seconds = String(phTime.getUTCSeconds()).padStart(2, '0');
            
            const formattedDateTime = `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
            document.getElementById('current-datetime').textContent = formattedDateTime;
        }

        // Update time immediately and then every second
        updateDateTime();
        setInterval(updateDateTime, 1000);
    </script>
</body>
</html>