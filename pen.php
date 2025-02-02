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

// Function to sanitize HTML input
function sanitizeHtml($input) {
    $allowedTags = '<b><i><u><em><strong><p><a><div><span><br><ul><ol><li>';
    $input = strip_tags($input, $allowedTags);

    if (preg_match('/<script.*?>|<\/script>/i', $input) || 
        preg_match('/javascript:/i', $input) || 
        preg_match('/on[a-z]+\s*=/i', $input)) {
        throw new Exception('Malicious code detected in HTML input.');
    }

    return $input;
}

// Check if the current user is the creator of the pen
$is_creator = false;
if (is_user_logged_in()) {
    $current_user = wp_get_current_user();
    if ($current_user->user_login === $pen['user_login']) {
        $is_creator = true;
    }
} else {
    // You might want to allow viewing for non-logged-in users, but editing should be restricted
    // die("You must be logged in to edit a pen.");
}

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete']) && $is_creator) {
        // Handle pen deletion
        $delete_sql = "DELETE FROM codelab_pens WHERE pen_id = ? AND user_login = ?";
        $stmt = $conn->prepare($delete_sql);
        $stmt->bind_param("is", $pen_id, $current_user->user_login);

        if ($stmt->execute()) {
            echo "<script>alert('Pen deleted successfully!'); window.location.href='index.php';</script>";
            exit;
        } else {
            echo "<script>alert('Error: Unable to delete the pen.');</script>";
        }
    }

    if (isset($_POST['save']) && $is_creator) {
        // Sanitize inputs before saving
        try {
            $new_html = sanitizeHtml($_POST['html_code']);
            $new_css = sanitize_text_field($_POST['css_code']); // Consider using a more robust CSS sanitizer
            $new_js = sanitize_text_field($_POST['js_code']); // Note: Storing JS for execution is risky

            $update_sql = "UPDATE codelab_pens SET html_code = ?, css_code = ?, js_code = ? WHERE pen_id = ?";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("sssi", $new_html, $new_css, $new_js, $pen_id);

            if ($stmt->execute()) {
                echo "<script>alert('Pen updated successfully!'); window.location.href='view_pen.php?id={$pen_id}';</script>";
                exit;
            } else {
                echo "<script>alert('Error: Unable to save changes.');</script>";
            }
        } catch (Exception $e) {
            echo "<script>alert('Error: " . htmlspecialchars($e->getMessage()) . "');</script>";
        }
    }
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

// Clean up old temporary files
cleanupTempFiles();

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
            font-family: 'Arial', sans-serif;
            background-color: #1e1e1e;
            color: #f5f5f5;
            margin: 0;
            padding: 0;
        }

        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #20232a;
            padding: 10px 20px;
        }

        .navbar a {
            color: #fff;
            text-decoration: none;
            margin: 0 10px;
            font-weight: bold;
            transition: color 0.3s;
        }

        .navbar a:hover {
            color: #ffcc00;
        }

        .content {
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .pen-meta {
            background-color: #333;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .pen-meta p {
            margin: 5px 0;
        }

        .editor-layout {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 20px;
            padding: 20px;
        }

        .editor-section {
            background-color: #2d2d2d;
            padding: 15px;
            border-radius: 5px;
        }

        .editor-section h3 {
            margin-bottom: 10px;
            font-size: 1.1rem;
            font-weight: bold;
            color: #ffcc00;
        }

        textarea {
            width: 100%;
            height: 250px;
            padding: 10px;
            background-color: #1a1a1d;
            color: #f5f5f5;
            border: 1px solid #444;
            font-family: 'Courier New', Courier, monospace;
            font-size: 1rem;
            border-radius: 5px;
            resize: none;
        }

        .action-buttons a, .action-buttons button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #ffcc00;
            color: #000;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            margin-right: 10px;
            transition: background-color 0.3s;
        }

        .action-buttons a:hover, .action-buttons button:hover {
            background-color: #ffb400;
        }

        .delete-button {
            background-color: #ff4d4d;
        }

        .delete-button:hover {
            background-color: #ff1a1a;
        }

        /* Live preview styles */
        #preview-area {
            margin-top: 20px;
            background-color: #333;
            padding: 20px;
            border-radius: 5px;
        }
        iframe {
            width: 100%;
            height: 500px;
            border: none;
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

    <!-- Content -->
    <div class="content">
        <h1>Edit Pen: <?= escapeCode($pen['title']) ?></h1>
        <div class="pen-meta">
            <p>Created by: <?= escapeCode($pen['user_login']) ?></p>
            <p>Created at: <?= escapeCode($pen['created_at']) ?></p>
        </div>

       <form method="POST">
    <div class="editor-layout">
        <div class="editor-section">
            <h3>HTML Code</h3>
            <textarea id="html_code" name="html_code"><?= htmlspecialchars($pen['html_code'], ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>
        <div class="editor-section">
            <h3>CSS Code</h3>
            <textarea id="css_code" name="css_code"><?= htmlspecialchars($pen['css_code'], ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>
        <div class="editor-section">
            <h3>JavaScript Code</h3>
            <textarea id="js_code" name="js_code"><?= htmlspecialchars($pen['js_code'], ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>
    </div>

    <div class="action-buttons">
        <a href="index.php">Go Back</a>
        <?php if ($is_creator): ?>
            <button type="submit" name="save">Save Changes</button>
            <button type="submit" name="delete" class="delete-button" onclick="return confirm('Are you sure you want to delete this pen?')">Delete Pen</button>
        <?php endif; ?>
    </div>
</form>


        <!-- Live Preview -->
        <div id="preview-area">
            <iframe id="livePreview"></iframe>
        </div>
    </div>

    <script>
        const htmlInput = document.getElementById('html_code');
        const cssInput = document.getElementById('css_code');
        const jsInput = document.getElementById('js_code');
        const iframe = document.getElementById('livePreview');

        function updatePreview() {
            const html = htmlInput.value;
            const css = cssInput.value;
            const js = jsInput.value;

            // Accessing the iframe's document
            const previewDocument = iframe.contentDocument || iframe.contentWindow.document;
            
            // Writing the content into the iframe
            previewDocument.open();
            previewDocument.write(`
                <!DOCTYPE html>
                <html lang="en">
                <head>
                    <meta charset="UTF-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                    <style>${css}</style>
                </head>
                <body>
                    ${html}
                    <script>${js}<\/script>
                </body>
                </html>
            `);
            previewDocument.close();
        }

        // Event listeners for live updating
        htmlInput.addEventListener('input', updatePreview);
        cssInput.addEventListener('input', updatePreview);
        jsInput.addEventListener('input', updatePreview);

        // Initial update to show the preview
        updatePreview();
    </script>

<?php
$conn->close();
?>
</body>
</html>
