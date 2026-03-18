-- PURGE ALL STUDENT DATA
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE enrollments;
TRUNCATE TABLE students;
TRUNCATE TABLE notes;
TRUNCATE TABLE advisor_requests;
TRUNCATE TABLE activities;
TRUNCATE TABLE password_resets;
TRUNCATE TABLE login_attempts;
SET FOREIGN_KEY_CHECKS = 1;

-- RESET UNITS TO EXACTLY 11
DELETE FROM units;

INSERT INTO units (course_id, lecturer_id, code, name, semester, year_level) VALUES
(1, 1, 'DBIT-101', 'Introduction to Databases', 'Semester 1', 1),
(1, 2, 'DBIT-102', 'Programming Fundamentals', 'Semester 1', 1),
(1, 3, 'DBIT-103', 'Information Systems', 'Semester 1', 1),
(1, 1, 'DBIT-104', 'Data Communications', 'Semester 1', 1),
(1, 2, 'DBIT-105', 'Discrete Structures', 'Semester 1', 1),
(1, 3, 'DBIT-201', 'Web Development', 'Semester 2', 2),
(1, 1, 'DBIT-202', 'Database Systems II', 'Semester 2', 2),
(1, 2, 'DBIT-203', 'Mobile Application Dev', 'Semester 2', 2),
(1, 3, 'DBIT-204', 'Networking Essentials', 'Semester 2', 2),
(1, 1, 'DBIT-205', 'IT Project Management', 'Semester 2', 2),
(1, 2, 'DBIT-206', 'Human Computer Interaction', 'Semester 2', 2);
