<?php
session_start();
require_once '../db_connect.php';

// Check if user is logged in and has faculty role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'faculty') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$faculty_id = $_SESSION['user_id'];
$trainee_id = $_GET['trainee_id'] ?? null;
$last_message_id = $_GET['last_message_id'] ?? 0;

if (!$trainee_id) {
    echo json_encode(['success' => false, 'message' => 'Missing trainee ID']);
    exit();
}

try {
    // Verify that the trainee is assigned to this faculty
    $stmt = $pdo->prepare("SELECT id FROM trainees WHERE user_id = ? AND faculty_id = ?");
    $stmt->execute([$trainee_id, $faculty_id]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized access to this trainee']);
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
    $stmt->execute([$last_message_id, $faculty_id, $trainee_id, $trainee_id, $faculty_id]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Update read status for received messages
    if (!empty($messages)) {
        $stmt = $pdo->prepare("UPDATE messages SET is_read = 1 
                               WHERE receiver_id = ? AND sender_id = ? AND id <= ?");
        $stmt->execute([$faculty_id, $trainee_id, $messages[count($messages) - 1]['id']]);
    }

    echo json_encode(['success' => true, 'messages' => $messages]);
} catch (PDOException $e) {
    error_log("Database error in get_messages.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while fetching messages']);
}

