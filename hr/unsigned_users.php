<?php
session_start();
require_once '../db_connect.php';

// Check if user is logged in and has HR role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'hr') {
    header("Location: ../login.php");
    exit();
}

// Fetch users who haven't used their access code
$stmt = $pdo->query("SELECT * FROM trainee_access WHERE used = FALSE");
$noAccessCodeUsers = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users without Access Code</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.1/css/jquery.dataTables.min.css">
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
            <h2>HR Dashboard</h2>
            <nav>
                <ul>
                    <li><a href="dashboard.php" >Overview</a></li>
                    <li><a href="add_trainee.php">Add New Trainee</a></li>
                    <li><a href="unsigned_users.php" class="active">Unsigned Users</a></li>
                    <li><a href="trainee_management.php">Trainee Management</a></li>
                    <li><a href="trainee_reviews.php">Trainee Reviews</a></li>
                    <li><a href="messages.php">Messages</a></li>
                </ul>
            </nav>
            <a href="../logout.php" class="logout">Logout</a>
        </aside>
        
        <main class="main-content">
            <h1>Users who didn't use or sign up with the access code</h1>
            
            <div class="table-wrapper">
                <!-- Search and Entries Info at the Top -->
                <div class="table-controls">
                    <div class="entries-info">
                        Show 
                        <select id="entriesPerPage1">
                            <option value="5">5</option>
                            <option value="10" selected>10</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                        entries
                    </div>
                    <div class="table-search">
                        <input type="text" id="searchInput1" placeholder="Search...">
                    </div>
                </div>

                <!-- Table -->
                <table id="accessCodeTable" class="data-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>Access Code</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($noAccessCodeUsers as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['name']); ?></td>
                                <td><?php echo htmlspecialchars($user['phone']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['access_code']); ?></td>
                                <td>
                                    <button class="send-mail-btn" data-email="<?php echo htmlspecialchars($user['email']); ?>" data-name="<?php echo htmlspecialchars($user['name']); ?>" data-access-code="<?php echo htmlspecialchars($user['access_code']); ?>">Send Mail</button>
                                    <button class="delete-btn" data-id="<?php echo htmlspecialchars($user['id']); ?>">Delete</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- Pagination and Table Info at the Bottom -->
                <div class="table-footer">
                    <div class="table-info"></div>
                    <div class="pagination">
                        <button class="page-nav" data-action="first">First</button>
                        <button class="page-nav" data-action="prev">Prev</button>
                        <div class="page-numbers"></div>
                        <button class="page-nav" data-action="next">Next</button>
                        <button class="page-nav" data-action="last">Last</button>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../table.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            new DataTable('accessCodeTable', { controlsSuffix: '1' });

            const sendMailButtons = document.querySelectorAll('.send-mail-btn');
            sendMailButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const email = this.getAttribute('data-email');
                    const name = this.getAttribute('data-name');
                    const accessCode = this.getAttribute('data-access-code');
                    
                    fetch('send_mail.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `email=${encodeURIComponent(email)}&name=${encodeURIComponent(name)}&access_code=${encodeURIComponent(accessCode)}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Email sent successfully!');
                        } else {
                            alert('Failed to send email. Please try again.');
                        }
                    })
                    .catch((error) => {
                        console.error('Error:', error);
                        alert('An error occurred while sending the email.');
                    });
                });
            });
            const deleteButtons = document.querySelectorAll('.delete-btn');
        deleteButtons.forEach(button => {
            button.addEventListener('click', function() {
                const userId = this.getAttribute('data-id');
                if (confirm('Are you sure you want to delete this user?')) {
                    fetch('delete_user.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `user_id=${encodeURIComponent(userId)}&type=no_access_code`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('User deleted successfully!');
                            this.closest('tr').remove();
                        } else {
                            alert('Failed to delete user. Please try again.');
                        }
                    })
                    .catch((error) => {
                        console.error('Error:', error);
                        alert('An error occurred while deleting the user.');
                    });
                }
            });
        });
        });
    </script>
</body>
</html>