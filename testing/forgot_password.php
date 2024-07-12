<?php
include('./users/db.php');

// Error Reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (isset($_POST['forgot_password'])) {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo '<p class="error">Invalid email format!</p>';
        exit;
    }
    
    // Check if the email exists
    $query = $connection->prepare("SELECT * FROM accounts WHERE email=:email");
    $query->bindParam("email", $email, PDO::PARAM_STR);
    $query->execute();
    
    if ($query->rowCount() > 0) {
        // Generate a unique token
        $token = bin2hex(random_bytes(50));

        // Set token expiry
        $expiry = date("Y-m-d H:i:s", strtotime("+15 minutes"));

        // Update the user record with the token
        $query = $connection->prepare("UPDATE accounts SET reset_token=:token, reset_token_expiry=:expiry WHERE email=:email");
        $query->bindParam("token", $token, PDO::PARAM_STR);
        $query->bindParam("expiry", $expiry, PDO::PARAM_STR);
        $query->bindParam("email", $email, PDO::PARAM_STR);
        $query->execute();

        // Send the reset link to the user's email
        $resetLink = "https://bfactor.org/reset_password.php?token=$token";
        mail($email, "Password Reset Request", "Click the link to reset your password: $resetLink");
        
        header("Location: login.php?reset=1");
        exit();
    }  // This closing brace was missing
}

if (isset($_POST['reset_password'])) {
    $token = $_POST['token'];
    $newPassword = $_POST['newPassword'];
    $password_hash = password_hash($newPassword, PASSWORD_BCRYPT);

    // Check token validity
    $query = $connection->prepare("SELECT * FROM accounts WHERE reset_token=:token AND reset_token_expiry > NOW()");
    $query->bindParam("token", $token, PDO::PARAM_STR);
    $query->execute();

    if ($query->rowCount() > 0) {
        // Update the password and clear the reset token
        $query = $connection->prepare("UPDATE accounts SET password=:password_hash, reset_token=NULL, reset_token_expiry=NULL WHERE reset_token=:token");
        $query->bindParam("password_hash", $password_hash, PDO::PARAM_STR);
        $query->bindParam("token", $token, PDO::PARAM_STR);
        $query->execute();
        
        echo '<p class="success">Password reset successful!</p>';
    } else {
        echo '<p class="error">Invalid or expired token!</p>';
    }
}
?>

