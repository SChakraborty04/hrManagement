<?php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'trainee') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$trainee_id = $_SESSION['user_id'];
$receiver_id = $_POST['receiver_id'] ?? null;
$chat_with = $_POST['chat_with'] ?? null;
$message = $_POST['message'] ?? null;

if (!$receiver_id || !$message || !$chat_with) {
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
            echo json_encode(['success' => false, 'message' => 'Unauthorized to send message to this faculty']);
            exit();
        }
    } elseif ($chat_with === 'hr') {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ? AND role = 'hr'");
        $stmt->execute([$receiver_id]);
        if (!$stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized to send message to this HR']);
            exit();
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid chat type']);
        exit();
    }

    // Insert the new message
    $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
    $stmt->execute([$trainee_id, $receiver_id, $message]);

    $message_id = $pdo->lastInsertId();

    echo json_encode([
        'success' => true, 
        'message' => 'Message sent successfully',
        'message_id' => $message_id,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
} catch (PDOException $e) {
    error_log("Database error in send_message.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while sending the message']);
}


