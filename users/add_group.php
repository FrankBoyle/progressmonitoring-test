<?php
session_start();
include('auth_session.php');
include('db.php');

// Enable PHP error logging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', 'error_log.log');  // Ensure this file is writable by the server

if (!isset($connection)) {
    error_log("Database connection is not set.");
    die("Database connection is not set.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create_group'])) {
    $groupName = $_POST['group_name'];
    $teacherId = $_SESSION['teacher_id'] ?? null;  // Assuming you want to link the group to the logged-in teacher
    $schoolId = $_SESSION['school_id'] ?? null;    // Assuming you want to link the group to the logged-in teacher's school

    if (empty($groupName)) {
        echo "Group name is required.";
        error_log("Group name is required.");
        exit;
    }

    try {
        $stmt = $connection->prepare("INSERT INTO Groups (teacher_id, school_id, group_name) VALUES (?, ?, ?)");
        $stmt->execute([$teacherId, $schoolId, $groupName]);
        echo "New group added successfully.";
    } catch (Exception $e) {
        error_log("Error adding new group: " . $e->getMessage());
        echo "Error adding new group: " . $e->getMessage();
    }
} else {
    echo "Invalid request.";
    error_log("Invalid request: POST method or create_group not set.");
}
?>
