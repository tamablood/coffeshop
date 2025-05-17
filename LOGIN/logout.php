<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logout</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <a href="javascript:history.back()" class="back-arrow" style="color: #000000;"><i class="fas fa-arrow-left" style="color: #000000;"></i></a>
        <h1 class="form-title">Logging out...</h1>
    </div>
</body>
</html>
<?php
session_destroy();
header("location: index.php");
?>