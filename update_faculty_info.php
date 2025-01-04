<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'faculty') {
    header("Location: login.php");
    exit();
}

$faculty_id = $_SESSION['user_id'];

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);

    if (empty($email) || empty($phone)) {
        $error = "Email and phone number are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } elseif (!empty($new_password) && $new_password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        try {
            $pdo->beginTransaction();

            if (!empty($new_password)) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET email = ?, phone = ?, password = ?, info_updated = TRUE WHERE id = ?");
                $stmt->execute([$email, $phone, $hashed_password, $faculty_id]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET email = ?, phone = ?, info_updated = TRUE WHERE id = ?");
                $stmt->execute([$email, $phone, $faculty_id]);
            }

            $pdo->commit();
            $success = "Information updated successfully. Redirecting to dashboard...";
            header("Refresh: 3; URL=faculty_dashboard.php");
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "An error occurred. Please try again.";
            error_log("Error updating faculty info: " . $e->getMessage());
        }
    }
}

// Fetch current faculty information
$stmt = $pdo->prepare("SELECT email, phone, username, info_updated FROM users WHERE id = ?");
$stmt->execute([$faculty_id]);
$faculty_info = $stmt->fetch();

if (!$faculty_info['info_updated']) {
    include '../update_faculty_info.php';
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Faculty Information</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .update-form-container {
            max-width: 500px;
            margin: 50px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
        }
        .form-group input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .btn-update {
            background-color: #3498db;
            color: #fff;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn-update:hover {
            background-color: #2980b9;
        }
        .error-message {
            color: #e74c3c;
            margin-bottom: 10px;
        }
        .success-message {
            color: #2ecc71;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="update-form-container">
        <h1>Update Your Information</h1>
        <?php if ($error): ?>
            <p class="error-message"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        <?php if ($success): ?>
            <p class="success-message"><?php echo htmlspecialchars($success); ?></p>
        <?php endif; ?>
        <form method="POST" action="../update_faculty_info.php">
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($faculty_info['email']); ?>" required>
            </div>
            <div class="form-group">
                <label for="phone">Phone:</label>
                <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($faculty_info['phone']); ?>" required>
            </div>
            <div class="form-group">
                <label for="new_password">New Password (leave blank to keep current password):</label>
                <input type="password" id="new_password" name="new_password">
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm New Password:</label>
                <input type="password" id="confirm_password" name="confirm_password">
            </div>
            <button type="submit" class="btn-update">Update Information</button>
        </form>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            form.addEventListener('submit', function(e) {
                const newPassword = document.getElementById('new_password').value;
                const confirmPassword = document.getElementById('confirm_password').value;
                if (newPassword !== confirmPassword) {
                    e.preventDefault();
                    alert('Passwords do not match. Please try again.');
                }
            });
        });
    </script>
</body>
</html>

