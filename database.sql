-- =====================================================================
-- StudyCircle: database structure + sample data
-- Import in phpMyAdmin: Import tab > choose this file > Go
-- On live hosting (InfinityFree etc.): delete the CREATE DATABASE and USE
-- lines below, create the database in the hosting panel, select it, then import.
--
-- Demo logins
--   Admin:   admin@nsbm.ac.lk              password: Admin@123
--   Student: nimal@students.nsbm.ac.lk     password: Student@123
--   (all sample students use Student@123)
-- =====================================================================

CREATE DATABASE IF NOT EXISTS studycircle_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE studycircle_db;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS study_sessions, group_members, study_groups, answers, questions,
                     material_ratings, materials, subjects, users;
SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------
-- Tables
-- ---------------------------------------------------------------------
CREATE TABLE users (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(100) NOT NULL,
  email       VARCHAR(150) NOT NULL UNIQUE,
  password    VARCHAR(255) NOT NULL,                       -- password_hash() value
  role        ENUM('student','admin') NOT NULL DEFAULT 'student',
  status      ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE subjects (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code         VARCHAR(20) NOT NULL UNIQUE,
  name         VARCHAR(120) NOT NULL,
  description  VARCHAR(255) NULL
) ENGINE=InnoDB;

CREATE TABLE materials (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id      INT UNSIGNED NOT NULL,
  subject_id   INT UNSIGNED NOT NULL,
  title        VARCHAR(150) NOT NULL,
  description  TEXT NULL,
  file_name    VARCHAR(255) NOT NULL,                      -- original name shown to users
  file_path    VARCHAR(255) NOT NULL,                      -- stored name inside /uploads
  status       ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  downloads    INT UNSIGNED NOT NULL DEFAULT 0,
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE CASCADE,
  FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
  INDEX idx_materials_status (status),
  INDEX idx_materials_subject (subject_id)
) ENGINE=InnoDB;

CREATE TABLE material_ratings (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  material_id  INT UNSIGNED NOT NULL,
  user_id      INT UNSIGNED NOT NULL,
  rating       TINYINT UNSIGNED NOT NULL,                  -- 1 to 5
  comment      VARCHAR(500) NULL,
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (material_id) REFERENCES materials(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id)     REFERENCES users(id)     ON DELETE CASCADE,
  UNIQUE KEY uq_one_rating (material_id, user_id),
  CHECK (rating BETWEEN 1 AND 5)
) ENGINE=InnoDB;

CREATE TABLE questions (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id      INT UNSIGNED NOT NULL,
  subject_id   INT UNSIGNED NOT NULL,
  title        VARCHAR(200) NOT NULL,
  body         TEXT NOT NULL,
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE CASCADE,
  FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE answers (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  question_id  INT UNSIGNED NOT NULL,
  user_id      INT UNSIGNED NOT NULL,
  body         TEXT NOT NULL,
  is_best      TINYINT(1) NOT NULL DEFAULT 0,              -- 1 = marked as best answer
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id)     REFERENCES users(id)     ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE study_groups (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name         VARCHAR(120) NOT NULL,
  subject_id   INT UNSIGNED NOT NULL,
  creator_id   INT UNSIGNED NOT NULL,
  description  VARCHAR(500) NULL,
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
  FOREIGN KEY (creator_id) REFERENCES users(id)    ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE group_members (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  group_id     INT UNSIGNED NOT NULL,
  user_id      INT UNSIGNED NOT NULL,
  joined_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (group_id) REFERENCES study_groups(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id)  REFERENCES users(id)        ON DELETE CASCADE,
  UNIQUE KEY uq_member (group_id, user_id)
) ENGINE=InnoDB;

CREATE TABLE study_sessions (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  group_id      INT UNSIGNED NOT NULL,
  title         VARCHAR(150) NOT NULL,
  session_date  DATETIME NOT NULL,
  location      VARCHAR(200) NULL,                         -- room name or online link
  created_by    INT UNSIGNED NOT NULL,
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (group_id)   REFERENCES study_groups(id) ON DELETE CASCADE,
  FOREIGN KEY (created_by) REFERENCES users(id)        ON DELETE CASCADE,
  INDEX idx_sessions_date (session_date)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Sample data
-- ---------------------------------------------------------------------
INSERT INTO users (id, name, email, password, role, status) VALUES
(1, 'StudyCircle Admin',   'admin@nsbm.ac.lk',                '$2y$10$MQKbmQ5zHZQM4DYFra1BQOqThC3LsZxvszeD7z1mLApsGVJDk38PK', 'admin',   'active'),
(2, 'Nimal Perera',        'nimal@students.nsbm.ac.lk',       '$2y$10$SbL1DuoPxZTqhW8BdYDUz.GaRagHe59AkO6Titf2P73pf5A8ozDAa', 'student', 'active'),
(3, 'Kasuni Dias',         'kasuni@students.nsbm.ac.lk',      '$2y$10$SbL1DuoPxZTqhW8BdYDUz.GaRagHe59AkO6Titf2P73pf5A8ozDAa', 'student', 'active'),
(4, 'Amal Silva',          'amal@students.nsbm.ac.lk',        '$2y$10$SbL1DuoPxZTqhW8BdYDUz.GaRagHe59AkO6Titf2P73pf5A8ozDAa', 'student', 'active'),
(5, 'Dilani Rajapaksha',   'dilani@students.nsbm.ac.lk',      '$2y$10$SbL1DuoPxZTqhW8BdYDUz.GaRagHe59AkO6Titf2P73pf5A8ozDAa', 'student', 'active'),
(6, 'Tharindu Fernando',   'tharindu@students.nsbm.ac.lk',    '$2y$10$SbL1DuoPxZTqhW8BdYDUz.GaRagHe59AkO6Titf2P73pf5A8ozDAa', 'student', 'active'),
(7, 'Sanduni Jayawardena', 'sanduni@students.nsbm.ac.lk',     '$2y$10$SbL1DuoPxZTqhW8BdYDUz.GaRagHe59AkO6Titf2P73pf5A8ozDAa', 'student', 'inactive');

INSERT INTO subjects (id, code, name, description) VALUES
(1, 'DBS',  'Database Systems',           'ER modelling, SQL, normalization and transactions'),
(2, 'WAD',  'Web Application Development','HTML, CSS, JavaScript, PHP and MySQL'),
(3, 'SE',   'Software Engineering',       'Requirements, design, UML and testing'),
(4, 'MAT',  'Mathematics',                'Calculus, algebra and discrete maths'),
(5, 'NET',  'Computer Networks',          'Network models, protocols and addressing'),
(6, 'PRG',  'Programming Fundamentals',   'Core programming concepts and problem solving');

INSERT INTO materials (id, user_id, subject_id, title, description, file_name, file_path, status, downloads, created_at) VALUES
(1, 2, 1, 'Normalization Summary',      'Short notes on 1NF, 2NF, 3NF and BCNF with worked examples.',   'normalization-summary.pdf', 'sample_normalization.pdf',  'approved', 96,  NOW() - INTERVAL 20 DAY),
(2, 3, 2, 'REST API Cheat Sheet',       'Common HTTP methods, status codes and PHP examples.',           'rest-api-cheatsheet.pdf',   'sample_rest_api.pdf',       'approved', 74,  NOW() - INTERVAL 18 DAY),
(3, 4, 3, 'UML Diagrams Guide',         'Use case, class and sequence diagrams explained step by step.', 'uml-diagrams-guide.pdf',    'sample_uml.pdf',            'approved', 58,  NOW() - INTERVAL 15 DAY),
(4, 5, 4, 'Calculus Past Papers',       'Past exam questions with short answers.',                       'calculus-past-papers.pdf',  'sample_calculus.pdf',       'approved', 132, NOW() - INTERVAL 12 DAY),
(5, 2, 1, 'SQL Joins Explained',        'INNER, LEFT, RIGHT and FULL joins with diagrams.',              'sql-joins.pdf',             'sample_sql_joins.pdf',      'approved', 88,  NOW() - INTERVAL 10 DAY),
(6, 3, 2, 'JavaScript Basics',          'Variables, functions, arrays and DOM basics.',                  'javascript-basics.pdf',     'sample_js_basics.pdf',      'approved', 41,  NOW() - INTERVAL 8 DAY),
(7, 6, 5, 'Subnetting Made Easy',       'Step by step subnetting method with practice questions.',       'subnetting.pdf',            'sample_subnetting.pdf',     'pending',  0,   NOW() - INTERVAL 2 DAY),
(8, 4, 6, 'Loops and Arrays Practice',  'Practice problems with answers for loops and arrays.',          'loops-arrays.pdf',          'sample_loops_arrays.pdf',   'pending',  0,   NOW() - INTERVAL 1 DAY),
(9, 5, 3, 'Agile vs Waterfall',         'Comparison table of both approaches.',                          'agile-vs-waterfall.pdf',    'sample_agile.pdf',          'rejected', 0,   NOW() - INTERVAL 5 DAY);

INSERT INTO material_ratings (material_id, user_id, rating, comment) VALUES
(1, 3, 5, 'Very clear examples. Helped me before the exam.'),
(1, 4, 5, 'Great summary of all the normal forms.'),
(1, 5, 4, 'Good notes. Could use more BCNF examples.'),
(2, 2, 5, 'Handy cheat sheet for the group project.'),
(2, 4, 4, 'Useful status code list.'),
(3, 2, 4, 'Diagrams are easy to follow.'),
(3, 3, 4, 'Nice explanation of sequence diagrams.'),
(4, 2, 5, 'Best past paper set so far.'),
(4, 3, 5, 'Answers were very helpful.'),
(5, 3, 5, 'The join diagrams made it click for me.'),
(5, 6, 4, 'Clear and short.'),
(6, 4, 4, 'Good starting point for beginners.');

INSERT INTO questions (id, user_id, subject_id, title, body, created_at) VALUES
(1, 2, 1, 'What is the difference between 2NF and 3NF?',  'I understand 1NF but I keep mixing up partial and transitive dependencies. Can someone explain with a small example?', NOW() - INTERVAL 6 DAY),
(2, 3, 2, 'How do I start a PHP session safely?',          'What should I do after login to keep the session secure? Should I regenerate the session id?',                          NOW() - INTERVAL 5 DAY),
(3, 4, 3, 'Best way to prepare for the UML exam?',         'Which diagrams are most likely to come up, and how much detail do we need to draw?',                                    NOW() - INTERVAL 4 DAY),
(4, 5, 4, 'Explain limits with an example',                'I cannot understand how to find the limit when the denominator becomes zero.',                                           NOW() - INTERVAL 3 DAY),
(5, 6, 5, 'How many hosts are in a /26 network?',          'I am confused about how to count usable hosts. Do we subtract anything?',                                                NOW() - INTERVAL 1 DAY);

INSERT INTO answers (question_id, user_id, body, is_best, created_at) VALUES
(1, 3, '2NF removes partial dependencies on part of a composite key. 3NF removes transitive dependencies, where a non-key column depends on another non-key column.', 1, NOW() - INTERVAL 6 DAY + INTERVAL 2 HOUR),
(1, 4, 'Tip: in 3NF every non-key attribute must depend on the key, the whole key and nothing but the key.',                                                          0, NOW() - INTERVAL 5 DAY),
(1, 5, 'The normalization summary note in the Materials page has a worked example for both.',                                                                         0, NOW() - INTERVAL 5 DAY + INTERVAL 3 HOUR),
(2, 2, 'Call session_regenerate_id(true) right after a successful login and store only the user id and role in the session.',                                           1, NOW() - INTERVAL 5 DAY + INTERVAL 4 HOUR),
(2, 4, 'Also use password_verify() and always escape output with htmlspecialchars().',                                                                                 0, NOW() - INTERVAL 4 DAY),
(3, 2, 'Focus on use case, class and sequence diagrams. Past papers usually ask for one of each.',                                                                     1, NOW() - INTERVAL 4 DAY + INTERVAL 5 HOUR),
(4, 3, 'Try factoring the numerator first. Often the zero term cancels with the denominator.',                                                                        0, NOW() - INTERVAL 2 DAY),
(5, 4, 'A /26 has 64 addresses. Subtract 2 (network and broadcast) to get 62 usable hosts.',                                                                          1, NOW() - INTERVAL 20 HOUR);

INSERT INTO study_groups (id, name, subject_id, creator_id, description, created_at) VALUES
(1, 'DBMS Exam Prep',     1, 2, 'Weekly revision for the Database Systems exam: SQL practice and past papers.',     NOW() - INTERVAL 14 DAY),
(2, 'Web Project Team',   2, 3, 'Helping each other with PHP and MySQL for the WAD final project.',                 NOW() - INTERVAL 10 DAY),
(3, 'SE Study Circle',    3, 4, 'UML practice and requirement analysis discussions.',                               NOW() - INTERVAL 7 DAY);

INSERT INTO group_members (group_id, user_id) VALUES
(1, 2), (1, 3), (1, 5), (1, 6),
(2, 3), (2, 2), (2, 4),
(3, 4), (3, 2), (3, 5);

INSERT INTO study_sessions (group_id, title, session_date, location, created_by) VALUES
(1, 'Exam revision',        NOW() + INTERVAL 2 DAY + INTERVAL 4 HOUR,  'Library, Study Room 2', 2),
(1, 'SQL joins practice',   NOW() + INTERVAL 7 DAY + INTERVAL 5 HOUR,  'Online (Zoom link in group)', 2),
(2, 'Project meetup',       NOW() + INTERVAL 3 DAY + INTERVAL 10 HOUR, 'Computer Lab 3',          3),
(3, 'UML diagram workshop', NOW() + INTERVAL 5 DAY + INTERVAL 2 HOUR,  'Seminar Room B',          4);
