<?php
session_start(); // Start the session

// Include your database connection
include('db_connection.php');

// Check if the hostel_id is set in the session
if (isset($_SESSION['hostel_id'])) {
    $hostel_id = $_SESSION['hostel_id'];
    
    // Query to fetch hostel name
    $query = "SELECT hostel_name FROM hostel_account WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $hostel_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if($row = $result->fetch_assoc()) {
        echo json_encode(['hostel_name' => $row['hostel_name']]);
    } else {
        echo json_encode(['error' => 'Hostel not found']);
    }

    $stmt->close();
} else {
    echo json_encode(['error' => 'Hostel ID not found in session']);
}

$conn->close();
?>
