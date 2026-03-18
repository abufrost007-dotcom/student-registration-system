<?php
require_once "config.php";
require_login();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: profile.php");
    exit();
}

verify_csrf();
$student_id = current_student_id();
$message = trim($_POST["message"] ?? "");

if ($message !== "") {
    $stmt = $mysqli->prepare("INSERT INTO advisor_requests (student_id, message) VALUES (?, ?)");
    $stmt->bind_param("is", $student_id, $message);
    $stmt->execute();
    add_activity($student_id, "Sent an advisor update request");
    header("Location: profile.php?request=1");
    exit();
}

header("Location: profile.php?request=0");
exit();
?>
