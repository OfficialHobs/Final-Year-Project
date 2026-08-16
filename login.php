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
$password = $_POST['password'];

$sql = "SELECT * FROM users WHERE full_name = '$full_name'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    if (password_verify($password, $row['password'])) {
        $_SESSION['full_name'] = $full_name; // Set session variable
        echo "<script>alert('Login successful'); window.location.href='profile.php';</script>";
    } else {
        echo "<script>alert('Incorrect password'); window.location.href='login.html';</script>";
    }
} else {
    echo "<script>alert('User not found'); window.location.href='login.html';</script>";
}

$conn->close();
?>
