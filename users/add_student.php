<?php
session_start();
include('auth_session.php');
include('db.php');

// Enable PHP error logging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', 'error_log.log');  // Ensure this file is writable by the server

// Check if the request is a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve form data
    $firstName = $_POST['first_name'] ?? null;
    $lastName = $_POST['last_name'] ?? null;
    $dateOfBirth = $_POST['date_of_birth'] ?? null;
    $gradeLevel = $_POST['grade_level'] ?? null;
    $schoolId = $_SESSION['school_id'] ?? null;

    // Validate the form data
    if (!$firstName || !$lastName || !$dateOfBirth || !$gradeLevel || !$schoolId) {
        error_log("Invalid input data: " . print_r($_POST, true));
        echo "Invalid input data.";
        exit;
    }

    try {
        // Prepare the SQL statement
        $stmt = $connection->prepare("INSERT INTO Students_new (first_name, last_name, date_of_birth, grade_level, school_id) VALUES (?, ?, ?, ?, ?)");
        
        // Bind parameters and execute the statement
        $stmt->execute([$firstName, $lastName, $dateOfBirth, $gradeLevel, $schoolId]);

        // Check if the student was added successfully
        if ($stmt->rowCount() > 0) {
            echo "Student added successfully.";
        } else {
            error_log("Failed to add student.");
            echo "Failed to add student.";
        }
    } catch (PDOException $e) {
        // Log any errors
        error_log("Database error: " . $e->getMessage());
        echo "Database error: " . $e->getMessage();
    }
} else {
    echo "Invalid request method.";
}
?>
