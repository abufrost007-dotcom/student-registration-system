<?php
require_once "config.php";
require_login();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    exit();
}

verify_csrf();
$student_id = current_student_id();

$theme = trim($_POST["theme"] ?? "");
$accent_hue = isset($_POST["accent_hue"]) ? intval($_POST["accent_hue"]) : null;
$density = trim($_POST["density"] ?? "");
$glass = trim($_POST["glass"] ?? "");
$motion = trim($_POST["motion"] ?? "");
$font = trim($_POST["font"] ?? "");
$art = isset($_POST["art"]) ? intval($_POST["art"]) : null;
$large_text = trim($_POST["large_text"] ?? "");
$minimal_mode = trim($_POST["minimal_mode"] ?? "");

$stmt = $mysqli->prepare(
    "INSERT INTO student_preferences (student_id, theme, accent_hue, density, glass, motion, font, art, large_text, minimal_mode)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
     ON DUPLICATE KEY UPDATE theme = VALUES(theme), accent_hue = VALUES(accent_hue), density = VALUES(density),
     glass = VALUES(glass), motion = VALUES(motion), font = VALUES(font), art = VALUES(art),
     large_text = VALUES(large_text), minimal_mode = VALUES(minimal_mode)"
);
$stmt->bind_param("isissssiss", $student_id, $theme, $accent_hue, $density, $glass, $motion, $font, $art, $large_text, $minimal_mode);
$stmt->execute();

header("Content-Type: application/json");
echo json_encode(["status" => "ok"]);
exit();
?>
