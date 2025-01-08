<?php
session_start();
require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $access_code = $_POST['access_code'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $email = $_POST['email'];

    // Verify access code
    $stmt = $pdo->prepare("SELECT * FROM trainee_access WHERE name = ? AND phone = ? AND access_code = ? AND used = FALSE");
    $stmt->execute([$name, $phone, $access_code]);
    $access = $stmt->fetch();

    if ($access) {
        // Create user account
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, password, email, phone, role) VALUES (?, ?, ?, ?, 'trainee')");
        $stmt->execute([$username, $hashed_password, $email, $phone]);
        $user_id = $pdo->lastInsertId();

        // Create trainee record
        $stmt = $pdo->prepare("INSERT INTO trainees (user_id, name) VALUES (?, ?)");
        $stmt->execute([$user_id, $name]);

        // Mark access code as used
        $stmt = $pdo->prepare("UPDATE trainee_access SET used = TRUE WHERE id = ?");
        $stmt->execute([$access['id']]);

        $_SESSION['user_id'] = $user_id;
        $_SESSION['role'] = 'trainee';
        header("Location: trainee/dashboard.php");
        exit();
    } else {
        $error = "Invalid access information";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trainee Signup</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header>
<div class="navbar">
<img src="logo.jpeg" id="logo" alt="drdo logo" />
<div class="brand">ठोस राज्य भौतिकी प्रयोगशाला मानव संसाधन प्रबंधन
/ Solid State Physics Laboratory Human Resource Management</div>
</div>
</header>
    <div class="container">
        <h1>Trainee Signup</h1>
        <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
        <form method="POST">
            <input type="text" name="name" placeholder="Full Name" required>
            <input type="tel" name="phone" placeholder="Phone Number" required>
            <input type="text" name="access_code" placeholder="Access Code" required>
            <input type="text" name="username" placeholder="Choose Username" required>
            <input type="password" name="password" placeholder="Choose Password" required>
            <input type="email" name="email" placeholder="Email Address" required>
            <button type="submit">Sign Up</button>
        </form>
        <p>Already have an account? <a href="login.php?role=trainee">Login here</a></p>
    </div>
</body>
</html>