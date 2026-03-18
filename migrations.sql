ALTER TABLE students
    ADD COLUMN email_verified TINYINT(1) NOT NULL DEFAULT 0,
    ADD COLUMN email_token VARCHAR(64) NULL;

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
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
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
    socials VARCHAR(255) NULL
);

INSERT INTO institution_settings (id) VALUES (1)
ON DUPLICATE KEY UPDATE id = id;

ALTER TABLE advisor_requests
    ADD COLUMN response TEXT NULL,
    ADD COLUMN responded_at TIMESTAMP NULL;

CREATE TABLE IF NOT EXISTS student_preferences (
    student_id INT PRIMARY KEY,
    theme VARCHAR(40) NULL,
    accent_hue INT NULL,
    density VARCHAR(20) NULL,
    glass VARCHAR(10) NULL,
    motion VARCHAR(10) NULL,
    font VARCHAR(20) NULL,
    art INT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id)
);

ALTER TABLE enrollments
    ADD COLUMN IF NOT EXISTS status VARCHAR(20) NOT NULL DEFAULT 'enrolled',
    ADD COLUMN IF NOT EXISTS grade VARCHAR(5) NULL;

ALTER TABLE institution_settings
    ADD COLUMN IF NOT EXISTS year_label VARCHAR(30) NULL DEFAULT 'Year',
    ADD COLUMN IF NOT EXISTS enrollment_start DATETIME NULL,
    ADD COLUMN IF NOT EXISTS enrollment_end DATETIME NULL;

ALTER TABLE units
    ADD COLUMN IF NOT EXISTS day_of_week VARCHAR(20) NULL,
    ADD COLUMN IF NOT EXISTS start_time TIME NULL,
    ADD COLUMN IF NOT EXISTS end_time TIME NULL,
    ADD COLUMN IF NOT EXISTS room VARCHAR(60) NULL;

ALTER TABLE student_preferences
    ADD COLUMN IF NOT EXISTS large_text VARCHAR(10) NULL,
    ADD COLUMN IF NOT EXISTS minimal_mode VARCHAR(10) NULL;

CREATE TABLE IF NOT EXISTS announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(160) NOT NULL,
    body TEXT NOT NULL,
    audience VARCHAR(20) NOT NULL DEFAULT 'students',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
