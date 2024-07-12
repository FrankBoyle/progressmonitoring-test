<?php
session_start();
include('auth_session.php');
include('db.php');

if (!isset($_SESSION['school_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$schoolId = $_SESSION['school_id'];

$data = json_decode(file_get_contents("php://input"), true);
$teacherId = $data['teacher_id'];

try {
    // Fetch the existing teacher details
    $stmt = $connection->prepare("SELECT account_id, program_id, fname, lname, subject_taught FROM Teachers WHERE teacher_id = :teacherId");
    $stmt->bindParam("teacherId", $teacherId, PDO::PARAM_INT);
    $stmt->execute();
    $teacherDetails = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$teacherDetails) {
        echo json_encode(['error' => 'Teacher not found']);
        exit();
    }

    // Create a new teacher entry with the same details but a new teacher_id and approved set to 0
    $insertStmt = $connection->prepare("
        INSERT INTO Teachers (account_id, program_id, school_id, is_admin, fname, lname, approved, subject_taught) 
        VALUES (:account_id, :program_id, :school_id, 0, :fname, :lname, 0, :subject_taught)
    ");
    $insertStmt->bindParam("account_id", $teacherDetails['account_id'], PDO::PARAM_INT);
    $insertStmt->bindParam("program_id", $teacherDetails['program_id'], PDO::PARAM_INT);
    $insertStmt->bindParam("school_id", $schoolId, PDO::PARAM_INT);
    $insertStmt->bindParam("fname", $teacherDetails['fname'], PDO::PARAM_STR);
    $insertStmt->bindParam("lname", $teacherDetails['lname'], PDO::PARAM_STR);
    $insertStmt->bindParam("subject_taught", $teacherDetails['subject_taught'], PDO::PARAM_STR);
    $insertStmt->execute();

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
