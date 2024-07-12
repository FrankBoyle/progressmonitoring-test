<?php
session_start();
include('auth_session.php'); // Ensure the user is authenticated
include('db.php'); // Include the database connection

$data = json_decode(file_get_contents("php://input"), true);

if (isset($data['studentId'])) {
    $studentId = $data['studentId'];
    $schoolId = $_SESSION['school_id'];

    try {
        // Activate the student
        $query = $connection->prepare("UPDATE Students_new SET archived = 0 WHERE student_id_new = :student_id AND school_id = :school_id");
        $query->bindParam(':student_id', $studentId, PDO::PARAM_INT);
        $query->bindParam(':school_id', $schoolId, PDO::PARAM_INT);
        $query->execute();

        // Fetch the full student data to return to the client
        if ($query->rowCount() > 0) {
            $stmt = $connection->prepare("SELECT * FROM Students_new WHERE student_id_new = :student_id AND school_id = :school_id AND archived = 0");
            $stmt->bindParam(':student_id', $studentId, PDO::PARAM_INT);
            $stmt->bindParam(':school_id', $schoolId, PDO::PARAM_INT);
            $stmt->execute();
            $studentData = $stmt->fetch(PDO::FETCH_ASSOC);

            echo json_encode(['success' => true, 'student' => $studentData]);
        } else {
            echo json_encode(['success' => false, 'message' => 'No changes made, student may already be active or does not exist.']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid or missing data']);
}
?>


