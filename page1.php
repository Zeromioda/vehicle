<!DOCTYPE html>
<html>
<head>
    <title>Page 1</title>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script>
        $(document).ready(function(){
            $('#notifyButton').click(function(){
                $.post('page2.php', { action: 'set_notification', message: 'Something happened on Page 1!' }, function(response){
                    // This response is from page2.php's server-side handling the AJAX request
                    console.log(response); // Will log "Notification set in session!"
                    alert('Notification state set for Page 2. Open or refresh Page 2 to see it.');
                });
            });
        });
    </script>
</head>
<body>
    <h1>Welcome to Page 1</h1>
    <button id="notifyButton">Set Notification for Page 2</button>
    <p>This button sends a message to the server, which Page 2 can pick up.</p>
    <p>You can still navigate to <a href="page2.php" target="_blank">Page 2</a> independently.</p>
</body>
</html>