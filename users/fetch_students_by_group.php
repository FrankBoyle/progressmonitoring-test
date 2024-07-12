<?php
session_start();
include('auth_session.php');
include('db.php');

// Enable PHP error logging
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);
//ini_set('log_errors', 1);
//ini_set('error_log', 'error_log.log');  // Ensure this file is writable by the server

if (!isset($connection)) {
    error_log("Database connection is not set.");
    die("Database connection is not set.");
}

$teacherId = $_SESSION['teacher_id'];
$groupId = $_POST['group_id'] ?? '';

if ($groupId) {
    function fetchStudentsByGroup($groupId) {
        global $connection;
        $stmt = $connection->prepare("
            SELECT s.* FROM Students_new s
            INNER JOIN StudentGroup sg ON s.student_id_new = sg.student_id_new
            WHERE sg.group_id = ?
        ");
        $stmt->execute([$groupId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    $students = fetchStudentsByGroup($groupId);
    echo json_encode($students);
} else {
    echo json_encode([]);
}
?>

