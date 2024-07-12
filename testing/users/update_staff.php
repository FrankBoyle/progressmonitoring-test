<?php
session_start();
include('auth_session.php'); // Ensure the user is authenticated
include('db.php'); // Include the database connection

$data = json_decode(file_get_contents("php://input"), true);

if (isset($data['teacher_id']) && isset($data['name']) && isset($data['is_admin'])) {
    $teacherId = $data['teacher_id'];
    $name = $data['name'];
    $subjectTaught = isset($data['subject_taught']) ? $data['subject_taught'] : null; // Allow subject_taught to be null
    $isAdmin = $data['is_admin'];

    try {
        $query = $connection->prepare("UPDATE Teachers SET name = :name, subject_taught = :subject_taught, is_admin = :is_admin WHERE teacher_id = :teacher_id");
        $query->bindParam(':name', $name, PDO::PARAM_STR);
        $query->bindParam(':subject_taught', $subjectTaught, PDO::PARAM_STR);
        $query->bindParam(':is_admin', $isAdmin, PDO::PARAM_INT);
        $query->bindParam(':teacher_id', $teacherId, PDO::PARAM_INT);
        $query->execute();

        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
}
?>


