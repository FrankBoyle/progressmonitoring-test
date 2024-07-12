<?php
session_start();
include('auth_session.php');
include('db.php');

header('Content-Type: application/json');

try {
    if (isset($_POST['metadata_id'], $_POST['custom_column_names'])) {
        $metadataId = $_POST['metadata_id'];
        $customColumnNames = json_decode($_POST['custom_column_names'], true);

        if (empty($metadataId) || empty($customColumnNames)) {
            throw new Exception('Missing required parameters.');
        }

        // Prepare the update query for the Metadata table
        $updateFields = [];
        $params = [];
        foreach ($customColumnNames as $key => $value) {
            $updateFields[] = "$key = ?";
            $params[] = $value;
        }
        $params[] = $metadataId; // Append metadata_id to the end of the params array

        $updateQuery = "UPDATE Metadata SET " . implode(", ", $updateFields) . " WHERE metadata_id = ?";
        $stmt = $connection->prepare($updateQuery);
        $stmt->execute($params);

        echo json_encode(["message" => "Custom column names updated successfully."]);
    } else {
        throw new Exception('Invalid request, missing required parameters.');
    }
} catch (Exception $e) {
    error_log("Error updating custom column names: " . $e->getMessage());
    echo json_encode(["error" => "Error updating custom column names: " . $e->getMessage()]);
}
?>
