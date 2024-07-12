<?php
session_start();
include('auth_session.php');
include('db.php');

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"));

if (isset($data->teacher_id)) {
    $teacherId = $data->teacher_id;
    $schoolId = $_SESSION['school_id'];  // Fetching the school_id from session

    try {
        $query = $connection->prepare("
            DELETE FROM Teachers 
            WHERE teacher_id = :teacher_id AND school_id = :school_id
        ");
        $query->bindParam(':teacher_id', $teacherId, PDO::PARAM_INT);
        $query->bindParam(':school_id', $schoolId, PDO::PARAM_INT);
        $query->execute();

        if ($query->rowCount() > 0) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'No matching record found']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
}
?>
