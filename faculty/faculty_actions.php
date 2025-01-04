<?php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'faculty') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$faculty_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    $trainee_id = $_POST['trainee_id'] ?? '';
    if (empty($action) || empty($trainee_id)) {
        echo json_encode(['success' => false, 'message' => 'Missing required information']);
        exit();
    }
    try {
        switch ($action) {
            case 'accept_trainee':
                $stmt = $pdo->prepare("UPDATE trainees SET status = 'accepted' WHERE id = ? AND faculty_id = ?");
                $stmt->execute([$trainee_id, $faculty_id]);
                break;

            case 'reject_trainee':
                $reason = $_POST['reject_reason'] ?? '';
                $forward_to = $_POST['forward_to'] ?? null;

                if (empty($reason)) {
                    echo json_encode(['success' => false, 'message' => 'Rejection reason is required']);
                    exit();
                }

                $stmt = $pdo->prepare("UPDATE trainees SET status = 'rejected', status_reason = ?, forwarded_to = ?, forwarded_by = ?, hr_processed = FALSE WHERE id = ? AND faculty_id = ?");
                $stmt->execute([$reason, $forward_to, $faculty_id, $trainee_id, $faculty_id]);
                break;

            case 'assign_project':
                $project = $_POST['project'] ?? '';
                if (empty($project)) {
                    echo json_encode(['success' => false, 'message' => 'Project description is required']);
                    exit();
                }
                $stmt = $pdo->prepare("UPDATE trainees SET project = ? WHERE id = ? AND faculty_id = ?");
                $stmt->execute([$project, $trainee_id, $faculty_id]);
                break;

            case 'complete_project':
                $stmt = $pdo->prepare("UPDATE trainees SET project_completed = TRUE WHERE id = ? AND faculty_id = ?");
                $stmt->execute([$trainee_id, $faculty_id]);
                break;
            case 'submit_review':
                $rating = $_POST['rating'] ?? '';
                $notes = $_POST['notes'] ?? '';

                if (empty($rating) || empty($notes)) {
                    echo json_encode(['success' => false, 'message' => 'Rating and notes are required']);
                    exit();
                }

                $stmt = $pdo->prepare("INSERT INTO reviews (trainee_id, faculty_id, rating, notes, review_date) 
                                       VALUES (?, ?, ?, ?, NOW()) 
                                       ON DUPLICATE KEY UPDATE 
                                       rating = VALUES(rating), 
                                       notes = VALUES(notes), 
                                       review_date = NOW()");
                $stmt->execute([$trainee_id, $faculty_id, $rating, $notes]);
                $message = 'Review submitted successfully';
                break;
                
            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
                exit();
        }

        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Action completed successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'No changes were made. Please check if the trainee is assigned to you.']);
        }
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}