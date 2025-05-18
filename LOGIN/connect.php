<?php

$host = "localhost";
$user = "root";
$pass = "";

// First connect without database
$conn = new mysqli($host, $user, $pass);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database if it doesn't exist
$sql = "CREATE DATABASE IF NOT EXISTS login";
if ($conn->query($sql) === FALSE) {
    die("Error creating database: " . $conn->error);
}

// Select the database explicitly
if (!$conn->select_db("login")) {
    die("Error selecting database: " . $conn->error);
}

// Enable autocommit
$conn->autocommit(TRUE);

// Create users table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    firstName VARCHAR(50) NOT NULL,
    lastName VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === FALSE) {
    die("Error creating table: " . $conn->error);
}

// Verify database and table
$current_db = $conn->query("SELECT DATABASE()")->fetch_row()[0];
echo "Current database: " . $current_db . "<br>";

$tables = $conn->query("SHOW TABLES");
echo "Tables in database:<br>";
while ($table = $tables->fetch_row()) {
    echo "- " . $table[0] . "<br>";
}
?>