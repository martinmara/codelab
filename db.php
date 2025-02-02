<?php
// Database connection details
$servername = "localhost"; // Database host
$username = "u920174079_09kTB"; // Your database username
$password = "Martinnew2024pas?"; // Your database password
$dbname = "u920174079_MolfK"; // Your WordPress database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
