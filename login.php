<?php
session_start();
require_once 'db_connect.php';

$role = isset($_GET['role']) ? $_GET['role'] : 'trainee';
$roleTitle = ucfirst($role);

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $login_role = $_POST['role'] ?? '';

    if (empty($username) || empty($password)) {
        $error = "Username and password are required.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND role = ?");
        $stmt->execute([$username, $login_role]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            switch ($user['role']) {
                case 'hr':
                    header("Location: hr/hr_dashboard.php");
                    break;
                case 'faculty':
                    header("Location: faculty/faculty_dashboard.php");
                    break;
                case 'trainee':
                    header("Location: trainee/dashboard.php");
                    break;
            }
            exit();
        } else {
            $error = "Invalid username or password";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - HR Management System</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .login-container {
            max-width: 400px;
            margin: 50px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .role-selector {
            margin-bottom: 20px;
            text-align: center;
        }
        .role-selector a {
            display: inline-block;
            padding: 10px 20px;
            margin: 0 10px;
            background-color: #f0f0f0;
            color: #333;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        .role-selector a.active {
            background-color: #3498db;
            color: #fff;
        }
        .login-form input[type="text"],
        .login-form input[type="password"],
        .login-form button {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .login-form button {
            background-color: #3498db;
            color: #fff;
            border: none;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .login-form button:hover {
            background-color: #2980b9;
        }
        .error-message {
            color: #e74c3c;
            text-align: center;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h1>HR Management System Login</h1>
        <div class="role-selector">
            <a href="?role=trainee" class="<?php echo $role === 'trainee' ? 'active' : ''; ?>">Trainee</a>
            <a href="?role=faculty" class="<?php echo $role === 'faculty' ? 'active' : ''; ?>">Faculty</a>
            <a href="?role=hr" class="<?php echo $role === 'hr' ? 'active' : ''; ?>">HR</a>
        </div>
        <?php if ($error): ?>
            <p class='error-message'><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        <form method="POST" action="login.php" class="login-form">
            <input type="hidden" name="role" value="<?php echo htmlspecialchars($role); ?>">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Login as <?php echo $roleTitle; ?></button>
        </form>
        <?php if ($role === 'trainee'): ?>
            <p>New trainee? <a href="trainee_signup.php">Sign up here</a></p>
        <?php endif; ?>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('.login-form');
            form.addEventListener('submit', function(e) {
                const username = form.querySelector('input[name="username"]').value.trim();
                const password = form.querySelector('input[name="password"]').value.trim();
                if (username === '' || password === '') {
                    e.preventDefault();
                    alert('Please enter both username and password.');
                }
            });
        });
    </script>
</body>
</html>