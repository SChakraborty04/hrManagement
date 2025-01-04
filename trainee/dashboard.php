<?php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'trainee') {
    header("Location: ../login.php");
    exit();
}

$trainee_id = $_SESSION['user_id'];

// Fetch trainee information with registration status
$stmt = $pdo->prepare("SELECT t.*, u.username, u.email, u.phone, f.username as faculty_name 
                       FROM trainees t 
                       JOIN users u ON t.user_id = u.id 
                       LEFT JOIN users f ON t.faculty_id = f.id
                       WHERE t.user_id = ?");
$stmt->execute([$trainee_id]);
$trainee = $stmt->fetch();

// Check if registration is completed
if (!$trainee['registration_completed']) {
    $registration_alert = "Please complete your registration form to proceed with the training.";
}

// Success message for registration
if (isset($_GET['registration']) && $_GET['registration'] === 'success') {
    $success_message = "Registration form submitted successfully!";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trainee Dashboard</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <h2>Trainee Dashboard</h2>
            <nav>
                <ul>
                    <li><a href="dashboard.php" class="active">Overview</a></li>
                    <li><a href="registration_form.php">Registration Form</a></li>
                    <?php if ($trainee['status'] === 'accepted' && $trainee['faculty_id']): ?>
                    <li><a href="messages.php">Chat with Faculty</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
            <a href="../logout.php" class="logout">Logout</a>
        </aside>
        
        <main class="main-content">
            <h1>Welcome, <?php echo htmlspecialchars($trainee['name']); ?></h1>
            
            <?php if (isset($registration_alert)): ?>
                <div class="alert alert-warning">
                    <?php echo $registration_alert; ?>
                </div>
            <?php endif; ?>

            <?php if (isset($success_message)): ?>
                <div class="alert alert-success">
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>

            <div class="registration-status">
                <h3>Registration Status</h3>
                <p>
                    Status: 
                    <span class="status-badge <?php echo $trainee['registration_completed'] ? 'status-complete' : 'status-pending'; ?>">
                        <?php echo $trainee['registration_completed'] ? 'Complete' : 'Pending'; ?>
                    </span>
                </p>
                <?php if ($trainee['registration_completed']): ?>
                    <p>Submitted on: <?php echo date('F j, Y', strtotime($trainee['registration_date'])); ?></p>
                <?php else: ?>
                    <a href="registration_form.php" class="action-button">Complete Registration</a>
                <?php endif; ?>
            </div>

            <div class="trainee-info">
                <h2>Your Information</h2>
                <p><strong>Name:</strong> <?php echo htmlspecialchars($trainee['name']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($trainee['email']); ?></p>
                <p><strong>Phone:</strong> <?php echo htmlspecialchars($trainee['phone']); ?></p>
                <p><strong>Status:</strong> <?php echo htmlspecialchars($trainee['status']); ?></p>
                <p><strong>Assigned Faculty:</strong> <?php echo htmlspecialchars($trainee['faculty_name'] ?? 'Not Assigned'); ?></p>
                <p><strong>Project:</strong> <?php echo htmlspecialchars($trainee['project'] ?? 'Not Assigned'); ?></p>
                <p><strong>Project Completed:</strong> <?php echo $trainee['project_completed'] ? 'Yes' : 'No'; ?></p>
            </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    $(document).ready(function() {
        const chatMessages = $('#chatMessages');
        const chatForm = $('#chatForm');
        const messageInput = $('#messageInput');

        chatForm.on('submit', function(e) {
            e.preventDefault();
            const message = messageInput.val().trim();
            if (message) {
                $.ajax({
                    url: 'send_message.php',
                    method: 'POST',
                    data: {
                        message: message,
                        receiver_id: <?php echo $trainee['faculty_id']; ?>
                    },
                    success: function(response) {
                        const data = JSON.parse(response);
                        if (data.success) {
                            appendMessage(message, 'sent');
                            messageInput.val('');
                        } else {
                            alert('Failed to send message. Please try again.');
                        }
                    },
                    error: function() {
                        alert('An error occurred. Please try again.');
                    }
                });
            }
        });

        function appendMessage(message, type) {
            const messageElement = $('<div>').addClass('message').addClass(type);
            messageElement.append($('<p>').text(message));
            messageElement.append($('<span>').addClass('timestamp').text(getCurrentTime()));
            chatMessages.append(messageElement);
            chatMessages.scrollTop(chatMessages[0].scrollHeight);
        }

        function getCurrentTime() {
            const now = new Date();
            return now.toLocaleString('en-US', { month: 'short', day: 'numeric', hour: 'numeric', minute: 'numeric', hour12: true });
        }

        // Poll for new messages
        setInterval(function() {
            $.ajax({
                url: 'get_messages.php',
                method: 'GET',
                data: {
                    last_message_id: $('.message').last().data('message-id') || 0
                },
                success: function(response) {
                    const data = JSON.parse(response);
                    if (data.success) {
                        data.messages.forEach(function(message) {
                            appendMessage(message.message, 'received');
                        });
                    }
                }
            });
        }, 5000);
    });
    </script>
</body>
</html>

