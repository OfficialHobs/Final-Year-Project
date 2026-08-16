<?php
//THIS DB_CONNECTION.PHP IS file that initializes the $conn variable with a valid connection to the database.
//it is meant to work with the admin_page.php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "my_hostel";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
