<?php
session_start();
include('auth_session.php');
include('db.php');

header('Content-Type: application/json');

try {
    $schoolId = $_SESSION['school_id'];
    
    // Fetch only metadata templates (where metadata_template is 1)
    $stmt = $connection->prepare("SELECT * FROM Metadata WHERE school_id = ? AND metadata_template = 1");
    $stmt->execute([$schoolId]);
    $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($templates);
} catch (Exception $e) {
    error_log("Error fetching metadata templates: " . $e->getMessage());
    echo json_encode(["error" => "Error fetching metadata templates: " . $e->getMessage()]);
}
?>
