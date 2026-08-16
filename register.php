<?php
session_start();

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "my_hostel";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$full_name = strtoupper($_POST['full_name']);
$department = $_POST['department'];
$faculty = $_POST['faculty'];
$matric_number = $_POST['matric_number'];
$dob = $_POST['dob'];
$gender = $_POST['gender'];
$student_type = $_POST['student_type'];
$phone_number = $_POST['phone_number'];
$address = $_POST['address'];
$email_address = $_POST['email_address'];
$guardian_name = $_POST['guardian_name'];
$guardian_phone_number = $_POST['guardian_phone_number'];
$password = password_hash($_POST['password'], PASSWORD_DEFAULT);

$sql = "SELECT * FROM users WHERE full_name = '$full_name' AND matric_number = '$matric_number'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    echo "<script>alert('User with the same name and matriculation number already exists'); window.location.href='signup.html';</script>";
} else {
    $sql = "INSERT INTO users (full_name, department, faculty, matric_number, dob, gender, student_type, phone_number, address, email_address, guardian_name, guardian_phone_number, password)
    VALUES ('$full_name', '$department', '$faculty', '$matric_number', '$dob', '$gender', '$student_type', '$phone_number', '$address', '$email_address', '$guardian_name', '$guardian_phone_number', '$password')";

    if ($conn->query($sql) === TRUE) {
        $_SESSION['full_name'] = $full_name; // Start session and set full_name
        echo "<script>alert('Sign up successful'); window.location.href='profile.php';</script>";
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
}

$conn->close();
?>
