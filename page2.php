<?php
session_start(); // Start a session to store the notification

// This block handles the AJAX request from page1.php
if (isset($_POST['action']) && $_POST['action'] === 'set_notification') {
    $_SESSION['notification_message'] = $_POST['message'];
    $_SESSION['notification_time'] = time(); // Optional: store timestamp
    echo "Notification set in session!"; // Response sent back to page1.php's AJAX call
    exit(); // Stop further execution for AJAX request
}

// This block runs when page2.php is loaded normally (not via AJAX)
$notification_message = null;
if (isset($_SESSION['notification_message'])) {
    $notification_message = $_SESSION['notification_message'];
    // IMPORTANT: Clear the notification from the session *after* retrieving it
    // so it doesn't show up again on subsequent refreshes unless reset by page1.php
    unset($_SESSION['notification_message']);
    unset($_SESSION['notification_time']);
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Page 2</title>
    <style>
        .notification-box {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
    </style>
    <script>
        // JavaScript to display the notification as a browser alert on load
        window.onload = function() {
            <?php if ($notification_message): ?>
                // You can choose to use alert or just rely on the HTML display
                alert("New Notification: <?php echo addslashes($notification_message); ?>");
            <?php endif; ?>
        };
    </script>
</head>
<body>
    <h1>Welcome to Page 2</h1>

    <?php if ($notification_message): ?>
        <div class="notification-box">
            <strong>Notification:</strong> <?php echo htmlspecialchars($notification_message); ?>
        </div>
    <?php else: ?>
        <p>No new notifications.</p>
    <?php endif; ?>

</body>
</html>