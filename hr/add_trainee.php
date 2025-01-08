<?php
session_start();
require_once '../db_connect.php';

// Check if user is logged in and has HR role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'hr') {
    header("Location: ../login.php");
    exit();
}

$success_message = '';
$error_message = '';
$form_data = [
    'name' => '',
    'phone' => '',
    'email' => ''
];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    
    // Store form data in case of error
    $form_data = [
        'name' => $name,
        'phone' => $phone,
        'email' => $email
    ];

    // Check for duplicate entries
    $stmt = $pdo->prepare("SELECT * FROM trainee_access WHERE name = ? OR phone = ? OR email = ?");
    $stmt->execute([$name, $phone, $email]);
    $existing_entries = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $is_duplicate = false;
    if (!empty($existing_entries)) {
        foreach ($existing_entries as $entry) {
            if ($entry['name'] == $name) {
                $error_message .= "A trainee with this name already exists. ";
                $is_duplicate = true;
            }
            if ($entry['phone'] == $phone) {
                $error_message .= "This phone number is already registered. ";
                $is_duplicate = true;
            }
            if ($entry['email'] == $email) {
                $error_message .= "This email is already registered. ";
                $is_duplicate = true;
            }
        }
    }

    if (!$is_duplicate) {
        // Generate a random access code
        $access_code = bin2hex(random_bytes(4));
        
        try {
            $stmt = $pdo->prepare("INSERT INTO trainee_access (name, phone, email, access_code) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $phone, $email, $access_code]);
            $success_message = "New trainee added successfully with access code: " . $access_code;
            // Clear form data on success
            $form_data = ['name' => '', 'phone' => '', 'email' => ''];
        } catch (PDOException $e) {
            $error_message = "Error adding new trainee: " . $e->getMessage();
        }
    }
}

// Prepare the response data
$response = [
    'success' => !empty($success_message),
    'message' => $success_message ?: $error_message,
    'formData' => $form_data
];

// If it's an AJAX request, return JSON response
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Trainee</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .error-message {
            color: #721c24;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
        }
        .success-message {
            color: #155724;
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
        }
    </style>
</head>
<body>
<header>
<div class="navbar">
<img src="../logo.jpeg" id="logo" alt="drdo logo" />
<div class="brand">ठोस राज्य भौतिकी प्रयोगशाला मानव संसाधन प्रबंधन
/ Solid State Physics Laboratory Human Resource Management</div>
</div>
</header>
    <div class="dashboard-container">
        <aside class="sidebar">
            <h2>HR Dashboard</h2>
            <nav>
                <ul>
                    <li><a href="dashboard.php" >Overview</a></li>
                    <li><a href="add_trainee.php" class="active">Add New Trainee</a></li>
                    <li><a href="unsigned_users.php" >Unsigned Users</a></li>
                    <li><a href="trainee_management.php">Trainee Management</a></li>
                    <li><a href="trainee_reviews.php">Trainee Reviews</a></li>
                    <li><a href="messages.php">Messages</a></li>
                </ul>
            </nav>
            <a href="../logout.php" class="logout">Logout</a>
        </aside>
        
        <main class="main-content">
            <h1>Add New Trainee</h1>
            
            <div id="message-container"></div>
            
            <form method="POST" class="add-trainee-form" id="addTraineeForm">
                <input type="text" name="name" placeholder="Trainee Name" required value="<?php echo htmlspecialchars($form_data['name']); ?>">
                <input type="tel" name="phone" placeholder="Phone Number" required value="<?php echo htmlspecialchars($form_data['phone']); ?>">
                <input type="email" name="email" placeholder="Email Address" required value="<?php echo htmlspecialchars($form_data['email']); ?>">
                <button type="submit">Add Trainee</button>
            </form>
        </main>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('addTraineeForm');
        const messageContainer = document.getElementById('message-container');

        function showMessage(message, type) {
            messageContainer.innerHTML = `<div class="${type}-message">${message}</div>`;
        }

        <?php if ($success_message): ?>
        showMessage('<?php echo addslashes($success_message); ?>', 'success');
        <?php endif; ?>

        <?php if ($error_message): ?>
        showMessage('<?php echo addslashes($error_message); ?>', 'error');
        <?php endif; ?>

        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(form);
            
            fetch('add_trainee.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage(data.message, 'success');
                    form.reset();
                } else {
                    showMessage(data.message, 'error');
                }

                // Update form fields with returned values
                form.querySelector('[name="name"]').value = data.formData.name;
                form.querySelector('[name="phone"]').value = data.formData.phone;
                form.querySelector('[name="email"]').value = data.formData.email;
            })
            .catch(error => {
                console.error('Error:', error);
                showMessage('An error occurred. Please try again.', 'error');
            });
        });
    });
    </script>
</body>
</html>

