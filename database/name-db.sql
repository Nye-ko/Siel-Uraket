
CREATE DATABASE IF NOT EXISTS sielUraket
	CHARACTER SET utf8mb4
	COLLATE utf8mb4_unicode_ci;

USE sielUraket;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS job_reports;
DROP TABLE IF EXISTS messages;
DROP TABLE IF EXISTS reviews;
DROP TABLE IF EXISTS applications;
DROP TABLE IF EXISTS job_skills;
DROP TABLE IF EXISTS jobs;
DROP TABLE IF EXISTS skills;
DROP TABLE IF EXISTS user_profiles;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
	id VARCHAR(64) NOT NULL,
	role ENUM('admin', 'client', 'freelancer') NOT NULL,
	full_name VARCHAR(150) NOT NULL,
	email VARCHAR(255) NOT NULL,
	password_hash VARCHAR(255) NOT NULL,
	created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (id),
	UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB;

CREATE TABLE user_profiles (
	user_id VARCHAR(64) NOT NULL,
	organization VARCHAR(150) NOT NULL,
	about TEXT NULL,
	PRIMARY KEY (user_id),
	CONSTRAINT fk_profiles_user FOREIGN KEY (user_id) REFERENCES users (id)
		ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE skills (
	id VARCHAR(64) NOT NULL,
	name VARCHAR(100) NOT NULL,
	PRIMARY KEY (id),
	UNIQUE KEY uq_skills_name (name)
) ENGINE=InnoDB;

CREATE TABLE user_skills (
	user_id VARCHAR(64) NOT NULL,
	skill_id VARCHAR(64) NOT NULL,
	PRIMARY KEY (user_id, skill_id),
	CONSTRAINT fk_user_skills_user FOREIGN KEY (user_id) REFERENCES users (id)
		ON DELETE CASCADE ON UPDATE CASCADE,
	CONSTRAINT fk_user_skills_skill FOREIGN KEY (skill_id) REFERENCES skills (id)
		ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE jobs (
	id VARCHAR(64) NOT NULL,
	client_id VARCHAR(64) NOT NULL,
	title VARCHAR(200) NOT NULL,
	job_type VARCHAR(60) NOT NULL,
	budget DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
	description TEXT NOT NULL,
	status ENUM('open', 'closed', 'completed') NOT NULL DEFAULT 'open',
	deadline DATE NULL,
	posted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (id),
	KEY idx_jobs_client (client_id),
	KEY idx_jobs_status (status),
	CONSTRAINT fk_jobs_client FOREIGN KEY (client_id) REFERENCES users (id)
		ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE job_skills (
	job_id VARCHAR(64) NOT NULL,
	skill_id VARCHAR(64) NOT NULL,
	PRIMARY KEY (job_id, skill_id),
	CONSTRAINT fk_job_skills_job FOREIGN KEY (job_id) REFERENCES jobs (id)
		ON DELETE CASCADE ON UPDATE CASCADE,
	CONSTRAINT fk_job_skills_skill FOREIGN KEY (skill_id) REFERENCES skills (id)
		ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE applications (
	id VARCHAR(64) NOT NULL,
	job_id VARCHAR(64) NOT NULL,
	freelancer_id VARCHAR(64) NOT NULL,
	title VARCHAR(200) NULL,
	message TEXT NULL,
	status ENUM('pending', 'accepted', 'completed', 'rejected') NOT NULL DEFAULT 'pending',
	applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (id),
	UNIQUE KEY uq_application_job_freelancer (job_id, freelancer_id),
	KEY idx_applications_freelancer (freelancer_id),
	CONSTRAINT fk_applications_job FOREIGN KEY (job_id) REFERENCES jobs (id)
		ON DELETE CASCADE ON UPDATE CASCADE,
	CONSTRAINT fk_applications_freelancer FOREIGN KEY (freelancer_id) REFERENCES users (id)
		ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE reviews (
	id VARCHAR(64) NOT NULL,
	application_id VARCHAR(64) NOT NULL,
	reviewer_id VARCHAR(64) NOT NULL,
	reviewee_id VARCHAR(64) NOT NULL,
	rating TINYINT UNSIGNED NOT NULL,
	comment TEXT NULL,
	created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (id),
	UNIQUE KEY uq_review_application_reviewer (application_id, reviewer_id),
	CONSTRAINT chk_reviews_rating CHECK (rating BETWEEN 1 AND 5),
	CONSTRAINT fk_reviews_application FOREIGN KEY (application_id) REFERENCES applications (id)
		ON DELETE CASCADE ON UPDATE CASCADE,
	CONSTRAINT fk_reviews_reviewer FOREIGN KEY (reviewer_id) REFERENCES users (id)
		ON DELETE RESTRICT ON UPDATE CASCADE,
	CONSTRAINT fk_reviews_reviewee FOREIGN KEY (reviewee_id) REFERENCES users (id)
		ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE messages (
	id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	sender_id VARCHAR(64) NOT NULL,
	recipient_id VARCHAR(64) NOT NULL,
	job_id VARCHAR(64) NULL,
	body TEXT NOT NULL,
	sent_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	read_at DATETIME NULL,
	PRIMARY KEY (id),
	KEY idx_messages_recipient (recipient_id, read_at),
	CONSTRAINT fk_messages_sender FOREIGN KEY (sender_id) REFERENCES users (id)
		ON DELETE RESTRICT ON UPDATE CASCADE,
	CONSTRAINT fk_messages_recipient FOREIGN KEY (recipient_id) REFERENCES users (id)
		ON DELETE RESTRICT ON UPDATE CASCADE,
	CONSTRAINT fk_messages_job FOREIGN KEY (job_id) REFERENCES jobs (id)
		ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE job_reports (
	id VARCHAR(64) NOT NULL,
	job_id VARCHAR(64) NOT NULL,
	reporter_id VARCHAR(64) NOT NULL,
	reason VARCHAR(150) NOT NULL,
	note TEXT NULL,
	status ENUM('pending', 'resolved') NOT NULL DEFAULT 'pending',
	reported_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	resolved_at DATETIME NULL,
	PRIMARY KEY (id),
	KEY idx_reports_status (status),
	CONSTRAINT fk_reports_job FOREIGN KEY (job_id) REFERENCES jobs (id)
		ON DELETE CASCADE ON UPDATE CASCADE,
	CONSTRAINT fk_reports_reporter FOREIGN KEY (reporter_id) REFERENCES users (id)
		ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- The seeded login password for these accounts is: password
INSERT INTO users (id, role, full_name, email, password_hash) VALUES
	('admin-1', 'admin', 'Admin User', 'admin@example.com', '$2y$10$BMKGiZFl8C52PkjpF9gWIOTjQ64biLGpWfzbc2VByaVzigOWyXwc.'),
	('client-1', 'client', 'Client User', 'client@example.com', '$2y$10$BMKGiZFl8C52PkjpF9gWIOTjQ64biLGpWfzbc2VByaVzigOWyXwc.'),
	('client-2', 'client', 'Registrar''s Office', 'registrar@example.com', '$2y$10$BMKGiZFl8C52PkjpF9gWIOTjQ64biLGpWfzbc2VByaVzigOWyXwc.'),
	('client-01', 'client', 'Prof. A. Santos', 'prof.santos@example.com', '$2y$10$BMKGiZFl8C52PkjpF9gWIOTjQ64biLGpWfzbc2VByaVzigOWyXwc.'),
	('client-02', 'client', 'Student Council', 'council@example.com', '$2y$10$BMKGiZFl8C52PkjpF9gWIOTjQ64biLGpWfzbc2VByaVzigOWyXwc.'),
	('freelancer-1', 'freelancer', 'Student User', 'student@example.com', '$2y$10$BMKGiZFl8C52PkjpF9gWIOTjQ64biLGpWfzbc2VByaVzigOWyXwc.'),
	('freelancer-01', 'freelancer', 'Student User', 'student01@example.com', '$2y$10$BMKGiZFl8C52PkjpF9gWIOTjQ64biLGpWfzbc2VByaVzigOWyXwc.'),
	('freelancer-02', 'freelancer', 'J. Cruz', 'j.cruz@example.com', '$2y$10$BMKGiZFl8C52PkjpF9gWIOTjQ64biLGpWfzbc2VByaVzigOWyXwc.'),
	('freelancer-03', 'freelancer', 'M. Reyes', 'm.reyes@example.com', '$2y$10$BMKGiZFl8C52PkjpF9gWIOTjQ64biLGpWfzbc2VByaVzigOWyXwc.');

INSERT INTO user_profiles (user_id, organization, about) VALUES
	('client-1', 'Film Club', 'Faculty member looking for reliable students for campus projects.'),
	('client-01', 'Film Club', 'Faculty member looking for reliable students for campus projects.'),
	('client-2', 'Registrar''s Office', NULL),
	('client-02', 'Student Council', NULL),
	('freelancer-1', 'Student', NULL),
	('freelancer-01', 'Student', NULL),
	('freelancer-02', 'Student', NULL),
	('freelancer-03', 'Student', NULL);

INSERT INTO skills (id, name) VALUES
	('skill-1', 'Graphic Design'), ('skill-2', 'Logo Design'),
	('skill-3', 'Web Development'), ('skill-4', 'Programming'),
	('skill-5', 'Data Entry'), ('skill-6', 'Photography'),
	('skill-7', 'Video Editing'), ('skill-8', 'Writing'),
	('skill-9', 'Social Media'), ('skill-10', 'Tutoring');

INSERT INTO user_skills (user_id, skill_id) VALUES
	('freelancer-1', 'skill-4'),
	('freelancer-01', 'skill-1'), ('freelancer-01', 'skill-7'),
	('freelancer-02', 'skill-1'), ('freelancer-02', 'skill-2'),
	('freelancer-03', 'skill-6');

INSERT INTO jobs (id, client_id, title, job_type, budget, description, status, deadline, posted_at) VALUES
	('job-101', 'client-01', 'Poster design for orientation week', 'Service', 1500, 'Create promotional artwork for the upcoming student orientation.', 'open', '2026-08-30', '2026-08-12 10:00:00'),
	('job-102', 'client-01', 'Short promo video for the film club', 'Commission', 2500, 'Edit a short promotional video for an upcoming campus screening.', 'open', NULL, '2026-07-28 10:00:00'),
	('job-103', 'client-02', 'Student organization website', 'Service', 5000, 'Build and maintain a simple website for a student organization.', 'open', NULL, '2026-08-01 10:00:00'),
	('j-102', 'client-1', 'Logo design for student org', 'Design', 1500, 'Create a clean and simple logo for a student organization.', 'open', NULL, '2026-08-10 09:15:00'),
	('j-098', 'client-2', 'Data entry - 10 hrs/week', 'Part-time', 3000, 'Help organize and encode campus-related data.', 'open', NULL, '2026-08-08 14:40:00'),
	('j-075', 'client-1', 'Event Flyer Design', 'Design & Creative', 100, 'Create a flyer for a campus event.', 'closed', NULL, '2026-07-20 09:15:00'),
	('job-orientation-02', 'client-1', 'Poster design for orientation week', 'Service', 1500, 'Create promotional artwork for the upcoming student orientation.', 'open', '2026-08-30', '2026-08-12 10:00:00'),
	('job-film-01', 'client-1', 'Short promo video for the film club', 'Commission', 2500, 'Edit a short promotional video for an upcoming campus screening.', 'completed', NULL, '2026-07-28 10:00:00'),
	('job-data-03', 'client-2', 'Data entry - 10 hrs/week', 'Part-time', 3000, 'Assist with data entry tasks for the registrar.', 'closed', NULL, '2026-07-15 09:00:00'),
	('j-081', 'client-1', 'Photography for campus event', 'Service', 1800, 'Take photographs for a campus event.', 'open', NULL, '2026-08-02 11:05:00'),
	('job-1', 'client-01', 'Short promo video for the film club', 'Commission', 2500, 'Edit a short promotional video for an upcoming campus screening.', 'completed', '2026-08-05', '2026-07-28 10:00:00'),
	('job-2', 'client-01', 'Poster design for orientation week', 'Service', 1800, 'Create promotional artwork for the upcoming student orientation.', 'open', '2026-08-30', '2026-08-12 10:00:00'),
	('job-4', 'client-01', 'Social media graphics for student event', 'Service', 1500, 'Create social media graphics for a student event.', 'open', '2026-09-01', '2026-08-12 10:00:00');

INSERT INTO job_skills (job_id, skill_id) VALUES
	('job-101', 'skill-1'), ('job-102', 'skill-7'), ('job-103', 'skill-4'), ('job-103', 'skill-8'),
	('j-102', 'skill-2'), ('j-098', 'skill-5'), ('job-orientation-02', 'skill-1'),
	('job-film-01', 'skill-7'), ('job-data-03', 'skill-5'), ('j-081', 'skill-6'),
	('job-1', 'skill-7'), ('job-2', 'skill-1'), ('job-4', 'skill-9');

INSERT INTO applications (id, job_id, freelancer_id, title, message, status, applied_at) VALUES
	('app-77', 'job-102', 'freelancer-01', 'Video editing application', 'I can edit the promotional video for the film club.', 'completed', '2026-07-28 10:00:00'),
	('app-81', 'job-101', 'freelancer-01', 'Poster design application', 'I would love to create the orientation poster.', 'pending', '2026-08-12 14:30:00'),
	('app-64', 'job-data-03', 'freelancer-01', 'Data entry application', 'I am interested in helping with the data entry work.', 'rejected', '2026-07-15 09:00:00'),
	('a-1', 'j-102', 'freelancer-02', 'I can create the requested logo.', 'I have experience creating logos for student organizations.', 'pending', '2026-08-10 09:15:00'),
	('a-2', 'j-102', 'freelancer-03', 'Logo design application', 'I would be happy to work on this project.', 'rejected', '2026-08-10 09:15:00');

INSERT INTO job_reports (id, job_id, reporter_id, reason, note, status, reported_at) VALUES
	('r1', 'j-102', 'freelancer-02', 'Misleading job description', 'Price listed does not match what the client offered in messages.', 'pending', '2026-08-10 09:15:00'),
	('r2', 'j-098', 'client-01', 'Inappropriate content', NULL, 'pending', '2026-08-08 14:40:00'),
	('r3', 'j-081', 'freelancer-03', 'Spam or duplicate posting', 'Same listing posted twice under different titles.', 'resolved', '2026-08-02 11:05:00');
