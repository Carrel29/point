<?php
session_start();

function deleteImage($imagePath) {
    if (empty($imagePath)) return true;
    
    // Normalize directory separators
    $imagePath = str_replace('\\', '/', $imagePath);
    
    // Try different path combinations
    $possiblePaths = [
        $imagePath,                          // Original path
        __DIR__ . '/' . $imagePath,         // Absolute path
        realpath($imagePath),               // Resolved path
        realpath(__DIR__ . '/' . $imagePath) // Resolved absolute path
    ];
    
    foreach ($possiblePaths as $path) {
        if ($path && file_exists($path) && is_file($path)) {
            error_log("Deleting image: " . $path);
            return unlink($path);
        }
    }
    
    error_log("Could not find image to delete: " . $imagePath);
    return true; // Return true if file doesn't exist
}

function validateImagePath($path) {
    // Normalize directory separators
    $path = str_replace('\\', '/', $path);
    
    // Remove any parent directory references
    $path = str_replace('../', '', $path);
    
    // Ensure path starts with images/
    if (strpos($path, 'images/') !== 0) {
        $path = 'images/' . $path;
    }
    
    return $path;
}

// Add cache control headers
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
// At the top of your PHP file
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Increase upload limits if needed
ini_set('upload_max_filesize', '10M');
ini_set('post_max_size', '10M');
// Check if user is logged in and is admin
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: index.php");
    exit;
}
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
// Database connection
$conn = new mysqli("localhost", "root", "", "cafe_pos");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = '';
$target_dir = "images/";
if (!file_exists($target_dir)) {
    mkdir($target_dir, 0777, true);
    // Set permissions for the images directory and its contents
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($target_dir));
    foreach($iterator as $item) {
        chmod($item, 0755);
    }
    chmod($target_dir, 0755);
}

// Make sure the directory is writable
if (!is_writable($target_dir)) {
    chmod($target_dir, 0777);
}

// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        if (isset($_POST['add_payment_method'])) {
            try {
                $name = $conn->real_escape_string($_POST['name']);
                $code = $conn->real_escape_string($_POST['code']);
                $type = $conn->real_escape_string($_POST['type']);
                
                // Handle file upload for QR code
                $payment_image = null;
                if ($type === 'online' && isset($_FILES['payment_image']) && $_FILES['payment_image']['size'] > 0) {
                    // Validate file
                    $file_info = getimagesize($_FILES["payment_image"]["tmp_name"]);
                    if ($file_info === false) {
                        throw new Exception("File is not an image.");
                    }
                    
                    $file_extension = strtolower(pathinfo($_FILES["payment_image"]["name"], PATHINFO_EXTENSION));
                    if (!in_array($file_extension, ['jpg', 'jpeg', 'png'])) {
                        throw new Exception("Only JPG, JPEG & PNG files are allowed.");
                    }
                    
                    // Create year/month based folder structure
                    $date_folder = date('Y/m');
                    $target_folder = $target_dir . $date_folder;
                    if (!file_exists($target_folder)) {
                        mkdir($target_folder, 0777, true);
                    }
                    
                    // Generate new filename
                    $new_filename = uniqid() . '.' . $file_extension;
                    $relative_path = $date_folder . '/' . $new_filename;
                    $target_file = $target_dir . $relative_path;
                    
                    if (move_uploaded_file($_FILES["payment_image"]["tmp_name"], $target_file)) {
                        $payment_image = $target_file;
                    } else {
                        throw new Exception("Failed to upload image.");
                    }
                }
                
                // Rest of your existing code...
            } catch (Exception $e) {
                $message = $e->getMessage();
            }
        }
        
        // Handle archive/restore operations
        if (isset($_POST['archive_method'])) {
            $id = (int)$_POST['method_id'];
            $sql = "UPDATE payment_methods SET archived = 1 WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                $message = "Payment method archived successfully.";
            }
        }
        
        if (isset($_POST['restore_method'])) {
            $id = (int)$_POST['method_id'];
            $sql = "UPDATE payment_methods SET archived = 0 WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                $message = "Payment method restored successfully.";
            }
        }
        
        if (isset($_POST['edit_method'])) {
            try {
                $id = (int)$_POST['id'];
                $name = $conn->real_escape_string($_POST['name']);
                $code = $conn->real_escape_string($_POST['code']);
                $type = $conn->real_escape_string($_POST['type']);
                
                error_log("Edit method started - ID: $id, Name: $name, Type: $type");
                error_log("FILES data: " . print_r($_FILES, true));
        
                // Get current image before making any changes
                $stmt = $conn->prepare("SELECT payment_image FROM payment_methods WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $result = $stmt->get_result();
                $current_record = $result->fetch_assoc();
                $current_image = $current_record ? $current_record['payment_image'] : null;
        
                if (isset($_FILES['payment_image']) && $_FILES['payment_image']['size'] > 0) {
                    try {
                        // Validate file
                        $file_info = getimagesize($_FILES['payment_image']['tmp_name']);
                        if ($file_info === false) {
                            throw new Exception("Invalid image file");
                        }
        
                        // Check file extension
                        $file_extension = strtolower(pathinfo($_FILES['payment_image']['name'], PATHINFO_EXTENSION));
                        if (!in_array($file_extension, ['jpg', 'jpeg', 'png'])) {
                            throw new Exception("Only JPG, JPEG & PNG files are allowed");
                        }
        
                        // Create year/month based folder structure
                        $date_folder = date('Y/m');
                        $target_folder = $target_dir . $date_folder;
                        
                        // Create directory if it doesn't exist
                        if (!file_exists($target_folder)) {
                            if (!mkdir($target_folder, 0777, true)) {
                                throw new Exception("Failed to create directory structure");
                            }
                        }
        
                        // Generate new filename
                        $new_filename = uniqid() . '.' . $file_extension;
                        $relative_path = $date_folder . '/' . $new_filename;
                        $target_file = $target_dir . $relative_path;
                        
                        // Normalize path for storage
                        $target_file = str_replace('\\', '/', $target_file);
        
                        // Get current image before attempting new upload
                        $stmt = $conn->prepare("SELECT payment_image FROM payment_methods WHERE id = ?");
                        $stmt->bind_param("i", $id);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $current_record = $result->fetch_assoc();
                        $old_image = $current_record ? $current_record['payment_image'] : null;
        
                        // Move uploaded file
                        if (move_uploaded_file($_FILES['payment_image']['tmp_name'], $target_file)) {
                            // Delete old image only after successful upload
                            if ($old_image) {
                                deleteImage($old_image);
                            }
        
                            // Update database with new image path
                            $sql = "UPDATE payment_methods SET name = ?, code = ?, type = ?, payment_image = ? WHERE id = ?";
                            $stmt = $conn->prepare($sql);
                            $stmt->bind_param("ssssi", $name, $code, $type, $target_file, $id);
                            
                            if (!$stmt->execute()) {
                                // If database update fails, delete the uploaded file
                                unlink($target_file);
                                throw new Exception("Failed to update database");
                            }
                        } else {
                            throw new Exception("Failed to upload image");
                        }
                    } catch (Exception $e) {
                        error_log("Error in image upload: " . $e->getMessage());
                        throw $e;
                    }
                } else {
                    // Update without changing the image
                    $sql = "UPDATE payment_methods SET name = ?, code = ?, type = ? WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("sssi", $name, $code, $type, $id);
                    
                    if (!$stmt->execute()) {
                        throw new Exception("Failed to update payment method");
                    }
                }
        
                $_SESSION['success_message'] = "Payment method updated successfully";
                header("Location: " . $_SERVER['PHP_SELF']);
                exit();
        
            } catch (Exception $e) {
                error_log("Error in edit_method: " . $e->getMessage());
                $message = "Error: " . $e->getMessage();
            }
        }
    } catch (Exception $e) {
        $message = $e->getMessage();
    }
}

// Get display mode
$show_archived = isset($_GET['show_archived']) && $_GET['show_archived'] == 1;

// Fetch payment methods
$archived_clause = $show_archived ? "archived = 1" : "archived = 0";
$payment_methods = $conn->query("SELECT * FROM payment_methods WHERE $archived_clause ORDER BY name");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Settings - Café POS System</title>
    <link rel="stylesheet" href="css/settings.css">
</head>
<body>
    <div class="container">
        <?php if (!empty($message)): ?>
            <div class="message <?php echo strpos($message, 'Error') !== false ? 'error' : 'success'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="header">
            <h1>Payment Settings</h1>
            <div>
                <div class="user-info">
                    <div id="current-datetime" class="datetime">
                        Loading time...
                    </div>
                    <div class="datetime-label">
                        Philippine Time (UTC+8)
                    </div>
                </div>
                <div class="header-buttons">
                    <a href="?<?php echo $show_archived ? '' : 'show_archived=1'; ?>" class="toggle-archived">
                        <?php echo $show_archived ? 'Back to Active Methods' : 'View Archived Methods'; ?>
                    </a>
                    <a href="settings.php" class="back-button">Back to Settings</a>
                </div>
            </div>
        </div>

        <div class="section">
            <div class="section-header">
                <h2><?php echo $show_archived ? 'Archived Payment Methods' : 'Payment Methods'; ?></h2>
                <?php if (!$show_archived): ?>
                    <button onclick="showAddMethodModal()" class="btn btn-primary">Add New Method</button>
                <?php endif; ?>
            </div>

            <table class="items-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Code</th>
                        <th>Type</th>
                        <th>QR Image</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($method = $payment_methods->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($method['name']); ?></td>
                        <td><?php echo htmlspecialchars($method['code']); ?></td>
                        <td><?php echo htmlspecialchars($method['type']); ?></td>
                        <td>
                            <?php if ($method['payment_image']): ?>
                                <?php
                                $image_path = $method['payment_image'];
                                $image_url = str_replace('\\', '/', $image_path); // Normalize path for URL
                                ?>
                                <img src="<?php echo htmlspecialchars($image_url); ?>?v=<?php echo time(); ?>" 
                                    alt="Payment Image" style="max-width: 50px;"
                                    onerror="this.onerror=null; this.src='images/placeholder.png';">
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($show_archived): ?>
                                <button class="restore-btn" onclick="confirmRestore(<?php echo $method['id']; ?>)">
                                    Restore
                                </button>
                            <?php else: ?>
                                <button class="edit-btn" onclick='showEditMethodModal(<?php echo json_encode($method); ?>)'>
                                Edit
                            </button>
                                <button class="archive-btn" onclick="confirmArchive(<?php echo $method['id']; ?>)">
                                    Archive
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- Add Method Modal -->
        <div id="addMethodModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeModal('addMethodModal')">&times;</span>
                <h2>Add Payment Method</h2>
                <form method="POST" enctype="multipart/form-data" onsubmit="return validateForm()">
                    <div class="form-group">
                        <label>Name:</label>
                        <input type="text" name="name" required>
                    </div>
                    <div class="form-group">
                        <label>Code:</label>
                        <input type="text" name="code" required>
                    </div>
                    <div class="form-group">
                        <label>Type:</label>
                        <select name="type" required onchange="toggleImageUpload(this.value)">
                            <option value="cash">Cash</option>
                            <option value="online">Online</option>
                        </select>
                    </div>
                    <div class="form-group" id="image-upload" style="display: none;">
                        <label>Payment Image:</label>
                        <input type="file" name="payment_image" accept="image/*">
                    </div>
                    <button type="submit" name="add_payment_method" class="btn btn-primary">Add Method</button>
                </form>
            </div>
        </div>

        <!-- Edit Method Modal -->
        <!-- Edit Method Modal -->
        <div id="editMethodModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('editMethodModal')">&times;</span>
        <h2>Edit Payment Method</h2>
        <form method="POST" enctype="multipart/form-data" id="editMethodForm">
            <input type="hidden" name="id" id="edit_method_id">
            <input type="hidden" name="edit_method" value="1">
            <div class="form-group">
                <label>Name:</label>
                <input type="text" name="name" id="edit_method_name" required>
            </div>
            <div class="form-group">
                <label>Code:</label>
                <input type="text" name="code" id="edit_method_code" required>
            </div>
            <div class="form-group">
                <label>Type:</label>
                <select name="type" id="edit_method_type" required onchange="toggleEditImageUpload(this.value)">
                    <option value="cash">Cash</option>
                    <option value="online">Online</option>
                </select>
            </div>
            <div class="form-group" id="edit-image-upload">
                <label>Payment Image:</label>
                <input type="file" name="payment_image" id="edit_payment_image" accept="image/*" onchange="previewImage(this)">
                <div id="current-image-preview" style="margin-top: 10px;"></div>
                <small>Select a new image to update. Supported formats: JPG, JPEG, PNG</small>
            </div>
            <button type="submit" class="btn btn-primary">Save Changes</button>
        </form>
    </div>
</div>
    </div>

    <script>
        
        function showAddMethodModal() {
            document.getElementById('addMethodModal').style.display = 'block';
        }

        function previewImage(input) {
    const preview = document.getElementById('current-image-preview');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            preview.innerHTML = `
                <p>New Image Preview:</p>
                <img src="${e.target.result}" 
                     alt="New Image Preview" 
                     style="max-width: 200px;"
                     onerror="this.onerror=null; this.src='images/placeholder.png';">
                <p><small>Click Save Changes to update the image</small></p>
            `;
        };
        
        reader.readAsDataURL(input.files[0]);
    }
}


function showEditMethodModal(method) {
    try {
        // Parse the method if it's a string
        const methodData = typeof method === 'string' ? JSON.parse(method) : method;
        
        // Debug log
        console.log("Editing method:", methodData);
        
        // Set form values
        document.getElementById('edit_method_id').value = methodData.id;
        document.getElementById('edit_method_name').value = methodData.name;
        document.getElementById('edit_method_code').value = methodData.code;
        document.getElementById('edit_method_type').value = methodData.type;
        
        // Reset file input
        const fileInput = document.getElementById('edit_payment_image');
        fileInput.value = '';
        
        // Toggle image upload field
        const imageUpload = document.getElementById('edit-image-upload');
        imageUpload.style.display = methodData.type === 'online' ? 'block' : 'none';
        
        // Show current image if exists
        const preview = document.getElementById('current-image-preview');
        if (methodData.payment_image) {
            preview.innerHTML = `
                <p>Current Image:</p>
                <img src="${methodData.payment_image}?v=${new Date().getTime()}" 
                     alt="Payment Image" 
                     style="max-width: 200px;"
                     onerror="this.src='images/placeholder.png';">
            `;
        } else {
            preview.innerHTML = '<p>No image currently set</p>';
        }
        
        // Show modal
        document.getElementById('editMethodModal').style.display = 'block';
    } catch (error) {
        console.error("Error showing edit modal:", error);
        alert("Error showing edit form. Please try again.");
    }
}
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        function toggleImageUpload(type) {
            document.getElementById('image-upload').style.display = type === 'online' ? 'block' : 'none';
        }

        function toggleEditImageUpload(type) {
            const imageUpload = document.getElementById('edit-image-upload');
            imageUpload.style.display = type === 'online' ? 'block' : 'none';
        }

        function confirmArchive(id) {
            if (confirm('Are you sure you want to archive this payment method?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="method_id" value="${id}">
                    <input type="hidden" name="archive_method" value="1">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function confirmRestore(id) {
            if (confirm('Are you sure you want to restore this payment method?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="method_id" value="${id}">
                    <input type="hidden" name="restore_method" value="1">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.className === 'modal') {
                event.target.style.display = 'none';
            }
        }
        function submitEditForm(event) {
    // Remove this function entirely as we're using normal form submission
}
        function validateForm() {
            const form = document.activeElement.form;
            const isEditForm = form.querySelector('input[name="edit_method"]') !== null;
            const type = form.querySelector('select[name="type"]').value;
            const imageInput = form.querySelector('input[name="payment_image"]');
            
            // For new online payment methods, require image
            if (!isEditForm && type === 'online' && (!imageInput.files || imageInput.files.length === 0)) {
                alert('Please select an image for online payment methods.');
                return false;
            }
            
            return true;
        }

    document.getElementById('editMethodForm').addEventListener('submit', function(e) {
    // Form will submit normally, but we can add validation here if needed
    const type = document.getElementById('edit_method_type').value;
    const imageInput = document.getElementById('edit_payment_image');
    
    // If it's an online payment and no image is set, check if there's an existing image
    if (type === 'online' && !imageInput.files.length) {
        const preview = document.getElementById('current-image-preview');
        if (!preview.querySelector('img')) {
            e.preventDefault();
            alert('Please select an image for online payment methods.');
            return false;
        }
    }
    return true;
});

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