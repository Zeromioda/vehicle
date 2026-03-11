<?php
include 'db.php';

if(isset($_GET['token'])){
    $token = $_GET['token'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE verification_token = ? AND token_expiry > NOW()");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if($user){
        $update = $pdo->prepare("UPDATE users SET is_verified = 1, verification_token = NULL, token_expiry = NULL WHERE id = ?");
        $update->execute([$user['id']]);

        echo "<h2>Email verified successfully!</h2>";
        echo "<p>You can now <a href='driver_login.php'>login</a>.</p>";
    } else {
        echo "<h2>Invalid or expired verification link.</h2>";
    }
} else {
    echo "<h2>No verification token provided.</h2>";
}
?>