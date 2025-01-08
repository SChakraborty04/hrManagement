<?php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'faculty') {
    header("Location: ../login.php");
    exit();
}

$faculty_id = $_SESSION['user_id'];

// Check if faculty has updated their information
$stmt = $pdo->prepare("SELECT info_updated, email, phone, username FROM users WHERE id = ?");
$stmt->execute([$faculty_id]);
$faculty_info = $stmt->fetch();

if (!$faculty_info['info_updated']) {
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_info'])) {
        $new_email = $_POST['email'];
        $new_phone = $_POST['phone'];
        $new_password = $_POST['password'];
        
        if (!empty($new_password)) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET email = ?, phone = ?, password = ?, info_updated = TRUE WHERE id = ?");
            $stmt->execute([$new_email, $new_phone, $hashed_password, $faculty_id]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET email = ?, phone = ?, info_updated = TRUE WHERE id = ?");
            $stmt->execute([$new_email, $new_phone, $faculty_id]);
        }
        
        header("Location: faculty_dashboard.php");
        exit();
    }
    
    // Display form to update information
    include '../update_faculty_info.php';
    exit();
}

// Handle trainee actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['accept_trainee'])) {
        $trainee_id = $_POST['trainee_id'];
        $stmt = $pdo->prepare("UPDATE trainees SET status = 'accepted' WHERE id = ? AND faculty_id = ?");
        $stmt->execute([$trainee_id, $faculty_id]);
    } elseif (isset($_POST['reject_trainee'])) {
        $trainee_id = $_POST['trainee_id'];
        $reason = $_POST['reject_reason'];
        $forward_to = $_POST['forward_to'];
        $stmt = $pdo->prepare("UPDATE trainees SET status = 'rejected', status_reason = ?, forwarded_to = ? WHERE id = ? AND faculty_id = ?");
        $stmt->execute([$reason, $forward_to, $trainee_id, $faculty_id]);
    } elseif (isset($_POST['assign_project'])) {
        $trainee_id = $_POST['trainee_id'];
        $project = $_POST['project'];
        $stmt = $pdo->prepare("UPDATE trainees SET project = ? WHERE id = ? AND faculty_id = ?");
        $stmt->execute([$project, $trainee_id, $faculty_id]);
    } elseif (isset($_POST['complete_project'])) {
        $trainee_id = $_POST['trainee_id'];
        $stmt = $pdo->prepare("UPDATE trainees SET project_completed = TRUE WHERE id = ? AND faculty_id = ?");
        $stmt->execute([$trainee_id, $faculty_id]);
    }
}

// Fetch statistics
$stmt = $pdo->prepare("SELECT 
    COUNT(*) as total_assigned,
    SUM(CASE WHEN project IS NOT NULL AND project_completed = FALSE THEN 1 ELSE 0 END) as ongoing_projects,
    SUM(CASE WHEN project_completed = TRUE THEN 1 ELSE 0 END) as completed_projects,
    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_trainees
    FROM trainees 
    WHERE faculty_id = ?");
$stmt->execute([$faculty_id]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch assigned trainees
$stmt = $pdo->prepare("SELECT t.*, u.username, u.email, u.phone 
                       FROM trainees t 
                       JOIN users u ON t.user_id = u.id 
                       WHERE t.faculty_id = ?");
$stmt->execute([$faculty_id]);
$trainees = $stmt->fetchAll();

// Fetch all faculty members for forwarding
$stmt = $pdo->prepare("SELECT u.id, u.username FROM users u WHERE u.role = 'faculty' AND u.id != ?");
$stmt->execute([$faculty_id]);
$faculty_members = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Dashboard</title>
    <link rel="stylesheet" href="style.css">

</head>
<body>
<header>
<div class="navbar">
<img src="../logo.jpeg" id="logo" alt="drdo logo" />
<div class="brand">ठोस राज्य भौतिकी प्रयोगशाला मानव संसाधन प्रबंधन
/ Solid State Physics Laboratory Human Resource Management</div>
</div>
</header>
    <div class="dashboard-container">
        <aside class="sidebar">
            <h2>Faculty Dashboard</h2>
            <nav>
                <ul>
                    <li><a href="dashboard.php" class="active">Assigned Trainees</a></li>
                    <li><a href="review.php" >Review Trainees</a></li>
                    <li><a href="messages.php">Messages</a></li>
                </ul>
            </nav>
            <a href="../logout.php" class="logout">Logout</a>
        </aside>
        
        <main class="main-content">
            <h1>Welcome, <?php echo htmlspecialchars($faculty_info['username']); ?></h1>
             <div class="stats-container">
                <div class="stat-card">
                    <h3>Assigned Trainees</h3>
                    <p class="stat-number"><?php echo $stats['total_assigned']; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Ongoing Projects</h3>
                    <p class="stat-number"><?php echo $stats['ongoing_projects']; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Completed Projects</h3>
                    <p class="stat-number"><?php echo $stats['completed_projects']; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Rejected Trainees</h3>
                    <p class="stat-number"><?php echo $stats['rejected_trainees']; ?></p>
                </div>
            </div>
            <h2>Assigned Trainees</h2>
            <div class="table-wrapper">
                <div class="table-controls">
                    <div class="entries-info">
                        Show 
                        <select id="entriesPerPage3">
                            <option value="5">5</option>
                            <option value="10" selected>10</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                        entries
                    </div>
                    <div class="table-search">
                        <input type="text" id="searchInput3" placeholder="Search...">
                    </div>
                </div>
                <table id="facultyTable" class="data-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Project</th>
                            <th>Project Completed</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($trainees as $trainee): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($trainee['name']); ?></td>
                            <td><?php echo htmlspecialchars($trainee['phone']); ?></td>
                            <td><?php echo htmlspecialchars($trainee['email']); ?></td>
                            <td><?php echo htmlspecialchars($trainee['status']); ?></td>
                            <td><?php echo htmlspecialchars($trainee['project']); ?></td>
                            <td><?php echo $trainee['project_completed'] ? 'Yes' : 'No'; ?></td>
                            <td>
                                <?php if ($trainee['status'] == 'pending'): ?>
                                    <button class="btn btn-primary accept-btn" data-trainee-id="<?php echo $trainee['id']; ?>">Accept</button>
                                    <button class="btn btn-danger reject-btn" data-trainee-id="<?php echo $trainee['id']; ?>">Reject</button>
                                <?php elseif ($trainee['status'] == 'accepted'): ?>
                                    <?php if (!$trainee['project']): ?>
                                        <button class="btn btn-secondary assign-project-btn" data-trainee-id="<?php echo $trainee['id']; ?>">Assign Project</button>
                                    <?php elseif (!$trainee['project_completed']): ?>
                                        <button class="btn btn-success complete-project-btn" data-trainee-id="<?php echo $trainee['id']; ?>">Mark as Completed</button>
                                    <?php endif; ?>
                                <?php elseif ($trainee['status'] == 'rejected'): ?>
                                    <span class="badge badge-danger">Rejected</span>
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


    <!-- Reject Trainee Modal -->
    <div id="rejectModal" class="modal">
        <div class="modal-content">
            <h2>Reject Trainee</h2>
            <form id="rejectForm">
                <input type="hidden" id="rejectTraineeId" name="trainee_id">
                <div class="form-group">
                    <label for="rejectReason">Reason for Rejection:</label>
                    <textarea id="rejectReason" name="reject_reason" required></textarea>
                </div>
                <div class="form-group">
                    <label for="forwardTo">Suggest Faculty to Forward:</label>
                    <select id="forwardTo" name="forward_to">
                        <option value="">Select Faculty</option>
                        <?php foreach ($faculty_members as $faculty): ?>
                            <option value="<?php echo $faculty['id']; ?>"><?php echo htmlspecialchars($faculty['username']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-danger">Confirm Rejection</button>
                <button type="button" class="btn btn-secondary close-modal">Cancel</button>
            </form>
        </div>
    </div>

    <!-- Assign Project Modal -->
    <div id="assignProjectModal" class="modal">
        <div class="modal-content">
            <h2>Assign Project</h2>
            <form id="assignProjectForm">
                <input type="hidden" id="assignProjectTraineeId" name="trainee_id">
                <div class="form-group">
                    <label for="projectDescription">Project Description:</label>
                    <textarea id="projectDescription" name="project" required></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Assign Project</button>
                <button type="button" class="btn btn-secondary close-modal">Cancel</button>
            </form>
        </div>
    </div>
    <script src="../table.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            new DataTable('facultyTable', { controlsSuffix: '3' });
            const rejectModal = document.getElementById('rejectModal');
            const assignProjectModal = document.getElementById('assignProjectModal');
            const rejectForm = document.getElementById('rejectForm');
            const assignProjectForm = document.getElementById('assignProjectForm');

            // Open reject modal
            document.querySelectorAll('.reject-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const traineeId = this.getAttribute('data-trainee-id');
                    document.getElementById('rejectTraineeId').value = traineeId;
                    rejectModal.style.display = 'block';
                });
            });

            // Open assign project modal
            document.querySelectorAll('.assign-project-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const traineeId = this.getAttribute('data-trainee-id');
                    document.getElementById('assignProjectTraineeId').value = traineeId;
                    assignProjectModal.style.display = 'block';
                });
            });

            // Close modals
            document.querySelectorAll('.close-modal').forEach(button => {
                button.addEventListener('click', function() {
                    rejectModal.style.display = 'none';
                    assignProjectModal.style.display = 'none';
                });
            });

            // Handle reject form submission
            rejectForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                formData.append('action', 'reject_trainee');
                fetch('faculty_actions.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Trainee rejected successfully');
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred. Please try again.');
                });
                rejectModal.style.display = 'none';
            });

            // Handle assign project form submission
            assignProjectForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                formData.append('action', 'assign_project');
                fetch('faculty_actions.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Project assigned successfully');
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred. Please try again.');
                });
                assignProjectModal.style.display = 'none';
            });

            // Handle accept trainee
            document.querySelectorAll('.accept-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const traineeId = this.getAttribute('data-trainee-id');
                    if (confirm('Are you sure you want to accept this trainee?')) {
                        const formData = new FormData();
                        formData.append('action', 'accept_trainee');
                        formData.append('trainee_id', traineeId);
                        fetch('faculty_actions.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                alert('Trainee accepted successfully');
                                location.reload();
                            } else {
                                alert('Error: ' + data.message);
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('An error occurred. Please try again.');
                        });
                    }
                });
            });

            // Handle complete project
            document.querySelectorAll('.complete-project-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const traineeId = this.getAttribute('data-trainee-id');
                    if (confirm('Are you sure you want to mark this project as completed?')) {
                        const formData = new FormData();
                        formData.append('action', 'complete_project');
                        formData.append('trainee_id', traineeId);
                        fetch('faculty_actions.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                alert('Project marked as completed successfully');
                                location.reload();
                            } else {
                                alert('Error: ' + data.message);
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('An error occurred. Please try again.');
                        });
                    }
                });
            });
        });
    </script>
</body>
</html>



