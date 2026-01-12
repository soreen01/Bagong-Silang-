<?php
if (!isset($page_title)) $page_title = 'Bagong Silang Portal';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?php echo $page_title; ?></title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<nav class="navbar">
  <div class="nav-container">
    <a href="index.php" class="logo">🏠 Bagong Silang Portal</a>
    <div class="nav-links">
      <?php if(isLoggedIn()): ?>
        <?php if(isAdmin()): ?>
          <a href="admin_dashboard.php">Dashboard</a>
          <a href="admin_manage.php">Manage Requests</a>
          <a href="admin_users.php">Residents</a>
        <?php else: ?>
          <a href="resident_dashboard.php">Dashboard</a>
          <a href="resident_request.php">Request Permit</a>
          <a href="resident_track.php">Track</a>
          <a href="resident_history.php">History</a>
        <?php endif; ?>
        <a href="logout.php">Logout</a>
      <?php else: ?>
        <a href="index.php">Home</a>
        <a href="login.php">Login</a>
        <a href="register.php">Register</a>
      <?php endif; ?>
    </div>
  </div>
</nav>
<main class="container">
