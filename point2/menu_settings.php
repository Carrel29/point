<?php
session_start();

date_default_timezone_set('Asia/Manila');
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

require_once 'refresh_menu.php';
// Initialize message variable
$message = '';

// Handle archive/restore operations
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Archive item
        if (isset($_POST['archive_item'])) {
            $id = (int)$_POST['id'];
            $sql = "UPDATE products SET archived = 1 WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                $message = "Item moved to archive successfully.";
            } else {
                throw new Exception("Error archiving item.");
            }
        }

        // Restore item
        if (isset($_POST['restore_item'])) {
            $id = (int)$_POST['id'];
            $sql = "UPDATE products SET archived = 0 WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                $message = "Item restored successfully.";
            } else {
                throw new Exception("Error restoring item.");
            }
        }

        // Archive category
        if (isset($_POST['archive_category'])) {
            $id = (int)$_POST['category_id'];
            
            // Check if category has active products
            $check = $conn->query("SELECT COUNT(*) as count FROM products WHERE category_id = $id AND archived = 0");
            $result = $check->fetch_assoc();
            
            if ($result['count'] > 0) {
                throw new Exception("Cannot archive category with active products. Please archive all products in this category first.");
            }

            $sql = "UPDATE categories SET archived = 1 WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                $message = "Category moved to archive successfully.";
            } else {
                throw new Exception("Error archiving category.");
            }
        }

        // Restore category
        if (isset($_POST['restore_category'])) {
            $id = (int)$_POST['category_id'];
            $sql = "UPDATE categories SET archived = 0 WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                $message = "Category restored successfully.";
            } else {
                throw new Exception("Error restoring category.");
            }
        }

        //Edit item
        if (isset($_POST['edit_item'])) {
            $id = (int)$_POST['id'];
            $name = $conn->real_escape_string($_POST['name']);
            $code = $conn->real_escape_string($_POST['code']);
            $category_id = (int)$_POST['category_id'];
            $price_medium = !empty($_POST['price_medium']) ? (float)$_POST['price_medium'] : null;
            $price_large = !empty($_POST['price_large']) ? (float)$_POST['price_large'] : null;
            $price = !empty($_POST['price']) ? (float)$_POST['price'] : null;
        
            // Check if code exists for other items
            $check = $conn->query("SELECT id FROM products WHERE code = '$code' AND id != $id AND archived = 0");
            if ($check->num_rows > 0) {
                throw new Exception("Product code already exists!");
            }
        
            $sql = "UPDATE products 
                    SET name=?, code=?, price_medium=?, price_large=?, price=?, category_id=? 
                    WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssdddii", $name, $code, $price_medium, $price_large, $price, $category_id, $id);
            
            if ($stmt->execute()) {
                $message = "Item updated successfully!";
            } else {
                throw new Exception("Error updating item!");
            }
        }
        // Edit category
        if (isset($_POST['edit_category'])) {
            $category_id = (int)$_POST['category_id'];
            $category_name = $conn->real_escape_string($_POST['category_name']);
            
            $sql = "UPDATE categories SET name = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $category_name, $category_id);
            
            if ($stmt->execute()) {
                $message = "Category updated successfully!";
            } else {
                $message = "Error updating category!";
            }
        }

        // Add new category
        if (isset($_POST['add_category'])) {
            $category_name = $conn->real_escape_string($_POST['category_name']);
            
            $sql = "INSERT INTO categories (name) VALUES (?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $category_name);
            
            if ($stmt->execute()) {
                $message = "Category added successfully!";
            } else {
                $message = "Error adding category!";
            }
        }

        // Add new item
        if (isset($_POST['add_item'])) {
            $name = $conn->real_escape_string($_POST['name']);
            $code = $conn->real_escape_string($_POST['code']);
            $category_id = (int)$_POST['category_id'];
            $price_medium = !empty($_POST['price_medium']) ? (float)$_POST['price_medium'] : null;
            $price_large = !empty($_POST['price_large']) ? (float)$_POST['price_large'] : null;
            $price = !empty($_POST['price']) ? (float)$_POST['price'] : null;

            // Check if code exists
            $check = $conn->query("SELECT id FROM products WHERE code = '$code' AND archived = 0");
            if ($check->num_rows > 0) {
                $message = "Product code already exists!";
            } else {
                $sql = "INSERT INTO products (name, code, category_id, price_medium, price_large, price) 
                        VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssiddd", $name, $code, $category_id, $price_medium, $price_large, $price);
                
                if ($stmt->execute()) {
                    $message = "Item added successfully!";
                } else {
                    $message = "Error adding item!";
                }
            }
        }
        if ($stmt->execute()) {
            $message = "Operation successful!";
            clearMenuCache();
        }

    } catch (Exception $e) {
        $message = $e->getMessage();
    }
}

// Get display mode
$show_archived = isset($_GET['show_archived']) && $_GET['show_archived'] == 1;

// Fetch categories based on archive status
$archived_clause = $show_archived ? "archived = 1" : "archived = 0";
$categories = $conn->query("SELECT * FROM categories WHERE $archived_clause ORDER BY name");

// Fetch products with category names based on archive status
$products = $conn->query("SELECT p.*, c.name as category_name 
                         FROM products p 
                         LEFT JOIN categories c ON p.category_id = c.id 
                         WHERE p.$archived_clause 
                         ORDER BY c.name, p.name");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $show_archived ? 'Archived Items' : 'Settings'; ?> - Café POS System</title>
    <link rel="stylesheet" href="css/settings.css">
    <style>.add-btn {
        background-color: #4CAF50;
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 5px;
        cursor: pointer;
        font-size: 14px;
        text-decoration: none;
        display: inline-block;
        transition: background-color 0.3s;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .add-btn:hover {
        background-color: #45a049;
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    }
</style>
</head>
<body>
    <div class="container">
        <?php if (!empty($message)): ?>
            <div class="message <?php echo strpos($message, 'Error') !== false || strpos($message, 'Cannot') !== false ? 'error' : 'success'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="header">
            <h1><?php echo $show_archived ? 'Archived Items' : 'Menu Settings'; ?></h1>
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
                    <a href="?<?php echo $show_archived ? '' : 'show_archived=1'; ?>" class="toggle-archived">
                        <?php echo $show_archived ? 'Back to Active Items' : 'View Archived Items'; ?>
                    </a>
                    <a href="settings.php" class="back-button">Back to Settings</a>
                    <a href="index.php" class="back-button">Back to POS</a>
                    <a href="logout.php" class="logout-button">Logout</a>
                </div>
            </div>
        </div>

        <!-- Categories Section -->
        <div class="section">
            <div class="section-header">
                <h2><?php echo $show_archived ? 'Archived Categories' : 'Categories'; ?></h2>
                <?php if (!$show_archived): ?>
                    <button class="add-btn" onclick="document.getElementById('addCategoryModal').style.display='block'">Add New Category</button>
                <?php endif; ?>
            </div>
            <table class="category-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($category = $categories->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($category['name']); ?></td>
                        <td>
                        <?php if ($show_archived): ?>
                            <button class="restore-btn" onclick="confirmRestore('category', <?php echo $category['id']; ?>)">
                                Restore
                            </button>
                        <?php else: ?>
                            <button class="edit-btn" onclick="showEditCategoryModal(<?php echo htmlspecialchars(json_encode($category)); ?>)">
                                Edit
                            </button>
                            <button class="archive-btn" onclick="confirmArchive('category', <?php echo $category['id']; ?>)">
                                Archive
                            </button>
                        <?php endif; ?>
                    </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- Menu Items Section -->
        <div class="section">
        <div class="section-header">
            <h2><?php echo $show_archived ? 'Archived Menu Items' : 'Menu Items'; ?></h2>
            <?php if (!$show_archived): ?>
                <button class="add-btn" onclick="document.getElementById('addItemModal').style.display='block'">Add New Item</button>
            <?php endif; ?>
        </div>
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Price (Medium)</th>
                        <th>Price (Large)</th>
                        <th>Price</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($item = $products->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['code']); ?></td>
                        <td><?php echo htmlspecialchars($item['name']); ?></td>
                        <td><?php echo htmlspecialchars($item['category_name']); ?></td>
                        <td><?php echo $item['price_medium'] ? '₱'.number_format($item['price_medium'], 2) : '-'; ?></td>
                        <td><?php echo $item['price_large'] ? '₱'.number_format($item['price_large'], 2) : '-'; ?></td>
                        <td><?php echo $item['price'] ? '₱'.number_format($item['price'], 2) : '-'; ?></td>
                        <td>
                        <?php if ($show_archived): ?>
                            <button class="restore-btn" onclick="confirmRestore('item', <?php echo $item['id']; ?>)">
                                Restore
                            </button>
                        <?php else: ?>
                            <button class="edit-btn" onclick="showEditItemModal(<?php echo htmlspecialchars(json_encode($item)); ?>)">
                                Edit
                            </button>
                            <button class="archive-btn" onclick="confirmArchive('item', <?php echo $item['id']; ?>)">
                                Archive
                            </button>
                        <?php endif; ?>
                    </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
                <!-- Edit Item Modal -->
       <!-- Edit Item Modal -->
        <div id="editItemModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeModal('editItemModal')">&times;</span>
                <h2>Edit Item</h2>
                <form method="POST">
                    <input type="hidden" name="id" id="edit_item_id">
                    <div class="form-group">
                        <label>Name:</label>
                        <input type="text" name="name" id="edit_item_name" required>
                    </div>
                    <div class="form-group">
                        <label>Code:</label>
                        <input type="text" name="code" id="edit_item_code" required>
                    </div>
                    <div class="form-group">
                        <label>Category:</label>
                        <select name="category_id" id="edit_item_category" required>
                            <?php
                            $active_categories = $conn->query("SELECT * FROM categories WHERE archived = 0 ORDER BY name");
                            while($cat = $active_categories->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Price (Medium):</label>
                        <input type="number" step="0.01" name="price_medium" id="edit_item_price_medium">
                    </div>
                    <div class="form-group">
                        <label>Price (Large):</label>
                        <input type="number" step="0.01" name="price_large" id="edit_item_price_large">
                    </div>
                    <div class="form-group">
                        <label>Price:</label>
                        <input type="number" step="0.01" name="price" id="edit_item_price">
                    </div>
                    <button type="submit" name="edit_item" class="btn btn-primary">Save Changes</button>
                </form>
            </div>
        </div>

                <!-- Edit Category Modal -->
        <div id="editCategoryModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeModal('editCategoryModal')">&times;</span>
                <h2>Edit Category</h2>
                <form method="POST">
                    <input type="hidden" name="category_id" id="edit_category_id">
                    <div class="form-group">
                        <label>Name:</label>
                        <input type="text" name="category_name" id="edit_category_name" required>
                    </div>
                    <button type="submit" name="edit_category" class="btn btn-primary">Save Changes</button>
                </form>
            </div>
        </div>

        <!-- Add New Category Modal -->
        <div id="addCategoryModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeModal('addCategoryModal')">&times;</span>
                <h2>Add New Category</h2>
                <form method="POST">
                    <div class="form-group">
                        <label>Name:</label>
                        <input type="text" name="category_name" required>
                    </div>
                    <button type="submit" name="add_category" class="btn btn-primary">Add Category</button>
                </form>
            </div>
        </div>

        <!-- Add New Item Modal -->
        <div id="addItemModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeModal('addItemModal')">&times;</span>
                <h2>Add New Item</h2>
                <form method="POST">
                    <div class="form-group">
                        <label>Name:</label>
                        <input type="text" name="name" required>
                    </div>
                    <div class="form-group">
                        <label>Code:</label>
                        <input type="text" name="code" required>
                    </div>
                    <div class="form-group">
                        <label>Category:</label>
                        <select name="category_id" required>
                            <?php
                            $active_categories = $conn->query("SELECT * FROM categories WHERE archived = 0 ORDER BY name");
                            while($cat = $active_categories->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Price (Medium):</label>
                        <input type="number" step="0.01" name="price_medium">
                    </div>
                    <div class="form-group">
                        <label>Price (Large):</label>
                        <input type="number" step="0.01" name="price_large">
                    </div>
                    <div class="form-group">
                        <label>Price:</label>
                        <input type="number" step="0.01" name="price">
                    </div>
                    <button type="submit" name="add_item" class="btn btn-primary">Add Item</button>
                </form>
            </div>
        </div>
    </div>
    

    <script>
        // Function to confirm and handle archive operations
        function confirmArchive(type, id) {
            const message = type === 'category' 
                ? 'Are you sure you want to archive this category?' 
                : 'Are you sure you want to archive this item?';
                
            if (confirm(message)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="${type === 'category' ? 'category_id' : 'id'}" value="${id}">
                    <input type="hidden" name="archive_${type}" value="1">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Function to confirm and handle restore operations
        function confirmRestore(type, id) {
            const message = type === 'category' 
                ? 'Are you sure you want to restore this category?' 
                : 'Are you sure you want to restore this item?';
                
            if (confirm(message)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="${type === 'category' ? 'category_id' : 'id'}" value="${id}">
                    <input type="hidden" name="restore_${type}" value="1">
                    <input type="hidden" name="show_archived" value="1">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Auto-hide messages after 3 seconds
        setTimeout(function() {
            const message = document.querySelector('.message');
            if (message) {
                message.style.display = 'none';
            }
        }, 3000);
        function showEditItemModal(item) {
            document.getElementById('edit_item_id').value = item.id;
            document.getElementById('edit_item_name').value = item.name;
            document.getElementById('edit_item_code').value = item.code;
            document.getElementById('edit_item_category').value = item.category_id;
            document.getElementById('edit_item_price_medium').value = item.price_medium || '';
            document.getElementById('edit_item_price_large').value = item.price_large || '';
            document.getElementById('edit_item_price').value = item.price || '';
            document.getElementById('editItemModal').style.display = 'block';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.className === 'modal') {
                event.target.style.display = 'none';
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

        function showEditCategoryModal(category) {
            document.getElementById('edit_category_id').value = category.id;
            document.getElementById('edit_category_name').value = category.name;
            document.getElementById('editCategoryModal').style.display = 'block';
        }

        function clearMenuCache() {
            if (isset($_SESSION['menu_items'])) {
                unset($_SESSION['menu_items']);
            }
        }

        // Add this to your JavaScript section
function updatePriceFields() {
    const category = document.querySelector('select[name="category_id"]').value;
    const mediumPrice = document.querySelector('.price-medium');
    const largePrice = document.querySelector('.price-large');
    const regularPrice = document.querySelector('.price-regular');
    
    // Show/hide price fields based on category
    if (category) {
        mediumPrice.style.display = 'none';
        largePrice.style.display = 'none';
        regularPrice.style.display = 'block';
    }
}

// Add this to your form
document.querySelector('select[name="category_id"]').addEventListener('change', updatePriceFields);
    </script>
</body>
</html>