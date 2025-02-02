<?php
include('includes/config.php');

if (isset($_GET['pen_id'])) {
    $pen_id = $_GET['pen_id'];
    $sql = "SELECT * FROM codelab_pens WHERE pen_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $pen_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows == 1) {
        $pen = $result->fetch_assoc();
        $html_code = $pen['html_code'];
        $css_code = $pen['css_code'];
        $js_code = $pen['js_code'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Preview Pen</title>
    <style>
        /* Safely include the CSS code */
        <?php echo htmlspecialchars($css_code); ?>
    </style>
</head>
<body>
    <!-- Safely include the HTML code -->
    <?php echo $html_code; ?>
    <script>
        // Safely include the JS code
        <?php echo $js_code; ?>
    </script>
</body>
</html>
