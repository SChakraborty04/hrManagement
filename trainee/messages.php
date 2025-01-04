<?php

require_once '../db_connect.php';
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'trainee') {
    header("Location: ../login.php");
    exit();
}

$trainee_id = $_SESSION['user_id'];

// Fetch trainee information
$stmt = $pdo->prepare("SELECT t.*, u.username, u.email, u.phone, 
           f.id as faculty_id, f.username as faculty_name, 
           hr.id as hr_id, hr.username as hr_name
    FROM trainees t 
    JOIN users u ON t.user_id = u.id 
    LEFT JOIN users f ON t.faculty_id = f.id 
    LEFT JOIN users hr ON hr.role = 'hr'
    WHERE t.user_id = ?
    LIMIT 1");
$stmt->execute([$trainee_id]);
$trainee = $stmt->fetch();

$chat_with = isset($_GET['with']) ? $_GET['with'] : 'faculty';
$receiver_id = ($chat_with === 'faculty') ? $trainee['faculty_id'] : $trainee['hr_id'];

// Fetch messages
if ($receiver_id) {
    $stmt = $pdo->prepare("SELECT m.*, u.username as sender_name 
                           FROM messages m 
                           JOIN users u ON m.sender_id = u.id
                           WHERE (m.sender_id = ? AND m.receiver_id = ?) 
                              OR (m.sender_id = ? AND m.receiver_id = ?)
                           ORDER BY m.timestamp ASC");
    $stmt->execute([$trainee_id, $receiver_id, $receiver_id, $trainee_id]);
    $messages = $stmt->fetchAll();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trainee Chat</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <h2>Trainee Dashboard</h2>
            <nav>
                <ul>
                    <li><a href="dashboard.php">Overview</a></li>
                    <li><a href="registration_form.php">Registration Form</a></li>
                    <li><a href="messages.php" class="active">Chat with Faculty</a></li>
                </ul>
            </nav>
            <a href="../logout.php" class="logout">Logout</a>
        </aside>
        
        <main class="main-content">
            <h1>Welcome, <?php echo htmlspecialchars($trainee['name']); ?></h1>
            
            <div class="chat-container">
                <div class="chat-sidebar">
                    <h2>Chat With</h2>
                    <select id="chatWithSelect" class="chat-with-select">
                        <option value="faculty" <?php echo $chat_with === 'faculty' ? 'selected' : ''; ?>>Faculty</option>
                        <option value="hr" <?php echo $chat_with === 'hr' ? 'selected' : ''; ?>>HR</option>
                    </select>
                </div>
                <div class="chat-area">
                    <?php if ($receiver_id): ?>
                        <h2>Chat with <?php echo $chat_with === 'faculty' ? htmlspecialchars($trainee['faculty_name']) : 'HR'; ?></h2>
                        <div class="messages" id="messageContainer">
                            <?php foreach ($messages as $message): ?>
                                <div class="message <?php echo $message['sender_id'] == $trainee_id ? 'sent' : 'received'; ?>" id="message-<?php echo $message['id']; ?>" data-message-id="<?php echo $message['id']; ?>">
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
                        <p>You don't have a faculty assigned yet. Please contact HR for assistance.</p>
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
        const chatWithSelect = $('#chatWithSelect');
        let lastMessageId = <?php echo !empty($messages) ? $messages[count($messages) - 1]['id'] : 0; ?>;

        function scrollToBottom() {
            messageContainer.scrollTop(messageContainer[0].scrollHeight);
        }

        scrollToBottom();

        chatWithSelect.on('change', function() {
            const selectedChatWith = $(this).val();
            window.location.href = 'messages.php?with=' + selectedChatWith;
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
                        receiver_id: <?php echo json_encode($receiver_id); ?>,
                        chat_with: <?php echo json_encode($chat_with); ?>
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
                messageElement.append($('<strong>').text(type === 'sent' ? 'You: ' : (<?php echo json_encode($chat_with); ?> === 'faculty' ? 'Faculty: ' : 'HR: ')));
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
            if (<?php echo json_encode($receiver_id); ?>) {
                $.ajax({
                    url: 'get_messages.php',
                    method: 'GET',
                    data: {
                        receiver_id: <?php echo json_encode($receiver_id); ?>,
                        chat_with: <?php echo json_encode($chat_with); ?>,
                        last_message_id: lastMessageId
                    },
                    success: function(response) {
                        const data = JSON.parse(response);
                        if (data.success) {
                            data.messages.forEach(function(message) {
                                appendMessage(message.id, message.message, message.sender_id == <?php echo $trainee_id; ?> ? 'sent' : 'received', message.timestamp);
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



