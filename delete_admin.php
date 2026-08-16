<?php
session_start();

// Only proceed if admin is logged in and not trying to delete the main admin
if (isset($_GET['id']) && $_SESSION['role'] == 'main') {
    include('db_connection.php');
    
    $admin_id = $_GET['id'];
    
    // Ensure the admin being deleted is a guest admin
    $sql = "DELETE FROM admin WHERE id = ? AND role = 'guest'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo "<script>alert('Admin removed successfully'); window.location.href='admin_page.php';</script>";
    } else {
        echo "<script>alert('Failed to remove admin'); window.location.href='admin_page.php';</script>";
    }

    $stmt->close();
    $conn->close();
} else {
    echo "<script>alert('Unauthorized access'); window.location.href='admin_page.php';</script>";
}
?>
