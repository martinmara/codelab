<?php
// Include the database connection file
require_once('db.php');

// Check if the database connection is successful
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} else {
    echo "Database connection successful!";
}

// Close the connection (optional)
$conn->close();
?>
