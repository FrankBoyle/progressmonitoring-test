<?php
session_start();

include('auth_session.php');
include('db.php');

// Function to add or update notes
function addOrUpdateNotes($goalId, $studentIdNew, $schoolId, $metadataId, $reportingPeriod, $notes, $reportImage) {
    global $connection;

    try {
        // Check if a note with the given parameters already exists
        $stmtCheck = $connection->prepare("SELECT note_id FROM Goal_notes WHERE goal_id = ? AND student_id_new = ? AND school_id = ? AND metadata_id = ? AND reporting_period = ?");
        $stmtCheck->execute([$goalId, $studentIdNew, $schoolId, $metadataId, $reportingPeriod]);
        $existingNote = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        if ($existingNote) {
            // Update the existing note
            $stmtUpdate = $connection->prepare("UPDATE Goal_notes SET notes = ?, report_image = ? WHERE note_id = ?");
            $stmtUpdate->execute([$notes, $reportImage, $existingNote['note_id']]);

            header('Content-Type: application/json');
            echo json_encode(['status' => 'success', 'message' => 'Notes updated successfully.']);
        } else {
            // Insert new notes with report image
            $stmtInsert = $connection->prepare("INSERT INTO Goal_notes (goal_id, student_id_new, school_id, metadata_id, reporting_period, notes, report_image) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmtInsert->execute([$goalId, $studentIdNew, $schoolId, $metadataId, $reportingPeriod, $notes, $reportImage]);

            header('Content-Type: application/json');
            echo json_encode(['status' => 'success', 'message' => 'Notes added successfully.']);
        }
    } catch (PDOException $e) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

// Ensure the request method is POST and required parameters are set
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    // Log the received data for debugging
    error_log('Received data: ' . print_r($data, true));

    // Validate that all required parameters are set
    if (!isset($data['goal_id'], $data['student_id_new'], $data['school_id'], $data['metadata_id'], $data['reporting_period'], $data['notes'], $data['report_image'])) {
        error_log('Missing parameters: ' . print_r($data, true)); // Log missing parameters
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Missing required parameters.']);
        exit;
    }

    $goalId = $data['goal_id'];
    $studentIdNew = $data['student_id_new'];
    $schoolId = $data['school_id'];
    $metadataId = $data['metadata_id'];
    $reportingPeriod = $data['reporting_period'];
    $notes = $data['notes'];
    $reportImage = base64_decode($data['report_image']); // Decode the base64 string

    addOrUpdateNotes($goalId, $studentIdNew, $schoolId, $metadataId, $reportingPeriod, $notes, $reportImage);
} else {
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}

exit;
?>



