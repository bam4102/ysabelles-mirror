<?php
session_start();
?>
<!-- ADMIN ACCOUNT -->
<!-- ysabellesadmin -->
<!-- 4xTB6Upd&~:J5ZA -->

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Ysabelle's Bridalshop - Login</title>
  <link rel="icon" type="image/png" href="./assets/img/sitelogo.png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="./assets/css/login_page.css" rel="stylesheet">
  <link href="./assets/css/global.css" rel="stylesheet">
</head>

<body>
  <div class="header">
    <h1><br></h1>
  </div>
  <div class="login-container" style="padding: 60px;">
    <div class="container text-center">
      <img src="./assets/img/loginlogo.png" class="img-fluid w-100 d-block mx-auto"
        style="max-height: 300px; padding-bottom: 30px;">
    </div>
    <?php if (isset($_SESSION['error'])): ?>
      <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <?php 
        echo $_SESSION['error'];
        unset($_SESSION['error']);
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>
    <form action="./assets/controllers/login/login_process.php" method="POST">
      <input type="hidden" id="deviceUUID" name="deviceUUID">
      <div class="form-group">
        <label for="username">Username</label>
        <input type="text" id="username" name="username" class="form-control" required>
      </div>
      <div class="form-group mt-3">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" class="form-control" required>
      </div>
      <button type="submit" class="btn w-100 mt-3" style="color: #fff; background-color: #FC4A49">
        Sign in
      </button>
    </form>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="/js/device_fingerprint.js"></script>
  <!-- <script src="./assets/scripts/login_page.js"></script> --> 
</body>

</html>