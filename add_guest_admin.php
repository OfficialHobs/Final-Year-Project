<?php
session_start();
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['admin_username'])) {
    header('Location: admin_login.php');
    exit();
}

// Database connection
include('db_connection.php');

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize input values
    $first_name = $conn->real_escape_string($_POST['first_name']);
    $last_name = $conn->real_escape_string($_POST['last_name']);
    $email_address = $conn->real_escape_string($_POST['email_address']);
    $phone_number = $conn->real_escape_string($_POST['phone_number']);
    $password = $_POST['password'];
    $hostel_id = $_SESSION['hostel_id']; // Use the current hostel_id from session

    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert the new guest admin into the database
    $sql = "INSERT INTO admin (username, password, first_name, last_name, email_address, phone_number, role, hostel_id) 
            VALUES ('Admin', ?, ?, ?, ?, ?, 'guest', ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssi", $hashed_password, $first_name, $last_name, $email_address, $phone_number, $hostel_id);

    if ($stmt->execute()) {
        echo "<script>alert('New guest admin added successfully!'); window.location.href='admin_page.php';</script>";
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}

$conn->close();
?>
