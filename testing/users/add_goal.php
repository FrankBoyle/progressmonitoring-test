<?php
session_start();

include('auth_session.php');
include('db.php');

header('Content-Type: application/json');

try {
    if (isset($_POST['student_id'], $_POST['goal_description'], $_POST['goal_date'], $_POST['metadata_option'])) {
        $studentId = $_POST['student_id'];
        $goalDescription = $_POST['goal_description'];
        $goalDate = $_POST['goal_date'];
        $metadataOption = $_POST['metadata_option'];
        $schoolId = $_SESSION['school_id'];
        $newMetadataId = null;

        if (empty($studentId) || empty($goalDescription) || empty($goalDate) || empty($metadataOption) || empty($schoolId)) {
            throw new Exception('Missing required parameters.');
        }

        if ($metadataOption === 'existing') {
            if (!isset($_POST['existing_category_id'])) {
                throw new Exception('Existing category ID is required.');
            }
            $newMetadataId = $_POST['existing_category_id'];
        } else if ($metadataOption === 'template') {
            if (!isset($_POST['template_id'])) {
                throw new Exception('Template ID is required.');
            }

            $templateId = $_POST['template_id'];

            // Copy the template to create a new metadata entry
            $stmt = $connection->prepare("SELECT * FROM Metadata WHERE metadata_id = ?");
            $stmt->execute([$templateId]);
            $template = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$template) {
                throw new Exception('Template not found.');
            }

            $stmt = $connection->prepare("
                INSERT INTO Metadata (school_id, metadata_template, category_name, score1_name, score2_name, score3_name, score4_name, score5_name, score6_name, score7_name, score8_name, score9_name, score10_name) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $template['school_id'],
                0, // Not a template
                $template['category_name'],
                $template['score1_name'],
                $template['score2_name'],
                $template['score3_name'],
                $template['score4_name'],
                $template['score5_name'],
                $template['score6_name'],
                $template['score7_name'],
                $template['score8_name'],
                $template['score9_name'],
                $template['score10_name']
            ]);

            $newMetadataId = $connection->lastInsertId();
        } else {
            throw new Exception('Invalid metadata option.');
        }

        $stmt = $connection->prepare("
            INSERT INTO Goals (student_id_new, goal_description, goal_date, school_id, metadata_id) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$studentId, $goalDescription, $goalDate, $schoolId, $newMetadataId]);

        echo json_encode(["message" => "Goal added successfully."]);
    } else {
        throw new Exception('Invalid request, missing required parameters.');
    }
} catch (Exception $e) {
    error_log("Error adding goal: " . $e->getMessage());
    echo json_encode(["error" => "Error adding goal: " . $e->getMessage()]);
}
?>
