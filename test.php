<?php
// The default password
$default_password = 'welcome';

// Hash the password
$hashed_password = password_hash($default_password, PASSWORD_DEFAULT);

echo "Default password: $default_password\n";
echo "Hashed password: $hashed_password\n";

// Simulating password verification
$input_password = 'welcome';
if (password_verify($input_password, $hashed_password)) {
    echo "Password is valid!\n";
} else {
    echo "Invalid password.\n";
}

// Simulating how this might be used when creating a new faculty account
function create_faculty_account($username, $email) {
    global $hashed_password;
    // In a real application, you would insert this into your database
    echo "Creating faculty account:\n";
    echo "Username: $username\n";
    echo "Email: $email\n";
    echo "Hashed Password: $hashed_password\n";
}

create_faculty_account('new_faculty', 'faculty@example.com');

?>
