<?php
session_start();
require_once '../db_connect.php';

// Check if user is logged in and has HR role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'hr') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if (!isset($_GET['trainee_id'])) {
    echo json_encode(['success' => false, 'message' => 'Trainee ID is required']);
    exit();
}

$trainee_id = $_GET['trainee_id'];

try {
    $stmt = $pdo->prepare("SELECT r.*, t.name AS trainee_name, u.username AS faculty_name
                           FROM reviews r
                           JOIN trainees t ON r.trainee_id = t.id
                           JOIN users u ON r.faculty_id = u.id
                           WHERE r.trainee_id = ?
                           ORDER BY r.review_date DESC
                           LIMIT 1");
    $stmt->execute([$trainee_id]);
    $review = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($review) {
        $response = [
            'success' => true,
            'trainee_name' => $review['trainee_name'],
            'faculty_name' => $review['faculty_name'],
            'rating' => $review['rating'],
            'notes' => $review['notes'],
            'review_date' => date('Y-m-d', strtotime($review['review_date']))
        ];
    } else {
        $response = ['success' => false, 'message' => 'No review found for this trainee'];
    }

    echo json_encode($response);
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while fetching the review']);
}

