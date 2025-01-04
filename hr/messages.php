<?php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'hr') {
    header("Location: ../login.php");
    exit();
}

$hr_id = $_SESSION['user_id'];

// Fetch all trainees
$stmt = $pdo->prepare("SELECT u.id, u.username, t.id as trainee_id, t.name as trainee_name, f.username as faculty_name
                       FROM users u
                       JOIN trainees t ON u.id = t.user_id
                       LEFT JOIN users f ON t.faculty_id = f.id
                       ORDER BY t.name");
$stmt->execute();
$trainees = $stmt->fetchAll();

$chat_with = isset($_GET['with']) ? $_GET['with'] : ($trainees[0]['id'] ?? null);

// Fetch messages
if ($chat_with) {
    $stmt = $pdo->prepare("SELECT m.*, u.username as sender_name 
                           FROM messages m 
                           JOIN users u ON m.sender_id = u.id
                           WHERE (m.sender_id = ? AND m.receiver_id = ?) 
                              OR (m.sender_id = ? AND m.receiver_id = ?)
                           ORDER BY m.timestamp ASC");
    $stmt->execute([$hr_id, $chat_with, $chat_with, $hr_id]);
    $messages = $stmt->fetchAll();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HR Chat</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <h2>HR Dashboard</h2>
            <nav>
                <ul>
                    <li><a href="hr_dashboard.php">Overview</a></li>
                    <li><a href="add_trainee.php">Add New Trainee</a></li>
                    <li><a href="no_access_code_users.php">Users without Access Code</a></li>
                    <li><a href="trainee_management.php">Trainee Management</a></li>
                    <li><a href="chat.php" class="active">Messages</a></li>
                </ul>
            </nav>
            <a href="../logout.php" class="logout">Logout</a>
        </aside>
        
        <main class="main-content">
            <h1>HR Chat</h1>
            
            <div class="chat-container">
                <div class="chat-sidebar">
                    <h2>Chat With Trainees</h2>
                    <select id="traineeSelect" class="trainee-select">
                        <option value="">Select a trainee</option>
                        <?php foreach ($trainees as $trainee): ?>
                            <option value="<?php echo $trainee['id']; ?>" <?php echo $chat_with == $trainee['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($trainee['trainee_name']); ?> (<?php echo htmlspecialchars($trainee['faculty_name'] ?? 'Unassigned'); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="chat-area">
                    <?php if ($chat_with): ?>
                        <h2>Chat with <?php echo htmlspecialchars($trainees[array_search($chat_with, array_column($trainees, 'id'))]['trainee_name']); ?></h2>
                        <div class="messages" id="messageContainer">
                            <?php foreach ($messages as $message): ?>
                                <div class="message <?php echo $message['sender_id'] == $hr_id ? 'sent' : 'received'; ?>" id="message-<?php echo $message['id']; ?>" data-message-id="<?php echo $message['id']; ?>">
                                    <strong><?php echo htmlspecialchars($message['sender_name']); ?>:</strong>
                                    <p><?php echo htmlspecialchars($message['message']); ?></p>
                                    <small><?php echo date('M d, H:i', strtotime($message['timestamp'])); ?></small>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <form class="chat-form" id="chatForm">
                            <input type="text" name="message" id="messageInput" placeholder="Type your message..." required>
                            <button type="submit">Send</button>
                        </form>
                    <?php else: ?>
                        <p>Select a trainee to start chatting.</p>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    $(document).ready(function() {
        const messageContainer = $('#messageContainer');
        const chatForm = $('#chatForm');
        const messageInput = $('#messageInput');
        const traineeSelect = $('#traineeSelect');
        let lastMessageId = <?php echo !empty($messages) ? $messages[count($messages) - 1]['id'] : 0; ?>;

        function scrollToBottom() {
            messageContainer.scrollTop(messageContainer[0].scrollHeight);
        }

        scrollToBottom();

        traineeSelect.on('change', function() {
            const selectedTraineeId = $(this).val();
            if (selectedTraineeId) {
                window.location.href = 'messages.php?with=' + selectedTraineeId;
            }
        });

        chatForm.on('submit', function(e) {
            e.preventDefault();
            const message = messageInput.val().trim();
            if (message) {
                $.ajax({
                    url: 'send_message.php',
                    method: 'POST',
                    data: {
                        message: message,
                        receiver_id: <?php echo json_encode($chat_with); ?>
                    },
                    success: function(response) {
                        const data = JSON.parse(response);
                        if (data.success) {
                            appendMessage(data.message_id, message, 'sent', data.timestamp);
                            messageInput.val('');
                            scrollToBottom();
                            lastMessageId = Math.max(lastMessageId, data.message_id);
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

        function appendMessage(id, message, type, timestamp) {
            if ($(`#message-${id}`).length === 0) {
                const messageElement = $('<div>')
                    .addClass('message')
                    .addClass(type)
                    .attr('id', `message-${id}`)
                    .attr('data-message-id', id);
                messageElement.append($('<strong>').text(type === 'sent' ? 'You: ' : 'Trainee: '));
                messageElement.append($('<p>').text(message));
                messageElement.append($('<small>').text(formatTimestamp(timestamp)));
                messageContainer.append(messageElement);
                scrollToBottom();
            }
        }

        function formatTimestamp(timestamp) {
            const date = new Date(timestamp);
            return date.toLocaleString('en-US', { month: 'short', day: 'numeric', hour: 'numeric', minute: 'numeric', hour12: true });
        }

        // Poll for new messages
        setInterval(function() {
            if (<?php echo json_encode($chat_with); ?>) {
                $.ajax({
                    url: 'get_messages.php',
                    method: 'GET',
                    data: {
                        trainee_id: <?php echo json_encode($chat_with); ?>,
                        last_message_id: lastMessageId
                    },
                    success: function(response) {
                        const data = JSON.parse(response);
                        if (data.success) {
                            data.messages.forEach(function(message) {
                                appendMessage(message.id, message.message, message.sender_id == <?php echo $hr_id; ?> ? 'sent' : 'received', message.timestamp);
                                lastMessageId = Math.max(lastMessageId, message.id);
                            });
                            scrollToBottom();
                        }
                    }
                });
            }
        }, 5000);
    });
    </script>
</body>
</html>