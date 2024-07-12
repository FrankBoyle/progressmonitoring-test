<?php
session_start();
include('auth_session.php');
include('db.php');

header('Content-Type: application/json');

// Check if the request is a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['group_id']) && !empty($_POST['group_id'])) {
        $groupId = $_POST['group_id'];

        // Begin transaction
        $connection->beginTransaction();

        try {
            // Delete students from the group
            $removeStudentsStmt = $connection->prepare("DELETE FROM StudentGroup WHERE group_id = ?");
            $removeStudentsStmt->execute([$groupId]);

            // Delete shared groups associations
            $removeSharedGroupsStmt = $connection->prepare("DELETE FROM SharedGroups WHERE group_id = ?");
            $removeSharedGroupsStmt->execute([$groupId]);

            // Delete the group
            $deleteStmt = $connection->prepare("DELETE FROM Groups WHERE group_id = ?");
            $deleteStmt->execute([$groupId]);

            // Commit transaction
            $connection->commit();

            echo json_encode(["status" => "success", "message" => "Group deleted successfully"]);
        } catch (Exception $e) {
            $connection->rollBack();
            echo json_encode(["status" => "error", "message" => "Error deleting group: " . $e->getMessage()]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Invalid request"]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Invalid request method"]);
}
?>


