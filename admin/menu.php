<?php require_once 'auth_check.php'; ?>
<?php
require_once 'db.php';

// Initialize message variable
$message = '';

// Handle Add New Menu Item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $name = $_POST['name'];
    $price = $_POST['price'];
    $description = $_POST['description'];
    $image_url = '';

    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $target_dir = "uploads/";

        // Create directory if it doesn't exist
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $new_file_name = uniqid() . '.' . $file_extension;
        $target_file = $target_dir . $new_file_name;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
            $image_url = $target_file;
        }
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO menu (name, price, description, image_url) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $price, $description, $image_url]);
        $message = "Menu item added successfully!";
    } catch (\PDOException $e) {
        $message = "Error: " . $e->getMessage();
    }
}

// Handle Delete Menu Item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id = $_POST['id'];

    try {
        $stmt = $pdo->prepare("DELETE FROM menu WHERE id = ?");
        $stmt->execute([$id]);
        $message = "Menu item deleted successfully!";
    } catch (\PDOException $e) {
        $message = "Error: " . $e->getMessage();
    }
}

// Handle Edit Menu Item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $price = $_POST['price'];
    $description = $_POST['description'];

    try {
        // Check if a new image is uploaded
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $target_dir = "uploads/";

            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }

            $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $new_file_name = uniqid() . '.' . $file_extension;
            $target_file = $target_dir . $new_file_name;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                $stmt = $pdo->prepare("UPDATE menu SET name = ?, price = ?, description = ?, image_url = ? WHERE id = ?");
                $stmt->execute([$name, $price, $description, $target_file, $id]);
            }
        } else {
            $stmt = $pdo->prepare("UPDATE menu SET name = ?, price = ?, description = ? WHERE id = ?");
            $stmt->execute([$name, $price, $description, $id]);
        }

        $message = "Menu item updated successfully!";
    } catch (\PDOException $e) {
        $message = "Error: " . $e->getMessage();
    }
}

// Fetch all menu items
try {
    $stmt = $pdo->query("SELECT * FROM menu ORDER BY name");
    $menuItems = $stmt->fetchAll();
} catch (\PDOException $e) {
    $message = "Error: " . $e->getMessage();
    $menuItems = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu Management</title>
    <style>
        :root {
            --primary: #5b48da;
            --primary-light: #8172e6;
            --secondary: #24263c;
            --success: #2ecc71;
            --danger: #e74c3c;
            --warning: #f39c12;
            --info: #3498db;
            --light: #f8f9fa;
            --dark: #1a1c2d;
            --border: #e9ecef;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #f4f4f4;
            display: flex;
            min-height: 100vh;
        }
        .btn-sm {
            padding: 5px 10px;
            font-size: 12px;
        }

        .btn-edit {
            background-color: var(--info);
            color: white;
            border: none;
            border-radius: 3px;
            margin-right: 5px;
            cursor: pointer;
        }

        .btn-delete {
            background-color: var(--danger);
            color: white;
            border: none;
            border-radius: 3px;
            cursor: pointer;
        }
        .badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            color: white;
        }
        .food-image {
            width: 40px;
            height: 40px;
            border-radius: 5px;
            object-fit: cover;
            margin-right: 5px;
        }

        .food-container {
            display: flex;
            align-items: center;
        }

        /* Sidebar */
        .sidebar {
            width: 250px;
            background-color: #fff;
            padding: 20px 15px;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            height: 100vh;
            transition: all 0.3s ease;
            position: fixed;
            top: 0;
            left: 0;
            overflow-x: hidden;
            z-index: 100;
        }

        .sidebar.collapsed {
            width: 70px;
        }

        .sidebar .brand {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 20px;
        }

        .sidebar .brand h2 {
            font-size: 18px;
            white-space: nowrap;
            transition: opacity 0.3s;
        }

        .sidebar.collapsed .brand h2 {
            opacity: 0;
            display: none;
        }

        .hamburger {
            font-size: 22px;
            background: none;
            border: none;
            cursor: pointer;
            color: #5b48da;
            padding: 5px;
            z-index: 101;
        }

        .hamburger:hover {
            transform: scale(1.1);
        }

        .sidebar-menu {
            margin-top: 20px;
        }

        .menu-category {
            font-size: 14px;
            font-weight: bold;
            color: #888;
            margin: 10px 0;
            white-space: nowrap;
            transition: opacity 0.3s;
        }

        .sidebar.collapsed .menu-category {
            opacity: 0;
            display: none;
        }

        .menu-item {
            list-style: none;
            margin-bottom: 10px;
        }

        .menu-item a {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: #333;
            padding: 8px 12px;
            border-radius: 6px;
            transition: background-color 0.2s;
        }

        .menu-item a:hover,
        .menu-item.active a {
            background-color: #5b48da;
            color: #fff;
        }

        .menu-item a i {
            font-size: 18px;
            min-width: 20px;
            text-align: center;
        }

        .sidebar.collapsed .menu-item span {
            display: none;
        }

        /* Main content adjustment */
        .main-content {
            margin-left: 250px;
            padding: 20px;
            flex-grow: 1;
            transition: margin-left 0.3s ease;
            width: calc(100% - 250px);
        }

        .sidebar.collapsed + .main-content {
            margin-left: 70px;
            width: calc(100% - 70px);
        }

        /* Table Card */
        .table-card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 20px;
            margin-bottom: 20px;
            overflow-x: auto;
        }

        .table-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .table-card-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--secondary);
        }

        .alert {
            padding: 10px 15px;
            margin-bottom: 15px;
            border-radius: 5px;
            font-size: 14px;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .btn-primary {
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
        }

        .btn-primary:hover {
            background-color: var(--primary-light);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 600px;
        }

        th, td {
            padding: 12px 15px;
            text-align: left;
        }

        th {
            background-color: #f8f9fa;
            font-weight: 500;
            color: #777;
            font-size: 14px;
        }

        tbody tr {
            border-bottom: 1px solid #f1f1f1;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            overflow: auto;
        }

        .modal-content {
            background-color: white;
            margin: 10% auto;
            padding: 20px;
            border-radius: 8px;
            width: 90%;
            max-width: 600px;
            position: relative;
        }

        .close {
            position: absolute;
            right: 15px;
            top: 10px;
            font-size: 24px;
            font-weight: bold;
            cursor: pointer;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            padding: 8px 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        .form-buttons {
            display: flex;
            justify-content: flex-end;
            margin-top: 20px;
            gap: 10px;
        }

        .btn-cancel {
            background-color: #f1f1f1;
            color: #333;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
            }

            .sidebar .brand h2,
            .sidebar .menu-category,
            .sidebar .menu-item span {
                display: none;
            }

            .main-content {
                margin-left: 70px;
                width: calc(100% - 70px);
            }

            .sidebar.expanded {
                width: 250px;
            }

            .sidebar.expanded .brand h2,
            .sidebar.expanded .menu-category,
            .sidebar.expanded .menu-item span {
                display: block;
                opacity: 1;
            }

            .table-card-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .form-buttons {
                flex-direction: column;
            }

            .btn-cancel, .btn-primary {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>
<body>
<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <div class="brand">
        <button class="hamburger" id="toggle-btn">☰</button>
        <h2>Dashboard</h2>
    </div>
    <div class="sidebar-menu">
        <p class="menu-category">Main</p>
        <ul>
            <li class="menu-item">
                <a href="index.php"><i>📊</i> <span>Dashboard</span></a>
            </li>
            <li class="menu-item active">
                <a href="menu.php"><i>☕</i> <span>Menu Items</span></a>
            </li>
            <li class="menu-item">
                <a href="orders.php"><i>📋</i> <span>Orders</span></a>
            </li>
            <li class="menu-item">
                <a href="customers.php"><i>👥</i> <span>Customers</span></a>
            </li>
        </ul>
    </div>
</div>

<!-- Main content -->
<div class="main-content">
    <div class="table-card">
        <div class="table-card-header">
            <h3 class="table-card-title">Menu Items Management</h3>
            <button class="btn-primary" onclick="document.getElementById('newMenuItemModal').style.display='block'">+ Add Menu Item</button>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert <?php echo strpos($message, 'Error') !== false ? 'alert-danger' : 'alert-success'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <table>
            <thead>
            <tr>
                <th>Item</th>
                <th>Price</th>
                <th>Description</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($menuItems as $item): ?>
                <tr>
                    <td>
                        <div class="food-container">
                            <?php if (!empty($item['image_url'])): ?>
                                <img src="<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="food-image">
                            <?php else: ?>
                                <img src="/api/placeholder/40/40" alt="Food" class="food-image">
                            <?php endif; ?>
                            <span><?php echo htmlspecialchars($item['name']); ?></span>
                        </div>
                    </td>
                    <td>$<?php echo number_format($item['price'], 2); ?></td>
                    <td><?php echo htmlspecialchars($item['description']); ?></td>
                    <td>
                        <button class="btn-sm btn-edit" onclick="openEditModal(<?php echo $item['id']; ?>, '<?php echo addslashes($item['name']); ?>', <?php echo $item['price']; ?>, '<?php echo addslashes($item['description']); ?>')">✏️</button>
                        <button class="btn-sm btn-delete" onclick="confirmDelete(<?php echo $item['id']; ?>, '<?php echo addslashes($item['name']); ?>')">🗑️</button>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($menuItems)): ?>
                <tr>
                    <td colspan="4" style="text-align: center;">No menu items found. Add one to get started!</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- New Menu Item Modal -->
<div id="newMenuItemModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="document.getElementById('newMenuItemModal').style.display='none'">&times;</span>
        <h2 style="margin-bottom: 20px;">Add New Menu Item</h2>

        <form action="menu.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add">

            <div class="form-group">
                <label for="itemName">Item Name</label>
                <input type="text" class="form-control" id="itemName" name="name" placeholder="Enter menu item name" required>
            </div>

            <div class="form-group">
                <label for="price">Price ($)</label>
                <input type="number" step="0.01" class="form-control" id="price" name="price" placeholder="Enter price" required>
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea class="form-control" id="description" name="description" rows="3" placeholder="Enter item description"></textarea>
            </div>

            <div class="form-group">
                <label for="image">Upload Image</label>
                <input type="file" class="form-control" id="image" name="image" accept="image/*">
            </div>

            <div class="form-buttons">
                <button type="button" class="btn-cancel" onclick="document.getElementById('newMenuItemModal').style.display='none'">Cancel</button>
                <button type="submit" class="btn-primary">Add Item</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Menu Item Modal -->
<div id="editMenuItemModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="document.getElementById('editMenuItemModal').style.display='none'">&times;</span>
        <h2 style="margin-bottom: 20px;">Edit Menu Item</h2>

        <form action="menu.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="editItemId">

            <div class="form-group">
                <label for="editItemName">Item Name</label>
                <input type="text" class="form-control" id="editItemName" name="name" placeholder="Enter menu item name" required>
            </div>

            <div class="form-group">
                <label for="editPrice">Price ($)</label>
                <input type="number" step="0.01" class="form-control" id="editPrice" name="price" placeholder="Enter price" required>
            </div>

            <div class="form-group">
                <label for="editDescription">Description</label>
                <textarea class="form-control" id="editDescription" name="description" rows="3" placeholder="Enter item description"></textarea>
            </div>

            <div class="form-group">
                <label for="editImage">Upload New Image (leave empty to keep current image)</label>
                <input type="file" class="form-control" id="editImage" name="image" accept="image/*">
            </div>

            <div class="form-buttons">
                <button type="button" class="btn-cancel" onclick="document.getElementById('editMenuItemModal').style.display='none'">Cancel</button>
                <button type="submit" class="btn-primary">Update Item</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirmation Form (Hidden) -->
<form id="deleteForm" action="menu.php" method="POST" style="display: none;">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="id" id="deleteItemId">
</form>

<script>
    // Get the toggle button and sidebar elements
    const toggleBtn = document.getElementById('toggle-btn');
    const sidebar = document.getElementById('sidebar');

    // Add click event listener to the toggle button
    toggleBtn.addEventListener('click', function() {
        sidebar.classList.toggle('collapsed');
    });

    // For mobile devices, check if collapsed state should be default
    function checkScreenSize() {
        if (window.innerWidth <= 768) {
            sidebar.classList.add('collapsed');
        } else {
            sidebar.classList.remove('collapsed');
        }
    }

    // Check screen size on load and resize
    window.addEventListener('load', checkScreenSize);
    window.addEventListener('resize', checkScreenSize);

    // Function to open edit modal with item data
    function openEditModal(id, name, price, description) {
        document.getElementById('editItemId').value = id;
        document.getElementById('editItemName').value = name;
        document.getElementById('editPrice').value = price;
        document.getElementById('editDescription').value = description;
        document.getElementById('editMenuItemModal').style.display = 'block';
    }

    // Function to confirm delete
    function confirmDelete(id, name) {
        if (confirm(`Are you sure you want to delete "${name}"?`)) {
            document.getElementById('deleteItemId').value = id;
            document.getElementById('deleteForm').submit();
        }
    }

    // Close modals when clicking outside
    window.onclick = function(event) {
        if (event.target.className === 'modal') {
            event.target.style.display = 'none';
        }
    }
</script>
</body>
</html>