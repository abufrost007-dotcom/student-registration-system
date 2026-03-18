<?php
require_once "config.php";

$error = "";
$message = "";
$setup_mode = false;

$count = $mysqli->query("SELECT COUNT(*) AS total FROM admins")->fetch_assoc();
if (($count["total"] ?? 0) == 0) {
    $setup_mode = true;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verify_csrf();
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";
    if ($username === "" || $password === "") {
        $error = "Enter your username and password.";
    } else {
        if ($setup_mode && isset($_POST["setup_admin"])) {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $mysqli->prepare("INSERT INTO admins (username, password_hash) VALUES (?, ?)");
            $stmt->bind_param("ss", $username, $hash);
            if ($stmt->execute()) {
                $message = "Admin account created. Log in below.";
                $setup_mode = false;
            } else {
                $error = "Admin setup failed.";
            }
        } else {
            $stmt = $mysqli->prepare("SELECT id, password_hash FROM admins WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $stmt->bind_result($admin_id, $hash);
            if ($stmt->fetch() && password_verify($password, $hash)) {
                session_regenerate_id(true);
                unset($_SESSION["student_id"]);
                $_SESSION["admin_id"] = $admin_id;
                add_admin_activity($admin_id, "Logged in");
                header("Location: admin_dashboard.php");
                exit();
            }
            $error = "Invalid admin credentials.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($APP_NAME); ?> - Admin Login</title>
    <meta name="description" content="Admin portal for course and unit management.">
    <link rel="icon" type="image/png" href="<?php echo htmlspecialchars($FAVICON_PATH); ?>">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="container">
    <div class="topbar">
        <div class="brand"><?php echo brand_html(); ?> Admin</div>
        <button class="nav-toggle" type="button" data-nav-toggle>Menu</button>
        <div class="nav">
            <a href="login.php">Student Login</a>
        </div>
    </div>

    <div class="hero">
        <div class="card hero-card">
            <h1>Registrar Console</h1>
            <p>Manage courses, units, lecturers, and grades from one secure space.</p>
        </div>
        <div class="card" style="max-width: 420px;">
            <h2 class="section-title"><?php echo $setup_mode ? "Create Admin" : "Admin Login"; ?></h2>
            <?php if ($message !== "") : ?>
                <div class="alert"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            <?php if ($error !== "") : ?>
                <div class="alert error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                <?php if ($setup_mode) : ?>
                    <input type="hidden" name="setup_admin" value="1">
                <?php endif; ?>
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <div class="password-field">
                        <input id="admin_password" type="password" name="password" required>
                        <button type="button" aria-label="Toggle password visibility" data-password-toggle="admin_password" data-eye-state="closed"></button>
                    </div>
                </div>
                <button class="btn" type="submit"><?php echo $setup_mode ? "Create Admin" : "Login"; ?></button>
            </form>
            <?php if ($setup_mode) : ?>
                <p class="footer-note">Create the first admin account to unlock management tools.</p>
            <?php endif; ?>
        </div>
    </div>
</div>
<script src="assets/app.js"></script>
</body>
</html>
