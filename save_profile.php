<?php
session_start();
if (!isset($_SESSION['admin_username']) || !isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit();
}

$admin_id = $_SESSION['admin_id']; // Use admin_id to identify the admin
$admin_username = $_SESSION['admin_username'];

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "my_hostel";

// Create a connection
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get and sanitize form inputs
$first_name = $conn->real_escape_string($_POST['first_name']);
$last_name = $conn->real_escape_string($_POST['last_name']);
$email_address = $conn->real_escape_string($_POST['email_address']);
$phone_number = $conn->real_escape_string($_POST['phone_number']);
$profile_change_date = date("Y-m-d H:i:s");
$profile_image = "";

// Define the target directory
$target_dir = "uploads/";

// Check if the uploads directory exists, if not, create it
if (!is_dir($target_dir)) {
    if (!mkdir($target_dir, 0755, true)) {
        die("Failed to create directory: " . $target_dir);
    }
}

// Handle image upload
if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
    $profile_image = $target_dir . basename($_FILES['profile_image']['name']);
    
    // Move the uploaded file to the target directory
    if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $profile_image)) {
        // Image uploaded successfully
    } else {
        die("Error uploading image.");
    }
}

// Update profile in the database
if ($profile_image != "") {
    // If a new image is uploaded, include the image path in the update query
    $sql = "UPDATE admin SET first_name=?, last_name=?, email_address=?, phone_number=?, profile_change_date=?, profile_image=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssi", $first_name, $last_name, $email_address, $phone_number, $profile_change_date, $profile_image, $admin_id);
} else {
    // If no new image is uploaded, update only other fields
    $sql = "UPDATE admin SET first_name=?, last_name=?, email_address=?, phone_number=?, profile_change_date=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssi", $first_name, $last_name, $email_address, $phone_number, $profile_change_date, $admin_id);
}

// Execute the query
if ($stmt->execute()) {
    echo "<script>alert('Profile updated successfully'); window.location.href='admin_page.php';</script>";
} else {
    echo "Error: " . $sql . "<br>" . $conn->error;
}

// Close the statement and connection
$stmt->close();
$conn->close();
?>
