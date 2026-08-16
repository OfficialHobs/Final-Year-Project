<?php
session_start();
include('db_connection.php');

// Ensure hostel_id is set in the session
if (!isset($_SESSION['hostel_id'])) {
    die("Hostel ID is not set in session.");
}
$hostel_id = $_SESSION['hostel_id'];

// Retrieve POST data
$curfew_time = $_POST['curfew_time'] ?? '';
$monday = $_POST['monday'] ?? '';
$tuesday = $_POST['tuesday'] ?? '';
$wednesday = $_POST['wednesday'] ?? '';
$thursday = $_POST['thursday'] ?? '';
$friday = $_POST['friday'] ?? '';
$saturday = $_POST['saturday'] ?? '';
$sunday = $_POST['sunday'] ?? '';
$description = $_POST['description'] ?? '';

// Handle file upload
$image_url = '';
if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
    $uploadDir = 'uploads/';  // Ensure this directory exists and has write permissions
    $uploadFile = $uploadDir . basename($_FILES['image']['name']);
    
    // Validate image file type (optional)
    $imageFileType = strtolower(pathinfo($uploadFile, PATHINFO_EXTENSION));
    $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];

    if (!in_array($imageFileType, $allowedTypes)) {
        echo '<script>alert("Invalid image format. Please upload JPG, JPEG, PNG, or GIF files."); window.history.back();</script>';
        exit();
    }

    // Move uploaded file to destination
    if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadFile)) {
        $image_url = basename($_FILES['image']['name']);
    } else {
        echo '<script>alert("Failed to upload image. Please try again."); window.history.back();</script>';
        exit();
    }
}

// Check if an event already exists for this hostel
$sqlCheck = "SELECT id FROM events WHERE hostel_id = ?";
$stmtCheck = $conn->prepare($sqlCheck);
$stmtCheck->bind_param("i", $hostel_id);
$stmtCheck->execute();
$resultCheck = $stmtCheck->get_result();

if ($resultCheck->num_rows > 0) {
    // Update existing event
    $sqlUpdate = "UPDATE events SET curfew_time = ?, monday_activity = ?, tuesday_activity = ?, wednesday_activity = ?, thursday_activity = ?, friday_activity = ?, saturday_activity = ?, sunday_activity = ?, description = ?, visual_image = ? WHERE hostel_id = ?";
    $stmt = $conn->prepare($sqlUpdate);
    $stmt->bind_param("ssssssssssi", $curfew_time, $monday, $tuesday, $wednesday, $thursday, $friday, $saturday, $sunday, $description, $image_url, $hostel_id);
} else {
    // Insert new event
    $sqlInsert = "INSERT INTO events (hostel_id, curfew_time, monday_activity, tuesday_activity, wednesday_activity, thursday_activity, friday_activity, saturday_activity, sunday_activity, description, visual_image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sqlInsert);
    $stmt->bind_param("issssssssss", $hostel_id, $curfew_time, $monday, $tuesday, $wednesday, $thursday, $friday, $saturday, $sunday, $description, $image_url);
}

// Execute the query
if ($stmt->execute()) {
    echo '<script>alert("Event saved successfully!"); window.location.href = "admin_page.php";</script>';
} else {
    echo '<script>alert("Error saving changes: ' . $stmt->error . '"); window.history.back();</script>';
}

$stmt->close();
$conn->close();
?>
