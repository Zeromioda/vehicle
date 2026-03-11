<?php
session_start();
header('Content-Type: application/json');

if (isset($_POST['action']) && $_POST['action'] === 'notify_admin_arrival' && isset($_POST['driverName']) && isset($_POST['tripOrigin']) && isset($_POST['tripDestination'])) {
    $driverName = htmlspecialchars($_POST['driverName']);
    $tripOrigin = htmlspecialchars($_POST['tripOrigin']);
    $tripDestination = htmlspecialchars($_POST['tripDestination']);

    $_SESSION['admin_notification'] = [
        'message' => "Driver {$driverName} has *arrived* at {$tripDestination} for the trip from {$tripOrigin}.",
        'timestamp' => time()
    ];

    echo json_encode(['status' => 'success', 'message' => 'Admin notification set.']);
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Invalid request.']);
?>