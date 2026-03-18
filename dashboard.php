<?php
require_once "config.php";
require_login();

$student_id = current_student_id();
$page_size = 20;
$announcements = get_active_announcements("students");
$window_message = enrollment_window_message();

$stmt = $mysqli->prepare("SELECT reg_no, first_name, last_name, email, phone, program, year_level, advisor_name, advisor_email, advisor_phone FROM students WHERE id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
if (!$student) {
    header("Location: logout.php");
    exit();
}

$prefs = get_student_preferences($student_id);
if (!$student) {
    header("Location: logout.php");
    exit();
}


$course_id = null;
$course_name = "";
if ($student && $student["program"] !== "") {
    $course_stmt = $mysqli->prepare("SELECT id, name FROM courses WHERE code = ?");
    $course_stmt->bind_param("s", $student["program"]);
    $course_stmt->execute();
    $course_stmt->bind_result($course_id, $course_name);
    $course_stmt->fetch();
    $course_stmt->close();
}

$enrolled_page = max(1, intval($_GET["enrolled_page"] ?? 1));
$available_page = max(1, intval($_GET["available_page"] ?? 1));
$enrolled_offset = ($enrolled_page - 1) * $page_size;
$available_offset = ($available_page - 1) * $page_size;

$enrolled_total_stmt = $mysqli->prepare("SELECT COUNT(*) FROM enrollments WHERE student_id = ?");
$enrolled_total_stmt->bind_param("i", $student_id);
$enrolled_total_stmt->execute();
$enrolled_total_stmt->bind_result($enrolled_total);
$enrolled_total_stmt->fetch();
$enrolled_total_stmt->close();

$enrolled_stmt = $mysqli->prepare(
    "SELECT e.id, u.id AS unit_id, u.code, u.name, c.code AS course_code, l.name AS lecturer, u.semester, e.status, e.grade
     FROM enrollments e
     JOIN units u ON e.unit_id = u.id
     JOIN courses c ON u.course_id = c.id
     JOIN lecturers l ON u.lecturer_id = l.id
     WHERE e.student_id = ? AND e.status = 'enrolled'
     ORDER BY u.code
     LIMIT ? OFFSET ?"
);
$enrolled_stmt->bind_param("iii", $student_id, $page_size, $enrolled_offset);
$enrolled_stmt->execute();
$enrolled_units = $enrolled_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$withdrawn_stmt = $mysqli->prepare(
    "SELECT e.id, u.id AS unit_id, u.code, u.name, c.code AS course_code, l.name AS lecturer, u.semester, e.status, e.grade
     FROM enrollments e
     JOIN units u ON e.unit_id = u.id
     JOIN courses c ON u.course_id = c.id
     JOIN lecturers l ON u.lecturer_id = l.id
     WHERE e.student_id = ? AND e.status = 'withdrawn'
     ORDER BY u.code"
);
$withdrawn_stmt->bind_param("i", $student_id);
$withdrawn_stmt->execute();
$withdrawn_units = $withdrawn_stmt->get_result()->fetch_all(MYSQLI_ASSOC);


$enrolled_active_stmt = $mysqli->prepare(
    "SELECT u.code, u.name, u.day_of_week, u.start_time, u.end_time, u.room
     FROM enrollments e
     JOIN units u ON e.unit_id = u.id
     WHERE e.student_id = ? AND e.status = 'enrolled'"
);
$enrolled_active_stmt->bind_param("i", $student_id);
$enrolled_active_stmt->execute();
$enrolled_active_units = $enrolled_active_stmt->get_result()->fetch_all(MYSQLI_ASSOC);


$available_units = [];
$available_total = 0;
$available_total_stmt = $mysqli->prepare(
    "SELECT COUNT(*)
     FROM units u
     WHERE u.id NOT IN (SELECT unit_id FROM enrollments WHERE student_id = ? AND status = 'enrolled')
       AND u.code NOT IN (
            SELECT u2.code
            FROM enrollments e2
            JOIN units u2 ON e2.unit_id = u2.id
            WHERE e2.student_id = ? AND e2.status = 'enrolled'
       )"
);
$available_total_stmt->bind_param("ii", $student_id, $student_id);
$available_total_stmt->execute();
$available_total_stmt->bind_result($available_total);
$available_total_stmt->fetch();
$available_total_stmt->close();

$available_stmt = $mysqli->prepare(
    "SELECT u.id, u.code, u.name, c.code AS course_code, l.name AS lecturer, u.semester, u.year_level, u.day_of_week, u.start_time, u.end_time, u.room
     FROM units u
     JOIN courses c ON u.course_id = c.id
     JOIN lecturers l ON u.lecturer_id = l.id
     WHERE u.id NOT IN (SELECT unit_id FROM enrollments WHERE student_id = ? AND status = 'enrolled')
       AND u.code NOT IN (
            SELECT u2.code
            FROM enrollments e2
            JOIN units u2 ON e2.unit_id = u2.id
            WHERE e2.student_id = ? AND e2.status = 'enrolled'
       )
     ORDER BY u.year_level, u.code
     LIMIT ? OFFSET ?"
);
$available_stmt->bind_param("iiii", $student_id, $student_id, $page_size, $available_offset);
$available_stmt->execute();
$available_units = $available_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$courses = $mysqli->query("SELECT code, name, department FROM courses ORDER BY code")->fetch_all(MYSQLI_ASSOC);

$total_program_units = 0;
$required_units = $MAX_UNITS;
if ($course_id) {
    $total_program_units = $mysqli->query("SELECT COUNT(*) AS total FROM units WHERE course_id = " . intval($course_id))->fetch_assoc()["total"] ?? 0;
}

$enrolled_count_stmt = $mysqli->prepare(
    "SELECT COUNT(*) FROM enrollments WHERE student_id = ? AND status = 'enrolled'"
);
$enrolled_count_stmt->bind_param("i", $student_id);
$enrolled_count_stmt->execute();
$enrolled_count_stmt->bind_result($enrolled_active_count);
$enrolled_count_stmt->fetch();
$enrolled_count_stmt->close();

$progress = $required_units > 0 ? min(100, round(($enrolled_active_count / $required_units) * 100)) : 0;

$featured = null;
if ($course_id) {
    $featured_stmt = $mysqli->prepare(
        "SELECT u.code, u.name, c.code AS course_code, l.name AS lecturer, u.semester, COUNT(e.id) AS popularity
         FROM units u
         JOIN courses c ON u.course_id = c.id
         JOIN lecturers l ON u.lecturer_id = l.id
         LEFT JOIN enrollments e ON e.unit_id = u.id
         WHERE u.course_id = ? AND u.year_level = ?
         GROUP BY u.id
         ORDER BY popularity DESC, u.code
         LIMIT 1"
    );
    $featured_stmt->bind_param("ii", $course_id, $student["year_level"]);
    $featured_stmt->execute();
    $featured = $featured_stmt->get_result()->fetch_assoc();
}
if (!$featured) {
    $featured = $available_units[0] ?? ($enrolled_units[0] ?? null);
}

$note_body = "";
$note_stmt = $mysqli->prepare("SELECT body FROM notes WHERE student_id = ?");
$note_stmt->bind_param("i", $student_id);
$note_stmt->execute();
$note_stmt->bind_result($note_body);
$note_stmt->fetch();
$note_stmt->close();

$activities_stmt = $mysqli->prepare("SELECT activity, created_at FROM activities WHERE student_id = ? ORDER BY created_at DESC LIMIT 5");
$activities_stmt->bind_param("i", $student_id);
$activities_stmt->execute();
$activities = $activities_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$dates_stmt = $mysqli->prepare(
    "SELECT DATE(created_at) AS day FROM activities WHERE student_id = ? AND created_at >= (CURDATE() - INTERVAL 10 DAY) GROUP BY day ORDER BY day DESC"
);
$dates_stmt->bind_param("i", $student_id);
$dates_stmt->execute();
$date_rows = $dates_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$activity_days = array_map(fn($row) => $row["day"], $date_rows);

$streak = 0;
for ($i = 0; $i < 7; $i++) {
    $day = date("Y-m-d", strtotime("-" . $i . " day"));
    if (in_array($day, $activity_days, true)) {
        $streak++;
    } else {
        break;
    }
}

$milestones = [];
if ($required_units > 0) {
    $remaining = max(0, $required_units - $enrolled_active_count);
    $next_target = min($required_units, $enrolled_active_count + 2);
    $milestones[] = "Enroll " . min(2, $remaining) . " more units to reach " . round(($next_target / $required_units) * 100) . "% progress.";
    $milestones[] = $remaining > 0 ? "Complete " . $remaining . " remaining required units to finish enrollment." : "Enrollment requirement complete.";
    $milestones[] = "Review advisor feedback and confirm next semester focus.";
} else {
    $milestones[] = "Confirm your program with the registrar.";
}

$schedule_items = [];
$time_slots = ["09:00", "11:30", "14:00"];
foreach ($enrolled_active_units as $idx => $unit) {
    if ($idx >= 3) break;
    $time_label = $unit["start_time"] && $unit["end_time"]
        ? date("H:i", strtotime($unit["start_time"])) . " - " . date("H:i", strtotime($unit["end_time"]))
        : $time_slots[$idx];
    $day_prefix = $unit["day_of_week"] ? $unit["day_of_week"] . ": " : "";
    $room_suffix = $unit["room"] ? " (" . $unit["room"] . ")" : "";
    $schedule_items[] = ["time" => $time_label, "event" => $day_prefix . $unit["name"] . $room_suffix];
}
if (count($schedule_items) === 0) {
    $schedule_items = [
        ["time" => "09:00", "event" => "Plan your unit enrollments"],
        ["time" => "12:30", "event" => "Profile review + advisor check"],
        ["time" => "16:00", "event" => "Independent study session"]
    ];
}

$notice = "";
if (isset($_GET["enrolled"])) {
    $notice = "Enrollment successful. Your dashboard has been updated.";
} elseif (isset($_GET["limit"])) {
    $notice = "You have reached the maximum of " . $MAX_UNITS . " enrolled units.";
} elseif (isset($_GET["withdrawn"])) {
    $notice = "Unit withdrawn successfully.";
} elseif (isset($_GET["already_enrolled"])) {
    $notice = "That unit is already in your enrolled list.";
} elseif (isset($_GET["invalid_unit"])) {
    $notice = "That unit is not available for your program, year level, or enrollment window.";
} elseif (isset($_GET["withdraw_error"])) {
    $notice = "We could not withdraw that unit. Please try again.";
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
    <title><?php echo htmlspecialchars($APP_NAME); ?> - Dashboard</title>
    <meta name="description" content="Student dashboard for courses, enrollments, and progress tracking.">
    <link rel="icon" type="image/png" href="<?php echo htmlspecialchars($FAVICON_PATH); ?>">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body data-csrf="<?php echo htmlspecialchars(csrf_token()); ?>" style="<?php echo $BRAND_COLOR ? '--accent: ' . htmlspecialchars($BRAND_COLOR) . ';' : ''; ?>">
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
            <a href="profile.php">Profile</a>
            <a href="timetable.php">Timetable</a>
            <a href="history.php">History</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>

    <div class="art-haze"></div>
    <div class="art-day"></div>
    <div class="art-field"></div>
    <div class="art-sky"></div>

    <div class="hero">
        <div class="card hero-card">
            <h1><?php echo htmlspecialchars($student["first_name"]); ?>, your academic dashboard</h1>
            <p><?php echo htmlspecialchars($HERO_LOGIN); ?></p>
            <div class="chip-row">
                <span class="chip"><?php echo htmlspecialchars($student["program"]); ?><?php echo $course_name ? " - " . htmlspecialchars($course_name) : ""; ?></span>
                <span class="chip"><?php echo htmlspecialchars($YEAR_LABEL); ?> <?php echo htmlspecialchars($student["year_level"]); ?></span>
                <span class="chip">Max units: <?php echo $MAX_UNITS; ?></span>
            </div>
            <div class="today-strip" data-rotate>
                <span class="today-label">Window</span>
                <span class="today-text"><?php echo htmlspecialchars($window_message); ?></span>
            </div>
        </div>
        <div class="card">
            <h2 class="section-title">Academic Metrics</h2>
            <div class="tile-grid">
                <div class="tile">
                    <div class="value"><?php echo $enrolled_active_count; ?></div>
                    <div class="label">Units enrolled</div>
                </div>
                <div class="tile">
                    <div class="value"><?php echo $available_total; ?></div>
                    <div class="label">Units available</div>
                </div>
                <div class="tile">
                    <div class="value"><?php echo count($courses); ?></div>
                    <div class="label">Courses in catalogue</div>
                </div>
                <div class="tile">
                    <div class="value"><?php echo $progress; ?>%</div>
                    <div class="label">Completion progress</div>
                </div>
            </div>
            <p class="muted" style="margin-top: 14px;">Grades appear when the registrar updates your records.</p>
        </div>
    </div>

    <?php if ($notice !== "") : ?>
        <div class="notice-drawer" data-notice>
            <span><?php echo htmlspecialchars($notice); ?></span>
            <button class="notice-close" type="button" data-notice-close>Dismiss</button>
        </div>
    <?php endif; ?>

    <?php if (count($announcements) > 0) : ?>
        <div class="card" style="margin-bottom: 18px;">
            <h2 class="section-title">Announcements</h2>
            <div class="timeline">
                <?php foreach ($announcements as $announcement) : ?>
                    <div class="timeline-item">
                        <span class="dot"></span>
                        <div class="item-text">
                            <strong><?php echo htmlspecialchars($announcement["title"]); ?></strong><br>
                            <?php echo htmlspecialchars($announcement["body"]); ?>
                        </div>
                        <div class="item-time"><?php echo htmlspecialchars(date("M d", strtotime($announcement["created_at"]))); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="grid">
        <div class="card">
            <h2 class="section-title">Progress</h2>
            <div class="ring-wrap">
                <div class="ring" style="--progress: <?php echo $progress; ?>%;">
                    <div class="ring-label">
                        <div class="ring-value"><?php echo $progress; ?>%</div>
                        <div class="ring-caption">Completion</div>
                    </div>
                </div>
                <div class="ring-meta">
                    <p><strong><?php echo $enrolled_active_count; ?></strong> of <strong><?php echo $required_units; ?></strong> required units</p>
                    <p class="muted">Your plan updates when units are graded.</p>
                </div>
            </div>
        </div>
        <div class="card">
            <h2 class="section-title">Advisor</h2>
            <p><strong>Name:</strong> <?php echo htmlspecialchars($student["advisor_name"] ?: "Not assigned"); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($student["advisor_email"] ?: "Not assigned"); ?></p>
            <p><strong>Phone:</strong> <?php echo htmlspecialchars($student["advisor_phone"] ?: "Not assigned"); ?></p>
            <div class="action-row">
                <a class="btn" href="profile.php">Edit profile</a>
                <a class="btn secondary" href="#units">View units</a>
            </div>
        </div>
        <div class="card">
            <h2 class="section-title">Featured Unit</h2>
            <?php if ($featured) : ?>
                <p class="featured-title"><?php echo htmlspecialchars($featured["name"] ?? $featured["code"]); ?></p>
                <p class="muted">Unit code: <?php echo htmlspecialchars($featured["code"] ?? "-"); ?></p>
                <p class="muted">Lecturer: <?php echo htmlspecialchars($featured["lecturer"] ?? "TBA"); ?></p>
                <p class="muted">Semester: <?php echo htmlspecialchars($featured["semester"] ?? ""); ?></p>
            <?php else : ?>
                <p class="muted">No units available right now.</p>
            <?php endif; ?>
        </div>
        <div class="card">
            <h2 class="section-title">Export</h2>
            <p class="muted">Download or print your enrolled units.</p>
            <div class="action-row">
                <a class="btn" href="export.php">Export CSV</a>
                <a class="btn secondary" href="export_slip.php">Export PDF</a>
            </div>
        </div>
        <div class="card">
            <h2 class="section-title">Today's Schedule</h2>
            <div class="schedule">
                <?php foreach ($schedule_items as $item) : ?>
                    <div class="schedule-item">
                        <div class="time"><?php echo htmlspecialchars($item["time"]); ?></div>
                        <div class="event"><?php echo htmlspecialchars($item["event"]); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="card">
            <h2 class="section-title">Milestones</h2>
            <ul class="milestones">
                <?php foreach ($milestones as $m) : ?>
                    <li><?php echo htmlspecialchars($m); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div class="card">
            <h2 class="section-title">Learning Streak</h2>
            <div class="streak">
                <div class="streak-count"><?php echo $streak; ?></div>
                <div class="streak-copy">days on track</div>
            </div>
            <div class="streak-bar">
                <?php for ($i = 0; $i < 7; $i++) : ?>
                    <span class="streak-dot <?php echo $i < $streak ? "active" : ""; ?>"></span>
                <?php endfor; ?>
            </div>
        </div>
        <div class="card">
            <h2 class="section-title">Recent Activity</h2>
            <div class="timeline">
                <?php if (count($activities) === 0) : ?>
                    <div class="timeline-item">
                        <span class="dot"></span>
                        <div class="item-text">No activity yet. Enroll in a unit to get started.</div>
                        <div class="item-time">--</div>
                    </div>
                <?php else : ?>
                    <?php foreach ($activities as $activity) : ?>
                        <div class="timeline-item">
                            <span class="dot"></span>
                            <div class="item-text"><?php echo htmlspecialchars($activity["activity"]); ?></div>
                            <div class="item-time"><?php echo htmlspecialchars(date("M d, H:i", strtotime($activity["created_at"]))); ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <div class="card">
            <h2 class="section-title">Quick Notes</h2>
            <textarea class="notes" rows="4" placeholder="Write reminders, goals, or unit priorities..."><?php echo htmlspecialchars($note_body); ?></textarea>
            <button class="btn secondary" type="button" data-save-note>Save note</button>
        </div>
        <div class="card">
            <h2 class="section-title">Focus Timer</h2>
            <div class="timer">
                <div class="timer-display" data-timer-display>25:00</div>
                <div class="action-row">
                    <button class="btn" type="button" data-timer-start>Start</button>
                    <button class="btn secondary" type="button" data-timer-pause>Pause</button>
                    <button class="btn secondary" type="button" data-timer-reset>Reset</button>
                </div>
            </div>
        </div>
        <div class="card">
            <h2 class="section-title">Support</h2>
            <p class="muted">Need help? Reach the registrar or technical support.</p>
            <div class="action-row">
                <a class="btn" href="mailto:<?php echo htmlspecialchars($REGISTRAR_EMAIL); ?>">Registrar</a>
                <a class="btn secondary" href="mailto:<?php echo htmlspecialchars($SUPPORT_EMAIL); ?>">Tech Support</a>
            </div>
        </div>
    </div>

    <div class="card" style="margin-top: 18px;">
        <h2 class="section-title">Accent Color</h2>
        <p class="muted">Personalize the accent hue for your workspace.</p>
        <input class="accent-range" type="range" min="30" max="320" value="48" data-accent-range>
    </div>

    <div class="card" style="margin-top: 18px;">
        <h2 class="section-title">Personalization</h2>
        <p class="muted">Tune density, glass, motion, theme, contrast, and typography.</p>
        <div class="switch-grid">
            <label class="switch">
                <span>Compact Mode</span>
                <input type="checkbox" data-switch="density" data-on="compact" data-off="cozy">
                <em></em>
            </label>
            <label class="switch">
                <span>Glass Effect</span>
                <input type="checkbox" data-switch="glass" data-on="on" data-off="off">
                <em></em>
            </label>
            <label class="switch">
                <span>Motion</span>
                <input type="checkbox" data-switch="motion" data-on="on" data-off="off">
                <em></em>
            </label>
            <label class="switch">
                <span>Dark Mode</span>
                <input type="checkbox" data-switch="theme" data-on="dark-nebula" data-off="light-ocean">
                <em></em>
            </label>
            <label class="switch">
                <span>High Contrast</span>
                <input type="checkbox" data-local-switch="contrast" data-on="on" data-off="off">
                <em></em>
            </label>
            <label class="switch">
                <span>Focus Mode</span>
                <input type="checkbox" data-local-switch="focus" data-on="on" data-off="off">
                <em></em>
            </label>
            <label class="switch">
                <span>Large Text</span>
                <input type="checkbox" data-switch="large_text" data-on="on" data-off="off">
                <em></em>
            </label>
            <label class="switch">
                <span>Minimal Mode</span>
                <input type="checkbox" data-switch="minimal_mode" data-on="on" data-off="off">
                <em></em>
            </label>
        </div>
        <div class="toggle-row">
            <span class="muted" style="min-width: 120px;">Theme</span>
            <button class="toggle-pill" type="button" data-theme-value="light-ocean">Ocean</button>
            <button class="toggle-pill" type="button" data-theme-value="light-sunrise">Sunrise</button>
            <button class="toggle-pill" type="button" data-theme-value="dark-nebula">Nebula</button>
            <button class="toggle-pill" type="button" data-theme-value="dark-forest">Forest</button>
        </div>
        <div class="toggle-row">
            <span class="muted" style="min-width: 120px;">Typeface</span>
            <button class="toggle-pill" type="button" data-font="jakarta">Jakarta</button>
            <button class="toggle-pill" type="button" data-font="sora">Sora</button>
            <button class="toggle-pill" type="button" data-font="literata">Literata</button>
        </div>
        <div class="toggle-row">
            <span class="muted" style="min-width: 120px;">Background</span>
            <input class="accent-range" type="range" min="30" max="100" value="80" data-art-range>
        </div>
    </div>

    <div class="card" style="margin-top: 18px;">
        <h2 class="section-title">Profile Summary</h2>
        <p><strong>Name:</strong> <?php echo htmlspecialchars($student["first_name"] . " " . $student["last_name"]); ?></p>
        <p><strong>Reg No:</strong> <?php echo htmlspecialchars($student["reg_no"]); ?></p>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($student["email"]); ?></p>
        <p><strong>Program:</strong> <?php echo htmlspecialchars($student["program"]); ?></p>
        <p><strong>Year Level:</strong> <?php echo htmlspecialchars($student["year_level"]); ?></p>
        <p><strong>Phone:</strong> <?php echo htmlspecialchars($student["phone"] ?: "Not set"); ?></p>
    </div>

    <div class="card" style="margin-top: 18px;">
        <h2 class="section-title">Courses Catalogue</h2>
        <div class="course-marquee">
            <div class="course-track">
                <?php foreach (array_merge($courses, $courses) as $course) : ?>
                    <div class="course-card">
                        <h4><?php echo htmlspecialchars($course["code"]); ?></h4>
                        <p><?php echo htmlspecialchars($course["name"]); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <table>
            <thead>
            <tr>
                <th>Code</th>
                <th>Course</th>
                <th>Department</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($courses as $course) : ?>
                <tr>
                    <td><?php echo htmlspecialchars($course["code"]); ?></td>
                    <td><?php echo htmlspecialchars($course["name"]); ?></td>
                    <td><?php echo htmlspecialchars($course["department"]); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="card" style="margin-top: 18px;" id="units">
        <h2 class="section-title">Units Enrolled</h2>
        <div class="filter-row">
            <input class="filter-input" type="text" placeholder="Filter enrolled units" data-filter-enrolled>
        </div>
        <?php if (count($enrolled_units) === 0) : ?>
            <div class="empty-state">
                <div class="empty-blob"></div>
                <p class="muted">No units enrolled yet.</p>
            </div>
        <?php else : ?>
            <table data-enrolled-table>
                <thead>
                <tr>
                    <th>Unit</th>
                    <th>Title</th>
                    <th>Course</th>
                    <th>Lecturer</th>
                    <th>Semester</th>
                    <th>Status</th>
                    <th>Grade</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($enrolled_units as $unit) : ?>
                    <tr>
                        <td><?php echo htmlspecialchars($unit["code"]); ?></td>
                        <td><?php echo htmlspecialchars($unit["name"]); ?></td>
                        <td><?php echo htmlspecialchars($unit["course_code"]); ?></td>
                        <td><?php echo htmlspecialchars($unit["lecturer"]); ?></td>
                        <td><?php echo htmlspecialchars($unit["semester"]); ?></td>
                        <td><span class="status-badge status-<?php echo htmlspecialchars($unit["status"]); ?>"><?php echo htmlspecialchars($unit["status"]); ?></span></td>
                        <td><?php echo htmlspecialchars($unit["grade"] ?: "-"); ?></td>
                        <td>
                            <?php if ($unit["status"] === "enrolled") : ?>
                                <form method="post" action="drop.php" data-withdraw-form>
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                                    <input type="hidden" name="enrollment_id" value="<?php echo $unit["id"]; ?>">
                                    <button class="btn secondary" type="submit">Withdraw</button>
                                </form>
                            <?php else : ?>
                                <span class="muted">--</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <div class="pagination">
                <?php if ($enrolled_page > 1) : ?>
                    <a class="btn secondary" href="?enrolled_page=<?php echo $enrolled_page - 1; ?>#units">Prev</a>
                <?php endif; ?>
                <?php if ($enrolled_total > $enrolled_page * $page_size) : ?>
                    <a class="btn secondary" href="?enrolled_page=<?php echo $enrolled_page + 1; ?>#units">Next</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    
    <div class="card" style="margin-top: 18px;">
        <h2 class="section-title">Withdrawn Units</h2>
        <?php if (count($withdrawn_units) === 0) : ?>
            <div class="empty-state">
                <div class="empty-blob"></div>
                <p class="muted">No withdrawn units yet.</p>
            </div>
        <?php else : ?>
            <table>
                <thead>
                <tr>
                    <th>Unit</th>
                    <th>Title</th>
                    <th>Course</th>
                    <th>Lecturer</th>
                    <th>Semester</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($withdrawn_units as $unit) : ?>
                    <tr>
                        <td><?php echo htmlspecialchars($unit["code"]); ?></td>
                        <td><?php echo htmlspecialchars($unit["name"]); ?></td>
                        <td><?php echo htmlspecialchars($unit["course_code"]); ?></td>
                        <td><?php echo htmlspecialchars($unit["lecturer"]); ?></td>
                        <td><?php echo htmlspecialchars($unit["semester"]); ?></td>
                        <td><span class="status-badge status-withdrawn">withdrawn</span></td>
                        <td>
                            <form method="post" action="enroll.php">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                                <input type="hidden" name="unit_id" value="<?php echo $unit["unit_id"]; ?>">
                                <button class="btn secondary" type="submit">Re-enroll</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <div class="card" style="margin-top: 18px;">
        <h2 class="section-title">Available Units</h2>
        <p class="muted">Browse units you have not enrolled in yet.</p>
        <div class="filter-row">
            <input class="filter-input" type="text" placeholder="Filter units by code, title, or lecturer" data-filter-units>
        </div>
        <?php if (count($available_units) === 0) : ?>
            <div class="empty-state">
                <div class="empty-blob"></div>
                <p class="muted">No remaining units available.</p>
            </div>
        <?php else : ?>
            <table data-units-table>
                <thead>
                <tr>
                    <th>Unit</th>
                    <th>Title</th>
                    <th>Course</th>
                    <th>Lecturer</th>
                    <th>Semester</th>
                    <th>Year</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($available_units as $unit) : ?>
                    <tr>
                        <td><?php echo htmlspecialchars($unit["code"]); ?></td>
                        <td><?php echo htmlspecialchars($unit["name"]); ?></td>
                        <td><?php echo htmlspecialchars($unit["course_code"]); ?></td>
                        <td><?php echo htmlspecialchars($unit["lecturer"]); ?></td>
                        <td><?php echo htmlspecialchars($unit["semester"]); ?></td>
                        <td><?php echo htmlspecialchars($unit["year_level"]); ?></td>
                        <td>
                            <form method="post" action="enroll.php">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                                <input type="hidden" name="unit_id" value="<?php echo $unit["id"]; ?>">
                                <button class="btn secondary" type="submit">Enroll</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <div class="pagination">
                <?php if ($available_page > 1) : ?>
                    <a class="btn secondary" href="?available_page=<?php echo $available_page - 1; ?>#units">Prev</a>
                <?php endif; ?>
                <?php if ($available_total > $available_page * $page_size) : ?>
                    <a class="btn secondary" href="?available_page=<?php echo $available_page + 1; ?>#units">Next</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
<div class="quick-actions">
    <a class="quick-pill" href="#units">Enroll</a>
    <a class="quick-pill" href="profile.php">Profile</a>
    <button class="quick-pill" type="button" data-focus-toggle>Focus</button>
</div>
<div class="footer">
    <div><strong><?php echo htmlspecialchars($FOOTER_NAME); ?></strong></div>
    <div><?php echo htmlspecialchars($FOOTER_ADDRESS); ?></div>
    <div><?php echo htmlspecialchars($FOOTER_WEBSITE); ?></div>
    <div><?php echo htmlspecialchars($FOOTER_SOCIALS); ?></div>
</div>
<script src="assets/app.js"></script>
</body>
</html>

























