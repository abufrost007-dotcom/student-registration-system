<?php
function load_env($path)
{
    if (!file_exists($path)) {
        return;
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), "#")) {
            continue;
        }
        $parts = explode("=", $line, 2);
        if (count($parts) === 2) {
            $key = trim($parts[0]);
            $value = trim($parts[1]);
            $_ENV[$key] = $value;
        }
    }
}

function env($key, $default = null)
{
    return $_ENV[$key] ?? $default;
}

load_env(__DIR__ . "/.env");

// App configuration
$APP_NAME = env("APP_NAME", "Student Registration");
$SESSION_TIMEOUT_MINUTES = 30;
$MAX_UNITS = intval(env("MAX_UNITS", "6"));
$REGISTRAR_EMAIL = env("REGISTRAR_EMAIL", "registrar@college.edu");
$SUPPORT_EMAIL = env("SUPPORT_EMAIL", "support@college.edu");
date_default_timezone_set(env("APP_TIMEZONE", "Africa/Nairobi"));

// Use a writable session directory when the host default is restricted.
$session_path = env("SESSION_SAVE_PATH", __DIR__ . "/storage/sessions");
if (!is_dir($session_path)) {
    @mkdir($session_path, 0777, true);
}
if (is_dir($session_path) && is_writable($session_path)) {
    session_save_path($session_path);
}

session_start();

// Session timeout
if (isset($_SESSION["last_activity"])) {
    $inactive = time() - $_SESSION["last_activity"];
    if ($inactive > ($GLOBALS["SESSION_TIMEOUT_MINUTES"] * 60)) {
        session_unset();
        session_destroy();
        session_start();
    }
}
$_SESSION["last_activity"] = time();

$db_host = env("DB_HOST", "localhost");
$db_user = env("DB_USER", "root");
$db_pass = env("DB_PASS", "");
$db_name = env("DB_NAME", "student_registration");

$mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($mysqli->connect_errno) {
    die("Database connection failed: " . $mysqli->connect_error);
}
$mysqli->set_charset("utf8mb4");

function ensure_runtime_schema()
{
    $queries = [
        "CREATE TABLE IF NOT EXISTS announcements (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(160) NOT NULL,
            body TEXT NOT NULL,
            audience VARCHAR(20) NOT NULL DEFAULT 'students',
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_by INT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "ALTER TABLE institution_settings
            ADD COLUMN IF NOT EXISTS year_label VARCHAR(30) NULL DEFAULT 'Year',
            ADD COLUMN IF NOT EXISTS enrollment_start DATETIME NULL,
            ADD COLUMN IF NOT EXISTS enrollment_end DATETIME NULL",
        "ALTER TABLE units
            ADD COLUMN IF NOT EXISTS day_of_week VARCHAR(20) NULL,
            ADD COLUMN IF NOT EXISTS start_time TIME NULL,
            ADD COLUMN IF NOT EXISTS end_time TIME NULL,
            ADD COLUMN IF NOT EXISTS room VARCHAR(60) NULL",
        "ALTER TABLE student_preferences
            ADD COLUMN IF NOT EXISTS large_text VARCHAR(10) NULL,
            ADD COLUMN IF NOT EXISTS minimal_mode VARCHAR(10) NULL"
    ];

    foreach ($queries as $query) {
        $GLOBALS['mysqli']->query($query);
    }
}

ensure_runtime_schema();

$BRAND_COLOR = null;
$LOGO_PATH = null;
$FAVICON_PATH = "assets/favicon.svg";
$HERO_LOGIN = "Sign in to view your course dashboard, enroll in units, and track your academic progress.";
$HERO_REGISTER = "Register once and get a clean dashboard for courses, units, and advisor details.";
$FOOTER_NAME = $APP_NAME;
$FOOTER_ADDRESS = "";
$FOOTER_WEBSITE = "";
$FOOTER_SOCIALS = "";
$YEAR_LABEL = "Year";
$ENROLLMENT_START = null;
$ENROLLMENT_END = null;

if ($mysqli && !$mysqli->connect_errno) {
    $settings = $mysqli->query("SELECT * FROM institution_settings WHERE id = 1");
    if ($settings) {
        $row = $settings->fetch_assoc();
        if ($row) {
            $APP_NAME = $row["institution_name"] ?: $APP_NAME;
            $BRAND_COLOR = $row["brand_color"] ?: $BRAND_COLOR;
            $LOGO_PATH = $row["logo_path"] ?: $LOGO_PATH;
            $FAVICON_PATH = $row["favicon_path"] ?: $FAVICON_PATH;
            $MAX_UNITS = intval($row["max_units"] ?: $MAX_UNITS);
            $HERO_LOGIN = $row["hero_login"] ?: $HERO_LOGIN;
            $HERO_REGISTER = $row["hero_register"] ?: $HERO_REGISTER;
            $FOOTER_NAME = $row["institution_name"] ?: $FOOTER_NAME;
            $FOOTER_ADDRESS = $row["address"] ?: $FOOTER_ADDRESS;
            $FOOTER_WEBSITE = $row["website"] ?: $FOOTER_WEBSITE;
            $FOOTER_SOCIALS = $row["socials"] ?: $FOOTER_SOCIALS;
            $YEAR_LABEL = $row["year_label"] ?: $YEAR_LABEL;
            $ENROLLMENT_START = $row["enrollment_start"] ?: $ENROLLMENT_START;
            $ENROLLMENT_END = $row["enrollment_end"] ?: $ENROLLMENT_END;
        }
    }
}

function csrf_token()
{
    if (!isset($_SESSION["csrf_token"])) {
        $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
    }
    return $_SESSION["csrf_token"];
}

function verify_csrf()
{
    $token = $_POST["csrf_token"] ?? "";
    if ($token === "" || !isset($_SESSION["csrf_token"]) || !hash_equals($_SESSION["csrf_token"], $token)) {
        die("Invalid CSRF token.");
    }
}

function require_login()
{
    if (!isset($_SESSION["student_id"])) {
        header("Location: login.php");
        exit();
    }
}

function require_admin()
{
    if (!isset($_SESSION["admin_id"])) {
        header("Location: admin_login.php");
        exit();
    }
}

function current_student_id()
{
    return $_SESSION["student_id"] ?? null;
}

function current_admin_id()
{
    return $_SESSION["admin_id"] ?? null;
}

function add_activity($student_id, $activity)
{
    if (!$student_id || $activity === "") {
        return;
    }
    $stmt = $GLOBALS["mysqli"]->prepare("INSERT INTO activities (student_id, activity) VALUES (?, ?)");
    if (!$stmt) {
        return;
    }
    $stmt->bind_param("is", $student_id, $activity);
    $stmt->execute();
}

function add_admin_activity($admin_id, $activity)
{
    if (!$admin_id || $activity === "") {
        return;
    }
    $stmt = $GLOBALS["mysqli"]->prepare("INSERT INTO admin_activities (admin_id, activity) VALUES (?, ?)");
    if (!$stmt) {
        return;
    }
    $stmt->bind_param("is", $admin_id, $activity);
    $stmt->execute();
}

function get_student_preferences($student_id)
{
    $prefs = [
        "theme" => null,
        "accent_hue" => null,
        "density" => null,
        "glass" => null,
        "motion" => null,
        "font" => null,
        "art" => null,
        "large_text" => null,
        "minimal_mode" => null
    ];
    if (!$student_id) {
        return $prefs;
    }
    $stmt = $GLOBALS["mysqli"]->prepare("SELECT theme, accent_hue, density, glass, motion, font, art, large_text, minimal_mode FROM student_preferences WHERE student_id = ?");
    if (!$stmt) {
        return $prefs;
    }
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if ($row) {
        return array_merge($prefs, $row);
    }
    return $prefs;
}

function brand_html()
{
    $name = htmlspecialchars($GLOBALS["APP_NAME"]);
    $logo = $GLOBALS["LOGO_PATH"];
    if ($logo) {
        return '<span class="brand-logo"><img src="' . htmlspecialchars($logo) . '" alt="' . $name . '"></span>';
    }
    return $name;
}

function enrollment_window_open()
{
    $start = $GLOBALS["ENROLLMENT_START"];
    $end = $GLOBALS["ENROLLMENT_END"];
    $now = time();

    if ($start && strtotime($start) > $now) {
        return false;
    }
    if ($end && strtotime($end) < $now) {
        return false;
    }
    return true;
}

function enrollment_window_message()
{
    $start = $GLOBALS["ENROLLMENT_START"];
    $end = $GLOBALS["ENROLLMENT_END"];
    $now = time();

    if ($start && strtotime($start) > $now) {
        return "Enrollment opens on " . date("M d, Y H:i", strtotime($start)) . ".";
    }
    if ($end && strtotime($end) < $now) {
        return "Enrollment closed on " . date("M d, Y H:i", strtotime($end)) . ".";
    }
    if ($end) {
        return "Enrollment is open until " . date("M d, Y H:i", strtotime($end)) . ".";
    }
    return "Enrollment is currently open.";
}

function get_active_announcements($audience = "students")
{
    $stmt = $GLOBALS["mysqli"]->prepare(
        "SELECT id, title, body, audience, created_at
         FROM announcements
         WHERE is_active = 1 AND (audience = ? OR audience = 'all')
         ORDER BY created_at DESC
         LIMIT 5"
    );
    if (!$stmt) {
        return [];
    }
    $stmt->bind_param("s", $audience);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function grade_points_from_value($grade)
{
    $grade = trim((string) $grade);
    if ($grade === "") {
        return null;
    }
    if (is_numeric($grade)) {
        $value = floatval($grade);
        if ($value >= 70) return 4.0;
        if ($value >= 60) return 3.0;
        if ($value >= 50) return 2.0;
        if ($value >= 40) return 1.0;
        return 0.0;
    }
    $map = [
        "A" => 4.0, "A-" => 3.7,
        "B+" => 3.3, "B" => 3.0, "B-" => 2.7,
        "C+" => 2.3, "C" => 2.0, "C-" => 1.7,
        "D+" => 1.3, "D" => 1.0, "D-" => 0.7,
        "E" => 0.0, "F" => 0.0
    ];
    $grade = strtoupper($grade);
    return $map[$grade] ?? null;
}
?>





