<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include WordPress functions
require_once('../wp-load.php');

// Check if the user is logged in
if (!is_user_logged_in()) {
    wp_redirect(wp_login_url(home_url('/codelab/create_pen.php')));
    exit();
}

// Include the database connection
require_once('db.php');

// Check if the table exists, if not, create it
$table_check_sql = "
CREATE TABLE IF NOT EXISTS codelab_pens (
    pen_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    html_code LONGTEXT NOT NULL,
    css_code LONGTEXT NOT NULL,
    js_code LONGTEXT NOT NULL,
    user_login VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
if (!$conn->query($table_check_sql)) {
    die("Error creating table: " . $conn->error);
}

// CSRF Protection
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    check_admin_referer('submit_pen_action'); // Verify nonce
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_pen'])) {
    try {
        // Sanitize inputs
        $title = sanitize_text_field($_POST['title']);
        $html_code = wp_kses_post($_POST['html_code']);  // Save as plain text (not encoded)
        $css_code = sanitize_textarea_field($_POST['css_code']);  // Save as plain text (not encoded)
        $js_code = sanitize_textarea_field($_POST['js_code']);  // Save as plain text (not encoded)

        if (empty($title)) {
            throw new Exception('Title is required.');
        }

        $current_user = wp_get_current_user();
        $user_login = $current_user->user_login;

        // Insert data into the database
        $sql = "
        INSERT INTO codelab_pens (title, html_code, css_code, js_code, user_login, created_at) 
        VALUES (?, ?, ?, ?, ?, NOW())";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Error preparing statement: " . $conn->error);
        }

        $stmt->bind_param("sssss", $title, $html_code, $css_code, $js_code, $user_login);
        if (!$stmt->execute()) {
            throw new Exception("Error executing query: " . $stmt->error);
        }

        $stmt->close();

        // Redirect after success
        wp_redirect(home_url('/codelab/index.php'));
        exit();
    } catch (Exception $e) {
        error_log($e->getMessage());
        echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}

$conn->close();

// Add nonce for CSRF protection
$nonce = wp_create_nonce('submit_pen_action');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Your Pen</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background-color: #1e1e1e;
            color: #fff;
        }
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #20232a;
            padding: 10px 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .navbar img {
            height: 40px;
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
        .editor-layout {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            padding: 20px;
        }
        .editor-section {
            background-color: #2d2d2d;
            border: 1px solid #444;
            border-radius: 5px;
            padding: 10px;
            height: 200px;
            display: flex;
            flex-direction: column;
        }
        .editor-section textarea {
            flex-grow: 1;
            background-color: #1e1e1e;
            color: #fff;
            border: none;
            resize: none;
            padding: 10px;
            font-family: monospace;
        }
        .preview-container {
            width: 100%;
            background-color: #1e2125;
            margin-top: 20px;
            padding: 15px;
            border-radius: 8px;
        }
        iframe {
            width: 100%;
            height: 400px;
            border: 1px solid #444;
            border-radius: 8px;
        }
    </style>
</head>
<body>
<div class="navbar">
    <a href="index.php">
        <img src="./images/CODE.png" alt="Code Lab Logo" class="logo">
    </a>
    <div class="user-container">
        <a class="user-picture" href="profile.php">
            <?php echo get_avatar(wp_get_current_user()->ID, 40); ?>
        </a>
        <a href="<?php echo wp_logout_url(home_url('/codelab/')); ?>">Logout</a>
    </div>
</div>

<div class="container">
    <form method="POST">
        <input type="text" name="title" placeholder="Enter your pen title" required>
        <?php wp_nonce_field('submit_pen_action'); ?>
        <div class="editor-layout">
            <div class="editor-section">
                <h2>HTML</h2>
                <textarea name="html_code" placeholder="HTML"></textarea>
            </div>
            <div class="editor-section">
                <h2>CSS</h2>
                <textarea name="css_code" placeholder="CSS"></textarea>
            </div>
            <div class="editor-section">
                <h2>JavaScript</h2>
                <textarea name="js_code" placeholder="JavaScript"></textarea>
            </div>
        </div>
        <button type="submit" name="submit_pen" class="navbar-button">
            <i class="fas fa-save"></i> Save
        </button>
    </form>
    <div class="preview-container">
        <iframe id="livePreview"></iframe>
    </div>
</div>

<script>
    const htmlInput = document.querySelector('textarea[name="html_code"]');
    const cssInput = document.querySelector('textarea[name="css_code"]');
    const jsInput = document.querySelector('textarea[name="js_code"]');
    const iframe = document.getElementById('livePreview');

    function updatePreview() {
        const html = htmlInput.value;
        const css = cssInput.value;
        const js = jsInput.value;

        const doc = iframe.contentDocument || iframe.contentWindow.document;
        doc.open();
        doc.write(`
            <!DOCTYPE html>
            <html>
            <head><style>${css}</style></head>
            <body>${html}<script>${js}<\/script></body>
            </html>
        `);
        doc.close();
    }

    [htmlInput, cssInput, jsInput].forEach(input => input.addEventListener('input', updatePreview));
</script>
</body>
</html>
