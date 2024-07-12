<?php
session_start();

// Error reporting for development
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);

include('db.php');

if (isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    try {
        $query = $connection->prepare("SELECT * FROM accounts WHERE email=:email");
        $query->bindParam("email", $email, PDO::PARAM_STR);
        $query->execute();
        
        $result = $query->fetch(PDO::FETCH_ASSOC);  
        
        if (!$result) {
            echo '<p class="error">Username or password is incorrect!</p>';
        } else {
            if (password_verify($password, $result['password'])) {
                // Regenerate session ID to prevent session fixation
                session_regenerate_id(true);
                
                $_SESSION['user'] = $result['email'];
                $_SESSION['account_id'] = $result['id']; // Add account_id to session

                // Fetching additional information now that the user is verified
                $accountId = $result['id'];
                
                $teacherQuery = $connection->prepare("SELECT teacher_id, school_id, is_admin, approved, program_id FROM Teachers WHERE account_id = :accountId");
                $teacherQuery->bindParam("accountId", $accountId, PDO::PARAM_INT);
                $teacherQuery->execute();
                
                $teacherResult = $teacherQuery->fetch(PDO::FETCH_ASSOC);
                
                if ($teacherResult) {
                    $_SESSION['teacher_id'] = $teacherResult['teacher_id'];
                    $_SESSION['school_id'] = $teacherResult['school_id'];
                    $_SESSION['is_admin'] = $teacherResult['is_admin'] == 1; // Assuming 'is_admin' is the column name
                    $_SESSION['is_approved'] = $teacherResult['approved'] == 1;
                    $_SESSION['program_id'] = $teacherResult['program_id']; // Set program_id in session
                    
                    if (!$_SESSION['is_approved']) {
                        header("Location: ../not_approved.php");
                        exit();
                    }
                } else {
                    echo '<p class="error">No teacher ID associated with this account.</p>';
                    exit();
                }               
                
                // Redirect to the desired page after successful login
                header("Location: ../students.php");
                exit(); 
            } else {
                echo '<p class="error">Username or password is incorrect!</p>';
            }
        }
    } catch (PDOException $e) {
        echo "Database Error: " . $e->getMessage(); // Show the exception error message
    }
}
?>
