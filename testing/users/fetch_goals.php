<?php
session_start();

include('auth_session.php');
include('db.php');

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        if (isset($_GET['student_id'])) {
            $studentId = $_GET['student_id'];

            // Fetch goals and associated notes including report images
            $stmt = $connection->prepare("
                SELECT g.goal_id, g.goal_description, gm.metadata_id, gm.category_name, gn.note_id, gn.reporting_period, gn.notes
                FROM Goals g
                INNER JOIN Metadata gm ON g.metadata_id = gm.metadata_id
                LEFT JOIN Goal_notes gn ON g.goal_id = gn.goal_id
                WHERE g.student_id_new = ? AND g.archived = 0
            ");
            $stmt->execute([$studentId]);
            $goals = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $goalsGrouped = [];

            foreach ($goals as $goal) {
                $goalId = $goal['goal_id'];
                if (!isset($goalsGrouped[$goalId])) {
                    $goalsGrouped[$goalId] = [
                        'goal_id' => $goalId,
                        'goal_description' => $goal['goal_description'],
                        'metadata_id' => $goal['metadata_id'],
                        'category_name' => $goal['category_name'],
                        'notes' => []
                    ];
                }

                if ($goal['note_id']) {
                    $stmtImage = $connection->prepare("SELECT report_image FROM Goal_notes WHERE note_id = ?");
                    $stmtImage->execute([$goal['note_id']]);
                    $resultImage = $stmtImage->fetch(PDO::FETCH_ASSOC);

                    $goal['report_image'] = $resultImage && $resultImage['report_image'] 
                        ? 'data:image/png;base64,' . base64_encode($resultImage['report_image'])
                        : null;

                    $goalsGrouped[$goalId]['notes'][] = [
                        'note_id' => $goal['note_id'],
                        'report_image' => $goal['report_image']
                    ];
                }
            }

            echo json_encode(array_values($goalsGrouped));
        } else {
            echo json_encode(["error" => "Invalid request, missing student_id"]);
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['goal_id']) && isset($_POST['goal_description'])) {
            $goalId = $_POST['goal_id'];
            $goalDescription = $_POST['goal_description'];

            $stmt = $connection->prepare("UPDATE Goals SET goal_description = ? WHERE goal_id = ?");
            $stmt->execute([$goalDescription, $goalId]);

            echo json_encode(["status" => "success", "message" => "Goal updated successfully."]);
        } else {
            echo json_encode(["status" => "error", "message" => "Invalid request, missing goal_id or goal_description"]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Invalid request method"]);
    }
} catch (Exception $e) {
    error_log($e->getMessage());
    echo json_encode(["status" => "error", "message" => "Error processing request: " . $e->getMessage()]);
}
?>





