<?php
session_start();
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);

include('auth_session.php');
include('db.php');

$schoolId = $_SESSION['school_id'];
$teacherId = $_SESSION['teacher_id'];
$groupId = isset($_GET['group_id']) ? intval($_GET['group_id']) : 0;

function fetchStudentsByTeacher($teacherId, $groupId, $archived = false) {
    global $connection;
    $archivedValue = $archived ? 1 : 0;

    // Fetch all students assigned to the teacher and not archived
    $stmt = $connection->prepare("SELECT s.student_id_new, s.first_name, s.last_name 
                                  FROM Students_new s 
                                  INNER JOIN Teachers t ON s.school_id = t.school_id 
                                  WHERE t.teacher_id = ? AND s.archived = ?");
    $stmt->execute([$teacherId, $archivedValue]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch all students already in the group
    $stmt = $connection->prepare("SELECT student_id_new FROM StudentGroup WHERE group_id = ?");
    $stmt->execute([$groupId]);
    $groupStudents = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

    // Filter out students who are already in the group
    $filteredStudents = array_filter($students, function($student) use ($groupStudents) {
        return !in_array($student['student_id_new'], $groupStudents);
    });

    return $filteredStudents;
}

$allStudents = fetchStudentsByTeacher($teacherId, $groupId, false);

// Log the final students array
error_log(print_r($allStudents, true));

echo json_encode(array_values($allStudents)); // Output students as JSON
?>
