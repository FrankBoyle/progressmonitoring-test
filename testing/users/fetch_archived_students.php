<?php
session_start();
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);

include('auth_session.php');
include('db.php');

$schoolId = $_SESSION['school_id'];
$teacherId = $_SESSION['teacher_id'];

function fetchArchivedStudentsByTeacher($teacherId, $schoolId) {
    global $connection;
    // Fetch all archived students assigned to the teacher
    $stmt = $connection->prepare("SELECT s.student_id_new, s.first_name, s.last_name, s.date_of_birth, s.grade_level, s.archived 
                                  FROM Students_new s 
                                  INNER JOIN Teachers t ON s.school_id = t.school_id 
                                  WHERE t.teacher_id = :teacherId AND s.archived = 1 AND t.school_id = :schoolId");
    $stmt->bindParam(':teacherId', $teacherId, PDO::PARAM_INT);
    $stmt->bindParam(':schoolId', $schoolId, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$archivedStudents = fetchArchivedStudentsByTeacher($teacherId, $schoolId);

// Log the final students array
error_log(print_r($archivedStudents, true));

echo json_encode(array_values($archivedStudents)); // Output students as JSON
?>
