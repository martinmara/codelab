<?php
// Include WordPress functions to access user data, etc.
require_once('../wp-blog-header.php');

// Check if the user is logged in
if (!is_user_logged_in()) {
    wp_redirect(wp_login_url()); // Redirect to WordPress login page if not logged in
    exit;
}

// Your app's protected content
echo "Welcome to the Codelab app!";
?>
