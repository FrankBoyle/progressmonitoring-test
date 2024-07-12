<?php
session_start();
//file_put_contents('post_log.txt', print_r($_POST, true));
//error_reporting(E_ALL);
//ini_set('display_errors', 1);

include('auth_session.php');
include('db.php');

$response = ['success' => false];

// Check database connection
if (!$connection) {
    $response['message'] = "Connection to the database failed.";
    echo json_encode($response);
    exit;
}

$inputData = json_decode(file_get_contents('php://input'), true);

if (isset($inputData['performance_id'])) {
    $performanceId = $inputData['performance_id'];

    try {
        // Prepare the DELETE statement
        $stmt = $connection->prepare("DELETE FROM Performance WHERE performance_id = :performanceId");

        // Check if statement preparation is successful
        if (!$stmt) {
            $response['message'] = "Failed to prepare the statement.";
            echo json_encode($response);
            exit;
        }

        // Bind the parameters
        $stmt->bindParam(':performanceId', $performanceId, PDO::PARAM_INT);

        // Execute the statement
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $response['success'] = true;
        } else {
            $response['message'] = "No rows affected. The provided performance_id might not exist in the database.";
        }
    } catch (PDOException $e) {
        $response['message'] = "Database error: " . $e->getMessage();
    }
} else {
    $response['message'] = "Performance ID not provided.";
}

echo json_encode($response);
?>


