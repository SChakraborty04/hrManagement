<?php
session_start();
require_once '../db_connect.php';

// Check if user is logged in and has HR role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'hr') {
    header("Location: ../login.php");
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['assign_faculty'])) {
        $trainee_id = $_POST['trainee_id'];
        $faculty_id = $_POST['faculty_id'];
        $stmt = $pdo->prepare("UPDATE trainees SET faculty_id = ?, status = 'pending', forwarded_to = NULL, forwarded_by = NULL, hr_processed = TRUE WHERE id = ?");
        $stmt->execute([$faculty_id, $trainee_id]);
    } elseif (isset($_POST['issue_certificate'])) {
        $trainee_id = $_POST['trainee_id'];
        $stmt = $pdo->prepare("UPDATE trainees SET certificate_issued = TRUE WHERE id = ?");
        $stmt->execute([$trainee_id]);
    }
    // Refresh the page to show updated data
    header("Location: trainee_management.php");
    exit();
}

// Fetch all trainees
$stmt = $pdo->query("SELECT t.*, u.username, u.email as user_email, u.phone as user_phone, 
                    f1.username as current_faculty_name, f2.username as forwarded_to_name,
                    f3.username as forwarded_by_name
                    FROM trainees t 
                    LEFT JOIN users u ON t.user_id = u.id 
                    LEFT JOIN users f1 ON t.faculty_id = f1.id
                    LEFT JOIN users f2 ON t.forwarded_to = f2.id
                    LEFT JOIN users f3 ON t.forwarded_by = f3.id
                    ORDER BY CASE 
                        WHEN t.status = 'rejected' AND t.forwarded_to IS NOT NULL AND t.hr_processed = FALSE THEN 0
                        ELSE 1
                    END, t.id");
$trainees = $stmt->fetchAll();

// Fetch faculty members for assignment
$stmt = $pdo->query("SELECT id, username FROM users WHERE role = 'faculty'");
$faculty_members = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trainee Management</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .forwarded {
            background-color: #fff3cd;
        }
        .action-form {
            display: inline-block;
            margin-right: 10px;
        }
         .action-header {
            background-color: #4caf50;
            color: white;
        }

        .details-btn {
            background-color: #3f51b5;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .details-btn:hover {
            background-color: #303f9f;
            transform: translateY(-2px);
        }

        .faculty-assign-form select {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-right: 8px;
            min-width: 200px;
            background-color: white;
            margin-bottom: 4px;
        }

        .faculty-assign-form button {
            background-color: #4caf50;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .faculty-assign-form button:hover {
            background-color: #45a049;
            transform: translateY(-2px);
        }

        .details-dropdown {
            display: none;
            background-color: #f8f9fa;
            padding: 15px;
            border: 1px solid #e9ecef;
            border-radius: 4px;
            margin: 10px 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .details-dropdown.active {
            display: table-row;
        }

        .details-content {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            padding: 10px;
        }

        .details-item {
            display: flex;
            align-items: center;
            padding: 8px;
            background-color: white;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .details-item strong {
            margin-right: 8px;
            color: #3f51b5;
            min-width: 120px;
        }

        .table-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            margin-top: 10px;
            border-top: 1px solid #ddd;
        }

        .table-info {
            font-size: 14px;
            color: #555;
        }

        .pagination {
            display: flex;
            gap: 5px;
        }

        .page-nav, .page-number {
            background-color: #f4f4f4;
            border: 1px solid #ddd;
            padding: 5px 10px;
            cursor: pointer;
            border-radius: 3px;
        }

        .page-nav:hover, .page-number:hover {
            background-color: #ddd;
        }

        .page-number.active {
            background-color: #4caf50;
            color: white;
            border: 1px solid #4caf50;
        }

        .delete-btn {
            background-color: #f44336;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 8px;
        }

        .delete-btn:hover {
            background-color: #d32f2f;
            transform: translateY(-2px);
        }
        .forwarded {
            background-color: #fff3cd;
        }
        .action-form {
            display: inline-block;
            margin-right: 10px;
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
                    <li><a href="trainee_management.php" class="active">Trainee Management</a></li>
                    <li><a href="hr_trainee_reviews.php">Trainee Reviews</a></li>
                </ul>
            </nav>
            <a href="../logout.php" class="logout">Logout</a>
        </aside>
        
        <main class="main-content">
            <h1>Trainee Management</h1>
            
            <div class="table-wrapper">
                <div class="table-controls">
                    <div class="entries-info">
                        Show 
                        <select id="entriesPerPage2">
                            <option value="5">5</option>
                            <option value="10" selected>10</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                        entries
                    </div>
                    <div class="table-search">
                        <input type="text" id="searchInput2" placeholder="Search...">
                    </div>
                    <div class="table-actions">
                        <button id="downloadExcel" class="download-excel-btn">Download Excel</button>
                    </div>
                </div>

                <table id="traineeTable" class="data-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Current Faculty</th>
                            <th>Status</th>
                            <th>Project</th>
                            <th>Project Completed</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($trainees as $trainee): ?>
                        <tr class="<?php echo ($trainee['status'] == 'rejected' && $trainee['forwarded_to'] && !$trainee['hr_processed']) ? 'forwarded' : ''; ?>">
                            <td><?php echo htmlspecialchars($trainee['name']); ?></td>
                            <td><?php echo htmlspecialchars($trainee['user_email']); ?></td>
                            <td><?php echo htmlspecialchars($trainee['user_phone']); ?></td>
                            <td><?php echo htmlspecialchars($trainee['current_faculty_name'] ?? 'Not Assigned'); ?></td>
                            <td><?php echo htmlspecialchars($trainee['status']); ?></td>
                            <td><?php echo htmlspecialchars($trainee['project'] ?? 'Not Assigned'); ?></td>
                            <td><?php echo $trainee['project_completed'] ? 'Yes' : 'No'; ?></td>
                            <td>
                                <?php if ($trainee['status'] == 'rejected' && $trainee['forwarded_to'] && !$trainee['hr_processed']): ?>
                                    <p>Rejected by: <?php echo htmlspecialchars($trainee['forwarded_by_name']); ?></p>
                                    <p>Suggested Faculty: <?php echo htmlspecialchars($trainee['forwarded_to_name']); ?></p>
                                    <p>Reason: <?php echo htmlspecialchars($trainee['status_reason']); ?></p>
                                    <form method="POST" class="action-form">
                                        <input type="hidden" name="trainee_id" value="<?php echo $trainee['id']; ?>">
                                        <select name="faculty_id" required>
                                            <option value="">Select Faculty</option>
                                            <?php foreach ($faculty_members as $faculty): ?>
                                                <option value="<?php echo $faculty['id']; ?>"
                                                    <?php echo ($faculty['id'] == $trainee['forwarded_to']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($faculty['username']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="submit" name="assign_faculty">Reassign Faculty</button>
                                    </form>
                                <?php elseif ($trainee['status'] == 'rejected'): ?>
                                    <p>Rejected</p>
                                    <p>Reason: <?php echo htmlspecialchars($trainee['status_reason']); ?></p>
                                <?php else: ?>
                                    <?php if (!$trainee['faculty_id']): ?>
                                        <form method="POST" class="faculty-assign-form">
                                            <input type="hidden" name="trainee_id" value="<?php echo $trainee['id']; ?>">
                                            <select name="faculty_id" required>
                                                <option value="">Select Faculty</option>
                                                <?php foreach ($faculty_members as $faculty): ?>
                                                    <option value="<?php echo $faculty['id']; ?>">
                                                        <?php echo htmlspecialchars($faculty['username']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="submit" name="assign_faculty">Assign Faculty</button>
                                        </form>
                                    <?php endif; ?>
                                    <?php if ($trainee['project_completed'] && !$trainee['certificate_issued']): ?>
                                        <form method="POST">
                                            <input type="hidden" name="trainee_id" value="<?php echo $trainee['id']; ?>">
                                            <button type="submit" name="issue_certificate">Issue Certificate</button>
                                        </form>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <button class="delete-btn" data-id="<?php echo $trainee['id']; ?>">Delete</button>
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

    <script src="../table.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        new DataTable('traineeTable', { controlsSuffix: '2' });

        // Download Excel functionality
        const downloadExcelBtn = document.getElementById('downloadExcel');
        downloadExcelBtn.addEventListener('click', function() {
            const timestamp = new Date().toISOString().replace(/[:.]/g, '-');
            window.location.href = `generate_excel.php?timestamp=${timestamp}`;
        });

        // Details dropdown functionality
        const detailsButtons = document.querySelectorAll('.details-btn');
        let activeDetailsRow = null;
        let activeButton = null;

        detailsButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.stopPropagation();
                const traineeId = this.getAttribute('data-trainee-id');
                const detailsRow = document.getElementById(`details-${traineeId}`);

                // Close previously open details
                if (activeDetailsRow && activeDetailsRow !== detailsRow) {
                    activeDetailsRow.classList.remove('active');
                    if (activeButton) {
                        activeButton.classList.remove('active');
                    }
                }

                // Toggle current details
                detailsRow.classList.toggle('active');
                this.classList.toggle('active');
                
                activeDetailsRow = detailsRow.classList.contains('active') ? detailsRow : null;
                activeButton = this.classList.contains('active') ? this : null;
            });
        });

        // Close details when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.details-btn') && !e.target.closest('.details-dropdown')) {
                if (activeDetailsRow) {
                    activeDetailsRow.classList.remove('active');
                    if (activeButton) {
                        activeButton.classList.remove('active');
                    }
                    activeDetailsRow = null;
                    activeButton = null;
                }
            }
        });

        // Delete trainee functionality
        const deleteButtons = document.querySelectorAll('.delete-btn');
        deleteButtons.forEach(button => {
            button.addEventListener('click', function() {
                const traineeId = this.getAttribute('data-id');
                if (confirm('Are you sure you want to delete this trainee?')) {
                    fetch('delete_user.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `user_id=${encodeURIComponent(traineeId)}&type=trainee`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Trainee deleted successfully!');
                            this.closest('tr').remove();
                        } else {
                            alert('Failed to delete trainee. Please try again.');
                        }
                    })
                    .catch((error) => {
                        console.error('Error:', error);
                        alert('An error occurred while deleting the trainee.');
                    });
                }
            });
        });
    });
    </script>
</body>
</html>
