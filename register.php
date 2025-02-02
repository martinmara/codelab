<!-- register.php - CodeLab Registration Page -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
</head>
<body>

<h2>Register</h2>

<form method="POST" action="register.php">
    <label for="username">Username:</label>
    <input type="text" name="username" required><br><br>

    <label for="password">Password:</label>
    <input type="password" name="password" required><br><br>

    <label for="email">Email:</label>
    <input type="email" name="email" required><br><br>

    <button type="submit" name="register">Register</button>
</form>

</body>
</html>
<?php
// Start the session
session_start();

// Include the database connection
require_once('db.php');  // Include the db.php file

if (isset($_POST['register'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $email = $_POST['email'];

    // Check if the username already exists
    $sql = "SELECT * FROM wp_users WHERE user_login = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo "Username already exists.";
    } else {
        // Insert new user into WordPress wp_users table
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $insert_sql = "INSERT INTO wp_users (user_login, user_pass, user_email) VALUES (?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("sss", $username, $hashed_password, $email);
        $insert_stmt->execute();

        echo "Registration successful!";
    }
}
?>
