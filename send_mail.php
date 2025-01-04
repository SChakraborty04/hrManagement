<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // Make sure you have PHPMailer installed via Composer

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $name = $_POST['name'] ?? '';
    $accessCode = $_POST['access_code'] ?? '';

    if (empty($email) || empty($name) || empty($accessCode)) {
        echo json_encode(['success' => false, 'message' => 'Missing required information']);
        exit;
    }

    $mail = new PHPMailer(true);

    try {
        //Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.freesmtpservers.com'; // Replace with your SMTP server
        $mail->SMTPAuth   = true;
        $mail->Username   = 'your_username'; // Replace with your SMTP username
        $mail->Password   = 'your_password'; // Replace with your SMTP password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 25;

        //Recipients
        $mail->setFrom('from@example.com', 'HR Management System');
        $mail->addAddress($email, $name);

        //Content
        $mail->isHTML(true);
        $mail->Subject = 'Your Access Code for HR Management System';
        $mail->Body    = "
            <html>
            <body>
                <h2>Welcome to HR Management System</h2>
                <p>Dear {$name},</p>
                <p>Your access code for the HR Management System is: <strong>{$accessCode}</strong></p>
                <p>Please use this code to complete your registration.</p>
                <p>If you have any questions, please don't hesitate to contact us.</p>
                <p>Best regards,<br>HR Team</p>
            </body>
            </html>
        ";

        $mail->send();
        echo json_encode(['success' => true, 'message' => 'Email sent successfully']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => "Message could not be sent. Mailer Error: {$mail->ErrorInfo}"]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

