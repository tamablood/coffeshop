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
    <title>Responsive Sidebar Dashboard</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #f4f4f4;
            display: flex;
            min-height: 100vh;
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

        .header {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            margin-bottom: 30px;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 10px;
            position: relative;
            cursor: pointer;
        }

        .user-profile img {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
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
            background-color: #f8f8f8;
            color: #5b48da;
        }

        .admin-dropdown-divider {
            border-top: 1px solid #eee;
            margin: 8px 0;
        }

        /* Modal styles */
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
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            width: 400px;
            max-width: 90%;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .modal-title {
            font-size: 18px;
            font-weight: 600;
        }

        .close-modal {
            font-size: 20px;
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

        .form-group input {
            width: 100%;
            padding: 8px 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .btn {
            padding: 8px 15px;
            background-color: #5b48da;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }

        .btn:hover {
            background-color: #4c3cb8;
        }

        .dashboard-title {
            font-size: 24px;
            margin-bottom: 20px;
        }

        .dashboard-cards {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }

        .card {
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            width: 100%;
            max-width: 300px;
        }

        .card-icon {
            font-size: 30px;
            margin-bottom: 10px;
        }

        .bg-primary { color: #5b48da; }
        .bg-success { color: #28a745; }
        .bg-warning { color: #ffc107; }

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

<!-- Main content -->
<div class="main-content">
    <div class="header">
        <div class="user-profile" id="admin-profile">
            <span>Admin</span>
            <img src="images.jpg" alt="User" id="admin-img">
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
                <div class="admin-dropdown-item" onclick="openModal('photo-modal')">
                    <span>📷</span> Upload Photo
                </div>
                <div class="admin-dropdown-divider"></div>
                <div class="admin-dropdown-item">
                    <span>🚪</span> Logout
                </div>
            </div>
        </div>
    </div>

    <h2 class="dashboard-title">Restaurant Dashboard</h2>

    <div class="dashboard-cards">
        <div class="card">
            <div class="card-icon bg-primary">💰</div>
            <h3 class="card-title">Revenue Today</h3>
            <p class="card-value" data-type="revenue">$<?= number_format($revenue ?? 0, 2) ?></p>
        </div>
        <div class="card">
            <div class="card-icon bg-success">📋</div>
            <h3 class="card-title">Today's Orders</h3>
            <p class="card-value" data-type="orders"><?= $orders ?></p>
        </div>
        <div class="card">
            <div class="card-icon bg-warning">👥</div>
            <h3 class="card-title">New Customers</h3>
            <p class="card-value" data-type="customers"><?= $customers ?></p>
        </div>
    </div>
</div>

<!-- Modals -->
<!-- Edit Profile Modal -->
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

<!-- Email Modal -->
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

<!-- Password Modal -->
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

<!-- Photo Upload Modal -->
<div id="photo-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Upload Photo</h3>
            <span class="close-modal" onclick="closeModal('photo-modal')">&times;</span>
        </div>
        <form id="photo-form" enctype="multipart/form-data">
            <div class="form-group">
                <label for="profile-photo">Select Image</label>
                <input type="file" id="profile-photo" name="profile_photo" accept="image/*" required>
            </div>
            <button type="submit" class="btn">Upload</button>
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

    // Admin dropdown functionality
    const adminProfile = document.getElementById('admin-profile');
    const adminDropdown = document.getElementById('admin-dropdown');

    adminProfile.addEventListener('click', function(event) {
        event.stopPropagation();
        adminDropdown.classList.toggle('show');
    });

    // Close dropdown when clicking elsewhere
    document.addEventListener('click', function(event) {
        if (!adminProfile.contains(event.target)) {
            adminDropdown.classList.remove('show');
        }
    });

    // Modal functions
    function openModal(modalId) {
        document.getElementById(modalId).style.display = 'block';
        adminDropdown.classList.remove('show');
    }

    function closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
    }

    // Close modal when clicking outside
    window.addEventListener('click', function(event) {
        const modals = document.getElementsByClassName('modal');
        for (let i = 0; i < modals.length; i++) {
            if (event.target === modals[i]) {
                modals[i].style.display = 'none';
            }
        }
    });

    // Form submissions
    document.getElementById('profile-form').addEventListener('submit', function(event) {
        event.preventDefault();
        // Here you would typically send an AJAX request to update the profile
        alert('Profile updated successfully!');
        closeModal('profile-modal');
    });

    document.getElementById('email-form').addEventListener('submit', function(event) {
        event.preventDefault();
        // Validate email format and match
        if (document.getElementById('new-email').value !== document.getElementById('confirm-email').value) {
            alert('Emails do not match!');
            return;
        }
        // Here you would typically send an AJAX request to update the email
        alert('Email updated successfully!');
        closeModal('email-modal');
    });

    document.getElementById('password-form').addEventListener('submit', function(event) {
        event.preventDefault();
        // Validate password match
        if (document.getElementById('new-password').value !== document.getElementById('confirm-password').value) {
            alert('Passwords do not match!');
            return;
        }
        // Here you would typically send an AJAX request to update the password
        alert('Password updated successfully!');
        closeModal('password-modal');
    });

    document.getElementById('photo-form').addEventListener('submit', function(event) {
        event.preventDefault();
        // Here you would typically send an AJAX request to upload the photo
        const fileInput = document.getElementById('profile-photo');
        if (fileInput.files.length > 0) {
            // Simple preview (in a real application, you'd upload the file to the server)
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('admin-img').src = e.target.result;
            };
            reader.readAsDataURL(fileInput.files[0]);
            alert('Photo uploaded successfully!');
            closeModal('photo-modal');
        }
    });
</script>
</body>
</html>
