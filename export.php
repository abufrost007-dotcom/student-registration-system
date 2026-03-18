<?php
require_once "config.php";
require_login();

$student_id = current_student_id();

$stmt = $mysqli->prepare(
    "SELECT u.code, u.name, c.code AS course_code, l.name AS lecturer, u.semester, e.status, e.grade
     FROM enrollments e
     JOIN units u ON e.unit_id = u.id
     JOIN courses c ON u.course_id = c.id
     JOIN lecturers l ON u.lecturer_id = l.id
     WHERE e.student_id = ?
     ORDER BY u.code"
);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
add_activity($student_id, "Exported enrollment CSV");

header("Content-Type: text/csv");
header("Content-Disposition: attachment; filename=\"enrolled_units.csv\"");

$out = fopen("php://output", "w");
fputcsv($out, ["Unit Code", "Unit Name", "Course", "Lecturer", "Semester", "Status", "Grade"]);
foreach ($rows as $row) {
    fputcsv($out, [$row["code"], $row["name"], $row["course_code"], $row["lecturer"], $row["semester"], $row["status"], $row["grade"]]);
}
fclose($out);
exit();
?>
