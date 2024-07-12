<?php
session_start();
include('auth_session.php');

// Error Reporting
//ini_set('display_errors', 1); // Enable display errors
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);

include('db.php');

function fetchPerformanceData($studentId, $metadata_id, $iep_date = null) {
    global $connection;
    $query = "SELECT * FROM Performance WHERE student_id_new = ? AND metadata_id = ? ";
    $query .= $iep_date ? "AND score_date >= ? " : "";
    $query .= "ORDER BY score_date ASC LIMIT 41";
    
    $stmt = $connection->prepare($query);
    $params = $iep_date ? [$studentId, $metadata_id, $iep_date] : [$studentId, $metadata_id];
    $stmt->execute($params);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function fetchScoreNames($school_id, $metadata_id) {
    global $connection;
    $stmt = $connection->prepare(
        "SELECT 
            score1_name, 
            score2_name, 
            score3_name, 
            score4_name, 
            score5_name, 
            score6_name, 
            score7_name, 
            score8_name, 
            score9_name, 
            score10_name 
        FROM Metadata 
        WHERE school_id = ? AND metadata_id = ?"
    );
    $stmt->execute([$school_id, $metadata_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function fetchIepDate($studentId) {
    global $connection;
    $stmt = $connection->prepare("SELECT IEP_Date FROM Students_new WHERE student_id_new = ?");
    $stmt->execute([$studentId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? $result['IEP_Date'] : null;
}

function fetchSchoolIdForStudent($studentId) {
    global $connection;
    $stmt = $connection->prepare("SELECT school_id FROM Students_new WHERE student_id_new = ?");
    $stmt->execute([$studentId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? $result['school_id'] : null;
}

function fetchStudentName($studentId) {
    global $connection;
    $stmt = $connection->prepare("SELECT CONCAT(first_name, ' ', last_name) as student_name FROM Students_new WHERE student_id_new = ?");
    $stmt->execute([$studentId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? $result['student_name'] : null;
}

function fetchCategoryName($metadata_id) {
    global $connection;
    $stmt = $connection->prepare("SELECT category_name FROM Metadata WHERE metadata_id = ?");
    $stmt->execute([$metadata_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? $result['category_name'] : null;
}

// Initialize empty arrays and variables
$performanceData = [];
$scoreNames = [];

// Check if student_id and metadata_id are set
if (!isset($_GET['student_id']) || !isset($_GET['metadata_id'])) {
    echo json_encode(['success' => false, 'message' => 'Student ID and Metadata ID are required.']);
    exit;
}

$studentId = $_GET['student_id'];
$metadata_id = $_GET['metadata_id'];

// Fetch the stored IEP date
$iep_date = fetchIepDate($studentId);

// Fetch school_id for the student
$school_id = fetchSchoolIdForStudent($studentId);

if (!$school_id) {
    echo json_encode(['success' => false, 'message' => 'School ID not found for the student.']);
    exit;
}

// Fetch performance data, score names, student name, and category name
try {
    $performanceData = fetchPerformanceData($studentId, $metadata_id, $iep_date);
    $scoreNames = fetchScoreNames($school_id, $metadata_id);
    $studentName = fetchStudentName($studentId);
    $categoryName = fetchCategoryName($metadata_id);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
}

// Prepare the response data
$response = [
    'performanceData' => $performanceData,
    'scoreNames' => $scoreNames,
    'iepDate' => $iep_date,  // Include IEP date in the response
    'studentName' => $studentName,
    'categoryName' => $categoryName
];

// Send JSON response
header('Content-Type: application/json');
echo json_encode($response);
?>
