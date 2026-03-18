<?php
require_once "config.php";

$error = "";
$verify_link = "";
$message = "";
$courses = $mysqli->query("SELECT code, name FROM courses ORDER BY code")->fetch_all(MYSQLI_ASSOC);
$valid_programs = array_column($courses, "code");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verify_csrf();
    $reg_no = trim($_POST["reg_no"] ?? "");
    $first_name = trim($_POST["first_name"] ?? "");
    $last_name = trim($_POST["last_name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $program = trim($_POST["program"] ?? "");
    $year_level = intval($_POST["year_level"] ?? 1);
    $password = $_POST["password"] ?? "";

    if ($reg_no === "" || $first_name === "" || $last_name === "" || $email === "" || $program === "" || $password === "") {
        $error = "Please fill in all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Enter a valid email address.";
    } elseif (!in_array($program, $valid_programs, true)) {
        $error = "Select a valid program.";
    } elseif ($year_level < 1 || $year_level > 4) {
        $error = "Choose a valid year level.";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters.";
    } else {
        $stmt = $mysqli->prepare("SELECT id FROM students WHERE email = ? OR reg_no = ?");
        $stmt->bind_param("ss", $email, $reg_no);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $error = "Email or registration number already exists.";
        } else {
            $password_hash = password_hash($password, PASSWORD_BCRYPT);
            $token = bin2hex(random_bytes(16));
            $stmt = $mysqli->prepare("INSERT INTO students (reg_no, first_name, last_name, email, phone, program, year_level, password_hash, email_token) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssiss", $reg_no, $first_name, $last_name, $email, $phone, $program, $year_level, $password_hash, $token);
            if ($stmt->execute()) {
                $verify_link = "verify.php?token=" . urlencode($token) . "&email=" . urlencode($email);
                $message = "Account created successfully.";
            } else {
                $error = "Registration failed. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($APP_NAME); ?> - Sign Up</title>
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
            <a href="login.php">Login</a>
        </div>
    </div>

    <div class="art-haze"></div>
    <div class="art-day"></div>
    <div class="art-field"></div>
    <div class="art-sky"></div>

    <div class="hero">
        <div class="card hero-card">
            <h1>Create your academic workspace</h1>
            <p><?php echo htmlspecialchars($HERO_REGISTER); ?></p>
        </div>
        <div class="card">
            <h2 class="section-title">Create Your Student Account</h2>
            <?php if ($error !== "") : ?>
                <div class="alert error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if ($message !== "") : ?>
                <div class="alert"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            <?php if ($verify_link !== "") : ?>
                <div class="alert">Verify your email: <a href="<?php echo htmlspecialchars($verify_link); ?>">Click here</a></div>
            <?php endif; ?>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                <div class="grid">
                    <div class="form-group">
                        <label>Registration Number *</label>
                        <input type="text" name="reg_no" required>
                    </div>
                    <div class="form-group">
                        <label>First Name *</label>
                        <input type="text" name="first_name" required>
                    </div>
                    <div class="form-group">
                        <label>Last Name *</label>
                        <input type="text" name="last_name" required>
                    </div>
                    <div class="form-group">
                        <label>Email *</label>
                        <input type="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label>Phone</label>
                        <input type="text" name="phone">
                    </div>
                    <div class="form-group">
                        <label>Program *</label>
                        <select name="program" required>
                            <?php foreach ($courses as $course) : ?>
                                <option value="<?php echo htmlspecialchars($course["code"]); ?>">
                                    <?php echo htmlspecialchars($course["code"] . " - " . $course["name"]); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Year Level *</label>
                        <select name="year_level">
                            <option value="1">Year 1</option>
                            <option value="2">Year 2</option>
                            <option value="3">Year 3</option>
                            <option value="4">Year 4</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Password *</label>
                        <div class="password-field">
                            <input id="register_password" type="password" name="password" required>
                            <button type="button" aria-label="Toggle password visibility" data-password-toggle="register_password" data-eye-state="closed"></button>
                        </div>
                    </div>
                </div>
                <button class="btn" type="submit">Create Account</button>
            </form>
            <p class="footer-note">A verification link will appear after registration.</p>
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
