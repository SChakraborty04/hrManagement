<?php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'trainee') {
    header("Location: ../login.php");
    exit();
}

$trainee_id = $_SESSION['user_id'];

// Fetch trainee information
$stmt = $pdo->prepare("SELECT * FROM trainees WHERE user_id = ?");
$stmt->execute([$trainee_id]);
$trainee = $stmt->fetch();

// Check if registration is already completed
if ($trainee['registration_completed']) {
    $registration_completed = true;
    $success_message = "Your registration form has already been submitted.";
} else {
    $registration_completed = false;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$registration_completed) {
    try {
        $pdo->beginTransaction();

        // Update trainee information
        $stmt = $pdo->prepare("
            UPDATE trainees SET 
                dob = ?,
                age = ?,
                identification_marks = ?,
                educational_qualification = ?,
                institute_name = ?,
                branch = ?,
                semester_grades = ?,
                foreign_relatives = ?,
                previous_organizations = ?,
                drdo_experience = ?,
                aadhar_no = ?,
                present_address = ?,
                permanent_address = ?,
                registration_completed = TRUE,
                registration_date = NOW()
            WHERE user_id = ?
        ");

        $stmt->execute([
            $_POST['dob'],
            $_POST['age'],
            $_POST['identification_marks'],
            $_POST['educational_qualification'],
            $_POST['institute_name'],
            $_POST['branch'],
            $_POST['semester_grades'],
            $_POST['foreign_relatives'],
            $_POST['previous_organizations'],
            $_POST['drdo_experience'],
            $_POST['aadhar_no'],
            $_POST['present_address'],
            $_POST['permanent_address'],
            $trainee_id
        ]);

        $pdo->commit();
        header("Location: trainee_dashboard.php?registration=success");
        exit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = "An error occurred. Please try again.";
        error_log($e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Form</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .registration-form {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .form-group input[type="text"],
        .form-group input[type="date"],
        .form-group input[type="number"],
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        .form-group textarea {
            height: 100px;
            resize: vertical;
        }

        .photo-upload {
            border: 2px dashed #ddd;
            padding: 20px;
            text-align: center;
            margin-bottom: 20px;
        }

        .required {
            color: red;
            margin-left: 3px;
        }

        .submit-btn {
            background-color: #3498db;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }

        .submit-btn:hover {
            background-color: #2980b9;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 4px;
        }
        .alert-info {
            color: #31708f;
            background-color: #d9edf7;
            border-color: #bce8f1;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <h2>Trainee Dashboard</h2>
            <nav>
                <ul>
                    <li><a href="dashboard.php">Overview</a></li>
                    <li><a href="registration_form.php" class="active">Registration Form</a></li>
                    <li><a href="messages.php">Chat with Faculty</a></li>
                </ul>
            </nav>
            <a href="../logout.php" class="logout">Logout</a>
        </aside>

        <main class="main-content">
            <h1>Training/Internship Registration Form</h1>

            <?php if (isset($error)): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" class="registration-form" enctype="multipart/form-data" <?php echo $registration_completed ? 'disabled' : ''; ?>>
                <?php if ($registration_completed): ?>
                    <div class="alert alert-info"><?php echo $success_message; ?></div>
                <?php endif; ?>
                <div class="form-group">
                    <label>Name<span class="required">*</span></label>
                    <input type="text" value="<?php echo htmlspecialchars($trainee['name']); ?>" readonly>
                </div>

                <div class="form-group">
                    <label>Date of Birth<span class="required">*</span></label>
                    <input type="date" name="dob" required value="<?php echo $trainee['dob'] ?? ''; ?>">
                </div>

                <div class="form-group">
                    <label>Age<span class="required">*</span></label>
                    <input type="number" name="age" required value="<?php echo $trainee['age'] ?? ''; ?>">
                </div>

                <div class="form-group">
                    <label>Present Address<span class="required">*</span></label>
                    <textarea name="present_address" required><?php echo $trainee['present_address'] ?? ''; ?></textarea>
                </div>

                <div class="form-group">
                    <label>Permanent Address<span class="required">*</span></label>
                    <textarea name="permanent_address" required><?php echo $trainee['permanent_address'] ?? ''; ?></textarea>
                </div>

                <div class="form-group">
                    <label>Identification Marks</label>
                    <textarea name="identification_marks"><?php echo $trainee['identification_marks'] ?? ''; ?></textarea>
                </div>

                <div class="form-group">
                    <label>Educational Qualification<span class="required">*</span></label>
                    <input type="text" name="educational_qualification" required value="<?php echo $trainee['educational_qualification'] ?? ''; ?>">
                </div>

                <div class="form-group">
                    <label>Institute Name<span class="required">*</span></label>
                    <input type="text" name="institute_name" required value="<?php echo $trainee['institute_name'] ?? ''; ?>">
                </div>

                <div class="form-group">
                    <label>Branch<span class="required">*</span></label>
                    <input type="text" name="branch" required value="<?php echo $trainee['branch'] ?? ''; ?>">
                </div>

                <div class="form-group">
                    <label>Semester wise Grades/Marks</label>
                    <textarea name="semester_grades"><?php echo $trainee['semester_grades'] ?? ''; ?></textarea>
                </div>

                <div class="form-group">
                    <label>Details of family members working in foreign organizations/Embassies</label>
                    <textarea name="foreign_relatives"><?php echo $trainee['foreign_relatives'] ?? ''; ?></textarea>
                </div>

                <div class="form-group">
                    <label>Previous Organizations (India/Abroad)</label>
                    <textarea name="previous_organizations"><?php echo $trainee['previous_organizations'] ?? ''; ?></textarea>
                </div>

                <div class="form-group">
                    <label>DRDO Labs Experience</label>
                    <textarea name="drdo_experience"><?php echo $trainee['drdo_experience'] ?? ''; ?></textarea>
                </div>

                <div class="form-group">
                    <label>Aadhar Number<span class="required">*</span></label>
                    <input type="text" name="aadhar_no" pattern="[0-9]{12}" title="Please enter valid 12-digit Aadhar number" required value="<?php echo $trainee['aadhar_no'] ?? ''; ?>">
                </div>

                <div class="photo-upload">
                    <p>Please note: You will need to submit 3 passport size photographs at the time of pass collection.</p>
                </div>

                <button type="submit" class="submit-btn" <?php echo $registration_completed ? 'disabled' : ''; ?>>
                    <?php echo $registration_completed ? 'Form Submitted' : 'Submit Registration'; ?>
                </button>
            </form>
        </main>
    </div>
</body>