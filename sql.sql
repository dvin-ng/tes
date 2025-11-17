-- =================================================================
-- SISTEM MANAJEMEN PROYEK KOLABORATIF: SKEMA DATABASE LENGKAP
-- =================================================================

-- 1. Buat Database jika belum ada
CREATE DATABASE IF NOT EXISTS project_management;
USE project_management;

-- =================================================================
-- 2. Tabel-Tabel Utama
-- =================================================================

-- Tabel Users (Menyimpan data pengguna)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('ADMIN', 'MANAGER', 'MEMBER') NOT NULL DEFAULT 'MEMBER',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel Projects (Menyimpan data proyek)
CREATE TABLE projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    start_date DATE,
    end_date DATE,
    status ENUM('PLANNING', 'ACTIVE', 'ON_HOLD', 'COMPLETED', 'CANCELLED') NOT NULL DEFAULT 'PLANNING',
    priority ENUM('LOW', 'MEDIUM', 'HIGH', 'CRITICAL') NOT NULL DEFAULT 'MEDIUM',
    manager_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (manager_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Tabel Tasks (Menyimpan data tugas)
CREATE TABLE tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    priority ENUM('LOW', 'MEDIUM', 'HIGH', 'CRITICAL') NOT NULL DEFAULT 'MEDIUM',
    status ENUM('TO_DO', 'IN_PROGRESS', 'REVIEW', 'DONE') NOT NULL DEFAULT 'TO_DO',
    due_date DATETIME,
    estimated_hours DECIMAL(5,2),
    actual_hours DECIMAL(5,2),
    project_id INT,
    created_by_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by_id) REFERENCES users(id) ON DELETE SET NULL
    -- NOTE: Kolom 'assignee_id' lama dihapus karena digantikan dengan tabel pivot
);

-- Tabel Documents (Menyimpan data dokumen)
CREATE TABLE documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    file_path VARCHAR(255) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_size INT,
    file_type VARCHAR(50),
    version VARCHAR(20) DEFAULT '1.0',
    category VARCHAR(50),
    uploaded_by_id INT,
    project_id INT,
    task_id INT,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (uploaded_by_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE
);

-- Tabel Teams (Menyimpan data tim/departemen)
CREATE TABLE teams (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    team_head_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (team_head_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Tabel Comments (Menyimpan data komentar pada tugas)
CREATE TABLE comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    author_id INT,
    task_id INT,
    -- Kolom tambahan untuk fitur komentar yang diperluas
    type ENUM('Pertanyaan', 'Saran', 'Laporan Bug', 'Blocker') NOT NULL DEFAULT 'Pertanyaan',
    privacy ENUM('ALL_MEMBERS', 'MANAGER_AND_ME') NOT NULL DEFAULT 'ALL_MEMBERS',
    file_path VARCHAR(255) NULL,
    file_name VARCHAR(255) NULL,
    file_size INT NULL,
    file_type VARCHAR(50) NULL,
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE
);


-- =================================================================
-- 3. Tabel Pivot (Hubungan Many-to-Many)
-- =================================================================

-- Tabel Pivot untuk relasi Many-to-Many antara Proyek dan Anggota
CREATE TABLE project_members (
    project_id INT,
    user_id INT,
    role_in_project ENUM('LEADER', 'CONTRIBUTOR', 'VIEWER') NOT NULL DEFAULT 'CONTRIBUTOR',
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (project_id, user_id),
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Tabel Pivot untuk relasi Many-to-Many antara Tim dan Anggota
CREATE TABLE team_members (
    team_id INT,
    user_id INT,
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (team_id, user_id),
    FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Tabel Pivot untuk relasi Many-to-Many antara Tugas dan Penanggung Jawab (Users)
CREATE TABLE task_assignees (
    task_id INT,
    user_id INT,
    PRIMARY KEY (task_id, user_id),
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

ALTER TABLE teams
ADD COLUMN logo_path VARCHAR(255) NULL AFTER description;



INSERT INTO users (name, email, password_hash, role) VALUES 
('Admin User', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ADMIN');