<?php
// Include WordPress functions (adjust the path as needed)
require_once('../wp-load.php');

// Include the database connection
require_once('db.php');

// Check database connection
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Check if user is logged in
if (!is_user_logged_in()) {
    // If user is not logged in, redirect to the login page
    header('Location: ' . wp_login_url());
    exit;
}

// Get current user info
$current_user = wp_get_current_user();
$username = $current_user->user_login;

// Prepare SQL query to fetch pens created by the logged-in user using their username
$sql = "SELECT * FROM codelab_pens WHERE user_login = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username); // Corrected parameter type to 's' for string
$stmt->execute();
$result = $stmt->get_result();

if (!$result) {
    die("Error fetching pens: " . $conn->error);
}

// Function to escape code (for security)
function escapeCode($code) {
    return htmlspecialchars($code, ENT_QUOTES, 'UTF-8');
}

// Function to generate the live preview URL
function generateLivePreview($html, $css, $js) {
    // Generate a unique filename
    $tempDir = 'temp_preview/';
    if (!file_exists($tempDir)) {
        mkdir($tempDir, 0777, true);
    }

    // Create the content for the live preview
    $content = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <style>{$css}</style>
</head>
<body>
    {$html}
    <script>{$js}</script>
</body>
</html>
HTML;

    // Generate a unique file name for the live preview
    $fileName = uniqid('pen_', true) . '.html';
    file_put_contents($tempDir . $fileName, $content);

    // Return the URL to the file
    return $tempDir . $fileName;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Profile</title>
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
        .navbar .logo {
            max-height: 50px;
        }
        .profile {
            text-align: center;
            padding: 50px 20px;
        }
        .profile h1 {
            font-size: 2.5rem;
        }
        .profile .avatar {
            border-radius: 50%;
            margin: 20px 0;
            border: 2px solid #ffcc00;
        }
        .pens-list {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 20px;
            padding: 20px;
        }
        .pen-card {
            background-color: #1a1a1d;
            border: 1px solid #333;
            border-radius: 8px;
            width: 100%;
            max-width: 600px;
            margin: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.5);
            transition: transform 0.3s ease-in-out;
            overflow: hidden;
            padding: 15px; /* Add padding for better spacing */
        }

        .pen-card:hover {
            transform: scale(1.05);
        }

        .pen-card h3 {
            background-color: #2b2b2e;
            margin: 0;
            padding: 15px;
            font-size: 1.2rem;
            text-align: center;
            color: #fff;
        }

        .pen-card .pen-meta {
            display: flex;
            justify-content: space-between; /* Distribute content equally */
            align-items: center;
            font-size: 0.9rem;
            margin: 10px 0; /* Add space between title and metadata */
            color: #bbb; /* Lighter color for metadata */
        }

        .pen-card .pen-meta h4, .pen-card .pen-meta h5 {
            margin: 0;
            font-weight: normal; /* Remove bold for metadata */
        }

        .pen-card iframe {
            width: 100%;
            height: 400px;  /* Adjust the height for a better preview */
            border: none;
        }

        .pen-card a{
            display: block;
            text-align: center;
            padding: 10px;
            background-color: #ffcc00;
            color: #000;
            text-decoration: none;
            font-weight: bold;
            margin-top: 10px; /* Space above the link */
        }

        .pen-card a:hover {
            background-color: #ffb400;
        }

        .footer {
            background-color: #1a1a1d;
            color: #d3d3d3;
            text-align: center;
            padding: 20px;
        }

        .logo {
            border-radius: 100%;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <div class="navbar">
        <div>
            <a href="index.php">
                <img src="./images/CODE.png" alt="Logo" class="logo">
            </a>
        </div>
        <a href="create_pen.php">Create Your's</a>
    </div>

    <!-- Profile Section -->
    <div class="profile">
        <h1>Welcome, <?php echo esc_html($current_user->display_name); ?>!</h1>
        <img src="<?php echo get_avatar_url($current_user->ID); ?>" alt="User Avatar" class="avatar" width="100">
        <p>Here are all your pens:</p>
    </div>

    <!-- Pens List -->
    <div class="pens-list">
        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
               <div class="pen-card">
                    <h3><?= escapeCode($row['title']) ?></h3>
                    
                    <!-- Meta information: Created at and Created by -->
                    <div class="pen-meta">
                        <h4>Created at: <?= escapeCode($row['created_at']) ?></h4>
                        <h5>Created by: <?= escapeCode($row['user_login']) ?></h5>
                    </div>
                    
                    <!-- Live Preview -->
                    <?php
                    $html = $row['html_code'];
                    $css = $row['css_code'];
                    $js = $row['js_code'];
                    $previewFile = generateLivePreview($html, $css, $js);
                    ?>
                    <iframe src="<?= $previewFile ?>"></iframe>

                    <!-- Link to Full Pen -->
                    <a href="pen.php?id=<?= $row['pen_id'] ?>">View Full Pen</a>
                </div>

            <?php endwhile; ?>
        <?php else: ?>
            <p>You have not created any pens yet.</p>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p>Code Lab &copy; <?= date('Y') ?> | <a href="create_pen.php">Create Your Pen</a></p>
    </div>
</body>
</html>

<?php
// Close the database connection
$stmt->close();
$conn->close();
?>
