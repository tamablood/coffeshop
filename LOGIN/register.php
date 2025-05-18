<?php 

include 'connect.php';

if(isset($_POST['signUp'])){
    $firstName = trim($_POST['fName']);
    $lastName = trim($_POST['lName']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    // Input validation
    if(empty($firstName) || empty($lastName) || empty($email) || empty($password)) {
        echo "All fields are required";
        exit();
    }
    
    if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "Invalid email format";
        exit();
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Check if email exists using prepared statement
        $checkEmail = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $checkEmail->bind_param("s", $email);
        $checkEmail->execute();
        $result = $checkEmail->get_result();
        
        if($result->num_rows > 0) {
            echo "Email Address Already Exists!";
            $conn->rollback();
        } else {
            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new user using prepared statement
            $insertQuery = $conn->prepare("INSERT INTO users(firstName, lastName, email, password) VALUES (?, ?, ?, ?)");
            $insertQuery->bind_param("ssss", $firstName, $lastName, $email, $hashedPassword);
            
            if($insertQuery->execute()) {
                // Verify the insert
                $verifyQuery = $conn->prepare("SELECT id FROM users WHERE email = ?");
                $verifyQuery->bind_param("s", $email);
                $verifyQuery->execute();
                $verifyResult = $verifyQuery->get_result();
                
                if($verifyResult->num_rows > 0) {
                    $conn->commit();
                    header("location: login.php");
                    exit();
                } else {
                    throw new Exception("Insert verification failed");
                }
            } else {
                throw new Exception("Error inserting user: " . $conn->error);
            }
        }
    } catch (Exception $e) {
        $conn->rollback();
        echo "Error: " . $e->getMessage();
    }
}

if(isset($_POST['signIn'])){
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    if(empty($email) || empty($password)) {
        echo "Email and password are required";
        exit();
    }
    
    // Get user using prepared statement
    $sql = $conn->prepare("SELECT id, email, password FROM users WHERE email = ?");
    $sql->bind_param("s", $email);
    $sql->execute();
    $result = $sql->get_result();
    
    if($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if(password_verify($password, $user['password'])) {
            session_start();
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['email'] = $user['email'];
            header("Location: index.html");
            exit();
        } else {
            echo "Incorrect password";
        }
    } else {
        echo "User not found";
    }
}
?>