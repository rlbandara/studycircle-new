-- Migration for existing StudyCircle databases
-- Run only if these four columns do not already exist.

ALTER TABLE users
ADD COLUMN profile_image VARCHAR(255) DEFAULT NULL,
ADD COLUMN personal_goal VARCHAR(255) DEFAULT NULL,
ADD COLUMN login_count INT UNSIGNED NOT NULL DEFAULT 0,
ADD COLUMN gender ENUM('female','male') DEFAULT NULL;
