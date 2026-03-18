<?php
require_once "config.php";
require_login();

$student_id = current_student_id();
$prefs = get_student_preferences($student_id);

$student_stmt = $mysqli->prepare("SELECT first_name, program, year_level FROM students WHERE id = ?");
$student_stmt->bind_param("i", $student_id);
$student_stmt->execute();
$student = $student_stmt->get_result()->fetch_assoc();

$stmt = $mysqli->prepare(
    "SELECT u.code, u.name, u.semester, e.status, e.grade, e.enrolled_at
     FROM enrollments e
     JOIN units u ON e.unit_id = u.id
     WHERE e.student_id = ?
     ORDER BY e.enrolled_at DESC, u.code"
);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$completed = [];
$gpa_total = 0.0;
$gpa_count = 0;
foreach ($rows as $row) {
    $points = grade_points_from_value($row["grade"]);
    if ($points !== null) {
        $gpa_total += $points;
        $gpa_count++;
    }
    $completed[] = $row;
}
$gpa = $gpa_count > 0 ? round($gpa_total / $gpa_count, 2) : null;
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
    <title><?php echo htmlspecialchars($APP_NAME); ?> - History</title>
    <link rel="icon" type="image/png" href="<?php echo htmlspecialchars($FAVICON_PATH); ?>">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body data-csrf="<?php echo htmlspecialchars(csrf_token()); ?>">
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
            <a href="logout.php">Logout</a>
        </div>
    </div>

    <div class="hero">
        <div class="card hero-card">
            <h1>Academic history</h1>
            <p>Review grades, past registrations, and your current GPA snapshot.</p>
        </div>
        <div class="card">
            <h2 class="section-title">Performance</h2>
            <div class="tile-grid">
                <div class="tile">
                    <div class="value"><?php echo count($rows); ?></div>
                    <div class="label">All registrations</div>
                </div>
                <div class="tile">
                    <div class="value"><?php echo $gpa !== null ? htmlspecialchars(number_format($gpa, 2)) : "--"; ?></div>
                    <div class="label">Estimated GPA</div>
                </div>
                <div class="tile">
                    <div class="value"><?php echo htmlspecialchars($student["year_level"] ?? "-"); ?></div>
                    <div class="label"><?php echo htmlspecialchars($YEAR_LABEL); ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <h2 class="section-title">Enrollment and Grade History</h2>
        <?php if (count($completed) === 0) : ?>
            <p class="muted">No history available yet.</p>
        <?php else : ?>
            <table>
                <thead>
                <tr>
                    <th>Unit</th>
                    <th>Title</th>
                    <th>Semester</th>
                    <th>Status</th>
                    <th>Grade</th>
                    <th>Recorded</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($completed as $row) : ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row["code"]); ?></td>
                        <td><?php echo htmlspecialchars($row["name"]); ?></td>
                        <td><?php echo htmlspecialchars($row["semester"]); ?></td>
                        <td><?php echo htmlspecialchars($row["status"]); ?></td>
                        <td><?php echo htmlspecialchars($row["grade"] ?: "-"); ?></td>
                        <td><?php echo htmlspecialchars(date("M d, Y", strtotime($row["enrolled_at"]))); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
<script src="assets/app.js"></script>
</body>
</html>
