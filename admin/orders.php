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
            content: "📋";
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

        .badge-new {
            background-color: #6D4C41;
        }

        .badge-preparing {
            background-color: #FF9800;
        }

        .badge-delivery {
            background-color: #2196F3;
        }

        .badge-delivered {
            background-color: #4CAF50;
        }

        .badge-cancelled {
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

        .item-row {
            margin-bottom: 10px;
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
    <div class="header">
        <h2 class="page-title">Order Management</h2>
        <button class="btn" onclick="document.getElementById('newOrderModal').style.display='block'">+ New Order</button>
    </div>

    <div class="table-card">
        <table>
            <thead>
            <tr>
                <th>Order ID</th>
                <th>Customer</th>
                <th>Items</th>
                <th>Total</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($orders as $order): ?>
                <tr>
                    <td>#ORD-<?= $order['id'] ?></td>
                    <td>
                        <div class="user-container">
                            <img src="/api/placeholder/30/30" alt="User" class="user-image">
                            <span><?= htmlspecialchars($order['customer_name']) ?></span>
                        </div>
                    </td>
                    <td><?= htmlspecialchars($order['items']) ?></td>
                    <td>$<?= number_format($order['total'], 2) ?></td>
                    <td>
                        <?php
                        $statusClass = 'badge';
                        switch($order['status']) {
                            case 'New': $statusClass .= ' badge-new'; break;
                            case 'Preparing': $statusClass .= ' badge-preparing'; break;
                            case 'Out for Delivery': $statusClass .= ' badge-delivery'; break;
                            case 'Delivered': $statusClass .= ' badge-delivered'; break;
                            case 'Cancelled': $statusClass .= ' badge-cancelled'; break;
                        }
                        ?>
                        <span class="<?= $statusClass ?>"><?= htmlspecialchars($order['status']) ?></span>
                    </td>
                    <td>
                        <button class="btn-sm btn-edit" onclick="editOrder(<?= $order['id'] ?>, '<?= htmlspecialchars($order['items'], ENT_QUOTES) ?>', <?= $order['total'] ?>, '<?= $order['status'] ?>')">✏️</button>
                        <a href="?delete=<?= $order['id'] ?>" onclick="return confirm('Are you sure you want to delete this order?')">
                            <button class="btn-sm btn-delete">🗑️</button>
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="newOrderModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Create New Order</h3>
            <span class="close" onclick="document.getElementById('newOrderModal').style.display='none'">&times;</span>
        </div>
        <form method="POST" onsubmit="return calculateTotal()">
            <input type="hidden" name="action" value="create">
            <div class="form-group">
                <label for="customer_id">Customer</label>
                <select name="customer_id" id="customer_id" class="form-control" required>
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
                <button type="button" class="btn" style="margin-top: 10px;" onclick="addItem()">+ Add Another Item</button>
            </div>
            <div class="form-group">
                <label for="order-total">Total ($)</label>
                <input type="number" name="total" step="0.01" class="form-control" id="order-total" readonly required>
            </div>
            <div class="form-group">
                <label for="status">Status</label>
                <select name="status" id="status" class="form-control">
                    <option value="New">New</option>
                    <option value="Preparing">Preparing</option>
                    <option value="Out for Delivery">Out for Delivery</option>
                    <option value="Delivered">Delivered</option>
                </select>
            </div>
            <div class="form-buttons">
                <button class="btn-cancel" type="button" onclick="document.getElementById('newOrderModal').style.display='none'">Cancel</button>
                <button class="btn" type="submit">Create Order</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Order Modal -->
<div id="editOrderModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Edit Order</h3>
            <span class="close" onclick="document.getElementById('editOrderModal').style.display='none'">&times;</span>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="order_id" id="edit-order-id">
            <div class="form-group">
                <label for="edit-items">Items</label>
                <textarea name="items" class="form-control" id="edit-items" required></textarea>
            </div>
            <div class="form-group">
                <label for="edit-total">Total ($)</label>
                <input type="number" name="total" step="0.01" class="form-control" id="edit-total" required>
            </div>
            <div class="form-group">
                <label for="edit-status">Status</label>
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