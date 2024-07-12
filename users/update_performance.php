<?php
session_start();
include('auth_session.php');
include('db.php');
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);

header('Content-Type: application/json');

// Start output buffering to catch any unexpected output
ob_start();

// Function to log errors server-side
function logError($error) {
    file_put_contents('error_log.txt', $error . PHP_EOL, FILE_APPEND);
}

// Function to handle and send back errors
function handleError($errorMessage, $missingData = []) {
    echo json_encode(['success' => false, 'error' => $errorMessage, 'missing_data' => $missingData]);
    ob_end_flush();
    exit;
}

try {
    // Use json_decode to parse the JSON body
    $inputData = json_decode(file_get_contents('php://input'), true);

    // Check if the JSON decoding was successful
    if (json_last_error() !== JSON_ERROR_NONE) {
        handleError("Invalid JSON input.");
    }

    // Extract data from the parsed JSON
    $performanceId = $inputData['performance_id'];
    $studentId = $inputData['student_id_new'];
    $schoolId = $inputData['school_id'];
    $scoreDate = $inputData['score_date'];
    $metadata_id = $inputData['metadata_id'];

    $scoreFields = [];
    for ($i = 1; $i <= 10; $i++) {
        $scoreField = "score$i";
        $scoreFields[$scoreField] = isset($inputData[$scoreField]) ? $inputData[$scoreField] : null;
    }

    // Check if the request method is POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        handleError("Invalid request method.");
    }

    if (empty($performanceId)) {
        handleError("performance_id is missing.");
    }
    if (empty($studentId)) {
        handleError("student_id_new is missing.");
    }
    if (empty($scoreDate)) {
        handleError("score_date is missing.");
    }
    if (empty($metadata_id)) {
        handleError("metadata_id is missing.");
    }

    // Update data in the database
    $setParts = [];
    $params = [];
    foreach ($scoreFields as $field => $value) {
        $setParts[] = "$field = ?";
        $params[] = $value;
    }
    $setParts[] = "score_date = ?";
    $params[] = $scoreDate;

    $setPartsString = implode(", ", $setParts);

    $stmt = $connection->prepare("
        UPDATE Performance 
        SET $setPartsString
        WHERE performance_id = ? AND student_id_new = ? AND metadata_id = ?
    ");
    $params[] = $performanceId;
    $params[] = $studentId;
    $params[] = $metadata_id;

    // Log the query and parameters for debugging
    file_put_contents('update_log.txt', "Query: UPDATE Performance SET $setPartsString WHERE performance_id = $performanceId AND student_id_new = $studentId AND metadata_id = $metadata_id\nParams: " . print_r($params, true), FILE_APPEND);

    if ($stmt->execute($params)) {
        echo json_encode(['success' => true]);
    } else {
        handleError("Failed to update data: " . implode(" | ", $stmt->errorInfo()));
    }
} catch (Exception $e) {
    logError($e->getMessage());
    handleError("An unexpected error occurred.");
}

// Flush the output buffer
ob_end_flush();
?>

