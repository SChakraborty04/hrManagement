<?php
session_start();

// If user is already logged in, redirect to appropriate dashboard
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    header("Location: {$_SESSION['role']}_dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to HR Management System</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .role-buttons {
            display: flex;
            justify-content: space-around;
            margin-top: 30px;
        }
        .role-button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        .role-button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Welcome to HR Management System</h1>
        <p>Please select your role to log in:</p>
        <div class="role-buttons">
            <a href="login.php?role=hr" class="role-button">HR Login</a>
            <a href="login.php?role=faculty" class="role-button">Faculty Login</a>
            <a href="login.php?role=trainee" class="role-button">Trainee Login</a>
        </div>
    </div>
</body>
</html>