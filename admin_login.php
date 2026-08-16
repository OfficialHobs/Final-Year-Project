<?php
session_start();

// Include the database connection
include('db_connection.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Query the database for all admins with the username "Admin"
    $sql = "SELECT id, username, password, hostel_id, role FROM admin WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    // Flag to check if a valid login happens
    $valid = false;

    // Loop through each admin record with the username "Admin"
    while ($admin = $result->fetch_assoc()) {
        // Verify the password for each admin using password_verify()
        if (password_verify($password, $admin['password'])) {
            // Store admin information in session
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['hostel_id'] = $admin['hostel_id'];
            $_SESSION['role'] = $admin['role'];  // Store the role (main or guest)

            // Redirect to admin page
            header("Location: admin_page.php");
            exit();
        }
    }

    // If no valid admin found, set error message
    $error_message = "Invalid username or password.";

    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #f5f5f5;
            margin: 0;
        }
        .login-container {
            background-color: #fff;
            padding: 30px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            border-radius: 5px;
            width: 350px;
            text-align: center;
        }
        .login-container h2 {
            margin-bottom: 20px;
        }
        .login-container form {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .login-container label {
            font-size: 14px;
            margin-bottom: 5px;
            align-self: flex-start;
        }
        .login-container input {
            margin-bottom: 15px;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
            width: 100%;
        }
        .login-container button {
            padding: 10px;
            border: none;
            border-radius: 5px;
            background-color: #007bff;
            color: #fff;
            font-size: 16px;
            cursor: pointer;
            width: 100%;
        }
        .login-container button:hover {
            background-color: #0056b3;
        }
        .login-container .error-message {
            color: red;
            font-size: 14px;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Admin Login</h2>
        <?php if (isset($error_message)) : ?>
            <div class="error-message"><?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>
        <form action="admin_login.php" method="post">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" placeholder="Enter your username" required>
            <label for="password">Password</label>
            <input type="password" id="password" name="password" placeholder="Enter your password" required>
            <input type="checkbox" id="show_passwords"> Show Passwords
            <button type="submit">Login</button>
        </form>
    </div>
    <script>
        const showPasswordsCheckbox = document.getElementById('show_passwords');
    if (showPasswordsCheckbox) {
        showPasswordsCheckbox.addEventListener('change', function() {
            const Password = document.getElementById('password');
            const passwordFields = [Password];

            passwordFields.forEach(field => {
                field.type = this.checked ? 'text' : 'password';
            });
        });
    }
    </script>
</body>
</html>
