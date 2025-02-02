<?php
include('config.php');

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Get saved pens
$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM codelab_pens WHERE user_id = '$user_id' ORDER BY created_at DESC";
$result = $conn->query($sql);

$pens = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $pens[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Pens - Codelab</title>
</head>
<body>
    <h2>Your Pens</h2>
    <a href="save.php">Create New Pen</a><br><br>

    <?php if (count($pens) > 0) { ?>
        <h3>Your Saved Pens</h3>
        <ul>
            <?php foreach ($pens as $pen) { ?>
                <li>
                    <a href="view_pen.php?id=<?php echo $pen['id']; ?>">View Pen <?php echo $pen['id']; ?></a><br>
                    <button onclick="deletePen(<?php echo $pen['id']; ?>)">Delete</button>
                </li>
            <?php } ?>
        </ul>
    <?php } else { ?>
        <p>You haven't saved any pens yet.</p>
    <?php } ?>

    <script>
        function deletePen(penId) {
            if (confirm("Are you sure you want to delete this pen?")) {
                window.location.href = "delete_pen.php?id=" + penId;
            }
        }
    </script>
</body>
</html>
