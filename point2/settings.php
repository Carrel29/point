<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Café POS System</title>
    <link rel="stylesheet" href="css/settings.css">
    <style>
        .settings-container {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin-top: 50px;
        }

        .settings-card {
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            width: 300px;
            text-align: center;
            transition: transform 0.3s;
        }

        .settings-card:hover {
            transform: translateY(-5px);
        }

        .settings-card h2 {
            color: #333;
            margin-bottom: 20px;
        }

        .settings-card p {
            color: #666;
            margin-bottom: 25px;
        }

        .settings-btn {
            background-color: #6c5ce7;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .settings-btn:hover {
            background-color: #5a4dcc;
        }
        .header-buttons {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }

        .back-button {
            background-color: #6c5ce7;
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
            transition: background-color 0.3s;
            font-size: 0.9em;
        }

        .back-button:hover {
            background-color: #5a4dcc;
        }

        .logout-button {
            background-color: #e74c3c !important;
        }

        .logout-button:hover {
            background-color: #c0392b !important;
        }
        
    </style>
</head>
<body>
    <div class="container">
    <div class="header">
            <h1>Settings</h1>
            <div>
                <div class="user-info">
                <span>Current User: <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    <div id="current-datetime" class="datetime">
                        Loading time...
                    </div>
                    <div class="datetime-label">
                        Philippine Time (UTC+8)
                    </div>
                </div>
                <div class="header-buttons">
                    <a href="index.php" class="back-button">Back to POS</a>
                    <a href="logout.php" class="back-button logout-button">Logout</a>
                </div>
            </div>
        </div>

        <div class="settings-container">
            <div class="settings-card">
                <h2>Menu Settings</h2>
                <p>Manage menu items, categories, and prices</p>
                <a href="menu_settings.php" class="settings-btn">Manage Menu</a>
            </div>

            <div class="settings-card">
                <h2>Payment Settings</h2>
                <p>Configure payment methods and options</p>
                <a href="payment_settings.php" class="settings-btn">Manage Payments</a>
            </div>
        </div>
    </div>
    <script>
    function updateDateTime() {
        const now = new Date();
        // Add 8 hours for Philippines time (UTC+8)
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