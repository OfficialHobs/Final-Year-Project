<?php
session_start();

// Check if the admin is logged in
if (!isset($_SESSION['admin_username']) || !isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit();
}

$admin_id = $_SESSION['admin_id'];  // Fetch admin ID from session
$admin_username = $_SESSION['admin_username'];

// Ensure hostel_id is set in the session
if (!isset($_SESSION['hostel_id'])) {
    die("Hostel ID is not set in session.");
}
$hostel_id = $_SESSION['hostel_id'];

// Include your database connection
include('db_connection.php');

// Fetch hostel name from the hostel_account table using hostel_id from session
$hostelQuery = "SELECT hostel_name FROM hostel_account WHERE id = ?";
$stmt = $conn->prepare($hostelQuery);
$stmt->bind_param("i", $hostel_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $hostel_name = $result->fetch_assoc()['hostel_name'];
} else {
    $hostel_name = "Hostel name not found";
}

$stmt->close();

// Fetch user profile data from the database using admin_id
$sql = "SELECT first_name, last_name, email_address, phone_number, profile_change_date, profile_image 
        FROM admin WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $admin_id);  // Bind the admin_id to differentiate admins
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $profile = $result->fetch_assoc();
    $full_name = $profile['first_name'] . ' ' . $profile['last_name'];
    $profile_image = $profile['profile_image'] ? $profile['profile_image'] : 'path/to/default-placeholder.jpg';
} else {
    // Set default values if profile information is not found
    $full_name = "Admin";
    $profile_image = 'path/to/default-placeholder.jpg';
    $profile = [
        'first_name' => '',
        'last_name' => '',
        'email_address' => '',
        'phone_number' => '',
        'profile_change_date' => ''
    ];
}

$stmt->close();

// Safely access profile data
$email_address = $profile['email_address'] ?? '';
$phone_number = $profile['phone_number'] ?? '';
$profile_change_date = $profile['profile_change_date'] ?? '';

// Fetch the main admin (admin with role = 'main')
$mainAdminQuery = "SELECT first_name, last_name, phone_number FROM admin WHERE role = 'main'";
$mainAdminResult = $conn->query($mainAdminQuery);

if ($mainAdminResult->num_rows > 0) {
    $main_admin = $mainAdminResult->fetch_assoc(); // Fetch the main admin's details
} else {
    $main_admin = null; // No main admin found
}

// Fetch guest admins (admins with role = 'guest')
$guestAdminQuery = "SELECT id, first_name, last_name, email_address, phone_number FROM admin WHERE role = 'guest'";
$guestAdminResult = $conn->query($guestAdminQuery);

if ($guestAdminResult->num_rows > 0) {
    $guest_admins = [];
    while ($row = $guestAdminResult->fetch_assoc()) {
        $guest_admins[] = $row; // Populate the guest admins array
    }
} else {
    $guest_admins = []; // No guest admins found
}

// Fetch hostel account data for the logged-in admin
$sql = "SELECT * FROM hostel_account WHERE admin_username = '$admin_username'";

$result = $conn->query($sql);
if ($result->num_rows > 0) {
    $hostel_account = $result->fetch_assoc();
} else {
     // Initialize default values if no data found
$hostel_account = [
    'institution_name' => '',
    'institution_type' => '',
    'country' => '',
    'state' => '',
    'lga' => '',
    'address' => '',
    'opening_time' => '',
    'closing_time' => '',
    'hostel_name' => '',
    'hostel_type' => '',
    'hostel_address' => '',
    'student_type' => '',
    'hostel_email' => '',
    'emergency_contact' => '',
    'hostel_division' => '',
    'total_rooms' => '',
    'total_registered_students' => 0,
    'total_male_students' => 0,
    'total_female_students' => 0,
];
}

// Dynamically calculate total registered students, male students, and female students from the `users` table

// Get total registered students
$sql_total_students = "SELECT COUNT(*) AS total_students FROM users";
$result_total_students = $conn->query($sql_total_students);
$total_registered_students = $result_total_students->num_rows > 0 ? $result_total_students->fetch_assoc()['total_students'] : 0;

// Get total male students
$sql_male_students = "SELECT COUNT(*) AS total_male_students FROM users WHERE gender = 'male'";
$result_male_students = $conn->query($sql_male_students);
$total_male_students = $result_male_students->num_rows > 0 ? $result_male_students->fetch_assoc()['total_male_students'] : 0;

// Get total female students
$sql_female_students = "SELECT COUNT(*) AS total_female_students FROM users WHERE gender = 'female'";
$result_female_students = $conn->query($sql_female_students);
$total_female_students = $result_female_students->num_rows > 0 ? $result_female_students->fetch_assoc()['total_female_students'] : 0;

// Get hostel account details
$sql = "SELECT * FROM hostel_account WHERE admin_username = '$admin_username'";
$result = $conn->query($sql);
$hostel_account = $result->fetch_assoc();

// Fetch registered students
$sql_students = "SELECT * FROM users";
$result_students = $conn->query($sql_students);

// Count male and female users
$sql_total_students = "SELECT COUNT(*) AS total_students FROM users";
$total_students = $conn->query($sql_total_students)->fetch_assoc()['total_students'];

$sql_male_students = "SELECT COUNT(*) AS total_male_students FROM users WHERE gender = 'male'";
$total_male_students = $conn->query($sql_male_students)->fetch_assoc()['total_male_students'];

$sql_female_students = "SELECT COUNT(*) AS total_female_students FROM users WHERE gender = 'female'";
$total_female_students = $conn->query($sql_female_students)->fetch_assoc()['total_female_students'];

// Assuming $admin_username is already set in the session

// Fetch room settings from the database
$sql = "SELECT total_male_rooms, total_female_rooms, total_rooms, room_division FROM hostel_account WHERE admin_username = '$admin_username'";
$result = $conn->query($sql);
$roomSettings = $result->fetch_assoc();

$totalRooms = $roomSettings['total_rooms'];
$totalMaleRooms = $roomSettings['total_male_rooms'];
$totalFemaleRooms = $roomSettings['total_female_rooms'];
$roomDivision = $roomSettings['room_division']; // How many users per room

// Fetch room assignments from the database
$sql = "SELECT user_id, room_number FROM room_assignments";
$result = $conn->query($sql);

$roomAssignments = [];
while ($row = $result->fetch_assoc()) {
    $roomAssignments[$row['user_id']] = $row['room_number'];
}

// Count the number of rooms assigned to male and female users
$sqlMaleAssigned = "SELECT COUNT(DISTINCT room_number) AS male_assigned_rooms FROM room_assignments WHERE gender = 'male'";
$sqlFemaleAssigned = "SELECT COUNT(DISTINCT room_number) AS female_assigned_rooms FROM room_assignments WHERE gender = 'female'";

$resultMaleAssigned = $conn->query($sqlMaleAssigned);
$maleAssignedRooms = $resultMaleAssigned->fetch_assoc()['male_assigned_rooms'];

$resultFemaleAssigned = $conn->query($sqlFemaleAssigned);
$femaleAssignedRooms = $resultFemaleAssigned->fetch_assoc()['female_assigned_rooms'];

// Calculate vacant rooms based on room division
$vacantMaleRooms = $totalMaleRooms - $maleAssignedRooms;
$vacantFemaleRooms = $totalFemaleRooms - $femaleAssignedRooms;
$vacantRooms = $totalRooms - ($maleAssignedRooms + $femaleAssignedRooms);

// Fetch the latest event data from the database
$hostel_id = $_SESSION['hostel_id'];
$sqlFetchEvent = "SELECT curfew_time, monday_activity, tuesday_activity, wednesday_activity, thursday_activity, friday_activity, saturday_activity, sunday_activity, description, visual_image FROM events WHERE hostel_id = ?";
$stmt = $conn->prepare($sqlFetchEvent);
$stmt->bind_param("i", $hostel_id);
$stmt->execute();
$result = $stmt->get_result();

$eventData = $result->fetch_assoc();
$curfew_time = $eventData['curfew_time'] ?? '';
$monday = $eventData['monday_activity'] ?? 'NONE';
$tuesday = $eventData['tuesday_activity'] ?? 'NONE';
$wednesday = $eventData['wednesday_activity'] ?? 'NONE';
$thursday = $eventData['thursday_activity'] ?? 'NONE';
$friday = $eventData['friday_activity'] ?? 'NONE';
$saturday = $eventData['saturday_activity'] ?? 'NONE';
$sunday = $eventData['sunday_activity'] ?? 'NONE';
$description = $eventData['description'] ?? '';
$image_url = $eventData['visual_image'] ?? '';

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrator’s Environment</title>
    <link rel="stylesheet" href="admin_page.css">
    <script defer src="admin_page.js"></script>
</head>
<body>
    <div class="sidebar">
        <ul>
            <li id="dashboard" class="active"><span class="icon">🏠</span> Dashboard</li>
            <li id="profile"><span class="icon">👤</span> Profile</li>
            <li id="hostelAccount"><span class="icon">🏢</span> Hostel Account</li>
            <li id="roomSettings"><span class="icon">🔧</span> Room Settings</li>
            <li id="events"><span class="icon">📅</span> Events</li>
            <li id="userManagement"><span class="icon">👥</span> User Management</li>
            <li id="gallery"><span class="icon">🖼️</span> Gallery</li>
            <li id="support"><span class="icon">🆘</span> Support</li>
        </ul>
    </div>

    <div class="main-content">
        <div class="top-bar">
            <h2>Administrator’s Environment</h2>
            <div class="user-info">
                <!-- Profile Image and Full Name Display -->
                <img src="<?php echo htmlspecialchars($profile_image); ?>" alt="Profile Image" id="small-profile-image">
                <span id="user-name"><?php echo htmlspecialchars($full_name); ?></span>
                <div class="dropdown">
                    <span class="dropdown-arrow">▼</span>
                    <div class="dropdown-content">
                        <a href="admin_logout.php">Logout</a>
                    </div>
                </div>
            </div>
        </div>
        <div id="dashboard-content" class="content active">
            <h2>Dashboard</h2>
            <div class="dashboard">
                <div class="card" id="viewProfile" data-target="profile-content">
                    <p>Profile</p>
                </div>
                <div class="card" id="viewHostelAccount" data-target="hostelAccount-content">
                    <p>Hostel Account</p>
                </div>
                <div class="card" id="viewRoomSettings" data-target="roomSettings-content">
                    <p>Room Settings</p>
                </div>
                <div class="card" id="viewEvents" data-target="events-content">
                    <p>Events</p>
                </div>
                <div class="card" id="viewUserManagement" data-target="userManagement-content">
                    <p>User Management</p>
                </div>
                <div class="card" id="viewGallery" data-target="gallery-content">
                    <p>Gallery</p>
                </div>
                <div class="card" id="viewSupport" data-target="support-content">
                    <p>Support</p>
                </div>
            </div>
        </div>

        <!-- Profile Section -->
        <div id="profile-content" class="content">
            <h2>Profile</h2>
            <button id="edit-profile-btn" class="edit-btn">Edit Profile</button>
            <form id="profile-form" action="save_profile.php" method="POST" enctype="multipart/form-data">
                <label for="permission">Permission</label>
                <input type="text" id="permission" name="permission" value="Admin" readonly>

                <label for="username">Username</label>
                <input type="text" id="username" name="username" value="Admin" readonly>

                <label for="first_name">First Name</label>
                <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($profile['first_name']); ?>" readonly>

                <label for="last_name">Last Name</label>
                <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($profile['last_name']); ?>" readonly>

                <label for="email_address">Email Address</label>
                <input type="email" id="email_address" name="email_address" value="<?php echo htmlspecialchars($profile['email_address']); ?>" readonly>

                <label for="phone_number">Phone Number</label>
                <input type="text" id="phone_number" name="phone_number" value="<?php echo htmlspecialchars($profile['phone_number']);?>" readonly>

                <label for="profile_change_date">Profile Change Date</label>
                <input type="text" id="profile_change_date" name="profile_change_date" value="<?php echo htmlspecialchars($profile['profile_change_date']); ?>" readonly>

                <label for="profile_image">Profile Image</label>
                <div class="image-upload">
                    <img id="image-preview" src="<?php echo htmlspecialchars($profile_image); ?>" alt="Profile Image" style="width: 150px; height: 150px; object-fit: cover; border-radius: 20%;">
                    <input type="file" id="profile_image" name="profile_image" style="display: none;">
                    <button type="button" id="upload-image-btn">Upload Image</button>
                </div>

                <button type="submit" id="save-changes-btn" style="display: none;">Save Changes</button>
            </form>
        </div>

        <!-- Hostel Account Section -->
        <div id="hostelAccount-content" class="content">
            <h2>Hostel Account</h2>
            <button id="edit-hostel-btn" class="edit-btn">Edit</button>
        <form id="hostel-account-form" action="save_hostel_account.php" method="POST">
            <!-- PART ONE -->
            <hr>
            <div id="part-one">
                <label for="institution_name">Institution Name (In Caps)</label>
                <input type="text" id="institution_name" name="institution_name" value="<?php echo htmlspecialchars($hostel_account['institution_name']); ?>" readonly>

                <label for="institution_type">Institution Type</label>
                <select id="institution_type" name="institution_type" disabled>
                    <option value="FEDERAL" <?php echo $hostel_account['institution_type'] == 'FEDERAL' ? 'selected' : ''; ?>>FEDERAL</option>
                    <option value="STATE" <?php echo $hostel_account['institution_type'] == 'STATE' ? 'selected' : ''; ?>>STATE</option>
                    <option value="PRIVATE" <?php echo $hostel_account['institution_type'] == 'PRIVATE' ? 'selected' : ''; ?>>PRIVATE</option>
                </select>

                <label for="country">Country</label>
                <select id="country" name="country" disabled>
                    <option value="NIGERIA" <?php echo $hostel_account['country'] == 'NIGERIA' ? 'selected' : ''; ?>>NIGERIA</option>
                </select>

                <label for="state">State</label>
                <input type="text" id="state" name="state" value="<?php echo htmlspecialchars($hostel_account['state']); ?>" readonly>

                <label for="lga">Local Government Area (LGA)</label>
                <input type="text" id="lga" name="lga" value="<?php echo htmlspecialchars($hostel_account['lga']); ?>" readonly>

                <label for="address">Address</label>
                <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($hostel_account['address']); ?>" readonly>

                <label for="opening_time">Opening Time</label>
                <input type="time" id="opening_time" name="opening_time" value="<?php echo htmlspecialchars($hostel_account['opening_time']); ?>" readonly>

                <label for="closing_time">Closing Time</label>
                <input type="time" id="closing_time" name="closing_time" value="<?php echo htmlspecialchars($hostel_account['closing_time']); ?>" readonly>
            </div>

            <!-- PART TWO -->
            <hr>
            <div id="part-two">
                <label for="hostel_name">Hostel Name (In caps)</label>
                <input type="text" id="hostel_name" name="hostel_name" value="<?php echo htmlspecialchars($hostel_account['hostel_name']); ?>" readonly>

                <label for="hostel_type">Hostel Type</label>
                <select id="hostel_type" name="hostel_type" disabled>
                    <option value="DORM ROOMS" <?php echo $hostel_account['hostel_type'] == 'DORM ROOMS' ? 'selected' : ''; ?>>DORM ROOMS</option>
                    <option value="PRIVATE ROOMS" <?php echo $hostel_account['hostel_type'] == 'PRIVATE ROOMS' ? 'selected' : ''; ?>>PRIVATE ROOMS</option>
                </select>

                <label for="hostel_address">Hostel Address</label>
                <input type="text" id="hostel_address" name="hostel_address" value="<?php echo htmlspecialchars($hostel_account['hostel_address']); ?>" readonly>

                <label for="student_type">Student Type</label>
                <select id="student_type" name="student_type" disabled>
                    <option value="FULL-TIME UNDERGRADUATE" <?php echo $hostel_account['student_type'] == 'FULL-TIME UNDERGRADUATE' ? 'selected' : ''; ?>>FULL-TIME UNDERGRADUATE</option>
                    <option value="UNDERGRADUATE (PART-TIME)" <?php echo $hostel_account['student_type'] == 'UNDERGRADUATE (PART-TIME)' ? 'selected' : ''; ?>>UNDERGRADUATE (PART-TIME)</option>
                    <option value="POSTGRADUATES" <?php echo $hostel_account['student_type'] == 'POSTGRADUATES' ? 'selected' : ''; ?>>POSTGRADUATES</option>
                </select>

                <label for="hostel_email">Hostel Email</label>
                <input type="email" id="hostel_email" name="hostel_email" value="<?php echo htmlspecialchars($hostel_account['hostel_email']); ?>" readonly>

                <label for="emergency_contact">Emergency Contact</label>
                <input type="number" id="emergency_contact" name="emergency_contact" value="<?php echo htmlspecialchars($hostel_account['emergency_contact']); ?>" readonly>

            </div>

            <!-- PART THREE -->
            <hr>
            <div id="part-three">
                <label for="total_rooms">Hostel Accommodation (Total Rooms)</label>
                <input type="number" id="total_rooms" name="total_rooms" value="<?php echo htmlspecialchars($hostel_account['total_rooms']); ?>" readonly>

                <label for="hostel_division">Hostel Division</label>
                <input type="number" id="hostel_division" name="hostel_division" value="<?php echo htmlspecialchars($hostel_account['hostel_division']); ?>" readonly>

                <!-- Total Registered Students Field with Label -->
                <label for="total_registered_students">Total Registered Students</label>
                <input type="text" id="total_registered_students" name="total_registered_students" value="<?php echo $total_registered_students; ?>" readonly>

                <!-- Total Male Students Field with Label -->
                <label for="total_male_students">Total Male Students</label>
                <input type="text" id="total_male_students" name="total_male_students" value="<?php echo $total_male_students; ?>" readonly>

                <!-- Total Female Students Field with Label -->
                <label for="total_female_students">Total Female Students</label>
                <input type="text" id="total_female_students" name="total_female_students" value="<?php echo $total_female_students; ?>" readonly>

            </div>

            <button type="submit" id="save-hostel-changes-btn" style="display: none;">Save Changes</button>
        </form>
        </div>

        <!-- Room Settings Content -->
        <div id="roomSettings-content" class="content">
            <h2>Room Settings</h2>
            
            <!-- Part One: Registered Students -->
            <h3>Registered Students</h3>
            <table id="registered-students-table">
                <thead>
                    <tr>
                        <th>Full Name</th>
                        <th>Matric Number</th>
                        <th>Department</th>
                        <th>Faculty</th>
                        <th>Gender</th>
                        <th>Phone Number</th>
                        <th>Room Number</th>
                        <th>View</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result_students->fetch_assoc()): 
                        $userId = $row['id'];
                        $assignedRoom = $roomAssignments[$userId];
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['matric_number']); ?></td>
                            <td><?php echo htmlspecialchars($row['department']); ?></td>
                            <td><?php echo htmlspecialchars($row['faculty']); ?></td>
                            <td><?php echo htmlspecialchars($row['gender']); ?></td>
                            <td><?php echo htmlspecialchars($row['phone_number']); ?></td>
                            <td><?php echo htmlspecialchars($assignedRoom); ?></td>
                            <td><button class="view-profile-btn" data-id="<?php echo $row['id']; ?>">View Profile</button></td>
                            <td><button class="delete-user-btn" data-id="<?php echo $row['id']; ?>">Delete</button></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>

            <!-- View Profile Modal -->
            <div id="profile-modal" class="modal">
                <div class="modal-content">
                    <h2>Full Student Profile</h2>
                    <div id="profile-details"></div>
                    <button id="close-modal-btn">Close</button>
                </div>
            </div>

                        <!-- Receipt Image Modal -->
            <div id="receipt-modal" class="modal">
                <div class="modal-content">
                    <h3>Receipt Image</h3>

                    <!-- Spinner (hidden by default) -->
                    <div id="loading-spinner" class="spinner" style="display: none;"></div>

                    <!-- Zoom Controls -->
                    <div class="zoom-controls">
                        <button id="zoom-in-btn">Zoom In</button>
                        <button id="zoom-out-btn">Zoom Out</button>
                        <button id="reset-zoom-btn">Reset</button>
                    </div>

                    <!-- Receipt Image (hidden initially) -->
                    <img id="receipt-image" src="" alt="Receipt Image" style="max-width: 100%; max-height: 400px; display: none;">

                    <button id="close-receipt-btn">Close</button>
                </div>
            </div>

            <hr>

            <!-- Part Two: Room Designation -->
            <form id="room-settings-form" action="save_room_settings.php" method="POST">
            <h3>Room Designation</h3>
        <!-- Add this part inside the room settings section -->
        <p>Total Rooms: <input type="number" id="total-rooms" name="total_rooms" value="<?php echo htmlspecialchars($totalRooms); ?>" readonly></p>
        <p>Room Division: <input type="number" id="room-division" name="room_division" min="1" max="4" value="<?php echo htmlspecialchars($roomDivision); ?>"></p>
        <p>Total Male Rooms: <input type="number" id="total-male-rooms" name="total_male_rooms" min="0" value="<?php echo htmlspecialchars($totalMaleRooms); ?>"></p>
        <p>Total Female Rooms: <input type="number" id="total-female-rooms" name="total_female_rooms" min="0" value="<?php echo htmlspecialchars($totalFemaleRooms); ?>"></p>

            <!-- User Assignment Table -->
            <table id="room-assignment-table">
    <thead>
        <tr>
            <th>Full Name</th>
            <th>Gender</th>
            <th>Matric Number</th>
            <th>Department</th>
            <th>Assign to</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $result_students->data_seek(0); // Reset result set pointer
        while ($row = $result_students->fetch_assoc()): 
            $userId = $row['id'];
            $assignedRoom = isset($roomAssignments[$userId]) ? $roomAssignments[$userId] : '';
        ?>
        <tr>
            <td><?php echo htmlspecialchars($row['full_name']); ?></td>
            <td><?php echo htmlspecialchars($row['gender']); ?></td>
            <td><?php echo htmlspecialchars($row['matric_number']); ?></td>
            <td><?php echo htmlspecialchars($row['department']); ?></td>
            <td>
                ROOM <input type="number" class="room-number" name="room_assignment[<?php echo $userId; ?>]" value="<?php echo htmlspecialchars($assignedRoom); ?>" min="1">
            </td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>


            <button type="submit" id="save-room-changes-btn">Save Changes</button>

            <hr>

            <!-- Part Three: Reserve -->
            <h3>Reserve</h3>
            <p>Aggregate Vacant Rooms: <input type="number" id="vacant-rooms" value="<?php echo htmlspecialchars($vacantRooms); ?>" readonly></p>
            <p>Aggregate Male Rooms: <input type="number" id="aggregate-male-rooms" value="<?php echo htmlspecialchars($vacantMaleRooms); ?>" readonly></p>
            <p>Aggregate Female Rooms: <input type="number" id="aggregate-female-rooms" value="<?php echo htmlspecialchars($vacantFemaleRooms); ?>" readonly></p>
            </form>
        </div>

        <div id="events-content" class="content">
    <h2>Events</h2>
    <form id="event-form" method="POST" action="save_event.php" enctype="multipart/form-data">
        <!-- Events content here -->
        
        <!-- Edit Button -->
        <button id="edit-event-btn" class="edit-btn" type="button">Edit</button>
        
        <hr>
        
        <!-- PART ONE: Hostel Information -->
        <div id="part-one">
            <label for="hostel-name">Name of Hostel:</label>
            <input type="text" id="hostel-name" value="<?php echo htmlspecialchars($hostel_name); ?>" readonly>
            
            <label for="curfew-time">Curfew (Light-Out):</label>
            <input type="time" id="curfew-time" name="curfew_time" value="<?php echo htmlspecialchars($curfew_time); ?>" disabled>
        </div>
        
        <hr>
        
        <!-- PART TWO: Weekdays Activities -->
        <div id="part-two">
            <h3>Weekdays Activities</h3>
            
            <label for="monday">Monday:</label>
            <input type="text" id="monday" name="monday" value="<?php echo htmlspecialchars($monday); ?>" disabled>
            
            <label for="tuesday">Tuesday:</label>
            <input type="text" id="tuesday" name="tuesday" value="<?php echo htmlspecialchars($tuesday); ?>" disabled>
            
            <label for="wednesday">Wednesday:</label>
            <input type="text" id="wednesday" name="wednesday" value="<?php echo htmlspecialchars($wednesday); ?>" disabled>
            
            <label for="thursday">Thursday:</label>
            <input type="text" id="thursday" name="thursday" value="<?php echo htmlspecialchars($thursday); ?>" disabled>
            
            <label for="friday">Friday:</label>
            <input type="text" id="friday" name="friday" value="<?php echo htmlspecialchars($friday); ?>" disabled>
            
            <label for="saturday">Saturday:</label>
            <input type="text" id="saturday" name="saturday" value="<?php echo htmlspecialchars($saturday); ?>" disabled>
            
            <label for="sunday">Sunday:</label>
            <input type="text" id="sunday" name="sunday" value="<?php echo htmlspecialchars($sunday); ?>" disabled>
        </div>

        
        <hr>
        
            <!-- PART THREE: Description and Visuals -->
        <div id="part-three">
            <h3>Description</h3>
            <textarea id="description" name="description" placeholder="Drop the description of event here!" rows="5" cols="50" disabled><?php echo htmlspecialchars($description); ?></textarea>
            <button id="clear-description-btn" type="button">Clear</button>
            
            <br><br>
            
            <label for="image-upload">Visuals:</label>
            <input type="file" id="image-upload" name="image" disabled>
            <br><br>
            <?php if (!empty($image_url)): ?>
                <img id="image-preview" src="<?php echo htmlspecialchars('uploads/' . $image_url); ?>" alt="Uploaded Visuals" style="max-width: 200px; display: block;">
            <?php else: ?>
                <img id="image-preview" src="path/to/default-placeholder.jpg" alt="No image uploaded" style="max-width: 200px; display: block;">
            <?php endif; ?>
        </div>

        <button id="save-event-changes-btn" class="save-changes-btn" type="submit" style="display: none;">Save Changes</button>
    </form>
</div>

<div id="userManagement-content" class="content">
    <h2>User Management</h2>

    <!-- Part One: Main Admin -->
    <h2>Main Admin</h2>
    <hr>
    <p>Username: <?php echo isset($main_admin) ? htmlspecialchars($main_admin['first_name'] . ' ' . $main_admin['last_name']) : 'N/A'; ?></p>
    <p>Phone Number: <?php echo isset($main_admin) ? htmlspecialchars($main_admin['phone_number']) : 'N/A'; ?></p>

    <!-- Part Two: Guest Admins -->
    <h2>Guest Admins</h2>
    <hr>
    <table>
        <thead>
            <tr>
                <th>Username</th>
                <th>Phone Number</th>
                <th>Email Address</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($guest_admins)) : ?>
                <?php foreach ($guest_admins as $admin) : ?>
                    <tr>
                        <td><?php echo htmlspecialchars($admin['first_name'] . ' ' . $admin['last_name']); ?></td>
                        <td><?php echo htmlspecialchars($admin['phone_number']); ?></td>
                        <td><?php echo htmlspecialchars($admin['email_address']); ?></td>
                        <td>
                            <button onclick="confirmDelete(<?php echo $admin['id']; ?>)">Delete</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="4">No guest admins found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Part Three: Add Guest Admin -->
    <h2>Add Guest Admin</h2>
    <hr>
    <form id="add-guest-admin-form" action="add_guest_admin.php" method="post">
        <label for="first_name">First Name:</label>
        <input type="text" id="first_name" name="first_name" placeholder="First Name" required>

        <label for="last_name">Last Name:</label>
        <input type="text" id="last_name" name="last_name" placeholder="Last Name" required>

        <label for="email_address">Email Address:</label>
        <input type="email" id="email_address" name="email_address" placeholder="Email Address" required>

        <label for="phone_number">Phone Number:</label>
        <input type="text" id="phone_number" name="phone_number" placeholder="Phone Number" required>

        <label for="password">Default Password:</label>
        <input type="password" id="password" name="password" placeholder="Default Password" required>

        <button type="submit">Add New</button>
    </form>
</div>

<script>
function confirmDelete(adminId) {
    if (confirm('Are you sure you want to remove this admin?')) {
        window.location.href = 'delete_admin.php?id=' + adminId;
    }
}
</script>

        <div id="gallery-content" class="content">
            <h2>Gallery</h2>
            <!-- Gallery content here -->
        </div>
        <div id="support-content" class="content">
    <h2>Report from Students</h2>
    <hr>
    <table id="report-table">
        <thead>
            <tr>
                <th>Full Name</th>
                <th>Matric Number</th>
                <th>View Report</th>
                <th>Respond</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Database connection
            include('db_connection.php');

            // Fetch reports from the database
            $sql = "SELECT reports.id as report_id, reports.user_id, reports.problem_type, reports.description, users.full_name, users.matric_number
                    FROM reports
                    JOIN users ON reports.user_id = users.id";
            $result = $conn->query($sql);

            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row['full_name']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['matric_number']) . "</td>";
                    echo "<td><button onclick='viewReport(" . $row['report_id'] . ")'>View Report</button></td>";
                    echo "<td><button onclick='respondReport(" . $row['report_id'] . ")'>Respond</button></td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='4'>No reports found.</td></tr>";
            }

            $conn->close();
            ?>
        </tbody>
    </table>

        <!-- Report View Modal -->
<div id="viewReportModal" class="modal">
    <div class="modal-content chat-modal">
        <span class="close" onclick="closeModal('viewReportModal')">&times;</span>
        <h3>Report Details</h3>

        <div class="chat-container">

            <!-- Student Report Section (Right) -->
            <div class="chat-section student-report">
                <h4>Student Report</h4>
                <p><strong>Type:</strong> <span id="reportType"></span></p>
                <p><strong>Description:</strong> <span id="reportDescription"></span></p>
            </div>

            <!-- Admin Response Section (Left) -->
            <div class="chat-section admin-response">
                <h4>Admin Response</h4>
                <p id="adminResponseText">No response yet.</p>
            </div>
        </div>

        <button onclick="closeModal('viewReportModal')">Close</button>
    </div>
</div>


        <!-- Respond to Report Modal -->
        <div id="respondReportModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeModal('respondReportModal')">&times;</span>
                <h3>Respond to Report</h3>
                <form id="respondForm" action="respond_report.php" method="POST">
                    <input type="hidden" id="reportId" name="report_id" value="">
                    <label for="admin_response">Response:</label>
                    <textarea id="admin_response" name="admin_response" rows="15" cols="15" required></textarea>
                    <br><br>
                    <button type="submit">Send Response</button>
                </form>
            </div>
        </div>
    </div>

    </div>
</body>
</html>
