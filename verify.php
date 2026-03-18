<?php
require_once "config.php";

$token = trim($_GET["token"] ?? "");
$email = trim($_GET["email"] ?? "");
$message = "Invalid verification link.";

if ($token !== "" && $email !== "") {
    $stmt = $mysqli->prepare("SELECT id FROM students WHERE email = ? AND email_token = ? AND email_verified = 0");
    $stmt->bind_param("ss", $email, $token);
    $stmt->execute();
    $stmt->bind_result($student_id);
    if ($stmt->fetch()) {
        $stmt->close();
        $update = $mysqli->prepare("UPDATE students SET email_verified = 1, email_token = NULL WHERE id = ?");
        $update->bind_param("i", $student_id);
        $update->execute();
        $message = "Email verified. You can now log in.";
        header("Location: login.php?verified=1");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($APP_NAME); ?> - Verify Email</title>
    <meta name="description" content="Verify your student registration account.">
    <link rel="icon" type="image/svg+xml" href="assets/favicon.svg">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="container">
    <div class="topbar">
        <div class="brand"><?php echo htmlspecialchars($APP_NAME); ?></div>
        <div class="nav">
            <a href="login.php">Login</a>
        </div>
    </div>
    <div class="card">
        <h2 class="section-title">Email Verification</h2>
        <div class="alert"><?php echo htmlspecialchars($message); ?></div>
    </div>
</div>
</body>
</html>
