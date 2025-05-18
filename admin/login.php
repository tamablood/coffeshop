<?php
session_start();

if (isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit;
}

require_once 'db.php';

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error_message = "Please enter both email and password.";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM admin WHERE email = ?");
            $stmt->execute([$email]);
            $admin = $stmt->fetch();

            if ($admin && $admin['password'] === $password) {
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_name'] = $admin['name'];
                $_SESSION['admin_email'] = $admin['email'];

                header("Location: index.php");
                exit;
            } else {
                $error_message = "Invalid email or password.";
            }
        } catch (\PDOException $e) {
            $error_message = "Error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Brew & Bean Admin Login</title>
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
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        .login-container {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
            width: 100%;
            max-width: 400px;
            padding: 30px;
            border-left: 4px solid #8D6E63;
        }

        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-header h1 {
            color: #5D4037;
            font-size: 24px;
            margin: 10px 0;
        }

        .login-header p {
            color: #A1887F;
            font-size: 14px;
        }

        .alert {
            padding: 10px 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #5D4037;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #D7CCC8;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        .form-control:focus {
            border-color: #8D6E63;
            outline: none;
        }

        .remember-me {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            color: #5D4037;
        }

        .remember-me input {
            margin-right: 8px;
        }

        .btn {
            padding: 12px 18px;
            background-color: #8D6E63;
            color: white;
            border: none;
            border-radius: 6px;
            width: 100%;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .btn:hover {
            background-color: #6D4C41;
        }

        .forgot-password {
            text-align: center;
            margin-top: 20px;
        }

        .forgot-password a {
            color: #8D6E63;
            text-decoration: none;
            font-size: 14px;
        }

        .forgot-password a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
<div class="login-container">
    <div class="login-header">
        <div style="font-size: 60px; text-align: center; margin-bottom: 15px;">☕</div>
        <h1>Brew & Bean</h1>
        <p>Enter your credentials to access the dashboard</p>
    </div>

    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger">
            <?php echo $error_message; ?>
        </div>
    <?php endif; ?>

    <form action="login.php" method="POST">
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email" required>
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
        </div>

        <div class="remember-me">
            <input type="checkbox" id="remember" name="remember">
            <label for="remember">Remember me</label>
        </div>

        <button type="submit" class="btn">Login</button>
    </form>

    <div class="forgot-password">
        <a href="#">Forgot your password?</a>
    </div>
</div>
</body>
</html>