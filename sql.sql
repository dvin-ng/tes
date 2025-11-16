-- -----------------------------------------------------
-- Skrip SQL untuk Sistem Manajemen Proyek Kolaboratif
-- -----------------------------------------------------

CREATE DATABASE IF NOT EXISTS collaborative_pm DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE collaborative_pm;

-- -----------------------------------------------------
-- Tabel users
-- -----------------------------------------------------
CREATE TABLE users (
  id INT(11) NOT NULL AUTO_INCREMENT,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('ADMIN', 'MANAGER', 'MEMBER') NOT NULL DEFAULT 'MEMBER',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Tabel teams
-- -----------------------------------------------------
CREATE TABLE teams (
  id INT(11) NOT NULL AUTO_INCREMENT,
  name VARCHAR(100) NOT NULL,
  description TEXT,
  team_head_id INT(11) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  FOREIGN KEY (team_head_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Tabel team_members (Pivot)
-- -----------------------------------------------------
CREATE TABLE team_members (
  team_id INT(11) NOT NULL,
  user_id INT(11) NOT NULL,
  PRIMARY KEY (team_id, user_id),
  FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Tabel projects
-- -----------------------------------------------------
CREATE TABLE projects (
  id INT(11) NOT NULL AUTO_INCREMENT,
  name VARCHAR(150) NOT NULL,
  description TEXT,
  start_date DATE NULL,
  end_date DATE NULL,
  status ENUM('PLANNING', 'ACTIVE', 'ON_HOLD', 'COMPLETED') NOT NULL DEFAULT 'PLANNING',
  priority ENUM('LOW', 'MEDIUM', 'HIGH', 'CRITICAL') NOT NULL DEFAULT 'MEDIUM',
  manager_id INT(11) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  FOREIGN KEY (manager_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Tabel project_members (Pivot)
-- -----------------------------------------------------
CREATE TABLE project_members (
  project_id INT(11) NOT NULL,
  user_id INT(11) NOT NULL,
  PRIMARY KEY (project_id, user_id),
  FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Tabel tasks
-- -----------------------------------------------------
CREATE TABLE tasks (
  id INT(11) NOT NULL AUTO_INCREMENT,
  title VARCHAR(200) NOT NULL,
  description TEXT,
  priority ENUM('LOW', 'MEDIUM', 'HIGH', 'CRITICAL') NOT NULL DEFAULT 'MEDIUM',
  status ENUM('TO_DO', 'IN_PROGRESS', 'REVIEW', 'DONE') NOT NULL DEFAULT 'TO_DO',
  due_date DATETIME NULL,
  estimated_hours DECIMAL(5,2) NULL,
  project_id INT(11) NOT NULL,
  assignee_id INT(11) NULL,
  created_by_id INT(11) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
  FOREIGN KEY (assignee_id) REFERENCES users(id) ON DELETE SET NULL,
  FOREIGN KEY (created_by_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Tabel comments
-- -----------------------------------------------------
CREATE TABLE comments (
  id INT(11) NOT NULL AUTO_INCREMENT,
  content TEXT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  author_id INT(11) NOT NULL,
  task_id INT(11) NOT NULL,
  PRIMARY KEY (id),
  FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Tabel documents
-- -----------------------------------------------------
CREATE TABLE documents (
  id INT(11) NOT NULL AUTO_INCREMENT,
  title VARCHAR(200) NOT NULL,
  description TEXT,
  file_path VARCHAR(255) NOT NULL,
  version VARCHAR(20) NOT NULL DEFAULT '1.0',
  category ENUM('DESIGN', 'DOCUMENT', 'REPORT', 'OTHER') NOT NULL DEFAULT 'OTHER',
  uploaded_by_id INT(11) NOT NULL,
  project_id INT(11) NULL,
  task_id INT(11) NULL,
  uploaded_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  FOREIGN KEY (uploaded_by_id) REFERENCES users(id) ON DELETE RESTRICT,
  FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
  FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE task_assignees (
    task_id INT,
    user_id INT,
    PRIMARY KEY (task_id, user_id),
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);









-- Tambahkan data pengguna awal untuk testing
-- Password untuk semua adalah 'password'

INSERT INTO `users` (`name`, `email`, `password_hash`, `role`) VALUES
('Admin System', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ADMIN'),
('Project Manager', 'manager@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'MANAGER'),
('Regular Member', 'member@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'MEMBER');




-- ALTER TABLE comments ADD COLUMN type ENUM('Pertanyaan', 'Saran', 'Laporan Bug', 'Blocker') DEFAULT 'Pertanyaan' AFTER content;
-- ALTER TABLE comments ADD COLUMN privacy ENUM('Semua Anggota Tim', 'Hanya Manajer & Saya') DEFAULT 'Semua Anggota Tim' AFTER type;
-- ALTER TABLE comments ADD COLUMN file_path VARCHAR(255) NULL AFTER privacy;
-- ALTER TABLE comments ADD COLUMN file_name VARCHAR(255) NULL AFTER file_path;