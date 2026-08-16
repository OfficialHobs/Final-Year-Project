<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start the session to access admin_id
session_start();

// Ensure that the admin is logged in and get their admin_id
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['error' => 'Admin not logged in']);
    exit();
}

$admin_id = $_SESSION['admin_id'];  // Get the admin ID from session
$admin_username = $_SESSION['admin_username']; // Assuming this is set during login

// Capture the raw POST data
$data = json_decode(file_get_contents('php://input'), true);

// Log received data for debugging
file_put_contents('php_debug.log', print_r($data, true), FILE_APPEND);  // Write the received data to a log file

if ($data) {
    $totalRooms = (int)$data['total_rooms'];
    $roomDivision = (int)$data['room_division'];
    $totalMaleRooms = (int)$data['total_male_rooms'];
    $totalFemaleRooms = (int)$data['total_female_rooms'];
    $roomAssignments = $data['roomAssignments'];  // This will contain user_id => room_number mapping

    // Connect to the database
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "my_hostel";

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        http_response_code(500);
        echo json_encode(['error' => 'Connection failed: ' . $conn->connect_error]);
        exit();
    }

    // Use a transaction to ensure atomicity (all or nothing for multiple changes)
    $conn->begin_transaction();

    // Update the room settings (total rooms, room division, etc.) in the hostel_account table
    $sqlUpdateHostelAccount = "UPDATE hostel_account
                               SET total_rooms = '$totalRooms',
                                   total_male_rooms = '$totalMaleRooms',
                                   total_female_rooms = '$totalFemaleRooms',
                                   room_division = '$roomDivision'
                               WHERE admin_username = '$admin_username'";

    if (!$conn->query($sqlUpdateHostelAccount)) {
        file_put_contents('php_debug.log', "Error updating hostel_account: " . $conn->error . "\n", FILE_APPEND);
        echo json_encode(['error' => 'Error updating hostel settings: ' . $conn->error]);
        $conn->rollback();
        $conn->close();
        exit();
    }

    // Step 1: Remove the current room assignments for the users in the transaction
    foreach ($roomAssignments as $userId => $newRoomNumber) {
        $sqlRemoveOldRoom = "DELETE FROM room_assignments WHERE user_id = '$userId'";
        if (!$conn->query($sqlRemoveOldRoom)) {
            file_put_contents('php_debug.log', "Error removing old room: " . $conn->error . "\n", FILE_APPEND);
            echo json_encode(['error' => 'Error removing old room assignment: ' . $conn->error]);
            $conn->rollback();
            $conn->close();
            exit();
        }
    }

    // Step 2: Check and insert new room assignments (after removal)
    foreach ($roomAssignments as $userId => $newRoomNumber) {
        // Fetch the user's gender
        $sqlGetGender = "SELECT gender FROM users WHERE id = '$userId'";
        $result = $conn->query($sqlGetGender);
        $userGender = $result->fetch_assoc()['gender'];

        // Check how many users are already in the new room
        $sqlCheckRoom = "SELECT COUNT(*) as user_count, gender FROM room_assignments 
                         WHERE room_number = '$newRoomNumber'";
        $resultCheckRoom = $conn->query($sqlCheckRoom);
        $roomData = $resultCheckRoom->fetch_assoc();
        $userCount = $roomData['user_count'];
        $existingGender = $roomData['gender'];

        // Rule 1: Check if the room is full based on room division
        if ($userCount >= $roomDivision) {
            echo json_encode(['error' => "Room $newRoomNumber is already full (max $roomDivision users)"]);
            $conn->rollback();
            $conn->close();
            exit();
        }

        // Rule 2: Check if genders match (male and female cannot share a room)
        if ($userCount > 0 && $existingGender !== $userGender) {
            echo json_encode(['error' => "Cannot assign a $userGender to Room $newRoomNumber. The room now has a $existingGender. please move $userGender to another room. or you can move the male to another room."]);
            $conn->rollback();
            $conn->close();
            exit();
        }

        // Step 3: Insert or update room assignments in the database
        $sql = "INSERT INTO room_assignments (room_number, user_id, gender, admin_id)
                VALUES ('$newRoomNumber', '$userId', '$userGender', '$admin_id')
                ON DUPLICATE KEY UPDATE room_number='$newRoomNumber', gender='$userGender', admin_id='$admin_id'";

        // Log the query for debugging
        file_put_contents('php_debug.log', "SQL Query: $sql\n", FILE_APPEND);

        if (!$conn->query($sql)) {
            file_put_contents('php_debug.log', "Error: " . $conn->error . "\n", FILE_APPEND);
            echo json_encode(['error' => 'Error updating room assignments: ' . $conn->error]);
            $conn->rollback();
            $conn->close();
            exit();
        }
    }

    // If all changes are successful, commit the transaction
    $conn->commit();
    echo json_encode(['success' => true]);
    $conn->close();
} else {
    echo json_encode(['error' => 'Invalid request']);
}
?>
