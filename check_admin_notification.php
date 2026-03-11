<?php
session_start();
header('Content-Type: application/json');

$response = ['hasNotification' => false, 'message' => null];

if (isset($_SESSION['admin_notification'])) {
    $response['hasNotification'] = true;
    $response['message'] = $_SESSION['admin_notification']['message'];
    unset($_SESSION['admin_notification']);
}

echo json_encode($response);
?>