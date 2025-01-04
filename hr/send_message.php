<?php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'hr') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$hr_id = $_SESSION['user_id'];
$receiver_id = $_POST['receiver_id'] ?? null;
$message = $_POST['message'] ?? null;

if (!$receiver_id || !$message) {
    echo json_encode(['success' => false, 'message' => 'Missing required information']);
    exit();
}

try {
    // Verify that the receiver is a trainee
    $stmt = $pdo->prepare("SELECT id FROM trainees WHERE user_id = ?");
    $stmt->execute([$receiver_id]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized to send message to this user']);
        exit();
    }

    // Insert the new message
    $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
    $stmt->execute([$hr_id, $receiver_id, $message]);

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

