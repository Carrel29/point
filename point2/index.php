<?php
session_start();
date_default_timezone_set('Asia/Manila');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // User is not logged in, redirect to login page
    header("Location: login.php");
    exit();
}

// Initialize cart if it doesn't exist
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Database connection
$conn = new mysqli("localhost", "root", "", "cafe_pos");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

require_once 'refresh_menu.php';

// Check if menu items exist in session, if not fetch them
if (!isset($_SESSION['menu_items'])) {
    $_SESSION['menu_items'] = refreshMenuItems($conn);
}

$menuItems = $_SESSION['menu_items'];

// Fetch all active categories
$categories_query = "SELECT DISTINCT c.name 
                    FROM categories c 
                    JOIN products p ON c.id = p.category_id 
                    WHERE c.archived = 0 AND p.archived = 0 
                    ORDER BY 
                        CASE c.name 
                            WHEN 'coffee' THEN 1
                            WHEN 'breakfast' THEN 2
                            WHEN 'addons' THEN 3
                            ELSE 4 
                        END";
$categories_result = $conn->query($categories_query);
$active_categories = [];
while ($row = $categories_result->fetch_assoc()) {
    $active_categories[] = strtolower($row['name']);
}

// Remove the second while loop since we're already using $_SESSION['menu_items']

// Debug output - remove this after testing
echo "<!-- Debug: Menu Items -->";
echo "<!-- " . print_r($menuItems, true) . " -->";
// Handle add-ons
// Handle add-ons
// Handle add-ons
if (isset($_POST['add_addon'])) {
    $addonId = $_POST['addon_id'] ?? '';
    $coffeeCode = $_POST['coffee_code'] ?? '';
    
    // Find the addon item
    $addon = array_filter($menuItems, function($item) use ($addonId) {
        return ($item['id'] ?? '') == $addonId && ($item['category'] ?? '') === 'addons';
    });
    $addon = reset($addon);
    
    if ($addon && isset($addon['name']) && isset($addon['price'])) {
        // Find the coffee in the cart
        foreach ($_SESSION['cart'] as &$cartItem) {
            if (($cartItem['code'] ?? '') === $coffeeCode && ($cartItem['category'] ?? '') === 'coffee') {
                // Add addon name to coffee name
                $cartItem['name'] .= ' + ' . $addon['name'];
                // Add addon price to coffee price
                $cartItem['price'] += $addon['price'];
                break;
            }
        }
    }
    
    // Redirect to prevent form resubmission
    header('Location: ' . $_SERVER['PHP_SELF'] . (isset($_GET['category']) ? '?category=' . $_GET['category'] : ''));
    exit;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_to_cart'])) {
        $itemId = $_POST['item_id'];
        $size = isset($_POST['size']) ? $_POST['size'] : null;
        
        $item = array_filter($menuItems, function($item) use ($itemId) {
            return $item['id'] == $itemId;
        });
        $item = reset($item);
        
        if ($item) {
            $cartItem = [
                'id' => $item['id'],
                'name' => $item['name'],
                'price' => $size ? $item['price'][$size] : $item['price'],
                'quantity' => 1,
                'category' => $item['category'],
                'code' => $item['code'] . ($size ? strtoupper($size[0]) : '')
            ];
            
            // Check if item already exists in cart
            $found = false;
            foreach ($_SESSION['cart'] as &$existingItem) {
                if ($existingItem['code'] === $cartItem['code']) {
                    $existingItem['quantity']++;
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                $_SESSION['cart'][] = $cartItem;
            }
        }
    } elseif (isset($_POST['remove_item'])) {
        $index = $_POST['index'];
        unset($_SESSION['cart'][$index]);
        $_SESSION['cart'] = array_values($_SESSION['cart']);
    } elseif (isset($_POST['clear_cart'])) {
        $_SESSION['cart'] = [];
    } elseif (isset($_POST['update_quantity'])) {
        $index = $_POST['index'];
        $delta = $_POST['delta'];
        
        if (isset($_SESSION['cart'][$index])) {
            $_SESSION['cart'][$index]['quantity'] += $delta;
            if ($_SESSION['cart'][$index]['quantity'] <= 0) {
                unset($_SESSION['cart'][$index]);
                $_SESSION['cart'] = array_values($_SESSION['cart']);
            }
        }
    }
    
    // Redirect to prevent form resubmission
    header('Location: ' . $_SERVER['PHP_SELF'] . (isset($_GET['category']) ? '?category=' . $_GET['category'] : ''));
    exit;
}


// Filter items based on category
$category = isset($_GET['category']) ? $_GET['category'] : null;
$searchTerm = isset($_GET['search']) ? strtolower($_GET['search']) : '';

$filteredItems = array_filter($menuItems, function($item) use ($category, $searchTerm) {
    return (!$category || $item['category'] === $category) && 
           (!$searchTerm || stripos($item['name'], $searchTerm) !== false || strtolower($item['code']) === $searchTerm);
});

// Calculate total
$total = 0;
foreach ($_SESSION['cart'] as $item) {
    $total += $item['price'] * $item['quantity'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Café POS System</title>
    <link rel="stylesheet" href="css/style.css">
<style>
    body {
    font-family: 'Arial', sans-serif;

    background-position: center;
    background-repeat: no-repeat;
    background-attachment: fixed;
    margin: 0;
    padding: 20px;
}
        .top-buttons {
            position: absolute;
            top: 10px;
            left: 10px;
            z-index: 1000;
            display: flex;
            gap: 10px;
        }

        .top-buttons a {
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 4px;
            font-weight: 500;
            display: inline-block;
            transition: background-color 0.3s;
        }

        .settings-button {
            background-color: #6c5ce7;
            color: white;
        }

        .settings-button:hover {
            background-color: #5a4dcc;
        }

        .history-button {
            background-color: #00b894;
            color: white;
        }

        .history-button:hover {
            background-color: #00a187;
        }

        .logout-button {
            background-color: #e74c3c;
            color: white;
        }

        .logout-button:hover {
            background-color: #c0392b;
        }
        .container {
    width: calc(100% - 340px); 
    padding: 20px;
    background-color: transparent; 
    border-radius: 10px;
}
.item {
    background-color: rgba(249, 249, 249, 0.9);
    border: 1px solid rgba(221, 221, 221, 0.8);
    border-radius: 8px;
    padding: 12px;
    text-align: center;
    backdrop-filter: blur(5px);
    -webkit-backdrop-filter: blur(5px);
}
.header h1 {
    color:rgb(145, 255, 0);
    font-size: 2em;
    margin: 0;
}

</style>
</head>
<body>
<div class="top-buttons">
    <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1): ?>
        <a href="settings.php" class="settings-button">Settings</a>
        <a href="history.php" class="history-button">History</a>
    <?php endif; ?>
    <a href="logout.php" class="logout-button">Logout</a>
</div>
    <div class="container">
        <div class="header">
            <h1>Café POS System</h1>
        </div>
        
        <div class="search-bar">
            <form method="GET" action="">
                <input type="text" name="search" placeholder="Search for items or type code names..." 
                       value="<?php echo htmlspecialchars($searchTerm); ?>">
                <input type="submit" value="Search">
            </form>
        </div>
        
        <div class="filter-buttons">
    <a href="?"><button>Show All</button></a>
    <?php foreach ($active_categories as $cat): ?>
        <a href="?category=<?php echo urlencode($cat); ?>">
            <button><?php echo ucfirst($cat); ?></button>
        </a>
    <?php endforeach; ?>
</div>
        
<div class="menu">
    <?php foreach ($filteredItems as $item): ?>
        <div class="item">
            <h3><?php echo htmlspecialchars($item['name'] ?? ''); ?> (<?php echo htmlspecialchars($item['code'] ?? ''); ?>)</h3>
            <?php if (isset($item['price']) && is_array($item['price']) && 
                      isset($item['price']['medium']) && isset($item['price']['large'])): ?>
                <p>Price: M: ₱<?php echo number_format($item['price']['medium'], 2); ?> L: ₱<?php echo number_format($item['price']['large'], 2); ?></p>
                <form method="POST">
                    <input type="hidden" name="item_id" value="<?php echo $item['id'] ?? ''; ?>">
                    <input type="hidden" name="size" value="medium">
                    <button type="submit" name="add_to_cart">Medium</button>
                </form>
                <form method="POST">
                    <input type="hidden" name="item_id" value="<?php echo $item['id'] ?? ''; ?>">
                    <input type="hidden" name="size" value="large">
                    <button type="submit" name="add_to_cart">Large</button>
                </form>
            <?php elseif ($item['category'] === 'addons'): ?>
                <p>Price: ₱<?php echo number_format($item['price'] ?? 0, 2); ?></p>
                <form method="POST">
                    <input type="hidden" name="addon_id" value="<?php echo $item['id']; ?>">
                    <select name="coffee_code" required>
                        <option value="">Select Coffee</option>
                        <?php 
                        foreach ($_SESSION['cart'] as $cartItem) {
                            // Only show coffee items in the dropdown
                            if ($cartItem['category'] === 'coffee') {
                                echo '<option value="' . htmlspecialchars($cartItem['code']) . '">' 
                                    . htmlspecialchars($cartItem['name']) 
                                    . ' (' . htmlspecialchars($cartItem['code']) . ')</option>';
                            }
                        }
                        ?>
                    </select>
                    <button type="submit" name="add_addon">Add to Coffee</button>
                </form>
            <?php else: ?>
                <p>Price: ₱<?php echo number_format($item['price'] ?? 0, 2); ?></p>
                <form method="POST">
                    <input type="hidden" name="item_id" value="<?php echo $item['id'] ?? ''; ?>">
                    <button type="submit" name="add_to_cart">Add to Cart</button>
                </form>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>>
    
<div class="cart">
    <h2>Cart</h2>
    <div id="cart-items">
        <?php if (empty($_SESSION['cart'])): ?>
            <p>Your cart is empty.</p>
        <?php else: ?>
            <?php foreach ($_SESSION['cart'] as $index => $item): ?>
                <div class="cart-item">
                    <span>
                        <?php echo htmlspecialchars($item['name'] ?? ''); ?> - 
                        ₱<?php echo number_format($item['price'] ?? 0, 2); ?> x 
                        <?php echo $item['quantity'] ?? 1; ?>
                    </span>
                    <div class="quantity-control">
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="index" value="<?php echo $index; ?>">
                            <input type="hidden" name="delta" value="-1">
                            <button type="submit" name="update_quantity">-</button>
                        </form>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="index" value="<?php echo $index; ?>">
                            <input type="hidden" name="delta" value="1">
                            <button type="submit" name="update_quantity">+</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <p class="total"><strong>Total: </strong>₱<?php echo number_format($total, 2); ?></p>
    
    <div class="remove-all">
        <form method="POST">
            <button type="submit" name="clear_cart" onclick="return confirm('Are you sure you want to remove all items from the cart?');">Remove All</button>
        </form>
    </div>
    
    <?php if (!empty($_SESSION['cart'])): ?>
        <div class="checkout">
            <form action="checkout.php" method="POST">
                <?php foreach ($_SESSION['cart'] as $index => $item): ?>
                    <input type="hidden" name="cart[<?php echo $index; ?>][name]" value="<?php echo htmlspecialchars($item['name']); ?>">
                    <input type="hidden" name="cart[<?php echo $index; ?>][price]" value="<?php echo $item['price']; ?>">
                    <input type="hidden" name="cart[<?php echo $index; ?>][quantity]" value="<?php echo $item['quantity']; ?>">
                <?php endforeach; ?>
                <input type="hidden" name="total" value="<?php echo $total; ?>">
                <button type="submit">Checkout</button>
            </form>
        </div>
    <?php endif; ?>
</div>
    </div>
</body>
</html>