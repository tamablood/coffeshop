<?php require_once 'auth_check.php'; ?>
<?php
require 'db.php';

if (isset($_GET['delete'])) {
    try {
        $id = intval($_GET['delete']);

        $pdo->beginTransaction();

        $pdo->prepare("DELETE FROM orders WHERE customer_id = ?")->execute([$id]);

        $pdo->prepare("DELETE FROM customers WHERE id = ?")->execute([$id]);

        $pdo->commit();

        $_SESSION['success_message'] = "Customer and all related orders deleted successfully.";

        header('Location: customers.php');
        exit;
    } catch (PDOException $e) {
        $pdo->rollBack();

        $_SESSION['error_message'] = "Could not delete customer. This customer may have associated records.";

        header('Location: customers.php');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];

    $stmt = $pdo->prepare("INSERT INTO customers (name, email, phone, address) VALUES (?, ?, ?, ?)");
    $stmt->execute([$name, $email, $phone, $address]);

    header("Location: customers.php");
    exit();
}

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

if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_customer = $stmt->fetch(PDO::FETCH_ASSOC);
}

$stmt = $pdo->query("SELECT c.*, 
                     COUNT(o.id) as order_count, 
                     SUM(o.total) as total_spent,
                     MAX(o.created_at) as last_order_date
                     FROM customers c
                     LEFT JOIN orders o ON c.id = o.customer_id
                     GROUP BY c.id");
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
            content: "👥";
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

        .badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            color: white;
            background-color: #8D6E63;
        }

        .badge-primary {
            background-color: #6D4C41;
        }

        .badge-success {
            background-color: #4CAF50;
        }

        .badge-warning {
            background-color: #FF9800;
        }

        .badge-info {
            background-color: #2196F3;
        }

        .badge-danger {
            background-color: #B71C1C;
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

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            color: white;
            font-weight: 500;
        }

        .alert-success {
            background-color: #4CAF50;
        }

        .alert-danger {
            background-color: #B71C1C;
        }

        .user-container {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .user-image {
            width: 30px;
            height: 30px;
            border-radius: 50%;
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

    <div class="header">
        <h2 class="page-title">Customer Management</h2>
        <button class="btn" onclick="document.getElementById('newCustomerModal').style.display='block'">+ New Customer</button>
    </div>

    <div class="table-card">
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
                        <button class="btn-sm btn-edit" onclick="editCustomer(<?= $customer['id'] ?>, '<?= htmlspecialchars($customer['name'], ENT_QUOTES) ?>', '<?= htmlspecialchars($customer['email'], ENT_QUOTES) ?>', '<?= htmlspecialchars($customer['phone'], ENT_QUOTES) ?>', '<?= htmlspecialchars($customer['address'], ENT_QUOTES) ?>')">✏️</button>
                        <a href="?delete=<?php echo $customer['id']; ?>" onclick="return confirm('Are you sure you want to delete this customer? This will also delete all associated orders.')">
                            <button class="btn-sm btn-delete">🗑️</button>
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="newCustomerModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Create New Customer</h3>
            <span class="close" onclick="document.getElementById('newCustomerModal').style.display='none'">&times;</span>
        </div>
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
                <button type="submit" class="btn">Add Customer</button>
            </div>
        </form>
    </div>
</div>

<div id="editCustomerModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Edit Customer</h3>
            <span class="close" onclick="document.getElementById('editCustomerModal').style.display='none'">&times;</span>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="customer_id" id="edit-customer-id">
            <div class="form-group">
                <label for="edit-name">Full Name</label>
                <input type="text" id="edit-name" name="name" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="edit-email">Email Address</label>
                <input type="email" id="edit-email" name="email" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="edit-phone">Phone Number</label>
                <input type="tel" id="edit-phone" name="phone" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="edit-address">Address</label>
                <textarea id="edit-address" name="address" class="form-control" required></textarea>
            </div>
            <div class="form-buttons">
                <button class="btn-cancel" type="button" onclick="document.getElementById('editCustomerModal').style.display='none'">Cancel</button>
                <button class="btn" type="submit">Save Changes</button>
            </div>
        </form>
    </div>
</div>

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

    function editCustomer(id, name, email, phone, address) {
        document.getElementById('edit-customer-id').value = id;
        document.getElementById('edit-name').value = name;
        document.getElementById('edit-email').value = email;
        document.getElementById('edit-phone').value = phone;
        document.getElementById('edit-address').value = address;
        document.getElementById('editCustomerModal').style.display = 'block';
    }

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

    window.addEventListener('click', function(event) {
        const modals = document.getElementsByClassName('modal');
        for (let i = 0; i < modals.length; i++) {
            if (event.target === modals[i]) {
                modals[i].style.display = 'none';
            }
        }
    });
</script>
</body>
</html>