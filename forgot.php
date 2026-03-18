<?php
require_once "config.php";

$message = "";
$error = "";
$reset_link = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verify_csrf();
    $email = trim($_POST["email"] ?? "");
    if ($email === "") {
        $error = "Enter the email linked to your account.";
    } else {
        $stmt = $mysqli->prepare("SELECT id FROM students WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->bind_result($student_id);
        if ($stmt->fetch()) {
            $stmt->close();
            $token = bin2hex(random_bytes(16));
            $insert = $mysqli->prepare("INSERT INTO password_resets (student_id, token) VALUES (?, ?)");
            $insert->bind_param("is", $student_id, $token);
            $insert->execute();
            $reset_link = "reset.php?token=" . urlencode($token) . "&email=" . urlencode($email);
            $message = "Password reset link generated below.";
        } else {
            $error = "No account found with that email.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($APP_NAME); ?> - Reset Password</title>
    <meta name="description" content="Reset your student account password.">
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

    <div class="hero">
        <div class="card hero-card">
            <h1>Recover your account</h1>
            <p>We will generate a secure link to reset your password.</p>
        </div>
        <div class="card" style="max-width: 420px;">
            <h2 class="section-title">Password Reset</h2>
            <?php if ($message !== "") : ?>
                <div class="alert"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            <?php if ($error !== "") : ?>
                <div class="alert error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if ($reset_link !== "") : ?>
                <div class="alert">Reset link: <a href="<?php echo htmlspecialchars($reset_link); ?>">Click here</a></div>
            <?php endif; ?>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" required>
                </div>
                <button class="btn" type="submit">Generate Link</button>
            </form>
        </div>
    </div>
</div>
<script src="assets/app.js"></script>
</body>
</html>
