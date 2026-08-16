<?php
session_start();
if (!isset($_SESSION['full_name'])) {
    header('Location: login.html');
    exit();
}
$full_name = $_SESSION['full_name'];

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "my_hostel";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Prepared statement to safely fetch user data
$sql = "SELECT * FROM users WHERE full_name = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $full_name);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $user_id = $user['id'];
    $hashed_password = $user['password']; // Current password (hashed)
} else {
    echo "<script>alert('User not found'); window.location.href='login.html';</script>";
    exit();
}

// Initialize messages for JavaScript alerts
$alert_message = "";

// Handle "Change Password" form submission
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['current_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Verify current password
    if (!password_verify($current_password, $hashed_password)) {
        $alert_message = "Invalid current password.";
    } elseif ($new_password !== $confirm_password) {
        $alert_message = "New password and confirm password do not match.";
    } else {
        // Hash the new password
        $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        // Update the password in the database
        $sql_update = "UPDATE users SET password = ? WHERE id = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param('si', $new_hashed_password, $user_id);

        if ($stmt_update->execute()) {
            $alert_message = "Password changed successfully.";
        } else {
            $alert_message = "Failed to change password. Please try again.";
        }

        $stmt_update->close();
    }

    // Show JavaScript alert and prevent form resubmission
    if (!empty($alert_message)) {
        echo "<script>alert('" . addslashes($alert_message) . "'); window.location.href='profile.php';</script>";
        exit();
    }
}
// Initialize variables for user report and admin response
$user_report = null;
$response = null;

// Check if a report already exists for the user
$sql_user_report = "SELECT * FROM reports WHERE user_id = ?";
$stmt_user_report = $conn->prepare($sql_user_report);
$stmt_user_report->bind_param('i', $user_id);
$stmt_user_report->execute();
$user_report_result = $stmt_user_report->get_result();

if ($user_report_result->num_rows > 0) {
    $user_report = $user_report_result->fetch_assoc();
}

// Handle "Support (Report)" form submission
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['form_type']) && $_POST['form_type'] === 'support') {
    $problem_type = $_POST['problem_type'];
    $description = $_POST['description'];

    if (empty($problem_type) || empty($description)) {
        $_SESSION['error_message'] = "All fields are required.";
    } else {
        if ($user_report) {
            // Update existing report
            $sql_update_report = "UPDATE reports SET problem_type = ?, description = ?, created_at = NOW() WHERE user_id = ?";
            $stmt_update_report = $conn->prepare($sql_update_report);
            $stmt_update_report->bind_param('ssi', $problem_type, $description, $user_id);
            $stmt_update_report->execute();
            $_SESSION['success_message'] = "Your report has been updated successfully.";
        } else {
            // Insert new report
            $sql_insert_report = "INSERT INTO reports (user_id, problem_type, description) VALUES (?, ?, ?)";
            $stmt_insert_report = $conn->prepare($sql_insert_report);
            $stmt_insert_report->bind_param('iss', $user_id, $problem_type, $description);
            $stmt_insert_report->execute();
            $_SESSION['success_message'] = "Report submitted successfully.";
        }
    }

    // Redirect to the same page to show the message and prevent form resubmission
    header("Location: profile.php");
    exit();
}

// Fetch admin response for the user
$sql_response = "SELECT admin_response FROM reports WHERE user_id = ? ORDER BY created_at DESC LIMIT 1";
$stmt_response = $conn->prepare($sql_response);
$stmt_response->bind_param('i', $user_id);
$stmt_response->execute();
$response_result = $stmt_response->get_result();

if ($response_result->num_rows > 0) {
    $response = $response_result->fetch_assoc();
}


// Fetch hostel details from the hostel_account table
$sql_hostel = "SELECT * FROM hostel_account LIMIT 1";
$hostel_result = $conn->query($sql_hostel);
$hostel = $hostel_result->fetch_assoc();

// Fetch user's room number from room_assignments
$sql_room = "SELECT room_number FROM room_assignments WHERE user_id = ?";
$stmt_room = $conn->prepare($sql_room);
$stmt_room->bind_param('i', $user_id);
$stmt_room->execute();
$room_result = $stmt_room->get_result();

if ($room_result->num_rows > 0) {
    $room = $room_result->fetch_assoc();
    $room_number = $room['room_number'];
} else {
    $room_number = "N/A"; // In case room number isn't assigned
}

$stmt_room->close();

// Fetch roommates with the same room number
$sql_roommates = "SELECT * FROM users WHERE id IN (SELECT user_id FROM room_assignments WHERE room_number = ? AND user_id != ?)";
$stmt_roommates = $conn->prepare($sql_roommates);
$stmt_roommates->bind_param('ii', $room_number, $user_id);
$stmt_roommates->execute();
$roommates_result = $stmt_roommates->get_result();

// Fetch event data
$sql_event = "SELECT * FROM events LIMIT 1"; // Assuming only one event
$event_result = $conn->query($sql_event);
$event = $event_result->fetch_assoc();

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile</title>
    <link rel="stylesheet" href="profile.css">
    <script defer src="profile.js"></script>
</head>
<body>
    <div class="sidebar">
        <ul>
            <li id="dashboard" class="active"><span class="icon">🏠</span> Dashboard</li>
            <li id="myProfile"><span class="icon">👤</span> My Profile</li>
            <li id="roomDetails"><span class="icon">🏢</span> Room Details</li>
            <li id="changePassword"><span class="icon">🔒</span> Change Password</li>
            <li id="assignments"><span class="icon">📝</span> Assignments</li>
            <li id="support"><span class="icon">🆘</span> Support</li>
        </ul>
    </div>

    <div class="main-content">
        <div class="top-bar">
            <h2>YOUR HOSTEL INFORMATION</h2>
            <div class="user-info">
                <span id="user-name"><?php echo htmlspecialchars($full_name); ?></span>
                <div class="dropdown">
                    <span class="dropdown-arrow">▼</span>
                    <div class="dropdown-content">
                        <a href="logout.php">Logout</a>
                    </div>
                </div>
            </div>
        </div>
        <div id="dashboard-content" class="content active">
            <h2>Dashboard</h2>
            <div class="dashboard">
                <div class="card" id="viewProfile" data-target="myProfile-content">
                    <p>Personal Info</p>
                </div>
                <div class="card" id="viewRoomDetails" data-target="roomDetails-content">
                    <p>Room Info</p>
                </div>
                <div class="card" id="updatePassword" data-target="changePassword-content">
                    <p>Password</p>
                </div>
                <div class="card" id="viewAssignments" data-target="assignments-content">
                    <p>Assignments</p>
                </div>
                <div class="card" id="supportGuidance" data-target="support-content">
                    <p>Support</p>
                </div>
            </div>
        </div>
        <div id="myProfile-content" class="content">
            <h2>My Profile</h2>
            <form id="profileForm" action="update_profile.php" method="POST" enctype="multipart/form-data">
                <label for="full_name">Full Name</label>
                <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" disabled>

                <label for="department">Department</label>
                <input type="text" id="department" name="department" value="<?php echo htmlspecialchars($user['department']); ?>" disabled>

                <label for="faculty">Faculty</label>
                <input type="text" id="faculty" name="faculty" value="<?php echo htmlspecialchars($user['faculty']); ?>" disabled>

                <label for="matric_number">Matriculation Number</label>
                <input type="text" id="matric_number" name="matric_number" value="<?php echo htmlspecialchars($user['matric_number']); ?>" disabled>

                <label for="dob">Date of Birth</label>
                <input type="text" id="dob" name="dob" value="<?php echo htmlspecialchars($user['dob']); ?>" disabled>

                <label for="gender">Gender</label>
                <input type="text" id="gender" name="gender" value="<?php echo htmlspecialchars($user['gender']); ?>" disabled>

                <label for="student_type">Student Type</label>
                <select id="student_type" name="student_type">
                    <option value="100level" <?php if($user['student_type'] == '100level') echo 'selected'; ?>>100 Level</option>
                    <option value="200level" <?php if($user['student_type'] == '200level') echo 'selected'; ?>>200 Level</option>
                    <option value="300level" <?php if($user['student_type'] == '300level') echo 'selected'; ?>>300 Level</option>
                    <option value="400level" <?php if($user['student_type'] == '400level') echo 'selected'; ?>>400 Level</option>
                </select>

                <label for="phone_number">Phone Number</label>
                <input type="text" id="phone_number" name="phone_number" value="<?php echo htmlspecialchars($user['phone_number']); ?>">

                <label for="address">Address</label>
                <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($user['address']); ?>">

                <label for="email_address">Email Address</label>
                <input type="email" id="email_address" name="email_address" value="<?php echo htmlspecialchars($user['email_address']); ?>">

                <label for="guardian_name">Guardian's Name</label>
                <input type="text" id="guardian_name" name="guardian_name" value="<?php echo htmlspecialchars($user['guardian_name']); ?>">

                <label for="guardian_phone_number">Guardian's Phone Number</label>
                <input type="text" id="guardian_phone_number" name="guardian_phone_number" value="<?php echo htmlspecialchars($user['guardian_phone_number']); ?>">

                <label for="receipt_image">Upload Receipt</label>
                <input type="file" id="receipt_image" name="receipt_image" accept="image/*">
                
                <?php if (!empty($user['receipt_image'])): ?>
                    <div class="image-preview">
                        <img src="uploads/receipts/<?php echo htmlspecialchars($user['receipt_image']); ?>" alt="Receipt Image" width="250">
                        <p>Current Receipt: <?php echo htmlspecialchars($user['receipt_image']); ?></p>
                    </div>
                <?php endif; ?>
                <button type="submit">Save Changes</button>
            </form>
        </div>
        <!-- Room Details Section -->
<div id="roomDetails-content" class="content">
    <h2>Room Details</h2>

    <!-- PART ONE: Hostel Details -->
    <h3>Hostel Details</h3>
    <div class="grid">
        <p><strong>Hostel Name:</strong> <?php echo htmlspecialchars($hostel['hostel_name']); ?></p>
        <p><strong>Hostel Address:</strong> <?php echo htmlspecialchars($hostel['hostel_address']); ?></p>
        <p><strong>Hostel Email:</strong> <?php echo htmlspecialchars($hostel['hostel_email']); ?></p>
        <p><strong>Emergency Contact:</strong> <?php echo htmlspecialchars($hostel['emergency_contact']); ?></p>
        <p><strong>Total Rooms:</strong> <?php echo htmlspecialchars($hostel['total_rooms']); ?></p>
        <p><strong>Room Division:</strong> <?php echo htmlspecialchars($hostel['room_division']); ?></p>
    </div>
    <hr>

    <!-- PART TWO: Your Room -->
    <h3>Your Room</h3>
    <p><strong>Room Number:</strong> ROOM <?php echo htmlspecialchars($room_number); ?></p>
    
    <!-- Personal Details Table -->
    <h4>Personal Details</h4>
    <table>
        <tr>
            <th>Full Name</th><td><?php echo htmlspecialchars($user['full_name']); ?></td>
        </tr>
        <tr>
            <th>Matric Number</th><td><?php echo htmlspecialchars($user['matric_number']); ?></td>
        </tr>
        <tr>
            <th>Department</th><td><?php echo htmlspecialchars($user['department']); ?></td>
        </tr>
        <tr>
            <th>Phone Number</th><td><?php echo htmlspecialchars($user['phone_number']); ?></td>
        </tr>
        <tr>
            <th>Student Type</th><td><?php echo htmlspecialchars($user['student_type']); ?></td>
        </tr>
    </table>

    <!-- Roommate's Details Table -->
    <h4>Roommate's Details</h4>
    <table>
        <tr>
            <th>Full Name</th><th>Matric Number</th><th>Department</th><th>Student Type</th>
        </tr>
        <?php while ($roommate = $roommates_result->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($roommate['full_name']); ?></td>
                <td><?php echo htmlspecialchars($roommate['matric_number']); ?></td>
                <td><?php echo htmlspecialchars($roommate['department']); ?></td>
                <td><?php echo htmlspecialchars($roommate['student_type']); ?></td>
            </tr>
        <?php endwhile; ?>
    </table>
    <hr>

    <!-- PART THREE: Print Room Details -->
    <h3>Print Room Details</h3>
    <p>Do you want to print your room details? <button id="printDetailsBtn">Click here to Print</button></p>
</div>
<script>
    document.getElementById('printDetailsBtn').addEventListener('click', function() {
        const printContent = `
            <div>
                <h1><?php echo htmlspecialchars($hostel['institution_name']); ?></h1>
                <p><?php echo htmlspecialchars($hostel['hostel_address']); ?></p>
                <hr>
                <h2><?php echo htmlspecialchars($hostel['hostel_name']); ?></h2>
                <p>Address: <?php echo htmlspecialchars($hostel['hostel_address']); ?></p>
                <p>Email: <?php echo htmlspecialchars($hostel['hostel_email']); ?></p>
                <p>Emergency Contact: <?php echo htmlspecialchars($hostel['emergency_contact']); ?></p>
                <hr>
                <h3>Your Details</h3>
                <p><strong>Room Number:</strong> <?php echo htmlspecialchars($room_number); ?></p>
                <p><strong>Full Name:</strong> <?php echo htmlspecialchars($user['full_name']); ?></p>
                <p><strong>Matric Number:</strong> <?php echo htmlspecialchars($user['matric_number']); ?></p>
                <p><strong>Department:</strong> <?php echo htmlspecialchars($user['department']); ?></p>
                <p><strong>Faculty:</strong> <?php echo htmlspecialchars($user['faculty']); ?></p>
                <p><strong>Phone Number:</strong> <?php echo htmlspecialchars($user['phone_number']); ?></p>
                <p><strong>Email Address:</strong> <?php echo htmlspecialchars($user['email_address']); ?></p>
                <p><strong>Current Level:</strong> <?php echo htmlspecialchars($user['student_type']); ?></p>
            </div>
        `;

        const printWindow = window.open('', '', 'width=800,height=600');
        printWindow.document.write(printContent);
        printWindow.document.close();
        printWindow.focus();
        printWindow.print();
        printWindow.close();
    });
</script>

        <div id="changePassword-content" class="content">
            <h2>Need to change your password to a new one?</h2>

            <?php if (!empty($error_message)): ?>
                <p style="color: red;"><?php echo htmlspecialchars($error_message); ?></p>
            <?php endif; ?>

            <?php if (!empty($success_message)): ?>
                <p style="color: green;"><?php echo htmlspecialchars($success_message); ?></p>
            <?php endif; ?>

            <form id="changePasswordForm" action="" method="POST">
                <label for="current_password">Current Password</label>
                <input type="password" id="current_password" name="current_password" placeholder="Enter current password" required>

                <label for="new_password">New Password</label>
                <input type="password" id="new_password" name="new_password" placeholder="Enter new password" required>

                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm new password" required>

                <!-- Toggle Password Visibility -->
                <input type="checkbox" id="show_passwords"> Show Passwords

                <button type="submit">Save Changes</button>
            </form>
        </div>
        <div id="assignments-content" class="content">
            <h2>Event</h2>

            <!-- Placeholder for Curfew (Lights Out) -->
            <h3>Curfew (Lights Out)</h3>
            <p><strong>Curfew Time:</strong> <?php echo htmlspecialchars($event['curfew_time']); ?></p>

            <!-- Table for Days of Event -->
            <h3>Days of Event</h3>
            <table id="daysOfEventTable">
                <thead>
                    <tr>
                        <th>Day</th>
                        <th>Type</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Monday</td>
                        <td><?php echo htmlspecialchars($event['monday_activity']); ?></td>
                        <td>
                            <?php if (!empty($event['monday_activity'])): ?>
                                <?php echo htmlspecialchars($event['description']); ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td>Tuesday</td>
                        <td><?php echo htmlspecialchars($event['tuesday_activity']); ?></td>
                        <td>
                            <?php if (!empty($event['tuesday_activity'])): ?>
                                <?php echo htmlspecialchars($event['description']); ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td>Wednesday</td>
                        <td><?php echo htmlspecialchars($event['wednesday_activity']); ?></td>
                        <td>
                            <?php if (!empty($event['wednesday_activity'])): ?>
                                <?php echo htmlspecialchars($event['description']); ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td>Thursday</td>
                        <td><?php echo htmlspecialchars($event['thursday_activity']); ?></td>
                        <td>
                            <?php if (!empty($event['thursday_activity'])): ?>
                                <?php echo htmlspecialchars($event['description']); ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td>Friday</td>
                        <td><?php echo htmlspecialchars($event['friday_activity']); ?></td>
                        <td>
                            <?php if (!empty($event['friday_activity'])): ?>
                                <?php echo htmlspecialchars($event['description']); ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td>Saturday</td>
                        <td><?php echo htmlspecialchars($event['saturday_activity']); ?></td>
                        <td>
                            <?php if (!empty($event['saturday_activity'])): ?>
                                <?php echo htmlspecialchars($event['description']); ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td>Sunday</td>
                        <td><?php echo htmlspecialchars($event['sunday_activity']); ?></td>
                        <td>
                            <?php if (!empty($event['sunday_activity'])): ?>
                                <?php echo htmlspecialchars($event['description']); ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Placeholder for Visual Description of Event -->
            <h3>Visual Description of Event</h3>
            <?php if (!empty($event['visual_image'])): ?>
                <img src="uploads/<?php echo htmlspecialchars($event['visual_image']); ?>" alt="Event Visual" class="event-visual">
            <?php else: ?>
                <p>No image available</p>
            <?php endif; ?>
        </div>

        <?php
// Check if there's a success or error message in the session and display it
if (isset($_SESSION['success_message'])) {
    echo "<script>alert('" . addslashes($_SESSION['success_message']) . "');</script>";
    unset($_SESSION['success_message']); // Clear the message after displaying it
}

if (isset($_SESSION['error_message'])) {
    echo "<script>alert('" . addslashes($_SESSION['error_message']) . "');</script>";
    unset($_SESSION['error_message']); // Clear the message after displaying it
}
?>

        <div id="support-content" class="content">
    <h2>Report</h2>
    <h3>Report a problem!</h3>

    <!-- Report Form -->
    <form id="reportForm" action="" method="POST">
        <input type="hidden" name="form_type" value="support">
        
        <label for="problem_type">Problem Type</label>
        <input type="text" id="problem_type" name="problem_type" placeholder="Type of the problem" required>

        <label for="description">Description</label>
        <textarea id="description" name="description" rows="6" placeholder="Describe the issue here" required></textarea>

        <button type="submit">Submit</button>
    </form>

    <hr>

    <!-- Your Report Section -->
    <h3>Your Report</h3>
    <?php if ($user_report): ?>
        <p><strong>Problem Type:</strong> <?php echo htmlspecialchars($user_report['problem_type']); ?></p>
        <textarea id="user_report_description" rows="6" disabled><?php echo htmlspecialchars($user_report['description']); ?></textarea>
    <?php else: ?>
        <p>No report submitted yet.</p>
    <?php endif; ?>

    <hr>

    <!-- Admin Response Section -->
    <h3>Response from Admin</h3>
    <?php if ($response): ?>
        <textarea id="admin_response" rows="6" disabled><?php echo htmlspecialchars($response['admin_response']); ?></textarea>
    <?php else: ?>
        <p>No response from the admin yet.</p>
    <?php endif; ?>
</div>

    </div>
</body>
</html>

