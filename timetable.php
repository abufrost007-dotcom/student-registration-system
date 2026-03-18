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
    "SELECT u.code, u.name, u.day_of_week, u.start_time, u.end_time, u.room, l.name AS lecturer
     FROM enrollments e
     JOIN units u ON e.unit_id = u.id
     JOIN lecturers l ON u.lecturer_id = l.id
     WHERE e.student_id = ? AND e.status = 'enrolled'
     ORDER BY FIELD(u.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), u.start_time, u.code"
);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$days = ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];
$grouped = [];
foreach ($days as $day) {
    $grouped[$day] = [];
}
foreach ($rows as $row) {
    $day = $row["day_of_week"] ?: "Monday";
    if (!isset($grouped[$day])) {
        $grouped[$day] = [];
    }
    $grouped[$day][] = $row;
}
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
    <title><?php echo htmlspecialchars($APP_NAME); ?> - Timetable</title>
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
            <a href="history.php">History</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>

    <div class="hero">
        <div class="card hero-card">
            <h1><?php echo htmlspecialchars($student["first_name"] ?? "Student"); ?>'s timetable</h1>
            <p>Track your weekly class flow by day, time, lecturer, and room.</p>
        </div>
        <div class="card">
            <h2 class="section-title">Overview</h2>
            <p><strong>Program:</strong> <?php echo htmlspecialchars($student["program"] ?? "-"); ?></p>
            <p><strong><?php echo htmlspecialchars($YEAR_LABEL); ?>:</strong> <?php echo htmlspecialchars($student["year_level"] ?? "-"); ?></p>
            <p><strong>Enrolled classes:</strong> <?php echo count($rows); ?></p>
        </div>
    </div>

    <div class="grid">
        <?php foreach ($grouped as $day => $items) : ?>
            <div class="card">
                <h2 class="section-title"><?php echo htmlspecialchars($day); ?></h2>
                <?php if (count($items) === 0) : ?>
                    <p class="muted">No classes scheduled.</p>
                <?php else : ?>
                    <div class="schedule">
                        <?php foreach ($items as $item) : ?>
                            <div class="schedule-item">
                                <div class="time">
                                    <?php
                                    if ($item["start_time"] && $item["end_time"]) {
                                        echo htmlspecialchars(date("H:i", strtotime($item["start_time"])) . " - " . date("H:i", strtotime($item["end_time"])));
                                    } else {
                                        echo "TBA";
                                    }
                                    ?>
                                </div>
                                <div class="event">
                                    <strong><?php echo htmlspecialchars($item["code"]); ?></strong> <?php echo htmlspecialchars($item["name"]); ?><br>
                                    Lecturer: <?php echo htmlspecialchars($item["lecturer"]); ?><br>
                                    Room: <?php echo htmlspecialchars($item["room"] ?: "TBA"); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<script src="assets/app.js"></script>
</body>
</html>
