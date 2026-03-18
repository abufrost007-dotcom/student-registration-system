CREATE DATABASE IF NOT EXISTS student_registration;
USE student_registration;

CREATE TABLE IF NOT EXISTS lecturers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(120) NOT NULL,
    phone VARCHAR(40) NULL
);

CREATE TABLE IF NOT EXISTS courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL,
    name VARCHAR(120) NOT NULL,
    department VARCHAR(120) NOT NULL
);

CREATE TABLE IF NOT EXISTS units (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    lecturer_id INT NOT NULL,
    code VARCHAR(20) NOT NULL,
    name VARCHAR(140) NOT NULL,
    semester VARCHAR(20) NOT NULL,
    year_level INT NOT NULL,
    day_of_week VARCHAR(20) NULL,
    start_time TIME NULL,
    end_time TIME NULL,
    room VARCHAR(60) NULL,
    FOREIGN KEY (course_id) REFERENCES courses(id),
    FOREIGN KEY (lecturer_id) REFERENCES lecturers(id)
);

CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reg_no VARCHAR(30) NOT NULL UNIQUE,
    first_name VARCHAR(60) NOT NULL,
    last_name VARCHAR(60) NOT NULL,
    email VARCHAR(120) NOT NULL UNIQUE,
    phone VARCHAR(40) NULL,
    program VARCHAR(120) NOT NULL,
    year_level INT NOT NULL,
    advisor_name VARCHAR(100) NULL,
    advisor_email VARCHAR(120) NULL,
    advisor_phone VARCHAR(40) NULL,
    password_hash VARCHAR(255) NOT NULL,
    email_verified TINYINT(1) NOT NULL DEFAULT 0,
    email_token VARCHAR(64) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS enrollments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    unit_id INT NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'enrolled',
    grade VARCHAR(5) NULL,
    enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_enrollment (student_id, unit_id),
    FOREIGN KEY (student_id) REFERENCES students(id),
    FOREIGN KEY (unit_id) REFERENCES units(id)
);

CREATE TABLE IF NOT EXISTS login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(120) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    token VARCHAR(64) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    used TINYINT(1) NOT NULL DEFAULT 0,
    FOREIGN KEY (student_id) REFERENCES students(id)
);

CREATE TABLE IF NOT EXISTS notes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    body TEXT NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id)
);

CREATE TABLE IF NOT EXISTS advisor_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    message TEXT NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'open',
    response TEXT NULL,
    responded_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id)
);

CREATE TABLE IF NOT EXISTS student_preferences (
    student_id INT PRIMARY KEY,
    theme VARCHAR(40) NULL,
    accent_hue INT NULL,
    density VARCHAR(20) NULL,
    glass VARCHAR(10) NULL,
    motion VARCHAR(10) NULL,
    font VARCHAR(20) NULL,
    art INT NULL,
    large_text VARCHAR(10) NULL,
    minimal_mode VARCHAR(10) NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id)
);

CREATE TABLE IF NOT EXISTS activities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    activity VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id)
);

CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(60) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS admin_activities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    activity VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admins(id)
);

CREATE TABLE IF NOT EXISTS institution_settings (
    id INT PRIMARY KEY DEFAULT 1,
    institution_name VARCHAR(140) NOT NULL DEFAULT 'Student Registration',
    logo_path VARCHAR(255) NULL,
    favicon_path VARCHAR(255) NULL,
    brand_color VARCHAR(20) NULL,
    max_units INT NOT NULL DEFAULT 6,
    hero_login TEXT NULL,
    hero_register TEXT NULL,
    address VARCHAR(255) NULL,
    website VARCHAR(255) NULL,
    socials VARCHAR(255) NULL,
    year_label VARCHAR(30) NULL DEFAULT 'Year',
    enrollment_start DATETIME NULL,
    enrollment_end DATETIME NULL
);

CREATE TABLE IF NOT EXISTS announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(160) NOT NULL,
    body TEXT NOT NULL,
    audience VARCHAR(20) NOT NULL DEFAULT 'students',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO institution_settings (id) VALUES (1)
ON DUPLICATE KEY UPDATE id = id;

INSERT INTO lecturers (name, email, phone) VALUES
('Dr. Amina Otieno', 'amina.otieno@college.edu', '0700-111-222'),
('Mr. Peter Mwangi', 'peter.mwangi@college.edu', '0700-222-333'),
('Ms. Grace Njeri', 'grace.njeri@college.edu', '0700-333-444');

INSERT INTO courses (code, name, department) VALUES
('DBIT', 'Diploma in Business IT', 'Computing'),
('BBIT', 'Bachelor of Business IT', 'Computing'),
('BSC-CS', 'BSc Computer Science', 'Computing');

INSERT INTO units (course_id, lecturer_id, code, name, semester, year_level) VALUES
(1, 1, 'DBIT-101', 'Introduction to Databases', 'Semester 1', 1),
(1, 2, 'DBIT-102', 'Programming Fundamentals', 'Semester 1', 1),
(1, 3, 'DBIT-201', 'Web Development', 'Semester 2', 2),
(2, 1, 'BBIT-210', 'Systems Analysis', 'Semester 2', 2),
(3, 2, 'CS-120', 'Discrete Mathematics', 'Semester 1', 1);

-- Add more units (20+ total per course)
INSERT INTO units (course_id, lecturer_id, code, name, semester, year_level) VALUES
(1, 1, 'DBIT-103', 'Information Systems', 'Semester 1', 1),
(1, 2, 'DBIT-104', 'Data Communications', 'Semester 1', 1),
(1, 3, 'DBIT-105', 'Discrete Structures', 'Semester 1', 1),
(1, 1, 'DBIT-106', 'Web Design Fundamentals', 'Semester 1', 1),
(1, 2, 'DBIT-107', 'Introduction to Programming II', 'Semester 2', 1),
(1, 3, 'DBIT-108', 'Computer Architecture', 'Semester 2', 1),
(1, 1, 'DBIT-202', 'Database Systems II', 'Semester 2', 2),
(1, 2, 'DBIT-203', 'Mobile Application Dev', 'Semester 2', 2),
(1, 3, 'DBIT-204', 'Networking Essentials', 'Semester 2', 2),
(1, 1, 'DBIT-205', 'IT Project Management', 'Semester 2', 2),
(1, 2, 'DBIT-206', 'Human Computer Interaction', 'Semester 2', 2),
(1, 3, 'DBIT-207', 'Operating Systems', 'Semester 2', 2),
(1, 1, 'DBIT-301', 'Cloud Computing', 'Semester 1', 3),
(1, 2, 'DBIT-302', 'Data Analytics', 'Semester 1', 3),
(1, 3, 'DBIT-303', 'Cybersecurity Basics', 'Semester 1', 3),
(2, 1, 'BBIT-211', 'Business Intelligence', 'Semester 2', 2),
(2, 2, 'BBIT-212', 'Enterprise Systems', 'Semester 2', 2),
(2, 3, 'BBIT-213', 'E-Commerce Platforms', 'Semester 2', 2),
(3, 1, 'CS-121', 'Algorithms I', 'Semester 1', 1),
(3, 2, 'CS-122', 'Programming Paradigms', 'Semester 1', 1);

