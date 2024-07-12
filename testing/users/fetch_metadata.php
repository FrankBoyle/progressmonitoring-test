<?php
session_start();
include('auth_session.php');
include('db.php');

// Assuming school_id comes from a session or a direct variable definition
// For example, if it's in the session, you might get it like this:
$school_id = $_SESSION['school_id'];

// If it's being passed as a GET parameter, you might retrieve it like this:
//$school_id = $_GET['school_id']; // make sure to validate and sanitize this input

try {
    // The SQL query now includes a WHERE clause to match the school_id
    $stmt = $connection->prepare("SELECT metadata_id, category_name FROM Metadata WHERE school_id = :school_id");
    $stmt->bindParam(':school_id', $school_id, PDO::PARAM_INT);
    $stmt->execute();
    $metadataEntries = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($metadataEntries);
} catch (PDOException $e) {
    echo "Error fetching metadata: " . $e->getMessage();
}
?>
