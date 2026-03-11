<?php
require 'vendor/autoload.php'; // PHPMailer
include 'db.php';
session_start();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if(isset($_POST['register'])) {
    $name = trim($_POST['name']);
    $age = isset($_POST['age']) ? (int)$_POST['age'] : null;
    $gender = $_POST['gender'] ?? null;
    $license_number = trim($_POST['license_number']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $passwordPlain = $_POST['password'];

    // Check for duplicates
    $check = $pdo->prepare("
        SELECT COUNT(*) FROM users u 
        JOIN drivers d ON u.username = d.name
        WHERE u.email = ? OR d.phone = ? OR d.license_number = ?
    ");
    $check->execute([$email, $phone, $license_number]);

    if($check->fetchColumn() > 0){
        echo "<script>alert('Driver with same email, phone, or license number already exists.');</script>";
    } else {
        // Handle photo upload
        if(!empty($_FILES['photo']['name'])){
            $origName = $_FILES['photo']['name'];
            $ext = pathinfo($origName, PATHINFO_EXTENSION);
            $newName = uniqid('drv_') . ($ext ? '.' . $ext : '');
            $uploadDir = 'uploads/';
            if(!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            $target = $uploadDir . $newName;

            if(!move_uploaded_file($_FILES['photo']['tmp_name'], $target)){
                echo "<script>alert('Failed to upload photo.');</script>";
                exit;
            }
        } else {
            $newName = null;
        }

        // Insert into drivers
        $stmtDriver = $pdo->prepare("INSERT INTO drivers (name, license_number, phone, photo) VALUES (?, ?, ?, ?)");
        $stmtDriver->execute([$name, $license_number, $phone, $newName]);
        $driver_id = $pdo->lastInsertId();

        // Insert into users
        $token = bin2hex(random_bytes(32));
        $expiry = date("Y-m-d H:i:s", strtotime("+1 hour"));
        $passwordHash = password_hash($passwordPlain, PASSWORD_DEFAULT);

        $stmtUser = $pdo->prepare("
            INSERT INTO users (username, email, password, role, verification_token, token_expiry)
            VALUES (?, ?, ?, 'driver', ?, ?)
        ");
        $stmtUser->execute([$name, $email, $passwordHash, $token, $expiry]);

        // Send verification email via PHPMailer
        $verificationLink = "http://localhost/Vehicle/driver_verify.php?token=$token";
        $mail = new PHPMailer(true);

        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com'; // or your SMTP server
            $mail->SMTPAuth   = true;
            $mail->Username   = 'youremail@gmail.com'; // your email
            $mail->Password   = 'yourapppassword';     // SMTP password / app password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            // Recipients
            $mail->setFrom('no-reply@yourdomain.com', 'Fleet Management');
            $mail->addAddress($email, $name);

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Verify Your Email - Fleet Management';
            $mail->Body    = "Hi $name,<br><br>Please verify your email by clicking the link below:<br>
                              <a href='$verificationLink'>$verificationLink</a><br><br>
                              This link expires in 1 hour.";
            $mail->AltBody = "Hi $name,\n\nPlease verify your email by visiting this link: $verificationLink\n\nThis link expires in 1 hour.";

            $mail->send();
            echo "<script>alert('Registration successful! Please check your email to verify your account.'); window.location='driver_login.php';</script>";
            exit;
        } catch (Exception $e) {
            echo "<script>alert('Registration successful, but email could not be sent. Mailer Error: {$mail->ErrorInfo}');</script>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Driver Registration - Fleet Management</title>
<style>
body {margin:0;font-family:Arial,sans-serif;display:flex;flex-direction:column;align-items:center;}
.title {background:linear-gradient(90deg,#0d6efd,black);color:white;padding:1.5rem;text-align:center;width:100%;height:100px;}
.bc_login {background-color:#187bcd;box-shadow:0 6px 22px rgba(0,0,0,0.05);text-align:center;width:400px;padding:30px;border-radius:10px;margin-top:40px;}
.signup {height:40px;width:90%;margin-bottom:20px;border-radius:5px;padding:10px;border:none;font-size:18px;}
input[type="file"] {width:90%;margin-bottom:20px;background-color:white;padding:10px;border-radius:5px;border:none;}
button.signup {background-color:#0d6efd;font-size:20px;color:white;cursor:pointer;}
button.signup:hover {background-color:#0b5ed7;}
a{color:black;}
#passwordMessage{font-weight:bold;margin-top:-10px;margin-bottom:15px;}
</style>
</head>
<body>
<div class="title">
<h1>Fleet & Transport Management System</h1>
</div>

<form method="post" class="bc_login" enctype="multipart/form-data" onsubmit="return validatePasswords()">
    <div><input class="signup" type="text" name="name" placeholder="Name" required></div>
    <div><input class="signup" type="number" name="age" placeholder="Age" required></div>
    <div>
        <input type="radio" name="gender" value="male" required> Male
        <input type="radio" name="gender" value="female" required> Female
    </div><br>
    <div><input class="signup" type="text" name="phone" placeholder="Phone Number" required></div>
    <div><h2>Upload Photo:</h2><input type="file" name="photo" accept="image/*" required></div>
    <div><input class="signup" type="text" name="license_number" placeholder="License ID no." required></div>
    <div><input class="signup" type="email" name="email" placeholder="Email Address" required></div>
    <div><input class="signup" type="password" id="password" name="password" placeholder="Password" required></div>
    <div><input class="signup" type="password" id="confirm_password" name="confirm_password" placeholder="Confirm Password" required></div>
    <p id="passwordMessage"></p>
    <div><button class="signup" type="submit" name="register">Register</button></div>
    <p>Already have an account? <a href="driver_login.php">Login here!</a></p>
</form>

<script>
const password = document.getElementById("password");
const confirm_password = document.getElementById("confirm_password");
const message = document.getElementById("passwordMessage");

function validatePasswords(){
    if(password.value !== confirm_password.value){
        alert("Passwords do not match!");
        confirm_password.focus();
        return false;
    }
    return true;
}

confirm_password.addEventListener("keyup", ()=>{
    if(confirm_password.value === ""){
        message.textContent = "";
        return;
    }
    if(password.value === confirm_password.value){
        message.style.color = "lime";
        message.textContent = "✅ Passwords match";
    } else {
        message.style.color = "red";
        message.textContent = "❌ Passwords do not match";
    }
});
</script>
</body>
</html>