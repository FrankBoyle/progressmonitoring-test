<?php
session_start();
include('auth_session.php');
include('db.php');

header('Content-Type: application/json');

try {
    if (!isset($_GET['student_id'], $_GET['school_id'])) {
        throw new Exception('Missing required parameters.');
    }

    $studentId = $_GET['student_id'];
    $schoolId = $_GET['school_id'];

    // Fetch metadata used by the student's goals and not templates
    $stmt = $connection->prepare("
        SELECT DISTINCT m.* 
        FROM Metadata m
        JOIN Goals g ON m.metadata_id = g.metadata_id
        WHERE m.metadata_template = 0 AND g.student_id_new = ? AND m.school_id = ?
    ");
    $stmt->execute([$studentId, $schoolId]);
    $existingCategories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($existingCategories);
} catch (Exception $e) {
    error_log("Error fetching existing categories: " . $e->getMessage());
    echo json_encode(["error" => "Error fetching existing categories: " . $e->getMessage()]);
}
?>
