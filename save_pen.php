<?php
session_start();
require_once('db.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $title = $_POST['title'];
    $code = $_POST['code'];

    // Insert the new pen into the database
    $sql = "INSERT INTO codelab_pens (user_id, title, code) VALUES ('$user_id', '$title', '$code')";

    if ($conn->query($sql) === TRUE) {
        // Get the ID of the inserted pen (assuming the 'id' column is auto-incremented)
        $pen_id = $conn->insert_id;

        // Redirect to the page displaying the saved pen
        header("Location: pen.php?id=" . $pen_id);
        exit(); // Make sure to call exit after header to stop further execution
    } else {
        echo "Error: " . $conn->error;
    }
}
?>

<form method="POST">
    <label>Title: <input type="text" name="title" required></label><br>
    <label>Code: <textarea name="code" required></textarea></label><br>
    <button type="submit">Save Pen</button>
</form>
