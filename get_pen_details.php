<?php
require_once 'db.php';

if (isset($_GET['id'])) {
    $penId = intval($_GET['id']);
    $query = "SELECT * FROM codelab_pens WHERE id = $penId";
    $result = $conn->query($query);

    if ($result && $result->num_rows > 0) {
        echo json_encode($result->fetch_assoc());
    } else {
        echo json_encode(['error' => 'Pen not found']);
    }
}
?>
