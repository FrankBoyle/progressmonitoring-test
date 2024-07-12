<?php
session_start();
include('auth_session.php'); // Ensure the user is authenticated
include('db.php'); // Include the database connection

$data = json_decode(file_get_contents("php://input"), true);

if (isset($data['student_id_new']) && isset($data['first_name']) && isset($data['last_name'])) {
    $studentId = $data['student_id_new'];
    $firstName = $data['first_name'];
    $lastName = $data['last_name'];
    $dateOfBirth = isset($data['date_of_birth']) ? $data['date_of_birth'] : null;
    $gradeLevel = isset($data['grade_level']) ? $data['grade_level'] : null;
    
    try {
        $query = $connection->prepare("UPDATE Students_new SET first_name = :first_name, last_name = :last_name, date_of_birth = :date_of_birth, grade_level = :grade_level WHERE student_id_new = :student_id_new");
        $query->bindParam(':first_name', $firstName, PDO::PARAM_STR);
        $query->bindParam(':last_name', $lastName, PDO::PARAM_STR);
        $query->bindParam(':date_of_birth', $dateOfBirth, PDO::PARAM_STR);
        $query->bindParam(':grade_level', $gradeLevel, PDO::PARAM_STR);
        $query->bindParam(':student_id_new', $studentId, PDO::PARAM_INT);
        $query->execute();

        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
}
?>
