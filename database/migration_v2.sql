-- ============================================================
-- BookYourShow Clone - Database Migration v2
-- Run this ONCE against bookmyshow_clone database
-- ============================================================

USE bookmyshow_clone;

-- ----- events table: add status & banner_image if missing -----
ALTER TABLE events
    ADD COLUMN IF NOT EXISTS status ENUM('pending','approved','rejected') DEFAULT 'approved',
    ADD COLUMN IF NOT EXISTS banner_image VARCHAR(255) DEFAULT '' AFTER event_image,
    ADD COLUMN IF NOT EXISTS city VARCHAR(100) DEFAULT '' AFTER location;

-- Update existing events to approved so they show up
UPDATE events SET status = 'approved' WHERE status IS NULL OR status = '';

-- ----- shows table: add language -----
ALTER TABLE shows
    ADD COLUMN IF NOT EXISTS language VARCHAR(50) DEFAULT 'Hindi' AFTER show_time,
    ADD COLUMN IF NOT EXISTS operator_id INT DEFAULT NULL AFTER language,
    ADD COLUMN IF NOT EXISTS status ENUM('active','cancelled') DEFAULT 'active' AFTER operator_id;

-- ----- operators table: add city -----
ALTER TABLE operators
    ADD COLUMN IF NOT EXISTS city VARCHAR(100) DEFAULT '' AFTER organization,
    ADD COLUMN IF NOT EXISTS status ENUM('active','suspended') DEFAULT 'active' AFTER city;

-- ----- theatres table: add licence fields and map_link -----
ALTER TABLE theatres
    ADD COLUMN IF NOT EXISTS map_link VARCHAR(500) DEFAULT '' AFTER location,
    ADD COLUMN IF NOT EXISTS is_verified TINYINT DEFAULT 0 AFTER map_link,
    ADD COLUMN IF NOT EXISTS added_by INT DEFAULT NULL AFTER is_verified;

-- ----- movies table: add banner_image if missing -----
ALTER TABLE movies
    ADD COLUMN IF NOT EXISTS banner_image VARCHAR(255) DEFAULT '' AFTER poster,
    ADD COLUMN IF NOT EXISTS status ENUM('active','inactive') DEFAULT 'active' AFTER rating;

-- ============================================================
-- NEW TABLE: theatre_licences
-- Tracks licence submissions from organizers per theatre
-- ============================================================
CREATE TABLE IF NOT EXISTS theatre_licences (
    licence_id     INT AUTO_INCREMENT PRIMARY KEY,
    theatre_id     INT NOT NULL,
    operator_id    INT NOT NULL,
    licence_number VARCHAR(150) NOT NULL,
    status         ENUM('pending','approved','rejected') DEFAULT 'pending',
    rejection_note VARCHAR(500) DEFAULT '',
    submitted_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reviewed_at    TIMESTAMP NULL DEFAULT NULL,
    UNIQUE KEY uq_theatre_operator (theatre_id, operator_id),
    FOREIGN KEY (theatre_id)  REFERENCES theatres(theatre_id)  ON DELETE CASCADE,
    FOREIGN KEY (operator_id) REFERENCES operators(operator_id) ON DELETE CASCADE
);

-- ============================================================
-- NEW TABLE: admin_notifications
-- System notifications for admin panel
-- ============================================================
CREATE TABLE IF NOT EXISTS admin_notifications (
    notif_id     INT AUTO_INCREMENT PRIMARY KEY,
    type         VARCHAR(50) NOT NULL DEFAULT 'general',
    title        VARCHAR(200) NOT NULL,
    message      TEXT,
    reference_id INT DEFAULT NULL,
    is_read      TINYINT DEFAULT 0,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- Update show dates to future (next 30 days range)
-- ============================================================
UPDATE shows SET show_date = DATE_ADD(CURDATE(), INTERVAL (show_id % 14) DAY)
WHERE show_date < CURDATE();

-- Mark existing theatres as verified (no licence needed for pre-existing ones)
UPDATE theatres SET is_verified = 1 WHERE is_verified = 0;

-- ============================================================
-- Sample admin notification
-- ============================================================
INSERT IGNORE INTO admin_notifications (type, title, message)
VALUES ('info', 'System Upgraded', 'BookYourShow Clone has been upgraded to v2 with theatre licensing and language-tagged showtimes.');

SELECT 'Migration v2 completed successfully!' AS result;
