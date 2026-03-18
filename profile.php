<?php
require_once "config.php";
require_login();

$student_id = current_student_id();
$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verify_csrf();
    $email = trim($_POST["email"] ?? "");
    $phone = trim($_POST["phone"] ?? "");

    if ($email === "") {
        $message = "Email is required.";
    } else {
        $stmt = $mysqli->prepare("UPDATE students SET email = ?, phone = ? WHERE id = ?");
        $stmt->bind_param("ssi", $email, $phone, $student_id);
        if ($stmt->execute()) {
            $message = "Profile updated.";
            add_activity($student_id, "Updated profile contact details");
        } else {
            $message = "Profile update failed.";
        }
    }
}

$stmt = $mysqli->prepare("SELECT reg_no, first_name, last_name, email, phone, program, year_level, advisor_name, advisor_email, advisor_phone FROM students WHERE id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$stmt->bind_result($reg_no, $first_name, $last_name, $email, $phone, $program, $year_level, $advisor_name, $advisor_email, $advisor_phone);
$stmt->fetch();
$prefs = get_student_preferences($student_id);
?>
<!DOCTYPE html>
<html lang="en"
      data-theme="<?php echo htmlspecialchars($prefs["theme"] ?: "light-ocean"); ?>"
      data-density="<?php echo htmlspecialchars($prefs["density"] ?: "cozy"); ?>"
      data-glass="<?php echo htmlspecialchars($prefs["glass"] ?: "on"); ?>"
      data-motion="<?php echo htmlspecialchars($prefs["motion"] ?: "on"); ?>"
      data-font="<?php echo htmlspecialchars($prefs["font"] ?: "jakarta"); ?>"
      data-art="<?php echo htmlspecialchars($prefs["art"] ?: "80"); ?>"
      data-accent-hue="<?php echo htmlspecialchars($prefs["accent_hue"] ?: "48"); ?>"
      data-large-text="<?php echo htmlspecialchars($prefs["large_text"] ?: "off"); ?>"
      data-minimal-mode="<?php echo htmlspecialchars($prefs["minimal_mode"] ?: "off"); ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($APP_NAME); ?> - Profile</title>
    <meta name="description" content="Manage your student profile and advisor requests.">
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
            <a href="dashboard.php">Dashboard</a>
            <a href="timetable.php">Timetable</a>
            <a href="history.php">History</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>

    <div class="art-haze"></div>
    <div class="art-day"></div>
    <div class="art-field"></div>
    <div class="art-sky"></div>

    <div class="hero">
        <div class="card hero-card">
            <h1>Profile settings</h1>
            <p>Review your records and keep your academic contact details up to date.</p>
        </div>
        <div class="card">
            <h2 class="section-title">Academic Advisor</h2>
            <p><strong>Name:</strong> <?php echo htmlspecialchars($advisor_name ?: "Not assigned"); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($advisor_email ?: "Not assigned"); ?></p>
            <p><strong>Phone:</strong> <?php echo htmlspecialchars($advisor_phone ?: "Not assigned"); ?></p>
            <p class="muted">Send a request if you need advisor details updated.</p>
            <?php if (isset($_GET["request"]) && $_GET["request"] == 1) : ?>
                <div class="alert">Advisor request sent to the registrar.</div>
            <?php endif; ?>
            <form method="post" action="advisor_request.php">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                <div class="form-group">
                    <label>Request Message</label>
                    <input type="text" name="message" placeholder="e.g. Update advisor email or phone">
                </div>
                <button class="btn secondary" type="submit">Send Request</button>
            </form>
        </div>
    </div>

    <div class="card" style="margin-top: 18px;">
        <h2 class="section-title">Personal Information</h2>
        <?php if ($message !== "") : ?>
            <div class="alert <?php echo $message === "Profile updated." ? "" : "error"; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
            <div class="grid">
                <div class="form-group">
                    <label>Registration Number</label>
                    <input type="text" value="<?php echo htmlspecialchars($reg_no); ?>" disabled>
                </div>
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" value="<?php echo htmlspecialchars($first_name . " " . $last_name); ?>" disabled>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <input type="text" name="phone" value="<?php echo htmlspecialchars($phone); ?>">
                </div>
                <div class="form-group">
                    <label>Program</label>
                    <input type="text" value="<?php echo htmlspecialchars($program); ?>" disabled>
                </div>
                <div class="form-group">
                    <label>Year Level</label>
                    <input type="text" value="<?php echo htmlspecialchars($year_level); ?>" disabled>
                    <p class="muted" style="margin-top:6px;">Year level is managed by the registrar.</p>
                </div>
            </div>
            <button class="btn" type="submit">Save Changes</button>
        </form>
        <div style="margin-top: 12px;">
            <a class="btn secondary" href="change_password.php">Change Password</a>
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
