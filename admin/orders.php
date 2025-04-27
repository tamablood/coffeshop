<?php require_once 'auth_check.php'; ?>
<?php
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $customer_id = $_POST['customer_id'] ?? null;
    $items_array = $_POST['items'] ?? [];
    $total = $_POST['total'] ?? 0;
    $status = $_POST['status'] ?? 'New';
    $items = implode(", ", $items_array);
    $stmt = $pdo->prepare("INSERT INTO orders (customer_id, items, total, status) VALUES (?, ?, ?, ?)");
    $stmt->execute([$customer_id, $items, $total, $status]);
}

if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $pdo->prepare("DELETE FROM orders WHERE id = ?")->execute([$id]);
    header('Location: orders.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $id = $_POST['order_id'];
    $items = $_POST['items'];
    $total = $_POST['total'];
    $status = $_POST['status'];
    $pdo->prepare("UPDATE orders SET items=?, total=?, status=? WHERE id = ?")
        ->execute([$items, $total, $status, $id]);
    header('Location: orders.php');
    exit;
}

$orders = $pdo->query("SELECT o.*, c.name as customer_name FROM orders o JOIN customers c ON o.customer_id = c.id ORDER BY o.created_at DESC")->fetchAll();
$menu_items = $pdo->query("SELECT * FROM menu")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Management</title>
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
            <li class="menu-item active">
                <a href="orders.php"><i>📋</i> <span>Orders</span></a>
            </li>
            <li class="menu-item">
                <a href="customers.php"><i>👥</i> <span>Customers</span></a>
            </li>
        </ul>
    </div>
</div>
<div class="main-content">
    <div class="table-card">
        <div class="table-card-header">
            <h3 class="table-card-title">Recent Orders</h3>
            <button class="btn-primary" onclick="document.getElementById('newOrderModal').style.display='block'">+ New Order</button>
        </div>
        <table>
            <thead>
            <tr><th>Order ID</th><th>Customer</th><th>Items</th><th>Total</th><th>Status</th><th>Actions</th></tr>
            </thead>
            <tbody>
            <?php foreach ($orders as $order): ?>
                <tr>
                    <td>#ORD-<?= $order['id'] ?></td>
                    <td><div class="user-container"><img src="/api/placeholder/30/30" alt="User" class="user-image"><span><?= htmlspecialchars($order['customer_name']) ?></span></div></td>
                    <td><?= htmlspecialchars($order['items']) ?></td>
                    <td>$<?= number_format($order['total'], 2) ?></td>
                    <td><span class="badge badge-primary"><?= htmlspecialchars($order['status']) ?></span></td>
                    <td>
                        <button class="btn-sm btn-edit" onclick="editOrder(<?= $order['id'] ?>, '<?= htmlspecialchars($order['items'], ENT_QUOTES) ?>', <?= $order['total'] ?>, '<?= $order['status'] ?>')">✏️</button>
                        <a href="?delete=<?= $order['id'] ?>" onclick="return confirm('Are you sure?')"><button class="btn-sm btn-delete">🗑️</button></a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- New Order Modal (unchanged) -->
<div id="newOrderModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="document.getElementById('newOrderModal').style.display='none'">&times;</span>
        <h2>Create New Order</h2>
        <form method="POST" onsubmit="return calculateTotal()">
            <input type="hidden" name="action" value="create">
            <div class="form-group">
                <label>Customer</label>
                <select name="customer_id" class="form-control" required>
                    <option value="">Select Customer</option>
                    <?php
                    $customers = $pdo->query("SELECT * FROM customers ORDER BY name")->fetchAll();
                    foreach($customers as $customer):
                        ?>
                        <option value="<?= $customer['id'] ?>"><?= htmlspecialchars($customer['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Items</label>
                <div id="items-container">
                    <div class="item-row">
                        <select name="items[]" class="form-control item-select" onchange="calculateTotal()" required>
                            <option value="">Select Item</option>
                            <?php foreach($menu_items as $item): ?>
                                <option value="<?= htmlspecialchars($item['name']) ?>" data-price="<?= $item['price'] ?>">
                                    <?= htmlspecialchars($item['name']) ?> - $<?= number_format($item['price'], 2) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <button type="button" class="btn-sm btn-primary" style="margin-top: 10px;" onclick="addItem()">+ Add Another Item</button>
            </div>
            <div class="form-group">
                <label>Total ($)</label>
                <input type="number" name="total" step="0.01" class="form-control" id="order-total" readonly required>
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="status" class="form-control">
                    <option value="New">New</option>
                    <option value="Preparing">Preparing</option>
                    <option value="Out for Delivery">Out for Delivery</option>
                    <option value="Delivered">Delivered</option>
                </select>
            </div>
            <div class="form-buttons">
                <button class="btn-cancel" type="button" onclick="document.getElementById('newOrderModal').style.display='none'">Cancel</button>
                <button class="btn-primary" type="submit">Create Order</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Order Modal -->
<div id="editOrderModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="document.getElementById('editOrderModal').style.display='none'">&times;</span>
        <h2>Edit Order</h2>
        <form method="POST">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="order_id" id="edit-order-id">
            <div class="form-group">
                <label>Items</label>
                <textarea name="items" class="form-control" id="edit-items" required></textarea>
            </div>
            <div class="form-group">
                <label>Total ($)</label>
                <input type="number" name="total" step="0.01" class="form-control" id="edit-total" required>
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="status" class="form-control" id="edit-status">
                    <option value="New">New</option>
                    <option value="Preparing">Preparing</option>
                    <option value="Out for Delivery">Out for Delivery</option>
                    <option value="Delivered">Delivered</option>
                    <option value="Cancelled">Cancelled</option>
                </select>
            </div>
            <div class="form-buttons">
                <button class="btn-cancel" type="button" onclick="document.getElementById('editOrderModal').style.display='none'">Cancel</button>
                <button class="btn-primary" type="submit">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
    function editOrder(id, items, total, status) {
        document.getElementById('edit-order-id').value = id;
        document.getElementById('edit-items').value = items;
        document.getElementById('edit-total').value = total;
        document.getElementById('edit-status').value = status;
        document.getElementById('editOrderModal').style.display = 'block';
    }
    function addItem() {
        const container = document.getElementById('items-container');
        const original = container.firstElementChild.cloneNode(true);
        original.querySelector('select').value = "";
        container.appendChild(original);
    }
    function calculateTotal() {
        let total = 0;
        const selects = document.querySelectorAll('.item-select');
        selects.forEach(select => {
            const option = select.options[select.selectedIndex];
            const price = parseFloat(option.getAttribute('data-price')) || 0;
            total += price;
        });
        document.getElementById('order-total').value = total.toFixed(2);
        return true;
    }
</script>
</body>
</html>
