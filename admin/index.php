<?php require_once 'auth_check.php'; ?>
<?php
require 'db.php';

$revenue = $pdo->query("SELECT SUM(total) FROM orders WHERE DATE(created_at) = CURDATE()")->fetchColumn();
$orders = $pdo->query("SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE()")->fetchColumn();
$customers = $pdo->query("SELECT COUNT(*) FROM customers WHERE DATE(created_at) = CURDATE()")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Coffee Shop Dashboard</title>
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

        .dashboard-title {
            font-size: 24px;
            color: #4E342E;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .dashboard-title:before {
            content: "☕";
            font-size: 28px;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 10px;
            position: relative;
            cursor: pointer;
            color: #4E342E;
        }

        .user-avatar {
            font-size: 24px;
            cursor: pointer;
        }

        .admin-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            background-color: white;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            border-radius: 8px;
            padding: 10px 0;
            min-width: 200px;
            z-index: 1000;
            display: none;
        }

        .admin-dropdown.show {
            display: block;
        }

        .admin-dropdown-item {
            padding: 8px 15px;
            cursor: pointer;
            transition: background 0.2s;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .admin-dropdown-item:hover {
            background-color: #f8f3e9;
            color: #5D4037;
        }

        .admin-dropdown-divider {
            border-top: 1px solid #EFEBE9;
            margin: 8px 0;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1001;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.4);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 10% auto;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
            width: 400px;
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

        .close-modal {
            font-size: 22px;
            font-weight: bold;
            cursor: pointer;
            color: #8D6E63;
        }

        .close-modal:hover {
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

        .form-group input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #D7CCC8;
            border-radius: 4px;
            transition: border-color 0.2s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #8D6E63;
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

        .dashboard-cards {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }

        .card {
            background-color: #fff;
            padding: 22px;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
            width: 100%;
            max-width: 300px;
            border-left: 4px solid transparent;
            transition: transform 0.2s;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .card-icon {
            font-size: 32px;
            margin-bottom: 15px;
        }

        .card-title {
            color: #5D4037;
            margin-bottom: 8px;
        }

        .card-value {
            font-size: 24px;
            font-weight: bold;
            color: #4E342E;
        }

        .bg-revenue { color: #A1887F; border-left-color: #A1887F; }
        .bg-orders { color: #8D6E63; border-left-color: #8D6E63; }
        .bg-customers { color: #6D4C41; border-left-color: #6D4C41; }

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
            .dashboard-cards {
                flex-direction: column;
            }
            .card {
                max-width: 100%;
            }
            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            .user-profile {
                align-self: flex-end;
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
            <li class="menu-item active">
                <a href="index.php"><i>📊</i> <span>Dashboard</span></a>
            </li>
            <li class="menu-item">
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
        <h2 class="dashboard-title">Coffee Shop Dashboard</h2>
        <div class="user-profile" id="admin-profile">
            <span>Admin</span>
            <div class="user-avatar">👤</div>
            <div class="admin-dropdown" id="admin-dropdown">
                <div class="admin-dropdown-item" onclick="openModal('profile-modal')">
                    <span>👤</span> Edit Profile
                </div>
                <div class="admin-dropdown-item" onclick="openModal('email-modal')">
                    <span>✉️</span> Change Email
                </div>
                <div class="admin-dropdown-item" onclick="openModal('password-modal')">
                    <span>🔒</span> Change Password
                </div>
                <div class="admin-dropdown-divider"></div>
                <div class="admin-dropdown-item" onclick="logout()">
                    <span>🚪</span> Logout
                </div>
            </div>
        </div>
    </div>

    <div class="dashboard-cards">
        <div class="card">
            <div class="card-icon bg-revenue">💰</div>
            <h3 class="card-title">Revenue Today</h3>
            <p class="card-value" data-type="revenue">$<?= number_format($revenue ?? 0, 2) ?></p>
        </div>
        <div class="card">
            <div class="card-icon bg-orders">📋</div>
            <h3 class="card-title">Today's Orders</h3>
            <p class="card-value" data-type="orders"><?= $orders ?></p>
        </div>
        <div class="card">
            <div class="card-icon bg-customers">👥</div>
            <h3 class="card-title">New Customers</h3>
            <p class="card-value" data-type="customers"><?= $customers ?></p>
        </div>
    </div>
</div>

<div id="profile-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Edit Profile</h3>
            <span class="close-modal" onclick="closeModal('profile-modal')">&times;</span>
        </div>
        <form id="profile-form">
            <div class="form-group">
                <label for="name">Name</label>
                <input type="text" id="name" name="name" value="Admin" required>
            </div>
            <button type="submit" class="btn">Save Changes</button>
        </form>
    </div>
</div>

<div id="email-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Change Email</h3>
            <span class="close-modal" onclick="closeModal('email-modal')">&times;</span>
        </div>
        <form id="email-form">
            <div class="form-group">
                <label for="current-email">Current Email</label>
                <input type="email" id="current-email" name="current_email" required>
            </div>
            <div class="form-group">
                <label for="new-email">New Email</label>
                <input type="email" id="new-email" name="new_email" required>
            </div>
            <div class="form-group">
                <label for="confirm-email">Confirm New Email</label>
                <input type="email" id="confirm-email" name="confirm_email" required>
            </div>
            <button type="submit" class="btn">Update Email</button>
        </form>
    </div>
</div>

<div id="password-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Change Password</h3>
            <span class="close-modal" onclick="closeModal('password-modal')">&times;</span>
        </div>
        <form id="password-form">
            <div class="form-group">
                <label for="current-password">Current Password</label>
                <input type="password" id="current-password" name="current_password" required>
            </div>
            <div class="form-group">
                <label for="new-password">New Password</label>
                <input type="password" id="new-password" name="new_password" required>
            </div>
            <div class="form-group">
                <label for="confirm-password">Confirm New Password</label>
                <input type="password" id="confirm-password" name="confirm_password" required>
            </div>
            <button type="submit" class="btn">Update Password</button>
        </form>
    </div>
</div>

<script>
    function logout() {
        window.location.href = 'logout.php';
    }
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

    const adminProfile = document.getElementById('admin-profile');
    const adminDropdown = document.getElementById('admin-dropdown');

    adminProfile.addEventListener('click', function(event) {
        event.stopPropagation();
        adminDropdown.classList.toggle('show');
    });

    document.addEventListener('click', function(event) {
        if (!adminProfile.contains(event.target)) {
            adminDropdown.classList.remove('show');
        }
    });

    function openModal(modalId) {
        document.getElementById(modalId).style.display = 'block';
        adminDropdown.classList.remove('show');
    }

    function closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
    }

    window.addEventListener('click', function(event) {
        const modals = document.getElementsByClassName('modal');
        for (let i = 0; i < modals.length; i++) {
            if (event.target === modals[i]) {
                modals[i].style.display = 'none';
            }
        }
    });

    document.getElementById('profile-form').addEventListener('submit', function(event) {
        event.preventDefault();
        alert('Profile updated successfully!');
        closeModal('profile-modal');
    });

    document.getElementById('email-form').addEventListener('submit', function(event) {
        event.preventDefault();
        if (document.getElementById('new-email').value !== document.getElementById('confirm-email').value) {
            alert('Emails do not match!');
            return;
        }
        alert('Email updated successfully!');
        closeModal('email-modal');
    });

    document.getElementById('password-form').addEventListener('submit', function(event) {
        event.preventDefault();
        if (document.getElementById('new-password').value !== document.getElementById('confirm-password').value) {
            alert('Passwords do not match!');
            return;
        }
        alert('Password updated successfully!');
        closeModal('password-modal');
    });
</script>
</body>
</html>