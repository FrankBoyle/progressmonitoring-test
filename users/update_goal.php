<?php
session_start();
include('auth_session.php');
include('db.php');
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);

// Set headers to expect JSON response
header('Content-Type: application/json');

// Read the incoming JSON data
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Check if data is parsed correctly and contains the required keys
if (json_last_error() !== JSON_ERROR_NONE || !isset($data['goal_id']) || !isset($data['new_text'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid data received']);
    exit;
}

$goalId = $data['goal_id'];
$newText = $data['new_text'];

// Ensure the connection is successful
if (!$connection) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Prepare and execute the update statement
try {
    $stmt = $connection->prepare("UPDATE Goals SET goal_description = :newText WHERE goal_id = :goalId");
    $stmt->bindParam(':newText', $newText, PDO::PARAM_STR);
    $stmt->bindParam(':goalId', $goalId, PDO::PARAM_INT);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update goal']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
