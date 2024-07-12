<?php
session_start();
include('auth_session.php');
include('db.php');

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['goal_id'])) {
    $goalId = $_GET['goal_id'];

    try {
        $stmt = $connection->prepare("SELECT reporting_period, notes FROM Goal_notes WHERE goal_id = ? ORDER BY reporting_period ASC");
        $stmt->execute([$goalId]);
        $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

        header('Content-Type: application/json');
        echo json_encode($reports);
    } catch (PDOException $e) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid request or missing parameters.']);
}

exit;
?>

