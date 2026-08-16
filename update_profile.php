<?php
session_start();
if (!isset($_SESSION['full_name'])) {
    header('Location: login.html');
    exit();
}
$full_name = $_SESSION['full_name'];

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "my_hostel";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Retrieve form data
$student_type = $_POST['student_type'];
$phone_number = $_POST['phone_number'];
$address = $_POST['address'];
$email_address = $_POST['email_address'];
$guardian_name = $_POST['guardian_name'];
$guardian_phone_number = $_POST['guardian_phone_number'];

// Handle image upload
$target_dir = "uploads/receipts/";
$receipt_image = null;

if (isset($_FILES['receipt_image']) && $_FILES['receipt_image']['error'] == 0) {
    $receipt_image = basename($_FILES["receipt_image"]["name"]);
    $target_file = $target_dir . $receipt_image;
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    // Validate file type
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
    if (in_array($imageFileType, $allowed_types)) {
        // Move the uploaded file to the target directory
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true); // Create the receipts directory if it doesn't exist
        }
        move_uploaded_file($_FILES["receipt_image"]["tmp_name"], $target_file);
    } else {
        echo "<script>alert('Only JPG, JPEG, PNG, and GIF files are allowed.'); window.location.href='profile.php';</script>";
        exit();
    }
}

// Update the user's data in the database
$sql = "UPDATE users SET 
    student_type='$student_type', 
    phone_number='$phone_number', 
    address='$address', 
    email_address='$email_address', 
    guardian_name='$guardian_name', 
    guardian_phone_number='$guardian_phone_number'";

if ($receipt_image) {
    $sql .= ", receipt_image='$receipt_image'";
}

$sql .= " WHERE full_name='$full_name'";

if ($conn->query($sql) === TRUE) {
    echo "<script>alert('Profile updated successfully'); window.location.href='profile.php';</script>";
} else {
    echo "Error: " . $sql . "<br>" . $conn->error;
}

$conn->close();
?>
