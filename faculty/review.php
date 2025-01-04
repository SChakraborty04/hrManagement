<?php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'faculty') {
    header("Location: ../login.php");
    exit();
}

$faculty_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
$stmt->execute([$faculty_id]);
$faculty_info = $stmt->fetch();
// Fetch assigned trainees
$stmt = $pdo->prepare("
    SELECT 
        t.id AS trainee_id, 
        t.name AS trainee_name, 
        u.phone, 
        u.email, 
        r.rating, 
        r.notes, 
        r.review_date
    FROM trainees t
    JOIN users u ON t.user_id = u.id
    LEFT JOIN reviews r ON t.id = r.trainee_id
    WHERE t.faculty_id = ? AND t.status = 'accepted'
    ORDER BY t.id, r.review_date DESC
");
$stmt->execute([$faculty_id]);
$rows = $stmt->fetchAll();

$trainees = [];
foreach ($rows as $row) {
    $trainees[$row['trainee_id']]['info'] = $row;
    if ($row['rating'] !== null) {
        $trainees[$row['trainee_id']]['reviews'][] = [
            'rating' => $row['rating'],
            'notes' => $row['notes'],
            'date' => $row['review_date']
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Dashboard</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="faculty_dashboard.css">
    <style>
    .review-history {
        background-color: #f9f9f9; /* Light gray background */
        font-size: 14px; /* Slightly smaller font for reviews */
        color: #333; /* Dark text color for readability */
        border: 2px solid #dadada; /* Separator line */
        padding: 10px; /* Add padding for spacing */
        border-radius: 25px;
    }

    .review-history td {
        padding: 15px;
    }

    .review-history ul {
        list-style-type: disc; /* Bulleted list */
        margin: 10px 20px; /* Space around the list */
        padding: 0;
    }

    .review-history ul li {
        margin-bottom: 5px; /* Space between review items */
    }

    .review-history strong {
        color: #007bff; /* Highlighted strong text in blue */
    }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <h2>Faculty Dashboard</h2>
            <nav>
                <ul>
                    <li><a href="faculty_dashboard.php">Assigned Trainees</a></li>
                    <li><a href="review.php" class="active">Review Trainees</a></li>
                    <li><a href="#">Projects</a></li>
                    <li><a href="#">Messages</a></li>
                    <li><a href="#">Profile</a></li>
                </ul>
            </nav>
            <a href="../logout.php" class="logout">Logout</a>
        </aside>
        
        <main class="main-content">
            <h1>Welcome, <?php echo htmlspecialchars($faculty_info['username']); ?></h1>
            <h2>Review Trainees</h2>
            <div class="table-wrapper">
                <div class="table-controls">
                    <div class="entries-info">
                        Show 
                        <select id="entriesPerPage4">
                            <option value="5">5</option>
                            <option value="10" selected>10</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                        entries
                    </div>
                    <div class="table-search">
                        <input type="text" id="searchInput4" placeholder="Search...">
                    </div>
                </div>
                <table id="reviewTable" class="data-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>Latest Review</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($trainees as $trainee_id => $trainee): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($trainee['info']['trainee_name']); ?></td>
                            <td><?php echo htmlspecialchars($trainee['info']['phone']); ?></td>
                            <td><?php echo htmlspecialchars($trainee['info']['email']); ?></td>
                            <td>
                                <?php if (!empty($trainee['reviews'])): ?>
                                    <?php 
                                    $latest_review = $trainee['reviews'][0];
                                    echo htmlspecialchars($latest_review['rating']) . ' (' . date('Y-m-d', strtotime($latest_review['date'])) . ')'; 
                                    ?>
                                <?php else: ?>
                                    Not reviewed yet
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="btn btn-secondary toggle-reviews" data-trainee-id="<?php echo $trainee_id; ?>">Show Reviews</button>
                                <button class="btn btn-primary review-btn" data-trainee-id="<?php echo $trainee_id; ?>">Review</button>
                            </td>
                        </tr>
                        <tr class="review-history" id="review-history-<?php echo $trainee_id; ?>"  style="display: none;" >
                            <td colspan="5">
                                <strong>Review History:</strong>
                                <?php if (!empty($trainee['reviews'])): ?>
                                    <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Rating</th>
                                            <th>Notes</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($trainee['reviews'] as $review): ?>
                                        <tr>
                                            <td><?php echo date('Y-m-d', strtotime($review['date'])); ?></td>
                                            <td><?php echo htmlspecialchars($review['rating']); ?></td>
                                            <td><?php echo htmlspecialchars($review['notes']); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                                <?php else: ?>
                                    No reviews available.
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>

                </table>
                <div class="table-footer">
                    <div class="table-info"></div>
                    <div class="pagination">
                        <button class="page-nav" data-action="first">First</button>
                        <button class="page-nav" data-action="prev">Previous</button>
                        <div class="page-numbers"></div>
                        <button class="page-nav" data-action="next">Next</button>
                        <button class="page-nav" data-action="last">Last</button>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <!-- Review Modal -->
    <div id="reviewModal" class="modal">
        <div class="modal-content">
            <h2>Review Trainee</h2>
            <form id="reviewForm">
                <input type="hidden" id="reviewTraineeId" name="trainee_id">
                <div class="form-group">
                    <label for="rating">Rating:</label>
                    <select id="rating" name="rating" required>
                        <option value="">Select a rating</option>
                        <option value="Poor">Poor</option>
                        <option value="Average">Average</option>
                        <option value="Good">Good</option>
                        <option value="Excellent">Excellent</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="notes">Notes:</label>
                    <textarea id="notes" name="notes" required></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Submit Review</button>
                <button type="button" class="btn btn-secondary close-modal">Cancel</button>
            </form>
        </div>
    </div>

    
    <script src="../table.js"></script>
    <script>

        document.addEventListener('DOMContentLoaded', function() {
            new DataTable('reviewTable', { controlsSuffix: '4' });
            //review
            const reviewModal = document.getElementById('reviewModal');
        const reviewForm = document.getElementById('reviewForm');

        // Open review modal
        document.querySelectorAll('.review-btn').forEach(button => {
            button.addEventListener('click', function() {
                const traineeId = this.getAttribute('data-trainee-id');
                document.getElementById('reviewTraineeId').value = traineeId;
                console.log("Hello! "+traineeId)
                reviewModal.style.display = 'block';
            });
        });

        // Close modal
        document.querySelectorAll('.close-modal').forEach(button => {
            button.addEventListener('click', function() {
                reviewModal.style.display = 'none';
            });
        });

         document.querySelectorAll('.toggle-reviews').forEach(button => {
                button.addEventListener('click', function () {
                    const traineeId = this.getAttribute('data-trainee-id');
                    const reviewRow = document.getElementById(`review-history-${traineeId}`);
                    const isVisible = reviewRow.style.display === 'table-row';
                    reviewRow.style.display = isVisible ? 'none' : 'table-row';
                    this.textContent = isVisible ? 'Show Reviews' : 'Hide Reviews';
                });
          });

        // Handle review form submission
        reviewForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'submit_review');
            fetch('faculty_actions.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            });
            reviewModal.style.display = 'none';
        });
        });
    </script>
</body>
</html>



