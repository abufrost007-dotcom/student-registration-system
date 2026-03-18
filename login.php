<?php
require_once "config.php";

$error = "";
$registered = isset($_GET["registered"]);
$info = isset($_GET["verified"]) ? "Email verified. You can now log in." : "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verify_csrf();
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $ip = $_SERVER["REMOTE_ADDR"] ?? "unknown";

    if ($email === "" || $password === "") {
        $error = "Enter your email and password.";
    } else {
        $limit_stmt = $mysqli->prepare("SELECT COUNT(*) FROM login_attempts WHERE email = ? AND ip_address = ? AND attempted_at > (NOW() - INTERVAL 15 MINUTE)");
        if (!$limit_stmt) {
            $error = "Database is missing the login_attempts table. Run migrations.sql in phpMyAdmin.";
        } else {
            $limit_stmt->bind_param("ss", $email, $ip);
            $limit_stmt->execute();
            $limit_stmt->bind_result($attempts);
            $limit_stmt->fetch();
            $limit_stmt->close();
            if ($attempts >= 5) {
                $error = "Too many attempts. Please try again in 15 minutes.";
            } else {
                $stmt = $mysqli->prepare("SELECT id, password_hash, email_verified FROM students WHERE email = ?");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $stmt->bind_result($student_id, $password_hash, $email_verified);
                if ($stmt->fetch()) {
                    if (!$email_verified) {
                        $error = "Please verify your email before logging in.";
                    } elseif (password_verify($password, $password_hash)) {
                        session_regenerate_id(true);
                        unset($_SESSION["admin_id"]);
                        $_SESSION["student_id"] = $student_id;
                        $clear_attempts = $mysqli->prepare("DELETE FROM login_attempts WHERE email = ? AND ip_address = ?");
                        if ($clear_attempts) {
                            $clear_attempts->bind_param("ss", $email, $ip);
                            $clear_attempts->execute();
                        }
                        add_activity($student_id, "Logged in");
                        header("Location: dashboard.php");
                        exit();
                    }
                }
                $stmt = $mysqli->prepare("INSERT INTO login_attempts (email, ip_address) VALUES (?, ?)");
                if ($stmt) {
                    $stmt->bind_param("ss", $email, $ip);
                    $stmt->execute();
                }
                if ($error === "") {
                    $error = "Invalid login details.";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($APP_NAME); ?> - Login</title>
    <meta name="description" content="Secure student registration system with dashboards, enrollments, and course tracking.">
    <link rel="icon" type="image/png" href="<?php echo htmlspecialchars($FAVICON_PATH); ?>">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="container">
    <div class="topbar">
        <div class="brand"><?php echo brand_html(); ?></div>
        <button class="nav-toggle" type="button" data-nav-toggle>Menu</button>
        <div class="nav">
            <div class="theme-toggle">
                <button class="theme-switch" type="button" data-theme-toggle aria-pressed="false">
                    <span class="icon-moon">Moon</span>
                    <span class="icon-sun">Sun</span>
                    <span class="theme-knob" data-theme-knob data-state="light"></span>
                </button>
            </div>
            <a href="register.php">Create account</a>
        </div>
    </div>

    <div class="art-haze"></div>
    <div class="art-day"></div>
    <div class="art-field"></div>
    <div class="art-sky"></div>

    <div class="hero">
        <div class="card hero-card">
            <h1><span data-greeting></span></h1>
            <p><?php echo htmlspecialchars($HERO_LOGIN); ?></p>
        </div>
        <div class="card" style="max-width: 420px;">
            <h2 class="section-title">Login</h2>
        <?php if ($registered) : ?>
            <div class="alert">Account created. Please verify your email to log in.</div>
        <?php endif; ?>
        <?php if ($info !== "") : ?>
            <div class="alert"><?php echo htmlspecialchars($info); ?></div>
        <?php endif; ?>
        <?php if ($error !== "") : ?>
            <div class="alert error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form method="post" data-login-form>
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required>
            </div>
                <div class="form-group">
                    <label>Password</label>
                    <div class="password-field">
                        <input id="login_password" type="password" name="password" required>
                        <button type="button" aria-label="Toggle password visibility" data-password-toggle="login_password" data-eye-state="closed"></button>
                    </div>
                </div>
                <button class="btn" type="submit">Login</button>
            </form>
            <p class="footer-note"><a href="forgot.php">Forgot password?</a></p>
        </div>
    </div>
</div>
<div class="footer">
    <div><strong><?php echo htmlspecialchars($FOOTER_NAME); ?></strong></div>
    <div><?php echo htmlspecialchars($FOOTER_ADDRESS); ?></div>
    <div><?php echo htmlspecialchars($FOOTER_WEBSITE); ?></div>
    <div><?php echo htmlspecialchars($FOOTER_SOCIALS); ?></div>
</div>
<script src="assets/app.js"></script>
</body>
</html>

