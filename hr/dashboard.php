<?php
session_start();
require_once '../db_connect.php';

// Check if user is logged in and has HR role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'hr') {
    header("Location: ../login.php");
    exit();
}

// Fetch statistics
$stmt = $pdo->query("SELECT COUNT(*) FROM trainee_access WHERE used = FALSE");
$noAccessCodeUsers = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'faculty'");
$facultyCount = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'trainee'");
$traineeCount = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM trainees WHERE project_completed = TRUE");
$completedTrainees = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM trainees WHERE certificate_issued = TRUE");
$certificatesIssued = $stmt->fetchColumn();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HR Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                    <li><a href="dashboard.php" class="active">Overview</a></li>
                    <li><a href="add_trainee.php">Add New Trainee</a></li>
                    <li><a href="unsigned_users.php" >Unsigned Users</a></li>
                    <li><a href="trainee_management.php">Trainee Management</a></li>
                    <li><a href="trainee_reviews.php">Trainee Reviews</a></li>
                    <li><a href="messages.php">Messages</a></li>
                </ul>
            </nav>
            <a href="../logout.php" class="logout">Logout</a>
        </aside>
        
        <main class="main-content">
            <h1>Overview</h1>
            
            <div class="stats-container">
                <div class="stat-card">
                    <h3>Users without Access Code</h3>
                    <p class="stat-number"><?php echo $noAccessCodeUsers; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Faculty Members</h3>
                    <p class="stat-number"><?php echo $facultyCount; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Total Trainees</h3>
                    <p class="stat-number"><?php echo $traineeCount; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Completed Trainees</h3>
                    <p class="stat-number"><?php echo $completedTrainees; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Certificates Issued</h3>
                    <p class="stat-number"><?php echo $certificatesIssued; ?></p>
                </div>
            </div>

            <div class="chart-container">
                <canvas id="traineeChart"></canvas>
            </div>
        </main>
    </div>

    <script>
    
        // Chart.js code
        const ctx = document.getElementById('traineeChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Total Trainees', 'Completed Trainees', 'Certificates Issued'],
                datasets: [{
                    label: 'Trainee Statistics',
                    data: [<?php echo $traineeCount; ?>, <?php echo $completedTrainees; ?>, <?php echo $certificatesIssued; ?>],
                    backgroundColor: [
                        'rgba(75, 192, 192, 0.6)',
                        'rgba(153, 102, 255, 0.6)',
                        'rgba(255, 159, 64, 0.6)'
                    ],
                    borderColor: [
                        'rgba(75, 192, 192, 1)',
                        'rgba(153, 102, 255, 1)',
                        'rgba(255, 159, 64, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>
</html>
