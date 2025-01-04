<?php
session_start();
require_once '../db_connect.php';

// Check if user is logged in and has HR role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'hr') {
    header("Location: ../login.php");
    exit();
}

// Fetch all trainees with their latest review
$stmt = $pdo->query("SELECT t.id, t.name, u.email, u.phone, f.username as faculty_name, 
                            r.rating, r.notes, r.review_date
                     FROM trainees t
                     JOIN users u ON t.user_id = u.id
                     LEFT JOIN users f ON t.faculty_id = f.id
                     LEFT JOIN reviews r ON t.id = r.trainee_id
                     WHERE r.id = (
                         SELECT id
                         FROM reviews
                         WHERE trainee_id = t.id
                         ORDER BY review_date DESC
                         LIMIT 1
                     )
                     ORDER BY r.review_date DESC");
$trainees = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trainee Reviews</title>
    <link rel="stylesheet" href="style.css">
    <style>
    /* Modal Background */
.modal {
    display: none; /* Hidden by default */
    position: fixed;
    z-index: 1000; /* Ensure it's above everything */
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto; /* Enable scrolling if needed */
    background-color: rgba(0, 0, 0, 0.6); /* Semi-transparent black background */
}

/* Modal Content */
.modal-content {
    background-color: #fff;
    margin: 10% auto; /* 10% from the top, centered horizontally */
    padding: 20px;
    border-radius: 8px;
    width: 50%; /* Adjust width as needed */
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    animation: slideDown 0.3s ease-out;
}

/* Heading */
.modal-content h2 {
    margin-top: 0;
    font-size: 24px;
    color: #333;
    text-align: center;
}

/* Modal Details */
#reviewDetails p {
    margin: 10px 0;
    font-size: 16px;
    color: #555;
}

/* Close Button */
.close-modal {
    display: block;
    margin: 20px auto 0;
    padding: 10px 20px;
    font-size: 16px;
    color: #fff;
    background-color: #007bff; /* Blue button */
    border: none;
    border-radius: 4px;
    cursor: pointer;
    text-align: center;
    transition: background-color 0.3s;
}

.close-modal:hover {
    background-color: #0056b3; /* Darker blue on hover */
}

/* Animations */
@keyframes slideDown {
    from {
        transform: translateY(-20%);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}
/* View Review Button */
.view-review-btn {
    display: inline-block;
    padding: 8px 16px;
    font-size: 14px;
    font-weight: bold;
    color: #fff;
    background-color: #28a745; /* Green button */
    border: none;
    border-radius: 4px;
    cursor: pointer;
    transition: background-color 0.3s, transform 0.2s;
    text-align: center;
    text-decoration: none;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

/* Hover Effect */
.view-review-btn:hover {
    background-color: #218838; /* Darker green */
    transform: translateY(-2px); /* Slight lift */
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
}

/* Focus Effect */
.view-review-btn:focus {
    outline: none;
    box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.5); /* Green focus ring */
}

/* Disabled State */
.view-review-btn:disabled {
    background-color: #6c757d; /* Gray button */
    cursor: not-allowed;
    box-shadow: none;
}


    </style>
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
                    <li><a href="hr_trainee_reviews.php" class="active">Trainee Reviews</a></li>
                </ul>
            </nav>
            <a href="../logout.php" class="logout">Logout</a>
        </aside>
        
        <main class="main-content">
            <h1>Trainee Reviews</h1>
            
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
                            <th>Trainee Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Faculty</th>
                            <th>Rating</th>
                            <th>Review Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($trainees as $trainee): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($trainee['name']); ?></td>
                            <td><?php echo htmlspecialchars($trainee['email']); ?></td>
                            <td><?php echo htmlspecialchars($trainee['phone']); ?></td>
                            <td><?php echo htmlspecialchars($trainee['faculty_name']); ?></td>
                            <td><?php echo htmlspecialchars($trainee['rating'] ?? 'Not reviewed'); ?></td>
                            <td><?php echo $trainee['review_date'] ? date('Y-m-d', strtotime($trainee['review_date'])) : 'N/A'; ?></td>
                            <td>
                                <button class="btn btn-primary view-review-btn" data-trainee-id="<?php echo $trainee['id']; ?>">View Review</button>
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
            <h2>Trainee Review</h2>
            <div id="reviewDetails"></div>
            <button class="btn btn-secondary close-modal">Close</button>
        </div>
    </div>

    <script src="../table.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        new DataTable('reviewTable',{ controlsSuffix: '4' });

        const reviewModal = document.getElementById('reviewModal');
        const reviewDetails = document.getElementById('reviewDetails');

        // Open review modal
        document.querySelectorAll('.view-review-btn').forEach(button => {
            button.addEventListener('click', function() {
                const traineeId = this.getAttribute('data-trainee-id');
                fetch(`get_review.php?trainee_id=${traineeId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            reviewDetails.innerHTML = `
                                <p><strong>Trainee:</strong> ${data.trainee_name}</p>
                                <p><strong>Faculty:</strong> ${data.faculty_name}</p>
                                <p><strong>Rating:</strong> ${data.rating}</p>
                                <p><strong>Review Date:</strong> ${data.review_date}</p>
                                <p><strong>Notes:</strong> ${data.notes}</p>
                            `;
                            reviewModal.style.display = 'block';
                        } else {
                            alert('Error: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred. Please try again.');
                    });
            });
        });

        // Close modal
        document.querySelector('.close-modal').addEventListener('click', function() {
            reviewModal.style.display = 'none';
        });
    });
    </script>
</body>
</html>

