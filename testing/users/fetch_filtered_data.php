<?php
session_start();
include('auth_session.php');
include('db.php');

header('Content-Type: application/json');

if (isset($_GET['student_id']) && isset($_GET['metadata_id']) && isset($_GET['iep_date'])) {
    $student_id = $_GET['student_id'];
    $metadata_id = $_GET['metadata_id'];
    $iep_date = $_GET['iep_date'];

    $stmt = $connection->prepare("SELECT * FROM Performance WHERE student_id_new = ? AND metadata_id = ? AND score_date >= ? ORDER BY score_date ASC");
    $stmt->execute([$student_id, $metadata_id, $iep_date]);
    $performanceData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Example to create scoreNames based on your existing data structure
    $scoreNames = [];
    if (count($performanceData) > 0) {
        foreach (array_keys($performanceData[0]) as $key) {
            if (strpos($key, 'score') !== false) { // assuming your score fields start with 'score'
                $scoreNames[$key] = ucfirst(str_replace('_', ' ', $key)); // Score field names as titles
            }
        }
    }

    echo json_encode([
        'performanceData' => $performanceData,
        'scoreNames' => $scoreNames
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid data.']);
}
?>
