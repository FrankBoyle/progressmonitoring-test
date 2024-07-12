<?php
session_start();
include('db.php');

// Check if the user is logged in
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

// Check if the user is a teacher and is approved
$user_email = $_SESSION['user'];
$query = $connection->prepare("SELECT id FROM accounts WHERE email = :email");
$query->bindParam(":email", $user_email, PDO::PARAM_STR);
$query->execute();
$account = $query->fetch(PDO::FETCH_ASSOC);

if ($account) {
    $account_id = $account['id'];
    $teacherQuery = $connection->prepare("SELECT approved FROM Teachers WHERE account_id = :account_id");
    $teacherQuery->bindParam(":account_id", $account_id, PDO::PARAM_INT);
    $teacherQuery->execute();
    $teacher = $teacherQuery->fetch(PDO::FETCH_ASSOC);

    if (!$teacher || $teacher['approved'] == 0) {
        $_SESSION['is_approved'] = false;
    } else {
        $_SESSION['is_approved'] = true;
    }
} else {
    header("Location: login.php");
    exit();
}
?>
