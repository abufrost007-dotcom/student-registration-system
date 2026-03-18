<?php
require_once "config.php";

$token = trim($_GET["token"] ?? "");
$email = trim($_GET["email"] ?? "");
$message = "";
$error = "";
$reset_ready = false;

if ($token !== "" && $email !== "") {
    $lookup = $mysqli->prepare(
        "SELECT pr.id
         FROM password_resets pr
         JOIN students s ON pr.student_id = s.id
         WHERE pr.token = ? AND s.email = ? AND pr.used = 0 AND pr.created_at > (NOW() - INTERVAL 1 DAY)"
    );
    $lookup->bind_param("ss", $token, $email);
    $lookup->execute();
    $lookup->bind_result($reset_lookup_id);
    $reset_ready = $lookup->fetch() ? true : false;
    $lookup->close();
    if (!$reset_ready) {
        $error = "Invalid or expired reset link.";
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verify_csrf();
    $token = trim($_POST["token"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    if ($token === "" || $email === "" || $password === "") {
        $error = "Please fill in all fields.";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters.";
    } else {
        $stmt = $mysqli->prepare(
            "SELECT pr.id, pr.student_id
             FROM password_resets pr
             JOIN students s ON pr.student_id = s.id
             WHERE pr.token = ? AND s.email = ? AND pr.used = 0 AND pr.created_at > (NOW() - INTERVAL 1 DAY)"
        );
        $stmt->bind_param("ss", $token, $email);
        $stmt->execute();
        $stmt->bind_result($reset_id, $student_id);
        if ($stmt->fetch()) {
            $stmt->close();
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $update = $mysqli->prepare("UPDATE students SET password_hash = ? WHERE id = ?");
            $update->bind_param("si", $hash, $student_id);
            $update->execute();
            $mark = $mysqli->prepare("UPDATE password_resets SET used = 1 WHERE student_id = ?");
            $mark->bind_param("i", $student_id);
            $mark->execute();
            $message = "Password reset successful. You can log in now.";
            $reset_ready = false;
        } else {
            $error = "Invalid or expired reset link.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($APP_NAME); ?> - Set New Password</title>
    <meta name="description" content="Set a new password for your student account.">
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
            <h1>Choose a new password</h1>
            <p>Passwords are stored securely. Set a strong one you will remember.</p>
        </div>
        <div class="card" style="max-width: 420px;">
            <h2 class="section-title">New Password</h2>
            <?php if ($message !== "") : ?>
                <div class="alert"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            <?php if ($error !== "") : ?>
                <div class="alert error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if ($reset_ready) : ?>
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                    <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
                    <div class="form-group">
                        <label>New Password</label>
                        <div class="password-field">
                            <input id="reset_password" type="password" name="password" required minlength="8">
                            <button type="button" aria-label="Toggle password visibility" data-password-toggle="reset_password" data-eye-state="closed"></button>
                        </div>
                    </div>
                    <button class="btn" type="submit">Update Password</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>
<script src="assets/app.js"></script>
</body>
</html>
