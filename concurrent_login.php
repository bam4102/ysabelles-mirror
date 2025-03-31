<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Session Expired - Ysabelle's Bridalshop</title>
    <link rel="icon" type="image/png" href="./assets/img/sitelogo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f8f9fa;
        }
        .message-container {
            text-align: center;
            padding: 2rem;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            max-width: 500px;
            width: 90%;
        }
        .countdown {
            font-size: 2rem;
            font-weight: bold;
            color: #FC4A49;
            margin: 1rem 0;
        }
        .redirect-message {
            color: #6c757d;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="message-container">
        <img src="./assets/img/loginlogo.png" class="img-fluid mb-4" style="max-height: 150px;">
        <h2 class="mb-3">Session Expired</h2>
        <p class="mb-4">Your session has expired because you logged in from another device or browser.</p>
        <div class="countdown">5</div>
        <p class="redirect-message">Redirecting to login page...</p>
    </div>

    <script>
        let countdown = 5;
        const countdownElement = document.querySelector('.countdown');
        
        const timer = setInterval(() => {
            countdown--;
            countdownElement.textContent = countdown;
            
            if (countdown <= 0) {
                clearInterval(timer);
                window.location.href = './login_page.php';
            }
        }, 1000);
    </script>
</body>
</html> 