<?php
session_start();
session_destroy(); // End the current session
?>

<!DOCTYPE html>
<html>
<head>
    <title>Unauthorized Access</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Meta refresh to redirect after 5 seconds -->
    <meta http-equiv="refresh" content="5; url=login_page.php">
</head>
<body>
    <div class="container mt-5">
        <div class="alert alert-danger" role="alert">
            <h4 class="alert-heading">Unauthorized Access!</h4>
            <p>Your account has insufficient permissions.</p>
            <hr>
            <p class="mb-0">Error code: 403 - Forbidden</p>
            <p class="mt-3">
                You will be redirected to the login page in <span id="countdown">5</span> seconds.
            </p>
        </div>
    </div>

    <!-- JavaScript countdown -->
    <script>
        let seconds = 5;
        const countdownElement = document.getElementById('countdown');

        const interval = setInterval(() => {
            seconds--;
            if (seconds < 0) {
                clearInterval(interval);
            } else {
                countdownElement.textContent = seconds;
            }
        }, 1000);
    </script>
</body>
</html>
