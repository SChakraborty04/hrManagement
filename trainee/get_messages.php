<?php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'trainee') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$trainee_id = $_SESSION['user_id'];
$receiver_id = $_GET['receiver_id'] ?? null;
$chat_with = $_GET['chat_with'] ?? null;
$last_message_id = $_GET['last_message_id'] ?? 0;

if (!$receiver_id || !$chat_with) {
    echo json_encode(['success' => false, 'message' => 'Missing required information']);
    exit();
}

try {
    // Verify that the receiver is either the assigned faculty or an HR
    if ($chat_with === 'faculty') {
        $stmt = $pdo->prepare("SELECT faculty_id FROM trainees WHERE user_id = ?");
        $stmt->execute([$trainee_id]);
        $assigned_faculty_id = $stmt->fetchColumn();
        if ($assigned_faculty_id != $receiver_id) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized access to this faculty']);
            exit();
        }
    } elseif ($chat_with === 'hr') {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ? AND role = 'hr'");
        $stmt->execute([$receiver_id]);
        if (!$stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized access to this HR']);
            exit();
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid chat type']);
        exit();
    }

    // Fetch new messages
    $stmt = $pdo->prepare("SELECT m.id, m.sender_id, m.message, m.timestamp, u.username as sender_name 
                           FROM messages m 
                           JOIN users u ON m.sender_id = u.id
                           WHERE m.id > ? AND
                                 ((m.sender_id = ? AND m.receiver_id = ?) OR 
                                  (m.sender_id = ? AND m.receiver_id = ?))
                           ORDER BY m.timestamp ASC");
    $stmt->execute([$last_message_id, $trainee_id, $receiver_id, $receiver_id, $trainee_id]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Update read status for received messages
    if (!empty($messages)) {
        $stmt = $pdo->prepare("UPDATE messages SET is_read = 1 
                               WHERE receiver_id = ? AND sender_id = ? AND id <= ?");
        $stmt->execute([$trainee_id, $receiver_id, $messages[count($messages) - 1]['id']]);
    }

    echo json_encode(['success' => true, 'messages' => $messages]);
} catch (PDOException $e) {
    error_log("Database error in get_messages.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while fetching messages']);
}

