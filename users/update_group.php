<?php
session_start();
include('auth_session.php');
include('db.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $groupId = $_POST['group_id'];
    $groupName = $_POST['group_name'];

    try {
        $stmt = $connection->prepare("UPDATE Groups SET group_name = ? WHERE group_id = ?");
        $stmt->execute([$groupName, $groupId]);
        echo "Group updated successfully.";
    } catch (PDOException $e) {
        error_log($e->getMessage());
        echo "Error updating group: " . $e->getMessage();
    }
}
?>
