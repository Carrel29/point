<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: index.php");
    exit;
}

// Database connection
$conn = new mysqli("localhost", "root", "", "cafe_pos");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set default timezone to Philippines
date_default_timezone_set('Asia/Manila');

// Initialize filters
$timeFilter = isset($_GET['time_filter']) ? $_GET['time_filter'] : 'all';
$paymentFilter = isset($_GET['payment_filter']) ? $_GET['payment_filter'] : 'all';
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : '';

// Build query based on filters
$query = "SELECT * FROM transactions WHERE 1=1";

// Time filter
switch($timeFilter) {
    case 'hour':
        $query .= " AND transaction_date >= DATE_SUB(NOW(), INTERVAL 1 HOUR)";
        break;
    case '24hours':
        $query .= " AND transaction_date >= DATE_SUB(NOW(), INTERVAL 24 HOUR)";
        break;
    case 'week':
        $query .= " AND transaction_date >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
        break;
    case 'month':
        $query .= " AND transaction_date >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
        break;
    case 'custom':
        if ($startDate && $endDate) {
            $query .= " AND DATE(transaction_date) BETWEEN '$startDate' AND '$endDate'";
        }
        break;
}

// Payment method filter
if ($paymentFilter != 'all') {
    $query .= " AND payment_method = '$paymentFilter'";
}

$query .= " ORDER BY transaction_date DESC";

$transactions = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction History - Café POS System</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            position: relative;
        }

        .back-to-pos {
            position: absolute;
            right: 20px;
            top: 20px;
        }

        .back-to-pos button {
            background-color: #6c5ce7;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s;
        }

        .back-to-pos button:hover {
            background-color: #5a4dcc;
        }

        .user-info {
            margin-top: 10px;
            color: #666;
        }

        .filter-button button {
            background-color: #6c5ce7;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s;
        }

        .filter-button button:hover {
            background-color: #5a4dcc;
        }

        .filters select, .filters input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background-color: white;
        }

        .view-details {
            color: white !important;
            cursor: pointer;
            background-color: #6c5ce7;
            padding: 5px 10px;
            border-radius: 3px;
            text-decoration: none;
            transition: background-color 0.3s;
        }

        .view-details:hover {
            background-color: #5a4dcc;
        }

        .modal-content {
            max-width: 600px;
            width: 90%;
            background: white;
            padding: 20px;
            border-radius: 5px;
            position: relative;
        }

        .date-range-title {
            text-align: center;
            margin: 10px 0;
            padding: 15px;
            background: linear-gradient(135deg, #8e44ad,rgb(233, 233, 233));
            color: black;
            border-radius: 8px;
            font-weight: bold;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            font-size: 16px;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
            border: 1px solid rgba(255,255,255,0.1);
        }

        h1 {
            color: #333;
            margin: 0;
        }
        .filters {
            margin: 20px 0;
            padding: 15px;
            background: #fff;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .filters select, .filters input {
            padding: 8px;
            margin: 5px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .transactions-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .transactions-table th, 
        .transactions-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .transactions-table th {
            background: #6c5ce7;
            color: white;
        }

        .transactions-table tr:hover {
            background: #f5f5f5;
        }

        .view-details {
            cursor: pointer;
            color: #6c5ce7;
            text-decoration: underline;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
        }

        .modal-content {
            background: #fff;
            margin: 15% auto;
            padding: 20px;
            width: 70%;
            border-radius: 5px;
        }

        .close {
            float: right;
            cursor: pointer;
            font-size: 20px;
        }
        .custom-date {
            display: none; /* Hide by default */
        }

        .date-range-title {
            text-align: center;
            margin: 10px 0;
            padding: 10px;
            background: #f0f0f0;
            border-radius: 5px;
            display: none; /* Hide by default */
        }
        .filter-labels {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }

        .filter-controls {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            align-items: center;
        }

        .filter-button {
            grid-column: 1 / -1;
            text-align: center;
            margin-top: 10px;
        }

        .custom-date {
            display: none; /* Hide by default */
        }

        .date-range-title {
            text-align: center;
            margin: 10px 0;
            padding: 10px;
            background: #f0f0f0;
            border-radius: 5px;
            font-weight: bold;
        }
        .date-label {
        display: none; 
        }
        .show-date-fields .date-label {
            display: block;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Transaction History</h1>
            <div class="back-to-pos">
                <button onclick="window.location.href='index.php'">Back to POS</button>
            </div>
            <div class="user-info">
                <span>Current User's Login: <?php echo htmlspecialchars($_SESSION['username']); ?></span>
    </br>
                <span>Current Date and Time: 
                    <span id="current-datetime">Loading...</span>
                </span>
            </div>
        </div>
        <div id="dateRangeTitle" class="date-range-title"></div>
        <div class="filters">
        <form action="" method="GET">
        <div class="filter-labels">
            <div>Date Filter</div>
            <div>Payment Method</div>
            <div class="date-label">Start Date</div>
            <div class="date-label">End Date</div>
        </div>
            <div class="filter-controls">
                <select name="time_filter">
                    <option value="all" <?php echo $timeFilter == 'all' ? 'selected' : ''; ?>>All Time</option>
                    <option value="hour" <?php echo $timeFilter == 'hour' ? 'selected' : ''; ?>>Past Hour</option>
                    <option value="24hours" <?php echo $timeFilter == '24hours' ? 'selected' : ''; ?>>Past 24 Hours</option>
                    <option value="week" <?php echo $timeFilter == 'week' ? 'selected' : ''; ?>>Past Week</option>
                    <option value="month" <?php echo $timeFilter == 'month' ? 'selected' : ''; ?>>Past Month</option>
                    <option value="custom" <?php echo $timeFilter == 'custom' ? 'selected' : ''; ?>>Custom Date Range</option>
                </select>

                <select name="payment_filter">
                    <option value="all" <?php echo $paymentFilter == 'all' ? 'selected' : ''; ?>>All Payment Methods</option>
                    <option value="cash" <?php echo $paymentFilter == 'cash' ? 'selected' : ''; ?>>Cash</option>
                    <option value="gcash" <?php echo $paymentFilter == 'gcash' ? 'selected' : ''; ?>>GCash</option>
                    <option value="maya" <?php echo $paymentFilter == 'maya' ? 'selected' : ''; ?>>Maya</option>
                </select>

                <input type="date" name="start_date" value="<?php echo $startDate; ?>" 
                    class="custom-date" <?php echo $timeFilter == 'custom' ? 'style="display: inline-block;"' : ''; ?>>
                <input type="date" name="end_date" value="<?php echo $endDate; ?>" 
                    class="custom-date" <?php echo $timeFilter == 'custom' ? 'style="display: inline-block;"' : ''; ?>>
            </div>
            <div class="filter-button">
                <button type="submit">Apply Filters</button>
            </div>
        </form>
    </div>

        <table class="transactions-table">
            <thead>
                <tr>
                    <th>Date & Time</th>
                    <th>Admin/Cashier</th> 
                    <th>Payment Method</th>
                    <th>Total Amount</th>
                    <th>Reference/Details</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($transaction = $transactions->fetch_assoc()): ?>
                <tr>
                    <td><?php echo date('Y-m-d H:i:s', strtotime($transaction['transaction_date'])); ?></td>
                    <td><?php echo htmlspecialchars($transaction['username']); ?></td>  <!-- Add this cell -->
                    <td><?php echo htmlspecialchars($transaction['payment_method']); ?></td>
                    <td>₱<?php echo number_format($transaction['total_amount'], 2); ?></td>
                    <td>
                        <?php
                        if ($transaction['payment_method'] == 'cash') {
                            echo "Change: ₱" . number_format($transaction['cash_change'], 2);
                        } else {
                            echo "Ref#: " . htmlspecialchars($transaction['reference_number']);
                        }
                        ?>
                    </td>
                    <td>
                        <span class="view-details" onclick="viewDetails(<?php echo htmlspecialchars(json_encode($transaction)); ?>)">
                            View Details
                        </span>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- Modal for transaction details -->
    <div id="transactionModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2>Transaction Details</h2>
            <div id="transactionDetails"></div>
        </div>
    </div>

    <script>
    // Time update function
    function updateDateTime() {
        const now = new Date();
        const options = {
            timeZone: 'Asia/Manila',
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: false
        };
        document.getElementById('current-datetime').textContent = now.toLocaleString('en-US', options);
    }

    // Update time immediately and then every second
    updateDateTime();
    setInterval(updateDateTime, 1000);

    function formatDateForDisplay(dateString) {
        const date = new Date(dateString);
        const options = { year: 'numeric', month: 'long', day: 'numeric' };
        return date.toLocaleDateString('en-US', options);
    }

    // Modal functions
    function viewDetails(transaction) {
        const modal = document.getElementById('transactionModal');
        const detailsDiv = document.getElementById('transactionDetails');
        
        const cartItems = JSON.parse(transaction.cart_items);
        let detailsHtml = `
            <p><strong>Date:</strong> ${transaction.transaction_date}</p>
            <p><strong>Cashier:</strong> ${transaction.username}</p>
            <p><strong>Payment Method:</strong> ${transaction.payment_method}</p>
            <p><strong>Total Amount:</strong> ₱${parseFloat(transaction.total_amount).toFixed(2)}</p>
        `;

        if (transaction.payment_method === 'cash') {
            detailsHtml += `
                <p><strong>Cash Received:</strong> ₱${parseFloat(transaction.cash_received).toFixed(2)}</p>
                <p><strong>Change:</strong> ₱${parseFloat(transaction.cash_change).toFixed(2)}</p>
            `;
        } else {
            detailsHtml += `<p><strong>Reference Number:</strong> ${transaction.reference_number}</p>`;
        }

        detailsHtml += `<h3>Items:</h3><ul>`;
        for (const item of cartItems) {
            detailsHtml += `<li>${item.name} x ${item.quantity} - ₱${(item.price * item.quantity).toFixed(2)}</li>`;
        }
        detailsHtml += `</ul>`;

        detailsDiv.innerHTML = detailsHtml;
        modal.style.display = 'block';
    }

    function closeModal() {
        document.getElementById('transactionModal').style.display = 'none';
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
        const modal = document.getElementById('transactionModal');
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    };

    // Initialize date fields visibility and event listeners when DOM is loaded
    document.addEventListener('DOMContentLoaded', function() {
        const timeFilter = document.querySelector('select[name="time_filter"]');
        const customDateInputs = document.querySelectorAll('.custom-date');
        const dateLabels = document.querySelectorAll('.date-label');
        const dateRangeTitle = document.getElementById('dateRangeTitle');
        const startDate = document.querySelector('input[name="start_date"]');
        const endDate = document.querySelector('input[name="end_date"]');

        function updateDateFieldsVisibility() {
            const isCustom = timeFilter.value === 'custom';
            
            customDateInputs.forEach(input => {
                input.style.display = isCustom ? 'inline-block' : 'none';
            });
            
            dateLabels.forEach(label => {
                label.style.display = isCustom ? 'block' : 'none';
            });
            
            if (isCustom && startDate.value && endDate.value) {
                dateRangeTitle.style.display = 'block';
                dateRangeTitle.innerHTML = `<strong>Showing transactions from ${formatDateForDisplay(startDate.value)} to ${formatDateForDisplay(endDate.value)}</strong>`;
            } else {
                dateRangeTitle.style.display = 'none';
            }
        }

        // Initial setup
        updateDateFieldsVisibility();

        // Handle filter changes
        timeFilter.addEventListener('change', updateDateFieldsVisibility);

        // Update title when dates change
        [startDate, endDate].forEach(input => {
            input.addEventListener('change', function() {
                if (timeFilter.value === 'custom' && startDate.value && endDate.value) {
                    dateRangeTitle.style.display = 'block';
                    dateRangeTitle.innerHTML = `<strong>Showing transactions from ${formatDateForDisplay(startDate.value)} to ${formatDateForDisplay(endDate.value)}</strong>`;
                }
            });
        });

        // Maintain visibility if custom is selected
        if (timeFilter.value === 'custom') {
            customDateInputs.forEach(input => input.style.display = 'inline-block');
            dateLabels.forEach(label => label.style.display = 'block');
        }
    });
    </script>
</body>
</html>