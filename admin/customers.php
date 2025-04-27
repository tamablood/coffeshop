<?php require_once 'auth_check.php'; ?>
<?php
require 'db.php';

// Handle customer deletion
if (isset($_GET['delete'])) {
    try {
        $id = intval($_GET['delete']);

        // Start a transaction
        $pdo->beginTransaction();

        // First delete related orders
        $pdo->prepare("DELETE FROM orders WHERE customer_id = ?")->execute([$id]);

        // Then delete the customer
        $pdo->prepare("DELETE FROM customers WHERE id = ?")->execute([$id]);

        // Commit the transaction
        $pdo->commit();

        // Set success message
        $_SESSION['success_message'] = "Customer and all related orders deleted successfully.";

        header('Location: customers.php');
        exit;
    } catch (PDOException $e) {
        // Rollback the transaction if something failed
        $pdo->rollBack();

        // Set error message
        $_SESSION['error_message'] = "Could not delete customer. This customer may have associated records.";

        header('Location: customers.php');
        exit;
    }
}

// Handle adding a new customer
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    // Get form data
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];

    // Insert into database
    $stmt = $pdo->prepare("INSERT INTO customers (name, email, phone, address) VALUES (?, ?, ?, ?)");
    $stmt->execute([$name, $email, $phone, $address]);

    // Redirect to prevent multiple submissions
    header("Location: customers.php");
    exit();
}

// Handle editing a customer
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $id = intval($_POST['customer_id']);
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];

    $stmt = $pdo->prepare("UPDATE customers SET name=?, email=?, phone=?, address=? WHERE id=?");
    $stmt->execute([$name, $email, $phone, $address, $id]);
    header('Location: customers.php');
    exit;
}

// Handle edit request - fetch customer data
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_customer = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get all customers
$stmt = $pdo->query("SELECT c.*, 
                     COUNT(o.id) as order_count, 
                     SUM(o.total) as total_spent,
                     MAX(o.created_at) as last_order_date
                     FROM customers c
                     LEFT JOIN orders o ON c.id = o.customer_id
                     GROUP BY c.id");
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Management</title>
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
        .badge-danger {
            background-color: var(--danger);
        }
        .user-image {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 5px;
        }

        .user-container {
            display: flex;
            align-items: center;
        }
        .badge-primary {
            background-color: var(--primary);
        }

        .badge-success {
            background-color: var(--success);
        }

        .badge-warning {
            background-color: var(--warning);
        }

        .badge-danger {
            background-color: var(--danger);
        }

        .badge-info {
            background-color: var(--info);
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

        /* Alert messages */
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            color: white;
            font-weight: 500;
        }

        .alert-success {
            background-color: var(--success);
        }

        .alert-danger {
            background-color: var(--danger);
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
            <li class="menu-item">
                <a href="menu.php"><i>☕</i> <span>Menu Items</span></a>
            </li>
            <li class="menu-item">
                <a href="orders.php"><i>📋</i> <span>Orders</span></a>
            </li>
            <li class="menu-item active">
                <a href="customers.php"><i>👥</i> <span>Customers</span></a>
            </li>
        </ul>
    </div>
</div>

<!-- Main content -->
<div class="main-content">
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger">
            <?php
            echo $_SESSION['error_message'];
            unset($_SESSION['error_message']);
            ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success">
            <?php
            echo $_SESSION['success_message'];
            unset($_SESSION['success_message']);
            ?>
        </div>
    <?php endif; ?>

    <div class="table-card">
        <div class="table-card-header">
            <h3 class="table-card-title">Customer Management</h3>
            <button class="btn-primary" onclick="document.getElementById('newCustomerModal').style.display='block'">+ New Customer</button>
        </div>

        <table>
            <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Address</th>
                <th>Orders</th>
                <th>Total Spent</th>
                <th>Last Order</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($customers as $customer): ?>
                <tr>
                    <td>
                        <div class="user-container">
                            <img src="/api/placeholder/30/30" alt="User" class="user-image">
                            <span><?php echo htmlspecialchars($customer['name']); ?></span>
                        </div>
                    </td>
                    <td><?php echo htmlspecialchars($customer['email']); ?></td>
                    <td><?php echo htmlspecialchars($customer['phone']); ?></td>
                    <td><?php echo htmlspecialchars($customer['address']); ?></td>
                    <td><?php echo $customer['order_count'] ? htmlspecialchars($customer['order_count']) : '0'; ?></td>
                    <td>$<?php echo $customer['total_spent'] ? number_format($customer['total_spent'], 2) : '0.00'; ?></td>
                    <td><?php
                        if (!empty($customer['last_order_date'])) {
                            $orderDate = new DateTime($customer['last_order_date']);
                            $today = new DateTime();
                            $interval = $orderDate->diff($today);

                            if ($interval->days == 0) {
                                echo 'Today';
                            } elseif ($interval->days == 1) {
                                echo 'Yesterday';
                            } else {
                                echo $interval->days . ' days ago';
                            }
                        } else {
                            echo 'No orders';
                        }
                        ?></td>
                    <td>
                        <a href="?edit=<?php echo $customer['id']; ?>"><button class="btn-sm btn-edit">✏️</button></a>
                        <a href="?delete=<?php echo $customer['id']; ?>" onclick="return confirm('Are you sure you want to delete this customer? This will also delete all associated orders.')"><button class="btn-sm btn-delete">🗑️</button></a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- New Customer Modal -->
<div id="newCustomerModal" class="modal" <?php if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] === 'create' && !empty($errors)) echo 'style="display:block"'; ?>>
    <div class="modal-content">
        <span class="close" onclick="document.getElementById('newCustomerModal').style.display='none'">&times;</span>
        <h2 style="margin-bottom: 20px;">Add New Customer</h2>

        <form method="POST">
            <input type="hidden" name="action" value="create">
            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" class="form-control" id="name" name="name" placeholder="Enter customer name" required>
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" class="form-control" id="email" name="email" placeholder="Enter email address" required>
            </div>

            <div class="form-group">
                <label for="phone">Phone Number</label>
                <input type="tel" class="form-control" id="phone" name="phone" placeholder="Enter phone number" required>
            </div>

            <div class="form-group">
                <label for="address">Address</label>
                <textarea class="form-control" id="address" name="address" rows="3" placeholder="Enter delivery address" required></textarea>
            </div>

            <div class="form-buttons">
                <button type="button" class="btn-cancel" onclick="document.getElementById('newCustomerModal').style.display='none'">Cancel</button>
                <button type="submit" class="btn-primary">Add Customer</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Customer Modal -->
<div id="editCustomerModal" class="modal" <?php if (isset($edit_customer)) echo 'style="display:block"'; ?>>
    <div class="modal-content">
        <span class="close" onclick="window.location.href='customers.php'">&times;</span>
        <h2>Edit Customer</h2>
        <form method="POST">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="customer_id" value="<?php echo isset($edit_customer) ? $edit_customer['id'] : ''; ?>">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="name" class="form-control" value="<?php echo isset($edit_customer) ? htmlspecialchars($edit_customer['name']) : ''; ?>" required>
            </div>
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" class="form-control" value="<?php echo isset($edit_customer) ? htmlspecialchars($edit_customer['email']) : ''; ?>" required>
            </div>
            <div class="form-group">
                <label>Phone Number</label>
                <input type="tel" name="phone" class="form-control" value="<?php echo isset($edit_customer) ? htmlspecialchars($edit_customer['phone']) : ''; ?>" required>
            </div>
            <div class="form-group">
                <label>Address</label>
                <textarea name="address" class="form-control" required><?php echo isset($edit_customer) ? htmlspecialchars($edit_customer['address']) : ''; ?></textarea>
            </div>
            <div class="form-buttons">
                <button class="btn-cancel" type="button" onclick="window.location.href='customers.php'">Cancel</button>
                <button class="btn-primary" type="submit">Save Changes</button>
            </div>
        </form>
    </div>
</div>

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

    // Auto-close alerts after 5 seconds
    document.addEventListener('DOMContentLoaded', function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            setTimeout(function() {
                alert.style.opacity = '0';
                alert.style.transition = 'opacity 1s';
                setTimeout(function() {
                    alert.style.display = 'none';
                }, 1000);
            }, 5000);
        });
    });

    // Check screen size on load and resize
    window.addEventListener('load', checkScreenSize);
    window.addEventListener('resize', checkScreenSize);
</script>
</body>
</html>