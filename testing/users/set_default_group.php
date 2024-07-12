<?php
session_start();
include('auth_session.php'); // Ensure the user is authenticated
include('db.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['group_id'])) {
    $groupId = $_POST['group_id'];
    $teacherId = $_SESSION['teacher_id'];

    // Update the default group
    $stmt = $connection->prepare("UPDATE Teachers SET default_group_id = ? WHERE teacher_id = ?");
    $stmt->execute([$groupId, $teacherId]);

    echo "Default group updated successfully.";
} else {
    echo "Invalid request.";
}
?>
