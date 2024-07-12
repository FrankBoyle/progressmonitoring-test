<?php
session_start();
include('auth_session.php');
include('db.php');

header('Content-Type: application/json'); // Default to JSON

if (isset($_GET['note_id'])) {
    $noteId = $_GET['note_id'];
    error_log("Fetching image for note_id: " . $noteId);

    $stmt = $connection->prepare("SELECT report_image FROM Goal_notes WHERE note_id = ?");
    $stmt->execute([$noteId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result && $result['report_image']) {
        $imageData = $result['report_image'];

        // Determine the image type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_buffer($finfo, $imageData);
        finfo_close($finfo);

        // Log the mime type for debugging
        error_log("Image mime type: " . $mimeType);

        // Set the Content-Type header and send the image data
        header('Content-Type: ' . $mimeType);
        echo $imageData;
    } else {
        error_log("Image not found for note_id: " . $noteId);
        echo json_encode(['error' => 'Image not found']);
    }
} else {
    error_log("Invalid request, missing note_id");
    echo json_encode(['error' => 'Invalid request, missing note_id']);
}
?>



