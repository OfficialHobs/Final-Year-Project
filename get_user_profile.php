<?php
if (isset($_GET['id'])) {
    $user_id = $_GET['id'];
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "my_hostel";

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Fetch all user details, including the receipt image
    $sql = "SELECT full_name, matric_number, department, faculty, gender, phone_number, address, email_address, guardian_name, guardian_phone_number, receipt_image FROM users WHERE id = $user_id";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        echo json_encode($user); // Return user data as JSON
    } else {
        echo json_encode(['error' => 'User not found']);
    }

    $conn->close();
} else {
    echo json_encode(['error' => 'Invalid ID']);
}
?>
