<?php
require_once "config.php";
require_login();

$student_id = current_student_id();
$message = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verify_csrf();
    $current = $_POST["current_password"] ?? "";
    $new = $_POST["new_password"] ?? "";
    if ($current === "" || $new === "") {
        $error = "Please fill in all fields.";
    } else {
        $stmt = $mysqli->prepare("SELECT password_hash FROM students WHERE id = ?");
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $stmt->bind_result($hash);
        if ($stmt->fetch() && password_verify($current, $hash)) {
            $stmt->close();
            $new_hash = password_hash($new, PASSWORD_BCRYPT);
            $update = $mysqli->prepare("UPDATE students SET password_hash = ? WHERE id = ?");
            $update->bind_param("si", $new_hash, $student_id);
            if ($update->execute()) {
                $message = "Password updated successfully.";
                add_activity($student_id, "Changed password");
            } else {
                $error = "Password update failed.";
            }
        } else {
            $error = "Current password is incorrect.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($APP_NAME); ?> - Change Password</title>
    <meta name="description" content="Update your account password.">
    <link rel="icon" type="image/svg+xml" href="assets/favicon.svg">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="container">
    <div class="topbar">
        <div class="brand"><?php echo htmlspecialchars($APP_NAME); ?></div>
        <button class="nav-toggle" type="button" data-nav-toggle>Menu</button>
        <div class="nav">
            <a href="dashboard.php">Dashboard</a>
            <a href="profile.php">Profile</a>
        </div>
    </div>
    <div class="card" style="max-width: 520px;">
        <h2 class="section-title">Change Password</h2>
        <?php if ($message !== "") : ?>
            <div class="alert"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <?php if ($error !== "") : ?>
            <div class="alert error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
            <div class="form-group">
                <label>Current Password</label>
                <div class="password-field">
                    <input id="current_password" type="password" name="current_password" required>
                    <button type="button" aria-label="Toggle password visibility" data-password-toggle="current_password" data-eye-state="closed"></button>
                </div>
            </div>
            <div class="form-group">
                <label>New Password</label>
                <div class="password-field">
                    <input id="new_password" type="password" name="new_password" required>
                    <button type="button" aria-label="Toggle password visibility" data-password-toggle="new_password" data-eye-state="closed"></button>
                </div>
            </div>
            <button class="btn" type="submit">Update Password</button>
        </form>
    </div>
</div>
<script src="assets/app.js"></script>
</body>
</html>
