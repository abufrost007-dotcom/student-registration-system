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

$student_stmt = $mysqli->prepare("SELECT reg_no, first_name, last_name, program, year_level FROM students WHERE id = ?");
$student_stmt->bind_param("i", $student_id);
$student_stmt->execute();
$student = $student_stmt->get_result()->fetch_assoc();
add_activity($student_id, "Exported enrollment slip");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($APP_NAME); ?> - Enrollment Slip</title>
    <meta name="description" content="Printable enrollment slip.">
    <link rel="icon" type="image/svg+xml" href="assets/favicon.svg">
    <link rel="stylesheet" href="assets/style.css">
    <style>
        body { background: #f8fafc; }
        .print-card { background: #fff; border: 1px solid #e2e8f0; }
        .print-actions { display: flex; gap: 10px; margin-top: 16px; }
        @media print {
            .print-actions, .topbar { display: none; }
            body { background: #fff; }
            .print-card { border: none; box-shadow: none; }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="topbar">
        <div class="brand"><?php echo htmlspecialchars($APP_NAME); ?></div>
        <div class="nav">
            <a href="dashboard.php">Back to Dashboard</a>
        </div>
    </div>

    <div class="card print-card">
        <h2 class="section-title">Enrollment Slip</h2>
        <p><strong>Student:</strong> <?php echo htmlspecialchars($student["first_name"] . " " . $student["last_name"]); ?></p>
        <p><strong>Reg No:</strong> <?php echo htmlspecialchars($student["reg_no"]); ?></p>
        <p><strong>Program:</strong> <?php echo htmlspecialchars($student["program"]); ?> | <strong>Year:</strong> <?php echo htmlspecialchars($student["year_level"]); ?></p>
        <table>
            <thead>
            <tr>
                <th>Unit</th>
                <th>Title</th>
                <th>Course</th>
                <th>Lecturer</th>
                <th>Semester</th>
                <th>Status</th>
                <th>Grade</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $row) : ?>
                <tr>
                    <td><?php echo htmlspecialchars($row["code"]); ?></td>
                    <td><?php echo htmlspecialchars($row["name"]); ?></td>
                    <td><?php echo htmlspecialchars($row["course_code"]); ?></td>
                    <td><?php echo htmlspecialchars($row["lecturer"]); ?></td>
                    <td><?php echo htmlspecialchars($row["semester"]); ?></td>
                    <td><?php echo htmlspecialchars($row["status"]); ?></td>
                    <td><?php echo htmlspecialchars($row["grade"] ?: "-"); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <div class="print-actions">
            <button class="btn" onclick="window.print()">Print / Save PDF</button>
            <a class="btn secondary" href="export.php">Download CSV</a>
        </div>
    </div>
</div>
</body>
</html>
