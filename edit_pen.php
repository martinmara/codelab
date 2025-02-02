<?php
// Include WordPress functions (adjust the path as needed)
require_once('../wp-load.php');

// Include the database connection
require_once('db.php');

// Check database connection
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Get the Pen ID from the URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Invalid Pen ID.");
}

$pen_id = intval($_GET['id']);

// Fetch the pen data from the database
$sql = "SELECT * FROM codelab_pens WHERE pen_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $pen_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Pen not found.");
}

$pen = $result->fetch_assoc();

// Function to safely escape code for display
function escapeCode($code) {
    return htmlspecialchars($code, ENT_QUOTES, 'UTF-8');
}

// Check if the current user is the creator of the pen
$is_creator = false;
if (is_user_logged_in()) {
    $current_user = wp_get_current_user();
    if ($current_user->user_login === $pen['user_login']) {
        $is_creator = true;
    }
} else {
    die("You must be logged in to edit a pen.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $is_creator) {
    // Handle the form submission to update the pen's code
    $new_html = $_POST['html_code'];
    $new_css = $_POST['css_code'];
    $new_js = $_POST['js_code'];

    $update_sql = "UPDATE codelab_pens SET html_code = ?, css_code = ?, js_code = ? WHERE pen_id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("sssi", $new_html, $new_css, $new_js, $pen_id);
    $stmt->execute();

    echo "<script>alert('Pen updated successfully!'); window.location.href='pen.php?id={$pen_id}';</script>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit <?= escapeCode($pen['title']) ?> - Code Lab</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #0e0e10;
            color: #f5f5f5;
            margin: 0;
            padding: 0;
        }

        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #1a1a1d;
            padding: 15px 30px;
        }

        .navbar a {
            color: #fff;
            text-decoration: none;
            margin: 0 10px;
            font-weight: bold;
        }

        .navbar a:hover {
            color: #ffcc00;
        }

        .content {
            padding: 20px;
        }

        .pen-meta {
            background-color: #1a1a1d;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }

        .pen-meta p {
            margin: 5px 0;
        }

        textarea {
            width: 100%;
            height: 300px;
            margin-bottom: 20px;
            padding: 10px;
            background-color: #1a1a1d;
            color: #f5f5f5;
            border: 1px solid #333;
            font-family: 'Courier New', Courier, monospace;
            font-size: 1rem;
            border-radius: 5px;
        }

        .action-buttons a {
            display: inline-block;
            margin-right: 10px;
            padding: 10px 20px;
            text-decoration: none;
            background-color: #ffcc00;
            color: #000;
            border-radius: 5px;
            font-weight: bold;
        }

        .action-buttons a:hover {
            background-color: #ffb400;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <div class="navbar">
        <a href="index.php">Back to Home</a>
        <?php if (is_user_logged_in()): ?>
            <div>
                <a href="profile.php"><?= get_avatar($current_user->ID, 40) ?></a>
                <a href="<?php echo wp_logout_url(home_url('/codelab/')); ?>">Logout</a>
            </div>
        <?php else: ?>
            <a href="<?php echo wp_login_url(home_url('/codelab/')); ?>">Login</a>
        <?php endif; ?>
    </div>

    <div class="content">
        <h1>Edit Pen: <?= escapeCode($pen['title']) ?></h1>
        <div class="pen-meta">
            <p>Created by: <?= escapeCode($pen['user_login']) ?></p>
            <p>Created at: <?= escapeCode($pen['created_at']) ?></p>
        </div>

        <form method="POST" action="edit_pen.php?id=<?= $pen_id ?>">
            <div class="code-section">
                <h3>HTML Code</h3>
                <textarea name="html_code"><?= escapeCode($pen['html_code']) ?></textarea>
            </div>

            <div class="code-section">
                <h3>CSS Code</h3>
                <textarea name="css_code"><?= escapeCode($pen['css_code']) ?></textarea>
            </div>

            <div class="code-section">
                <h3>JavaScript Code</h3>
                <textarea name="js_code"><?= escapeCode($pen['js_code']) ?></textarea>
            </div>

            <div class="action-buttons">
                <button type="submit">Save Changes</button>
                <a href="pen.php?id=<?= $pen_id ?>">Cancel</a>
            </div>
        </form>
    </div>
</body>
</html>

<?php
$conn->close();
?>
