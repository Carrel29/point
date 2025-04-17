<?php
session_start();


// Database connection
$conn = new mysqli("localhost", "root", "", "cafe_pos");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the cart data and total from POST
    $cart = isset($_POST['cart']) ? $_POST['cart'] : [];
    $total = isset($_POST['total']) ? floatval($_POST['total']) : 0;

    // Store the data in JavaScript variables
    $cartJSON = json_encode($cart);
    $totalJSON = json_encode($total);

    // Fetch active payment methods
    $payment_methods = $conn->query("SELECT * FROM payment_methods WHERE archived = 0 ORDER BY name");
} else {
    // Redirect back to index if accessed directly without POST data
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            padding: 20px;
        }
        .container {
            width: 100%;
            max-width: 1200px;
            padding: 20px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            margin-top: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h1 {
            color: #333;
            font-size: 2.5em;
            margin: 0;
        }
        .payment-options {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 20px;
        }
        .payment-options button {
            background-color: #6c5ce7;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
            transition: background-color 0.3s;
        }
        .payment-options button:hover {
            background-color: #5a4dcc;
        }
        .qr-code {
            text-align: center;
            margin-top: 20px;
        }
        .qr-code img {
            max-width: 300px;
            width: 100%;
            height: auto;
            margin: 20px 0;
        }
        .cash-payment {
            display: none;
            margin-top: 20px;
        }
        input[type="number"], input[type="text"] {
            padding: 10px;
            width: 60%; /* Reduced width to make the input smaller */
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        button {
            padding: 10px 20px;
            border: none;
            background-color: #6c5ce7;
            color: white;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
            margin-top: 10px;
        }
        button:hover {
            background-color: #5a4dcc;
        }
        .total {
            font-size: 1.2em;
            color: #333;
            margin: 20px 0;
            text-align: center;
        }
        .back-to-pos {
            display: none;
            text-align: center;
            margin-top: 20px;
        }
        .back-to-pos-cash {
            display: none;
            text-align: center;
            margin-top: 20px;
        }
        .receipt {
            margin-top: 20px;
        }
        .receipt-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #ddd;
        }
        .qr-code-container {
            text-align: center;
            margin: 20px 0;
        }

        .qr-code-container img {
            max-width: 300px;
            width: 100%;
            height: auto;
            margin: 20px 0;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 10px;
        }
        .transaction-details {
    margin-top: 20px;
    padding: 20px;
    background-color: #f8f9fa;
    border-radius: 10px;
    border: 2px solid #6c5ce7;
}

.transaction-details h2 {
    color: #6c5ce7;
    text-align: center;
    margin-bottom: 20px;
}

.transaction-info {
    margin-bottom: 15px;
    padding-bottom: 15px;
    border-bottom: 1px solid #dee2e6;
}

.transaction-info:last-child {
    border-bottom: none;
}

.detail-row {
    display: flex;
    justify-content: space-between;
    margin: 5px 0;
}

.detail-label {
    font-weight: bold;
    color: #495057;
}

.detail-value {
    color: #212529;
}

.items-list {
    margin: 10px 0;
    padding: 10px;
    background-color: white;
    border-radius: 5px;
}

.print-button {
    background-color: #28a745;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    margin-top: 15px;
    width: 100%;
}

.print-button:hover {
    background-color: #218838;
}
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Checkout</h1>
            <p><strong>Total: </strong>₱<span id="checkout-total">0</span></p>
        </div>
        <div class="receipt" id="receipt">
            <!-- Receipt items will be added here by JavaScript -->
        </div>
        <div class="payment-options">
            <?php while($method = $payment_methods->fetch_assoc()): ?>
                <button onclick="processPayment('<?php echo $method['code']; ?>', '<?php echo $method['type']; ?>')">
                    <?php echo htmlspecialchars($method['name']); ?>
                </button>
            <?php endwhile; ?>
        </div>

        <!-- Cash Payment Section -->
        <div class="cash-payment" id="cash-payment">
            <h3>Cash Payment</h3>
            <label for="cash-received">Enter Amount Received:</label>
            <input type="number" id="cash-received" placeholder="Enter amount...">
            <p><strong>Change: </strong>₱<span id="change">0</span></p>
            <button onclick="calculateChange()">Compute Change</button>
            <div class="back-to-pos-cash" id="back-to-pos-cash">
                <button onclick="goBackToPOS()">Order Again</button>
            </div>
        </div>

        <!-- QR Code Section -->
        <div class="qr-code" id="qr-code" style="display: none;">
            <?php
            // Reset the result pointer to fetch QR codes
            $payment_methods->data_seek(0);
            while($method = $payment_methods->fetch_assoc()) {
                if($method['type'] === 'online') {
                    echo '<div class="qr-code-container" id="qr-' . htmlspecialchars($method['code']) . '" style="display: none;">';
                    echo '<h3>Scan to Pay with ' . htmlspecialchars($method['name']) . '</h3>';
                    if($method['payment_image']) {
                        echo '<img src="' . htmlspecialchars($method['payment_image']) . '" alt="' . htmlspecialchars($method['name']) . ' QR Code">';
                    }
                    echo '</div>';
                }
            }
            ?>
            <br>
            <label for="reference-number">Enter Last 4 Digits of Reference Number:</label>
            <input type="text" id="reference-number" maxlength="4" placeholder="Last 4 digits...">
            <button onclick="confirmPayment()">Confirm Payment</button>
            <div class="back-to-pos" id="back-to-pos">
                <button onclick="goBackToPOS()">Order Again</button>
            </div>
        </div>
        <div id="transaction-complete" style="display: none;" class="transaction-details">
            <h2>Transaction Complete</h2>
            <div id="transaction-details"></div>
        </div>
    </div>

    <script>
    // Initialize the cart data from PHP
    const cartData = <?php echo $cartJSON; ?>;
    const totalAmount = <?php echo $totalJSON; ?>;

    // Set the total in the display
    document.getElementById('checkout-total').textContent = totalAmount.toFixed(2);

    function loadReceipt() {
        const receiptContainer = document.getElementById('receipt');
        
        if (!cartData || Object.keys(cartData).length === 0) {
            receiptContainer.innerHTML = '<p>Your receipt is empty.</p>';
        } else {
            // Clear existing content
            receiptContainer.innerHTML = '';
            
            // Add each item to the receipt
            Object.values(cartData).forEach(item => {
                const receiptItem = document.createElement('div');
                receiptItem.classList.add('receipt-item');
                receiptItem.innerHTML = `
                    <span>${item.name} x ${item.quantity}</span>
                    <span>₱${(parseFloat(item.price) * parseInt(item.quantity)).toFixed(2)}</span>
                `;
                receiptContainer.appendChild(receiptItem);
            });

            // Add total
            const totalItem = document.createElement('div');
            totalItem.classList.add('receipt-item');
            totalItem.innerHTML = `
                <strong>Total</strong>
                <strong>₱${totalAmount.toFixed(2)}</strong>
            `;
            receiptContainer.appendChild(totalItem);
        }
    }

    function processPayment(code, type) {
        // Hide all payment sections first
        document.getElementById('cash-payment').style.display = 'none';
        document.getElementById('qr-code').style.display = 'none';
        document.getElementById('back-to-pos').style.display = 'none';
        document.getElementById('back-to-pos-cash').style.display = 'none';

        // Show the appropriate section
        if (type === 'cash') {
            document.getElementById('cash-payment').style.display = 'block';
        } else if (type === 'online') {
            document.getElementById('qr-code').style.display = 'block';
            // Show the corresponding QR code
            showQRCode(code);
        }
    }
    function showQRCode(paymentCode) {
        // Get all QR code containers
        const qrContainers = document.querySelectorAll('.qr-code-container');
        
        // Hide all containers first
        qrContainers.forEach(container => {
            container.style.display = 'none';
        });
        
        // Show the selected payment method's QR code
        const selectedContainer = document.getElementById(`qr-${paymentCode}`);
        if (selectedContainer) {
            selectedContainer.style.display = 'block';
        }
    }

    function calculateChange() {
        const cashReceived = parseFloat(document.getElementById('cash-received').value);
        if (isNaN(cashReceived) || cashReceived < totalAmount) {
            alert('Invalid amount. Please enter an amount greater than or equal to the total.');
        } else {
            const change = cashReceived - totalAmount;
            document.getElementById('change').textContent = change.toFixed(2);
            
            // Save transaction
            saveTransaction('cash', cashReceived, change);
            
            alert('Transaction complete. Change: ₱' + change.toFixed(2));
            document.getElementById('back-to-pos-cash').style.display = 'block';
        }
    }


    function confirmPayment() {
        const referenceNumber = document.getElementById('reference-number').value;
        if (referenceNumber.length !== 4) {
            alert('Please enter the last 4 digits of the reference number.');
            return;
        }

        // Save transaction
        saveTransaction('online', null, null, referenceNumber);
        
        alert('Payment Confirmed! Reference Number: ' + referenceNumber);
        
        // Hide both the input and its label
        document.getElementById('reference-number').style.display = 'none';
        document.querySelector('label[for="reference-number"]').style.display = 'none';
        
        // Get the confirm payment button and change its text and onclick function
        const confirmButton = document.querySelector('.qr-code button');
        confirmButton.textContent = 'Order Again';
        confirmButton.onclick = function() {
            goBackToPOS();
        };
    }

    function saveTransaction(paymentMethod, cashReceived = null, change = null, referenceNumber = null) {
    const transactionData = {
        paymentMethod: paymentMethod,
        total: totalAmount,
        cart: cartData,
        cashReceived: cashReceived,
        change: change,
        referenceNumber: referenceNumber
    };

    fetch('save_transaction.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(transactionData)
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            console.error('Error saving transaction:', data.error);
            alert('Error saving transaction: ' + (data.error || 'Unknown error'));
        } else {
            // Hide payment sections
            document.getElementById('cash-payment').style.display = 'none';
            document.getElementById('qr-code').style.display = 'none';
            document.querySelector('.payment-options').style.display = 'none';

            // Create transaction details HTML
            const now = new Date();
            let detailsHTML = `
                <div class="transaction-info">
                    <div class="detail-row">
                        <span class="detail-label">Transaction Date:</span>
                        <span class="detail-value">${now.toLocaleString('en-US', { timeZone: 'UTC' })}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Payment Method:</span>
                        <span class="detail-value">${paymentMethod.toUpperCase()}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Total Amount:</span>
                        <span class="detail-value">₱${totalAmount.toFixed(2)}</span>
                    </div>`;

            if (paymentMethod === 'cash') {
                detailsHTML += `
                    <div class="detail-row">
                        <span class="detail-label">Cash Received:</span>
                        <span class="detail-value">₱${cashReceived.toFixed(2)}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Change:</span>
                        <span class="detail-value">₱${change.toFixed(2)}</span>
                    </div>`;
            } else {
                detailsHTML += `
                    <div class="detail-row">
                        <span class="detail-label">Reference Number:</span>
                        <span class="detail-value">${referenceNumber}</span>
                    </div>`;
            }

            detailsHTML += `</div>
                <div class="items-list">
                    <h3>Items Purchased</h3>`;

            Object.values(cartData).forEach(item => {
                detailsHTML += `
                    <div class="detail-row">
                        <span class="detail-label">${item.name} x ${item.quantity}</span>
                        <span class="detail-value">₱${(parseFloat(item.price) * parseInt(item.quantity)).toFixed(2)}</span>
                    </div>`;
            });

            detailsHTML += `</div>
                <button class="print-button" onclick="window.print()">Print Receipt</button>
                <button onclick="goBackToPOS()" class="print-button" style="margin-top: 10px; background-color: #6c5ce7;">New Order</button>`;

            // Show transaction details
            document.getElementById('transaction-details').innerHTML = detailsHTML;
            document.getElementById('transaction-complete').style.display = 'block';
        }
    })
    .catch(error => {
        console.error('Network error:', error);
        alert('Error saving transaction. Please try again.');
    });
}

    function goBackToPOS() {
    window.location.href = 'clear_cart.php';
    }

    // Load the receipt when the page loads
    loadReceipt();
</script>
</body>
</html>