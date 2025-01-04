<?php
session_start();
require_once '../db_connect.php';

// Check if user is logged in and has HR role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'hr') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_POST['user_id'] ?? '';
    $type = $_POST['type'] ?? '';

    if (empty($user_id) || empty($type)) {
        echo json_encode(['success' => false, 'message' => 'Missing required information']);
        exit();
    }

    try {
        if ($type === 'no_access_code') {
            $stmt = $pdo->prepare("DELETE FROM trainee_access WHERE id = ?");
        } elseif ($type === 'trainee') {
            // Start a transaction
            $pdo->beginTransaction();

            // Delete from trainees table
            $stmt = $pdo->prepare("DELETE FROM trainees WHERE id = ?");
            $stmt->execute([$user_id]);

            // Get the user_id from trainees table
            $stmt = $pdo->prepare("SELECT user_id FROM trainees WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();

            if ($user) {
                // Delete from users table
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$user['user_id']]);
            }

            // Commit the transaction
            $pdo->commit();
        } else {
            throw new Exception('Invalid user type');
        }

        $stmt->execute([$user_id]);
        echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
    } catch (Exception $e) {
        if ($type === 'trainee') {
            $pdo->rollBack();
        }
        echo json_encode(['success' => false, 'message' => 'Error deleting user: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

