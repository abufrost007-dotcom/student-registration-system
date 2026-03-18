<?php
require_once "config.php";
require_login();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: dashboard.php");
    exit();
}

verify_csrf();
$student_id = current_student_id();
$enrollment_id = intval($_POST["enrollment_id"] ?? 0);

if ($enrollment_id > 0) {
    $stmt = $mysqli->prepare("UPDATE enrollments SET status = 'withdrawn' WHERE id = ? AND student_id = ?");
    $stmt->bind_param("ii", $enrollment_id, $student_id);
    if ($stmt->execute()) {
        add_activity($student_id, "Withdrew from a unit");
        header("Location: dashboard.php?withdrawn=1");
        exit();
    }
}

header("Location: dashboard.php");
exit();
?>
