<?php
require_once "config.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($APP_NAME); ?> - Page Not Found</title>
    <meta name="description" content="Page not found.">
    <link rel="icon" type="image/svg+xml" href="assets/favicon.svg">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="container">
    <div class="topbar">
        <div class="brand"><?php echo htmlspecialchars($APP_NAME); ?></div>
        <div class="nav">
            <a href="login.php">Login</a>
            <a href="dashboard.php">Dashboard</a>
        </div>
    </div>
    <div class="card">
        <h2 class="section-title">Page Not Found</h2>
        <p class="muted">The page you requested could not be found. Use the navigation to continue.</p>
    </div>
</div>
</body>
</html>
