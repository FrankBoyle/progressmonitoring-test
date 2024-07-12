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
    $studentId = $inputData['student_id_new'];
    $schoolId = $inputData['school_id'];
    $weekStartDate = $inputData['score_date'];
    $scoreDate = $inputData['score_date'];
    $scores = $inputData['scores'];
    $metadata_id = $inputData['metadata_id'];
    $score1 = isset($scores['score1']) ? $scores['score1'] : null;
    $score2 = isset($scores['score2']) ? $scores['score2'] : null;
    $score3 = isset($scores['score3']) ? $scores['score3'] : null;
    $score4 = isset($scores['score4']) ? $scores['score4'] : null;
    $score5 = isset($scores['score5']) ? $scores['score5'] : null;
    $score6 = isset($scores['score6']) ? $scores['score6'] : null;
    $score7 = isset($scores['score7']) ? $scores['score7'] : null;
    $score8 = isset($scores['score8']) ? $scores['score8'] : null;
    $score9 = isset($scores['score9']) ? $scores['score9'] : null;
    $score10 = isset($scores['score10']) ? $scores['score10'] : null;
    $responseData = [];

    // Check if the request method is POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        handleError("Invalid request method.");
    }

    if (empty($studentId)) {
        handleError("student_id_new is missing.");
    }
    if (empty($scoreDate)) {
        handleError("score_date is missing.");
    }
    if (empty($scores)) {
        handleError("scores are missing.");
    }

    // Check for duplicate date entry
    $checkStmt = $connection->prepare(
        "SELECT COUNT(*) FROM Performance 
         WHERE student_id_new = ? AND score_date = ? AND metadata_id = ?"
    );
    $checkStmt->execute([$studentId, $scoreDate, $metadata_id]);
    $duplicateCount = $checkStmt->fetchColumn();

    if ($duplicateCount > 0) {
        handleError("Duplicate date entry is not allowed. A record with this date and metadata already exists for the selected student.");
    }

    // Insert data into the database
    $stmt = $connection->prepare("
        INSERT INTO Performance (student_id_new, metadata_id, school_id, score_date, score1, score2, score3, score4, score5, score6, score7, score8, score9, score10) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    if ($stmt->execute([$studentId, $metadata_id, $schoolId, $weekStartDate, $score1, $score2, $score3, $score4, $score5, $score6, $score7, $score8, $score9, $score10])) {
        $newPerformanceId = $connection->lastInsertId();
        $responseData = [
            'success' => true,
            'performance_id' => $newPerformanceId,
            'score_date' => $weekStartDate,
            'scores' => $scores,
            'school_id' => $schoolId,
            'metadata_id' => $metadata_id,
        ];
        echo json_encode($responseData);
    } else {
        handleError("Failed to insert data: " . implode(" | ", $stmt->errorInfo()));
    }
} catch (Exception $e) {
    logError($e->getMessage());
    handleError("An unexpected error occurred.");
}

// Flush the output buffer
ob_end_flush();
?>
