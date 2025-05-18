<?php require_once 'auth_check.php'; ?>
<?php
require_once 'db.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $name = $_POST['name'];
    $price = $_POST['price'];
    $description = $_POST['description'];
    $image_url = '';

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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $price = $_POST['price'];
    $description = $_POST['description'];

    try {
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
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #f8f3e9;
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 250px;
            background-color: #5D4037;
            padding: 20px 15px;
            box-shadow: 2px 0 10px rgba(0,0,0,0.2);
            height: 100vh;
            transition: all 0.3s ease;
            position: fixed;
            top: 0;
            left: 0;
            overflow-x: hidden;
            z-index: 100;
            color: #D7CCC8;
        }

        .sidebar.collapsed {
            width: 70px;
        }

        .sidebar .brand {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 30px;
            border-bottom: 1px solid #8D6E63;
            padding-bottom: 15px;
        }

        .sidebar .brand h2 {
            font-size: 18px;
            white-space: nowrap;
            transition: opacity 0.3s;
            color: #FFECB3;
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
            color: #FFECB3;
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
            color: #BCAAA4;
            margin: 15px 0 10px 10px;
            white-space: nowrap;
            transition: opacity 0.3s;
            text-transform: uppercase;
            letter-spacing: 1px;
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
            gap: 12px;
            text-decoration: none;
            color: #EFEBE9;
            padding: 10px 12px;
            border-radius: 6px;
            transition: background-color 0.2s;
        }

        .menu-item a:hover,
        .menu-item.active a {
            background-color: #8D6E63;
            color: #FFF8E1;
        }

        .menu-item a i {
            font-size: 18px;
            min-width: 20px;
            text-align: center;
        }

        .sidebar.collapsed .menu-item span {
            display: none;
        }

        .main-content {
            margin-left: 250px;
            padding: 25px;
            flex-grow: 1;
            transition: margin-left 0.3s ease;
            width: calc(100% - 250px);
        }

        .sidebar.collapsed + .main-content {
            margin-left: 70px;
            width: calc(100% - 70px);
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            background-color: #D7CCC8;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .page-title {
            font-size: 24px;
            color: #4E342E;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .page-title:before {
            content: "☕";
            font-size: 28px;
        }

        .btn {
            padding: 10px 18px;
            background-color: #8D6E63;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.2s;
        }

        .btn:hover {
            background-color: #6D4C41;
        }

        .btn-sm {
            padding: 5px 10px;
            font-size: 12px;
        }

        .btn-edit {
            background-color: #8D6E63;
            color: white;
            border: none;
            border-radius: 3px;
            margin-right: 5px;
            cursor: pointer;
        }

        .btn-delete {
            background-color: #B71C1C;
            color: white;
            border: none;
            border-radius: 3px;
            cursor: pointer;
        }

        .alert {
            padding: 12px 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-size: 14px;
        }

        .alert-success {
            background-color: #E8F5E9;
            color: #2E7D32;
            border-left: 4px solid #4CAF50;
        }

        .alert-danger {
            background-color: #FFEBEE;
            color: #C62828;
            border-left: 4px solid #F44336;
        }

        .table-card {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
            padding: 20px;
            margin-bottom: 20px;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 600px;
        }

        th, td {
            padding: 15px;
            text-align: left;
        }

        th {
            background-color: #EFEBE9;
            font-weight: 500;
            color: #5D4037;
            font-size: 14px;
        }

        tbody tr {
            border-bottom: 1px solid #f1f1f1;
        }

        tbody tr:hover {
            background-color: #FFF8E1;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            overflow: auto;
        }

        .modal-content {
            background-color: #fefefe;
            margin: 10% auto;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
            width: 500px;
            max-width: 90%;
            border-top: 5px solid #8D6E63;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .modal-title {
            font-size: 18px;
            font-weight: 600;
            color: #5D4037;
        }

        .close {
            font-size: 22px;
            font-weight: bold;
            cursor: pointer;
            color: #8D6E63;
        }

        .close:hover {
            color: #5D4037;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #5D4037;
        }

        .form-control {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #D7CCC8;
            border-radius: 4px;
            transition: border-color 0.2s;
            font-size: 14px;
        }

        .form-control:focus {
            outline: none;
            border-color: #8D6E63;
        }

        select.form-control {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%235D4037' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            padding-right: 28px;
        }

        .form-buttons {
            display: flex;
            justify-content: flex-end;
            margin-top: 20px;
            gap: 10px;
        }

        .btn-cancel {
            background-color: #EFEBE9;
            color: #5D4037;
            border: none;
            padding: 10px 18px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }

        .btn-cancel:hover {
            background-color: #D7CCC8;
        }

        .food-container {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .food-image {
            width: 45px;
            height: 45px;
            border-radius: 8px;
            object-fit: cover;
        }

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
            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            .form-buttons {
                flex-direction: column;
            }
            .btn-cancel, .btn {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>
<body>
<div class="sidebar" id="sidebar">
    <div class="brand">
        <button class="hamburger" id="toggle-btn">☰</button>
        <h2>Brew & Bean</h2>
    </div>
    <div class="sidebar-menu">
        <p class="menu-category">Management</p>
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

<div class="main-content">
    <div class="header">
        <h2 class="page-title">Menu Management</h2>
        <button class="btn" onclick="document.getElementById('newMenuItemModal').style.display='block'">+ Add Menu Item</button>
    </div>

    <?php if (!empty($message)): ?>
        <div class="alert <?php echo strpos($message, 'Error') !== false ? 'alert-danger' : 'alert-success'; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <div class="table-card">
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
                                <img src="/api/placeholder/45/45" alt="Food" class="food-image">
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

<div id="newMenuItemModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Add New Menu Item</h3>
            <span class="close" onclick="document.getElementById('newMenuItemModal').style.display='none'">&times;</span>
        </div>
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
                <button type="submit" class="btn">Add Item</button>
            </div>
        </form>
    </div>
</div>

<div id="editMenuItemModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Edit Menu Item</h3>
            <span class="close" onclick="document.getElementById('editMenuItemModal').style.display='none'">&times;</span>
        </div>
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
                <button type="submit" class="btn">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<form id="deleteForm" action="menu.php" method="POST" style="display: none;">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="id" id="deleteItemId">
</form>

<script>
    const toggleBtn = document.getElementById('toggle-btn');
    const sidebar = document.getElementById('sidebar');

    toggleBtn.addEventListener('click', function() {
        sidebar.classList.toggle('collapsed');
    });

    function checkScreenSize() {
        if (window.innerWidth <= 768) {
            sidebar.classList.add('collapsed');
        } else {
            sidebar.classList.remove('collapsed');
        }
    }

    window.addEventListener('load', checkScreenSize);
    window.addEventListener('resize', checkScreenSize);

    function openEditModal(id, name, price, description) {
        document.getElementById('editItemId').value = id;
        document.getElementById('editItemName').value = name;
        document.getElementById('editPrice').value = price;
        document.getElementById('editDescription').value = description;
        document.getElementById('editMenuItemModal').style.display = 'block';
    }

    function confirmDelete(id, name) {
        if (confirm(`Are you sure you want to delete "${name}"?`)) {
            document.getElementById('deleteItemId').value = id;
            document.getElementById('deleteForm').submit();
        }
    }

    window.onclick = function(event) {
        if (event.target.className === 'modal') {
            event.target.style.display = 'none';
        }
    }
</script>
</body>
</html>