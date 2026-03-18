<?php
require_once "config.php";
require_admin();

$message = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verify_csrf();
    $action = $_POST["action"] ?? "";

    if ($action === "update_settings") {
        $institution_name = trim($_POST["institution_name"] ?? "");
        $brand_color = trim($_POST["brand_color"] ?? "");
        $max_units = intval($_POST["max_units"] ?? 6);
        $hero_login = trim($_POST["hero_login"] ?? "");
        $hero_register = trim($_POST["hero_register"] ?? "");
        $address = trim($_POST["address"] ?? "");
        $website = trim($_POST["website"] ?? "");
        $socials = trim($_POST["socials"] ?? "");
        $year_label = trim($_POST["year_label"] ?? "Year");
        $enrollment_start = trim($_POST["enrollment_start"] ?? "");
        $enrollment_end = trim($_POST["enrollment_end"] ?? "");

        $enrollment_start = $enrollment_start === "" ? null : $enrollment_start;
        $enrollment_end = $enrollment_end === "" ? null : $enrollment_end;

        $logo_path = null;
        $favicon_path = null;
        if (!empty($_FILES["logo"]["name"])) {
            $logo_name = time() . "_" . basename($_FILES["logo"]["name"]);
            $logo_dest = "assets/uploads/" . $logo_name;
            if (move_uploaded_file($_FILES["logo"]["tmp_name"], __DIR__ . "/" . $logo_dest)) {
                $logo_path = $logo_dest;
            }
        }
        if (!empty($_FILES["favicon"]["name"])) {
            $favicon_name = time() . "_" . basename($_FILES["favicon"]["name"]);
            $favicon_dest = "assets/uploads/" . $favicon_name;
            if (move_uploaded_file($_FILES["favicon"]["tmp_name"], __DIR__ . "/" . $favicon_dest)) {
                $favicon_path = $favicon_dest;
            }
        }

        $stmt = $mysqli->prepare("UPDATE institution_settings SET institution_name = ?, brand_color = ?, max_units = ?, hero_login = ?, hero_register = ?, address = ?, website = ?, socials = ?, year_label = ?, enrollment_start = ?, enrollment_end = ?, logo_path = COALESCE(?, logo_path), favicon_path = COALESCE(?, favicon_path) WHERE id = 1");
        $stmt->bind_param("ssissssssssss", $institution_name, $brand_color, $max_units, $hero_login, $hero_register, $address, $website, $socials, $year_label, $enrollment_start, $enrollment_end, $logo_path, $favicon_path);
        $message = $stmt->execute() ? "Settings updated." : "Failed to update settings.";
        add_admin_activity(current_admin_id(), "Updated institution settings");
    }
    if ($action === "add_announcement") {
        $title = trim($_POST["title"] ?? "");
        $body = trim($_POST["body"] ?? "");
        $audience = trim($_POST["audience"] ?? "students");
        if ($title !== "" && $body !== "") {
            $stmt = $mysqli->prepare("INSERT INTO announcements (title, body, audience, created_by) VALUES (?, ?, ?, ?)");
            $admin_id = current_admin_id();
            $stmt->bind_param("sssi", $title, $body, $audience, $admin_id);
            $message = $stmt->execute() ? "Announcement published." : "Failed to publish announcement.";
            add_admin_activity($admin_id, "Published announcement: " . $title);
        } else {
            $error = "Announcement title and body are required.";
        }
    }
    if ($action === "delete_announcement") {
        $announcement_id = intval($_POST["announcement_id"] ?? 0);
        if ($announcement_id) {
            $stmt = $mysqli->prepare("DELETE FROM announcements WHERE id = ?");
            $stmt->bind_param("i", $announcement_id);
            $message = $stmt->execute() ? "Announcement deleted." : "Failed to delete announcement.";
            add_admin_activity(current_admin_id(), "Deleted announcement #" . $announcement_id);
        }
    }
if ($action === "add_course") {
        $code = trim($_POST["code"] ?? "");
        $name = trim($_POST["name"] ?? "");
        $dept = trim($_POST["department"] ?? "");
        if ($code && $name && $dept) {
            $stmt = $mysqli->prepare("INSERT INTO courses (code, name, department) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $code, $name, $dept);
            if ($stmt->execute()) {
                $message = "Course added.";
                add_admin_activity(current_admin_id(), "Added course " . $code);
            } else {
                $message = "Failed to add course.";
            }
        } else {
            $error = "Fill in all course fields.";
        }
    }

    if ($action === "add_lecturer") {
        $name = trim($_POST["name"] ?? "");
        $email = trim($_POST["email"] ?? "");
        $phone = trim($_POST["phone"] ?? "");
        if ($name && $email) {
            $stmt = $mysqli->prepare("INSERT INTO lecturers (name, email, phone) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $name, $email, $phone);
            if ($stmt->execute()) {
                $message = "Lecturer added.";
                add_admin_activity(current_admin_id(), "Added lecturer " . $name);
            } else {
                $message = "Failed to add lecturer.";
            }
        } else {
            $error = "Fill in lecturer name and email.";
        }
    }

    if ($action === "add_unit") {
        $course_id = intval($_POST["course_id"] ?? 0);
        $lecturer_id = intval($_POST["lecturer_id"] ?? 0);
        $code = trim($_POST["code"] ?? "");
        $name = trim($_POST["name"] ?? "");
        $semester = trim($_POST["semester"] ?? "");
        $year_level = intval($_POST["year_level"] ?? 1);
        $day_of_week = trim($_POST["day_of_week"] ?? "");
        $start_time = trim($_POST["start_time"] ?? "");
        $end_time = trim($_POST["end_time"] ?? "");
        $room = trim($_POST["room"] ?? "");
        $start_time = $start_time === "" ? null : $start_time;
        $end_time = $end_time === "" ? null : $end_time;
        $day_of_week = $day_of_week === "" ? null : $day_of_week;
        $room = $room === "" ? null : $room;
        if ($course_id && $lecturer_id && $code && $name && $semester) {
            $stmt = $mysqli->prepare("INSERT INTO units (course_id, lecturer_id, code, name, semester, year_level, day_of_week, start_time, end_time, room) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iisssissss", $course_id, $lecturer_id, $code, $name, $semester, $year_level, $day_of_week, $start_time, $end_time, $room);
            if ($stmt->execute()) {
                $message = "Unit added.";
                add_admin_activity(current_admin_id(), "Added unit " . $code);
            } else {
                $message = "Failed to add unit.";
            }
        } else {
            $error = "Fill in all unit fields.";
        }
    }

    if ($action === "update_course") {
        $course_id = intval($_POST["course_id"] ?? 0);
        $code = trim($_POST["code"] ?? "");
        $name = trim($_POST["name"] ?? "");
        $dept = trim($_POST["department"] ?? "");
        if ($course_id && $code && $name && $dept) {
            $stmt = $mysqli->prepare("UPDATE courses SET code = ?, name = ?, department = ? WHERE id = ?");
            $stmt->bind_param("sssi", $code, $name, $dept, $course_id);
            $message = $stmt->execute() ? "Course updated." : "Failed to update course.";
            add_admin_activity(current_admin_id(), "Updated course #" . $course_id);
        }
    }

    if ($action === "delete_course") {
        $course_id = intval($_POST["course_id"] ?? 0);
        if ($course_id) {
            $stmt = $mysqli->prepare("DELETE FROM courses WHERE id = ?");
            $stmt->bind_param("i", $course_id);
            $message = $stmt->execute() ? "Course deleted." : "Failed to delete course.";
            add_admin_activity(current_admin_id(), "Deleted course #" . $course_id);
        }
    }

    if ($action === "update_lecturer") {
        $lecturer_id = intval($_POST["lecturer_id"] ?? 0);
        $name = trim($_POST["name"] ?? "");
        $email = trim($_POST["email"] ?? "");
        $phone = trim($_POST["phone"] ?? "");
        if ($lecturer_id && $name && $email) {
            $stmt = $mysqli->prepare("UPDATE lecturers SET name = ?, email = ?, phone = ? WHERE id = ?");
            $stmt->bind_param("sssi", $name, $email, $phone, $lecturer_id);
            $message = $stmt->execute() ? "Lecturer updated." : "Failed to update lecturer.";
            add_admin_activity(current_admin_id(), "Updated lecturer #" . $lecturer_id);
        }
    }

    if ($action === "delete_lecturer") {
        $lecturer_id = intval($_POST["lecturer_id"] ?? 0);
        if ($lecturer_id) {
            $stmt = $mysqli->prepare("DELETE FROM lecturers WHERE id = ?");
            $stmt->bind_param("i", $lecturer_id);
            $message = $stmt->execute() ? "Lecturer deleted." : "Failed to delete lecturer.";
            add_admin_activity(current_admin_id(), "Deleted lecturer #" . $lecturer_id);
        }
    }

    if ($action === "update_unit") {
        $unit_id = intval($_POST["unit_id"] ?? 0);
        $course_id = intval($_POST["course_id"] ?? 0);
        $lecturer_id = intval($_POST["lecturer_id"] ?? 0);
        $code = trim($_POST["code"] ?? "");
        $name = trim($_POST["name"] ?? "");
        $semester = trim($_POST["semester"] ?? "");
        $year_level = intval($_POST["year_level"] ?? 1);
        $day_of_week = trim($_POST["day_of_week"] ?? "");
        $start_time = trim($_POST["start_time"] ?? "");
        $end_time = trim($_POST["end_time"] ?? "");
        $room = trim($_POST["room"] ?? "");
        $day_of_week = $day_of_week === "" ? null : $day_of_week;
        $start_time = $start_time === "" ? null : $start_time;
        $end_time = $end_time === "" ? null : $end_time;
        $room = $room === "" ? null : $room;
        if ($unit_id && $course_id && $lecturer_id && $code && $name && $semester) {
            $stmt = $mysqli->prepare("UPDATE units SET course_id = ?, lecturer_id = ?, code = ?, name = ?, semester = ?, year_level = ?, day_of_week = ?, start_time = ?, end_time = ?, room = ? WHERE id = ?");
            $stmt->bind_param("iisssissssi", $course_id, $lecturer_id, $code, $name, $semester, $year_level, $day_of_week, $start_time, $end_time, $room, $unit_id);
            $message = $stmt->execute() ? "Unit updated." : "Failed to update unit.";
            add_admin_activity(current_admin_id(), "Updated unit #" . $unit_id);
        }
    }

    if ($action === "delete_unit") {
        $unit_id = intval($_POST["unit_id"] ?? 0);
        if ($unit_id) {
            $stmt = $mysqli->prepare("DELETE FROM units WHERE id = ?");
            $stmt->bind_param("i", $unit_id);
            $message = $stmt->execute() ? "Unit deleted." : "Failed to delete unit.";
            add_admin_activity(current_admin_id(), "Deleted unit #" . $unit_id);
        }
    }

    if ($action === "update_student") {
        $student_id = intval($_POST["student_id"] ?? 0);
        $program = trim($_POST["program"] ?? "");
        $year_level = intval($_POST["year_level"] ?? 1);
        $email_verified = intval($_POST["email_verified"] ?? 0);
        if ($student_id && $program !== "") {
            $stmt = $mysqli->prepare("UPDATE students SET program = ?, year_level = ?, email_verified = ? WHERE id = ?");
            $stmt->bind_param("siii", $program, $year_level, $email_verified, $student_id);
            $message = $stmt->execute() ? "Student updated." : "Failed to update student.";
            add_admin_activity(current_admin_id(), "Updated student #" . $student_id);
        }
    }

    if ($action === "resolve_request") {
        $request_id = intval($_POST["request_id"] ?? 0);
        $advisor_name = trim($_POST["advisor_name"] ?? "");
        $advisor_email = trim($_POST["advisor_email"] ?? "");
        $advisor_phone = trim($_POST["advisor_phone"] ?? "");
        $status = trim($_POST["status"] ?? "open");
        $response = trim($_POST["response"] ?? "");
        if ($request_id) {
            $req_stmt = $mysqli->prepare("SELECT student_id FROM advisor_requests WHERE id = ?");
            $req_stmt->bind_param("i", $request_id);
            $req_stmt->execute();
            $req_stmt->bind_result($student_id);
            if ($req_stmt->fetch()) {
                $req_stmt->close();
                $update_req = $mysqli->prepare("UPDATE advisor_requests SET status = ?, response = ?, responded_at = NOW() WHERE id = ?");
                $update_req->bind_param("ssi", $status, $response, $request_id);
                $update_req->execute();
                if ($advisor_name || $advisor_email || $advisor_phone) {
                    $update_student = $mysqli->prepare("UPDATE students SET advisor_name = ?, advisor_email = ?, advisor_phone = ? WHERE id = ?");
                    $update_student->bind_param("sssi", $advisor_name, $advisor_email, $advisor_phone, $student_id);
                    $update_student->execute();
                }
                $message = "Advisor request updated.";
                add_admin_activity(current_admin_id(), "Resolved advisor request #" . $request_id);
            }
        }
    }

    if ($action === "update_grade") {
        $enroll_id = intval($_POST["enrollment_id"] ?? 0);
        $grade = trim($_POST["grade"] ?? "");
        $status = trim($_POST["status"] ?? "enrolled");
        $valid_grade = true;
        if ($grade !== "") {
            if (is_numeric($grade)) {
                $num = intval($grade);
                $valid_grade = $num >= 0 && $num <= 100;
            } else {
                $valid_grade = preg_match("/^(A|A-|B\\+|B|B-|C\\+|C|C-|D\\+|D|D-|E|F)$/", strtoupper($grade)) === 1;
            }
        }
        if ($enroll_id && $valid_grade) {
            $stmt = $mysqli->prepare("UPDATE enrollments SET grade = ?, status = ? WHERE id = ?");
            $stmt->bind_param("ssi", $grade, $status, $enroll_id);
            $message = $stmt->execute() ? "Grade updated." : "Failed to update grade.";
            add_admin_activity(current_admin_id(), "Updated grade for enrollment #" . $enroll_id);
        } elseif (!$valid_grade) {
            $error = "Invalid grade. Use A, B+, C, or numeric 0-100.";
        }
    }
}

$courses = $mysqli->query("SELECT id, code, name, department FROM courses ORDER BY code")->fetch_all(MYSQLI_ASSOC);
$lecturers = $mysqli->query("SELECT id, name, email, phone FROM lecturers ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$units = $mysqli->query(
    "SELECT u.id, u.code, u.name, u.semester, u.year_level, u.day_of_week, u.start_time, u.end_time, u.room, c.code AS course_code, l.name AS lecturer
     FROM units u
     JOIN courses c ON u.course_id = c.id
     JOIN lecturers l ON u.lecturer_id = l.id
     ORDER BY u.code"
)->fetch_all(MYSQLI_ASSOC);

$enroll_q = trim($_GET["q"] ?? "");
$enroll_page = max(1, intval($_GET["page"] ?? 1));
$enroll_limit = 50;
$enroll_offset = ($enroll_page - 1) * $enroll_limit;
$where = "";
if ($enroll_q !== "") {
    $safe_q = "%" . $mysqli->real_escape_string($enroll_q) . "%";
    $where = "WHERE s.reg_no LIKE '{$safe_q}' OR s.first_name LIKE '{$safe_q}' OR s.last_name LIKE '{$safe_q}' OR u.code LIKE '{$safe_q}' OR u.name LIKE '{$safe_q}'";
}
$enrollments = $mysqli->query(
    "SELECT e.id, s.reg_no, CONCAT(s.first_name,' ',s.last_name) AS student_name, u.code AS unit_code, u.name AS unit_name, e.status, e.grade
     FROM enrollments e
     JOIN students s ON e.student_id = s.id
     JOIN units u ON e.unit_id = u.id
     {$where}
     ORDER BY e.enrolled_at DESC
     LIMIT {$enroll_limit} OFFSET {$enroll_offset}"
)->fetch_all(MYSQLI_ASSOC);

$settings_row = $mysqli->query("SELECT * FROM institution_settings WHERE id = 1")->fetch_assoc();
$announcements = $mysqli->query("SELECT id, title, body, audience, created_at FROM announcements ORDER BY created_at DESC LIMIT 20")->fetch_all(MYSQLI_ASSOC);
$advisor_requests = $mysqli->query(
    "SELECT ar.id, CONCAT(s.first_name,' ',s.last_name) AS student_name, s.reg_no, ar.message, ar.status, ar.response, ar.created_at, ar.responded_at
     FROM advisor_requests ar
     JOIN students s ON ar.student_id = s.id
     ORDER BY ar.created_at DESC
     LIMIT 20"
)->fetch_all(MYSQLI_ASSOC);

$student_q = trim($_GET["student_q"] ?? "");
$student_where = "";
if ($student_q !== "") {
    $safe_student = "%" . $mysqli->real_escape_string($student_q) . "%";
    $student_where = "WHERE reg_no LIKE '{$safe_student}' OR first_name LIKE '{$safe_student}' OR last_name LIKE '{$safe_student}' OR email LIKE '{$safe_student}'";
}
$students = $mysqli->query(
    "SELECT id, reg_no, first_name, last_name, email, program, year_level
     FROM students
     {$student_where}
     ORDER BY created_at DESC
     LIMIT 100"
)->fetch_all(MYSQLI_ASSOC);

$admin_logs = $mysqli->query(
    "SELECT a.activity, a.created_at, ad.username
     FROM admin_activities a
     JOIN admins ad ON a.admin_id = ad.id
     ORDER BY a.created_at DESC
     LIMIT 20"
)->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($APP_NAME); ?> - Admin Dashboard</title>
    <meta name="description" content="Admin panel for course, unit, and grade management.">
    <link rel="icon" type="image/png" href="<?php echo htmlspecialchars($FAVICON_PATH); ?>">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="container">
    <div class="topbar">
        <div class="brand"><?php echo brand_html(); ?> Admin</div>
        <button class="nav-toggle" type="button" data-nav-toggle>Menu</button>
        <div class="nav">
            <a href="admin_dashboard.php">Dashboard</a>
            <a href="admin_logout.php">Logout</a>
        </div>
    </div>

    <?php if ($message !== "") : ?>
        <div class="alert"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    <?php if ($error !== "") : ?>
        <div class="alert error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="grid">
        <div class="card">
            <h2 class="section-title">Add Course</h2>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                <input type="hidden" name="action" value="add_course">
                <div class="form-group">
                    <label>Course Code</label>
                    <input type="text" name="code" required>
                </div>
                <div class="form-group">
                    <label>Course Name</label>
                    <input type="text" name="name" required>
                </div>
                <div class="form-group">
                    <label>Department</label>
                    <input type="text" name="department" required>
                </div>
                <button class="btn" type="submit">Add Course</button>
            </form>
        </div>
        <div class="card">
            <h2 class="section-title">Add Lecturer</h2>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                <input type="hidden" name="action" value="add_lecturer">
                <div class="form-group">
                    <label>Name</label>
                    <input type="text" name="name" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" required>
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <input type="text" name="phone">
                </div>
                <button class="btn" type="submit">Add Lecturer</button>
            </form>
        </div>
        <div class="card">
            <h2 class="section-title">Add Unit</h2>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                <input type="hidden" name="action" value="add_unit">
                <div class="form-group">
                    <label>Course</label>
                    <select name="course_id" required>
                        <?php foreach ($courses as $course) : ?>
                            <option value="<?php echo $course["id"]; ?>"><?php echo htmlspecialchars($course["code"] . " - " . $course["name"]); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Lecturer</label>
                    <select name="lecturer_id" required>
                        <?php foreach ($lecturers as $lecturer) : ?>
                            <option value="<?php echo $lecturer["id"]; ?>"><?php echo htmlspecialchars($lecturer["name"]); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Unit Code</label>
                    <input type="text" name="code" required>
                </div>
                <div class="form-group">
                    <label>Unit Title</label>
                    <input type="text" name="name" required>
                </div>
                <div class="form-group">
                    <label>Semester</label>
                    <input type="text" name="semester" placeholder="Semester 1" required>
                </div>
                <div class="form-group">
                    <label>Year Level</label>
                    <select name="year_level">
                        <option value="1">Year 1</option>
                        <option value="2">Year 2</option>
                        <option value="3">Year 3</option>
                        <option value="4">Year 4</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Day of Week</label>
                    <input type="text" name="day_of_week" placeholder="Monday">
                </div>
                <div class="form-group">
                    <label>Start Time</label>
                    <input type="time" name="start_time">
                </div>
                <div class="form-group">
                    <label>End Time</label>
                    <input type="time" name="end_time">
                </div>
                <div class="form-group">
                    <label>Room</label>
                    <input type="text" name="room" placeholder="Lab 2">
                </div>
                <button class="btn" type="submit">Add Unit</button>
            </form>
        </div>
    </div>

    <div class="card" style="margin-top: 18px;">
        <h2 class="section-title">Enrollment Grades</h2>
        <form method="get" style="margin-bottom: 10px;">
            <div class="filter-row">
                <input class="filter-input" type="text" name="q" placeholder="Search student, reg no, or unit" value="<?php echo htmlspecialchars($enroll_q); ?>">
            </div>
        </form>
        <table>
            <thead>
            <tr>
                <th>Student</th>
                <th>Reg No</th>
                <th>Unit</th>
                <th>Status</th>
                <th>Grade</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($enrollments as $row) : ?>
                <tr>
                    <td><?php echo htmlspecialchars($row["student_name"]); ?></td>
                    <td><?php echo htmlspecialchars($row["reg_no"]); ?></td>
                    <td><?php echo htmlspecialchars($row["unit_code"] . " - " . $row["unit_name"]); ?></td>
                    <td><?php echo htmlspecialchars($row["status"]); ?></td>
                    <td><?php echo htmlspecialchars($row["grade"] ?: "-"); ?></td>
                    <td>
                        <form method="post">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                            <input type="hidden" name="action" value="update_grade">
                            <input type="hidden" name="enrollment_id" value="<?php echo $row["id"]; ?>">
                            <input type="text" name="grade" placeholder="A, B+, 70" style="width: 80px;">
                            <select name="status">
                                <option value="enrolled" <?php if ($row["status"] === "enrolled") echo "selected"; ?>>Enrolled</option>
                                <option value="completed" <?php if ($row["status"] === "completed") echo "selected"; ?>>Completed</option>
                                <option value="withdrawn" <?php if ($row["status"] === "withdrawn") echo "selected"; ?>>Withdrawn</option>
                            </select>
                            <button class="btn secondary" type="submit">Update</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <div class="pagination">
            <?php if ($enroll_page > 1) : ?>
                <a class="btn secondary" href="?page=<?php echo $enroll_page - 1; ?>&q=<?php echo urlencode($enroll_q); ?>">Prev</a>
            <?php endif; ?>
            <?php if (count($enrollments) === $enroll_limit) : ?>
                <a class="btn secondary" href="?page=<?php echo $enroll_page + 1; ?>&q=<?php echo urlencode($enroll_q); ?>">Next</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="card" style="margin-top: 18px;">
        <h2 class="section-title">Advisor Requests</h2>
        <table>
            <thead>
            <tr>
                <th>Student</th>
                <th>Reg No</th>
                <th>Message</th>
                <th>Status</th>
                <th>Created</th>
                <th>Response</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($advisor_requests as $req) : ?>
                <tr>
                    <td><?php echo htmlspecialchars($req["student_name"]); ?></td>
                    <td><?php echo htmlspecialchars($req["reg_no"]); ?></td>
                    <td><?php echo htmlspecialchars($req["message"]); ?></td>
                    <td><?php echo htmlspecialchars($req["status"]); ?></td>
                    <td><?php echo htmlspecialchars($req["created_at"]); ?></td>
                    <td><?php echo htmlspecialchars($req["response"] ?? "-"); ?></td>
                    <td>
                        <form method="post">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                            <input type="hidden" name="action" value="resolve_request">
                            <input type="hidden" name="request_id" value="<?php echo $req["id"]; ?>">
                            <input type="text" name="advisor_name" placeholder="Advisor name" style="width: 140px;">
                            <input type="text" name="advisor_email" placeholder="Advisor email" style="width: 160px;">
                            <input type="text" name="advisor_phone" placeholder="Advisor phone" style="width: 120px;">
                            <input type="text" name="response" placeholder="Response" style="width: 160px;">
                            <select name="status">
                                <option value="open" <?php if ($req["status"] === "open") echo "selected"; ?>>Open</option>
                                <option value="resolved" <?php if ($req["status"] === "resolved") echo "selected"; ?>>Resolved</option>
                            </select>
                            <button class="btn secondary" type="submit">Update</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="card" style="margin-top: 18px;">
        <h2 class="section-title">Institution Settings</h2>
        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
            <input type="hidden" name="action" value="update_settings">
            <div class="grid">
                <div class="form-group">
                    <label>Institution Name</label>
                    <input type="text" name="institution_name" value="<?php echo htmlspecialchars($settings_row["institution_name"] ?? $APP_NAME); ?>" required>
                </div>
                <div class="form-group">
                    <label>Brand Color (hex)</label>
                    <input type="text" name="brand_color" value="<?php echo htmlspecialchars($settings_row["brand_color"] ?? ""); ?>" placeholder="#4fd3c4">
                </div>
                <div class="form-group">
                    <label>Max Units</label>
                    <input type="number" name="max_units" min="1" max="12" value="<?php echo htmlspecialchars($settings_row["max_units"] ?? $MAX_UNITS); ?>">
                </div>
                <div class="form-group">
                    <label>Login Hero Text</label>
                    <input type="text" name="hero_login" value="<?php echo htmlspecialchars($settings_row["hero_login"] ?? $HERO_LOGIN); ?>">
                </div>
                <div class="form-group">
                    <label>Register Hero Text</label>
                    <input type="text" name="hero_register" value="<?php echo htmlspecialchars($settings_row["hero_register"] ?? $HERO_REGISTER); ?>">
                </div>
                <div class="form-group">
                    <label>Address</label>
                    <input type="text" name="address" value="<?php echo htmlspecialchars($settings_row["address"] ?? ""); ?>">
                </div>
                <div class="form-group">
                    <label>Website</label>
                    <input type="text" name="website" value="<?php echo htmlspecialchars($settings_row["website"] ?? ""); ?>">
                </div>
                <div class="form-group">
                    <label>Social Links (comma)</label>
                    <input type="text" name="socials" value="<?php echo htmlspecialchars($settings_row["socials"] ?? ""); ?>">
                </div>
                <div class="form-group">
                    <label>Year Label</label>
                    <input type="text" name="year_label" value="<?php echo htmlspecialchars($settings_row["year_label"] ?? $YEAR_LABEL); ?>" placeholder="Year">
                </div>
                <div class="form-group">
                    <label>Enrollment Start</label>
                    <input type="datetime-local" name="enrollment_start" value="<?php echo !empty($settings_row["enrollment_start"]) ? htmlspecialchars(date("Y-m-d\\TH:i", strtotime($settings_row["enrollment_start"]))) : ""; ?>">
                </div>
                <div class="form-group">
                    <label>Enrollment End</label>
                    <input type="datetime-local" name="enrollment_end" value="<?php echo !empty($settings_row["enrollment_end"]) ? htmlspecialchars(date("Y-m-d\\TH:i", strtotime($settings_row["enrollment_end"]))) : ""; ?>">
                </div>
                <div class="form-group">
                    <label>Logo Upload</label>
                    <input type="file" name="logo" accept="image/*">
                </div>
                <div class="form-group">
                    <label>Favicon Upload</label>
                    <input type="file" name="favicon" accept="image/*">
                </div>
            </div>
            <button class="btn" type="submit">Save Settings</button>
        </form>
    </div>

    <div class="card" style="margin-top: 18px;">
        <h2 class="section-title">Announcements</h2>
        <form method="post" style="margin-bottom: 16px;">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
            <input type="hidden" name="action" value="add_announcement">
            <div class="grid">
                <div class="form-group">
                    <label>Title</label>
                    <input type="text" name="title" required>
                </div>
                <div class="form-group">
                    <label>Audience</label>
                    <select name="audience">
                        <option value="students">Students</option>
                        <option value="all">All users</option>
                    </select>
                </div>
                <div class="form-group" style="grid-column: 1 / -1;">
                    <label>Message</label>
                    <input type="text" name="body" required>
                </div>
            </div>
            <button class="btn" type="submit">Publish Announcement</button>
        </form>
        <?php if (count($announcements) === 0) : ?>
            <p class="muted">No announcements yet.</p>
        <?php else : ?>
            <table>
                <thead>
                <tr>
                    <th>Title</th>
                    <th>Audience</th>
                    <th>Created</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($announcements as $announcement) : ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($announcement["title"]); ?></strong><br><span class="muted"><?php echo htmlspecialchars($announcement["body"]); ?></span></td>
                        <td><?php echo htmlspecialchars($announcement["audience"]); ?></td>
                        <td><?php echo htmlspecialchars($announcement["created_at"]); ?></td>
                        <td>
                            <form method="post">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                                <input type="hidden" name="action" value="delete_announcement">
                                <input type="hidden" name="announcement_id" value="<?php echo $announcement["id"]; ?>">
                                <button class="btn secondary" type="submit">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <div class="card" style="margin-top: 18px;">
        <h2 class="section-title">Courses</h2>
        <table>
            <thead>
            <tr>
                <th>Code</th>
                <th>Course</th>
                <th>Department</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($courses as $course) : ?>
                <tr>
                    <td>
                        <form method="post">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                            <input type="hidden" name="action" value="update_course">
                            <input type="hidden" name="course_id" value="<?php echo $course["id"]; ?>">
                            <input type="text" name="code" value="<?php echo htmlspecialchars($course["code"]); ?>" style="width: 90px;">
                    </td>
                    <td><input type="text" name="name" value="<?php echo htmlspecialchars($course["name"]); ?>" style="width: 180px;"></td>
                    <td><input type="text" name="department" value="<?php echo htmlspecialchars($course["department"]); ?>" style="width: 140px;"></td>
                    <td>
                            <button class="btn secondary" type="submit">Save</button>
                        </form>
                        <form method="post" style="margin-top:6px;">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                            <input type="hidden" name="action" value="delete_course">
                            <input type="hidden" name="course_id" value="<?php echo $course["id"]; ?>">
                            <button class="btn secondary" type="submit">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="card" style="margin-top: 18px;">
        <h2 class="section-title">Lecturers</h2>
        <table>
            <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($lecturers as $lecturer) : ?>
                <tr>
                    <td>
                        <form method="post">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                            <input type="hidden" name="action" value="update_lecturer">
                            <input type="hidden" name="lecturer_id" value="<?php echo $lecturer["id"]; ?>">
                            <input type="text" name="name" value="<?php echo htmlspecialchars($lecturer["name"]); ?>" style="width: 160px;">
                    </td>
                    <td><input type="text" name="email" value="<?php echo htmlspecialchars($lecturer["email"]); ?>" style="width: 180px;"></td>
                    <td><input type="text" name="phone" value="<?php echo htmlspecialchars($lecturer["phone"]); ?>" style="width: 120px;"></td>
                    <td>
                            <button class="btn secondary" type="submit">Save</button>
                        </form>
                        <form method="post" style="margin-top:6px;">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                            <input type="hidden" name="action" value="delete_lecturer">
                            <input type="hidden" name="lecturer_id" value="<?php echo $lecturer["id"]; ?>">
                            <button class="btn secondary" type="submit">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="card" style="margin-top: 18px;">
        <h2 class="section-title">Units</h2>
        <table>
            <thead>
            <tr>
                <th>Code</th>
                <th>Title</th>
                <th>Course</th>
                <th>Lecturer</th>
                <th>Semester</th>
                <th>Year</th>
                <th>Day</th>
                <th>Start</th>
                <th>End</th>
                <th>Room</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($units as $unit) : ?>
                <tr>
                    <td>
                        <form method="post">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                            <input type="hidden" name="action" value="update_unit">
                            <input type="hidden" name="unit_id" value="<?php echo $unit["id"]; ?>">
                            <input type="text" name="code" value="<?php echo htmlspecialchars($unit["code"]); ?>" style="width: 90px;">
                    </td>
                    <td><input type="text" name="name" value="<?php echo htmlspecialchars($unit["name"]); ?>" style="width: 180px;"></td>
                    <td>
                        <select name="course_id">
                            <?php foreach ($courses as $course) : ?>
                                <option value="<?php echo $course["id"]; ?>" <?php if ($course["code"] === $unit["course_code"]) echo "selected"; ?>>
                                    <?php echo htmlspecialchars($course["code"]); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td>
                        <select name="lecturer_id">
                            <?php foreach ($lecturers as $lecturer) : ?>
                                <option value="<?php echo $lecturer["id"]; ?>" <?php if ($lecturer["name"] === $unit["lecturer"]) echo "selected"; ?>>
                                    <?php echo htmlspecialchars($lecturer["name"]); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td><input type="text" name="semester" value="<?php echo htmlspecialchars($unit["semester"]); ?>" style="width: 100px;"></td>
                    <td>
                        <select name="year_level">
                            <option value="1" <?php if ($unit["year_level"] == 1) echo "selected"; ?>>Year 1</option>
                            <option value="2" <?php if ($unit["year_level"] == 2) echo "selected"; ?>>Year 2</option>
                            <option value="3" <?php if ($unit["year_level"] == 3) echo "selected"; ?>>Year 3</option>
                            <option value="4" <?php if ($unit["year_level"] == 4) echo "selected"; ?>>Year 4</option>
                        </select>
                    </td>
                    <td><input type="text" name="day_of_week" value="<?php echo htmlspecialchars($unit["day_of_week"] ?? ""); ?>" style="width: 90px;"></td>
                    <td><input type="time" name="start_time" value="<?php echo htmlspecialchars($unit["start_time"] ?? ""); ?>" style="width: 120px;"></td>
                    <td><input type="time" name="end_time" value="<?php echo htmlspecialchars($unit["end_time"] ?? ""); ?>" style="width: 120px;"></td>
                    <td><input type="text" name="room" value="<?php echo htmlspecialchars($unit["room"] ?? ""); ?>" style="width: 90px;"></td>
                    <td>
                            <button class="btn secondary" type="submit">Save</button>
                        </form>
                        <form method="post" style="margin-top:6px;">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                            <input type="hidden" name="action" value="delete_unit">
                            <input type="hidden" name="unit_id" value="<?php echo $unit["id"]; ?>">
                            <button class="btn secondary" type="submit">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="card" style="margin-top: 18px;">
        <h2 class="section-title">Students</h2>
        <form method="get" style="margin-bottom: 10px;">
            <div class="filter-row">
                <input class="filter-input" type="text" name="student_q" placeholder="Search students" value="<?php echo htmlspecialchars($student_q); ?>">
            </div>
        </form>
        <table>
            <thead>
            <tr>
                <th>Reg No</th>
                <th>Name</th>
                <th>Email</th>
                <th>Program</th>
                <th>Year</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($students as $s) : ?>
                <tr>
                    <td><?php echo htmlspecialchars($s["reg_no"]); ?></td>
                    <td><?php echo htmlspecialchars($s["first_name"] . " " . $s["last_name"]); ?></td>
                    <td><?php echo htmlspecialchars($s["email"]); ?></td>
                    <td>
                        <form method="post">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                            <input type="hidden" name="action" value="update_student">
                            <input type="hidden" name="student_id" value="<?php echo $s["id"]; ?>">
                            <input type="text" name="program" value="<?php echo htmlspecialchars($s["program"]); ?>" style="width: 90px;">
                    </td>
                    <td>
                            <select name="year_level">
                                <option value="1" <?php if ($s["year_level"] == 1) echo "selected"; ?>>Year 1</option>
                                <option value="2" <?php if ($s["year_level"] == 2) echo "selected"; ?>>Year 2</option>
                                <option value="3" <?php if ($s["year_level"] == 3) echo "selected"; ?>>Year 3</option>
                                <option value="4" <?php if ($s["year_level"] == 4) echo "selected"; ?>>Year 4</option>
                            </select>
                    </td>
                    <td>
                            <button class="btn secondary" type="submit">Save</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="card" style="margin-top: 18px;">
        <h2 class="section-title">Admin Activity Log</h2>
        <table>
            <thead>
            <tr>
                <th>Admin</th>
                <th>Activity</th>
                <th>Time</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($admin_logs as $log) : ?>
                <tr>
                    <td><?php echo htmlspecialchars($log["username"]); ?></td>
                    <td><?php echo htmlspecialchars($log["activity"]); ?></td>
                    <td><?php echo htmlspecialchars($log["created_at"]); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<script src="assets/app.js"></script>
</body>
</html>


