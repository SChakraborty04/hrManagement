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
                    header("Location: hr/dashboard.php");
                    break;
                case 'faculty':
                    header("Location: faculty/dashboard.php");
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
</head>
<body>
<header>
<div class="navbar">
<img src="logo.jpeg" id="logo" alt="drdo logo" />
<div class="brand">ठोस राज्य भौतिकी प्रयोगशाला मानव संसाधन प्रबंधन
/ Solid State Physics Laboratory Human Resource Management</div>
</div>
</header>
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