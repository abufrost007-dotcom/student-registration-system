<?php
require_once "config.php";
require_login();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: dashboard.php");
    exit();
}

verify_csrf();
$student_id = current_student_id();
$unit_id = intval($_POST["unit_id"] ?? 0);
$result_flag = "";

if (!enrollment_window_open()) {
    header("Location: dashboard.php?invalid_unit=1");
    exit();
}

if ($unit_id > 0) {
    $student_stmt = $mysqli->prepare("SELECT program, year_level FROM students WHERE id = ?");
    $student_stmt->bind_param("i", $student_id);
    $student_stmt->execute();
    $student_stmt->bind_result($program_code, $year_level);
    $student_stmt->fetch();
    $student_stmt->close();

    $unit_check = $mysqli->prepare("SELECT id FROM units WHERE id = ?");
    $unit_check->bind_param("i", $unit_id);
    $unit_check->execute();
    $unit_check->store_result();
    if ($unit_check->num_rows === 0) {
        header("Location: dashboard.php?invalid_unit=1");
        exit();
    }
    $unit_check->close();

    $count_stmt = $mysqli->prepare("SELECT COUNT(*) FROM enrollments WHERE student_id = ? AND status = 'enrolled'");
    $count_stmt->bind_param("i", $student_id);
    $count_stmt->execute();
    $count_stmt->bind_result($current_count);
    $count_stmt->fetch();
    $count_stmt->close();

    if ($current_count < $MAX_UNITS) {
        $code_stmt = $mysqli->prepare("SELECT code FROM units WHERE id = ?");
        $code_stmt->bind_param("i", $unit_id);
        $code_stmt->execute();
        $code_stmt->bind_result($unit_code);
        $code_stmt->fetch();
        $code_stmt->close();

        $check = $mysqli->prepare(
            "SELECT e.id, e.status
             FROM enrollments e
             JOIN units u ON e.unit_id = u.id
             WHERE e.student_id = ? AND (e.unit_id = ? OR u.code = ?)
             ORDER BY e.id DESC
             LIMIT 1"
        );
        $check->bind_param("iis", $student_id, $unit_id, $unit_code);
        $check->execute();
        $check->bind_result($enroll_id, $status);
        if ($check->fetch()) {
            $check->close();
            if ($status === "withdrawn") {
                $update = $mysqli->prepare("UPDATE enrollments SET status = 'enrolled' WHERE id = ?");
                $update->bind_param("i", $enroll_id);
                $update->execute();
                add_activity($student_id, "Re-enrolled in a unit");
                $result_flag = "enrolled=1";
            } else {
                $result_flag = "already_enrolled=1";
            }
        } else {
            $stmt = $mysqli->prepare("INSERT IGNORE INTO enrollments (student_id, unit_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $student_id, $unit_id);
            $stmt->execute();
            add_activity($student_id, "Enrolled in a unit");
            $result_flag = "enrolled=1";
        }
    } else {
        $result_flag = "limit=1";
    }
}

if ($result_flag !== "") {
    header("Location: dashboard.php?" . $result_flag);
} else {
    header("Location: dashboard.php");
}
exit();
?>



