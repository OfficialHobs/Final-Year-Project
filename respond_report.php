<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit();
}

include('db_connection.php');

$report_id = $_POST['report_id'];
$admin_response = $conn->real_escape_string($_POST['admin_response']);

$sql = "UPDATE reports SET admin_response = ? WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $admin_response, $report_id);

if ($stmt->execute()) {
    echo "<script>alert('Response sent successfully!'); window.location.href='admin_page.php';</script>";
} else {
    echo "Error: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
