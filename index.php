<?php
// Include WordPress functions
require_once('../wp-load.php');

// Include the database connection
require_once('db.php');

// Check database connection
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Fetch all pens from the database
$sql = "SELECT * FROM codelab_pens ORDER BY created_at DESC";
$result = $conn->query($sql);

if (!$result) {
    die("Error fetching pens: " . $conn->error);
}

// Function to safely escape code for display
function escapeCode($code) {
    return htmlspecialchars($code, ENT_QUOTES, 'UTF-8');
}

function createTempFile($html, $css, $js) {
    $tempDir = 'temp_pens/';
    if (!is_dir($tempDir) && !mkdir($tempDir, 0777, true) && !is_dir($tempDir)) {
        die("Failed to create temp directory.");
    }

    $fileName = $tempDir . uniqid('pen_', true) . '.html';

    // Decode and clean up the content
    $html = stripslashes(htmlspecialchars_decode($html));
    $css = stripslashes(htmlspecialchars_decode($css));
    $js = stripslashes(htmlspecialchars_decode($js));

    $content = <<<HTML
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <style>$css</style>
    </head>
    <body>
        $html
        <script>$js</script>
    </body>
    </html>
    HTML;

    if (file_put_contents($fileName, $content) === false) {
        die("Failed to write temp file.");
    }

    return $fileName;
}

// Function to clean up old temporary files
function cleanupTempFiles($directory = 'temp_pens/', $ageLimit = 3600) {
    $files = glob($directory . '*');
    $currentTime = time();
    foreach ($files as $file) {
        if (is_file($file) && ($currentTime - filemtime($file)) > $ageLimit) {
            unlink($file);
        }
    }
}

// Call cleanup function on page load
cleanupTempFiles();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Code Lab</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
body {
            font-family: 'Roboto', sans-serif;
            background-color: #1e1e1e;
            color: #f0f0f0;
            margin: 0;
        }

        .sidebar {
            width: 200px;
            background-color: #151515;
            padding: 20px;
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            overflow-y: auto;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.3);
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 20px;
        }

        .sidebar a {
            color: #f0f0f0;
            text-decoration: none;
            font-size: 16px;
            padding: 10px;
            border-radius: 5px;
            transition: background-color 0.3s;
            width: 100%;
            text-align: center;
        }

        .sidebar a:hover {
            background-color: #333;
        }

        .logo img {
            width: 120px;
            margin-bottom: 20px;
        }

        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 20px;
            background-color: black;
            color: #fff;
            margin-left: 200px;
        }

        .navbar-links {
            margin-left: auto;
            display: flex;
            align-items: center;
        }

        .user-container {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .user-picture img {
            border-radius: 50%;
            border: 2px solid #fff;
            width: 40px;
            height: 40px;
        }

        .navbar-links a {
            color: #fff;
            text-decoration: none;
            padding: 5px 10px;
            border-radius: 5px;
            transition: background-color 0.3s;
        }

        .navbar-links a:hover {
            background-color: #555;
        }

        .logout-link, .login-link {
            margin-left: 15px;
            font-weight: bold;
        }

        .hero {
            margin-left: 200px;
            margin-top: 60px;
            padding: 80px 20px;
            text-align: center;
            background: linear-gradient(135deg, #252525, #1e1e1e);
            color: #fff;
        }

        .hero h1 {
            font-size: 2.5rem;
            margin-bottom: 20px;
        }

        .hero p {
            font-size: 1.2rem;
            color: #ccc;
        }

        .create-pen-link {
            display: inline-block;
            background-color: #ffcc00;
            color: #000;
            padding: 12px 25px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            font-size: 1.2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
        }

        .create-pen-link:hover {
            background-color: #ffa700;
            color: #fff;
            box-shadow: 0 6px 10px rgba(0, 0, 0, 0.3);
            transform: translateY(-3px);
        }

        .main-content {
            margin-left: 250px;
            padding: 20px;
            margin-top: 80px;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }

        .pen-card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }

        .pen-card iframe {
            width: 100%;
            height: 200px;
            border: none;
        }

        .pen-card-view {
            display: block;
            text-align: center;
            padding: 10px;
            background-color: #ffcc00;
            color: #000;
            text-decoration: none;
            font-weight: bold;
            margin-top: 10px;
        }

        .pen-card-view:hover {
            background-color: #ffb400;
        }

        .pen-meta {
            padding: 10px;
            font-size: 0.9rem;
            background-color: #2c2c2c;
            color: #ccc;
        }

        .user-avatar-container {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: 10px;
            margin-bottom: 10px;
        }

        .user-avatar {
            border-radius: 50%;
            border: 2px solid #fff;
            width: 40px;
            height: 40px;
            object-fit: cover;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease-in-out;
        }

        .user-avatar:hover {
            transform: scale(1.1);
        }

        .footer {
            text-align: center;
            padding: 10px;
            background-color: #151515;
            color: #bbb;
            margin-top: 20px;
        }

        @media screen and (max-width: 768px) {
            .sidebar {
                display: none;
            }

            .navbar {
                margin-left: 0;
            }

            .hero {
                margin-left: 0;
            }

            .main-content {
                margin-left: 0;
            }
        }    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo">
            <img src="./images/CODE.png" alt="Code Lab Logo">
        </div>
        <a href="index.php">Home</a>
        <a href="create_pen.php">Create Your's</a>
        <a href="profile.php">Profile</a>
        <a href="settings.php">Settings</a>
    </div>

    <div class="navbar">
        <div class="navbar-links">
            <?php if (is_user_logged_in()): ?>
                <?php $current_user = wp_get_current_user(); ?>
                <div class="user-container">
                    <a class="user-picture" href="profile.php">
                        <?= get_avatar($current_user->ID, 40); ?>
                    </a>
                    <a class="logout-link" href="<?= wp_logout_url(home_url('/codelab/')); ?>">Logout</a>
                </div>
            <?php else: ?>
                <a class="login-link" href="<?= wp_login_url(home_url('/codelab/')); ?>">Login</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="hero">
        <h1>Welcome to Code Lab</h1>
        <p>Create, showcase, and explore amazing code snippets with our community.</p>
        <a href="create_pen.php" class="create-pen-link">Create Your's</a>
    </div>

    <div class="main-content">
        <?php while ($row = $result->fetch_assoc()): ?>
            <div class="pen-card">
                <a href="pen.php?id=<?= $row['pen_id']; ?>"> 
                    <iframe src="<?= htmlspecialchars(createTempFile($row['html_code'], $row['css_code'], $row['js_code'])); ?>"></iframe>
                    <div class="pen-meta">
                        <p><?= escapeCode($row['title']); ?> by <?= escapeCode($row['user_login']); ?></p>
                        <p><?= escapeCode($row['created_at']); ?></p>
                    </div>
                    <?php
                    $user = get_user_by('login', $row['user_login']);
                    if ($user) {
                        $avatar_url = get_avatar_url($user->ID, ['size' => 40]);
                    }
                    ?>
                    <?php if (isset($avatar_url)): ?>
                        <div class="user-avatar-container">
                            <img src="<?= esc_url($avatar_url); ?>" alt="<?= esc_attr($user->display_name); ?>" class="user-avatar">
                        </div>
                    <?php endif; ?>
                </a>
                <a class="pen-card-view" href="pen.php?id=<?= $row['pen_id']; ?>">View Full Pen</a>
            </div>
        <?php endwhile; ?>
    </div>

    <div class="footer">
        <p>&copy; <?= date('Y'); ?> Code Lab. All rights reserved.</p>
    </div>
</body>
</html>
