<?php
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
$conn = new mysqli("localhost", "root", "", "cafe_pos");
if ($conn->connect_error) {
    die(json_encode(['success' => false, 'error' => "Connection failed: " . $conn->connect_error]));
}

// Log incoming data for debugging
$rawData = file_get_contents('php://input');
error_log("Received data: " . $rawData);

$data = json_decode($rawData, true);

if ($data) {
    try {
        // Validate data
        if (!isset($data['paymentMethod']) || !isset($data['total']) || !isset($data['cart'])) {
            throw new Exception("Missing required fields");
        }

        // Convert values to appropriate types
        $paymentMethod = strval($data['paymentMethod']);
        $total = floatval($data['total']);
        $cashReceived = isset($data['cashReceived']) ? floatval($data['cashReceived']) : null;
        $change = isset($data['change']) ? floatval($data['change']) : null;
        $referenceNumber = isset($data['referenceNumber']) ? strval($data['referenceNumber']) : null;
        $cartJson = json_encode($data['cart']);
        $username = isset($_SESSION['username']) ? $_SESSION['username'] : 'Unknown';

        // Prepare SQL with proper value handling
        $sql = "INSERT INTO transactions (
            transaction_date,
            payment_method,
            total_amount,
            cash_received,
            cash_change,
            reference_number,
            cart_items,
            username
        ) VALUES (NOW(), ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        // Bind parameters with proper type specification
        $stmt->bind_param(
            "sdddsss",
            $paymentMethod,
            $total,
            $cashReceived,
            $change,
            $referenceNumber,
            $cartJson,
            $username
        );

        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }

        echo json_encode([
            'success' => true,
            'message' => 'Transaction saved successfully'
        ]);

    } catch (Exception $e) {
        error_log("Transaction error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage(),
            'data_received' => $data
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Invalid data received',
        'raw_data' => $rawData
    ]);
}