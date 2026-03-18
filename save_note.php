<?php
require_once "config.php";
require_login();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    exit();
}

verify_csrf();
$student_id = current_student_id();
$body = trim($_POST["body"] ?? "");

$check = $mysqli->prepare("SELECT id FROM notes WHERE student_id = ?");
$check->bind_param("i", $student_id);
$check->execute();
$check->bind_result($note_id);
if ($check->fetch()) {
    $check->close();
    $update = $mysqli->prepare("UPDATE notes SET body = ? WHERE id = ?");
    $update->bind_param("si", $body, $note_id);
    $update->execute();
} else {
    $insert = $mysqli->prepare("INSERT INTO notes (student_id, body) VALUES (?, ?)");
    $insert->bind_param("is", $student_id, $body);
    $insert->execute();
}

add_activity($student_id, "Updated quick notes");
header("Content-Type: application/json");
echo json_encode(["status" => "ok"]);
exit();
?>
