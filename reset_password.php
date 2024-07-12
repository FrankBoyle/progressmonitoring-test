<?php
include('./users/db.php');

// Check if token is set in the URL
if (isset($_GET['token'])) {
    $token = $_GET['token'];
    ?>
    <form action="forgot_password.php" method="post">
        <input type="hidden" name="token" value="<?php echo $token; ?>">
        <label for="newPassword">New Password:</label>
        <input type="password" name="newPassword" required>
        <input type="submit" name="reset_password" value="Reset Password">
    </form>
    <?php
} else {
    echo "<p>Invalid token or token not provided.</p>";
}
?>