<?php
session_start();
include('auth_session.php'); // Ensure the user is authenticated
include('db.php'); // Include the database connection

$data = json_decode(file_get_contents("php://input"), true);

if (isset($data['student_id_new'])) {
    $studentId = $data['student_id_new'];
    $schoolId = $_SESSION['school_id'];

    try {
        $query = $connection->prepare("UPDATE Students_new SET archived = 1 WHERE student_id_new = :student_id AND school_id = :school_id");
        $query->bindParam(':student_id', $studentId, PDO::PARAM_INT);
        $query->bindParam(':school_id', $schoolId, PDO::PARAM_INT);
        $query->execute();

        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
}
?>
