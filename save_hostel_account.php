<?php
session_start();
if (!isset($_SESSION['admin_username'])) {
    header('Location: admin_login.php');
    exit();
}

$admin_username = $_SESSION['admin_username'];

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "my_hostel";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Gather form data and sanitize

$institution_name = $_POST['institution_name'];
$institution_type = $_POST['institution_type'];
$country = $_POST['country'];
$state = $_POST['state'];
$lga = $_POST['lga'];
$address = $_POST['address'];
$opening_time = $_POST['opening_time'];
$closing_time = $_POST['closing_time'];
$hostel_name = $_POST['hostel_name'];
$hostel_type = $_POST['hostel_type'];
$hostel_address = $_POST['hostel_address'];
$student_type = $_POST['student_type'];
$hostel_email = $_POST['hostel_email'];
$emergency_contact = $_POST['emergency_contact'];
$hostel_division = $_POST['hostel_division'];
$total_rooms =$_POST['total_rooms'];
$total_male_rooms =$_POST['total-male-rooms'];
$total_female_rooms =$_POST['total-female-rooms'];
// Update query to reflect changes
$sql = "UPDATE hostel_account SET 
    institution_name = '$institution_name',
    institution_type = '$institution_type',
    country = '$country',
    state = '$state',
    lga = '$lga',
    address = '$address',
    opening_time = '$opening_time',
    closing_time = '$closing_time',
    hostel_name = '$hostel_name',
    hostel_type = '$hostel_type',
    hostel_address = '$hostel_address',
    student_type = '$student_type',
    hostel_email = '$hostel_email',
    emergency_contact = '$emergency_contact',
    hostel_division = '$hostel_division',
    total_rooms = '$total_rooms'
    WHERE admin_username = '$admin_username'";

if ($conn->query($sql) === TRUE) {
    echo "<script>alert('Hostel account updated successfully'); window.location.href='admin_page.php';</script>";
} else {
    echo "Error updating record: " . $conn->error;
}

$conn->close();
?>
