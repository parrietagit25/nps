-- NPS Database Initialization Script
-- This script will be executed when the MySQL container starts for the first time

-- Create the database if it doesn't exist
CREATE DATABASE IF NOT EXISTS nps_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Use the database
USE nps_db;

-- Create users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255),
    full_name VARCHAR(255),
    role ENUM("admin", "manager", "viewer") DEFAULT "viewer",
    is_active BOOLEAN DEFAULT TRUE,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create campaigns table
CREATE TABLE IF NOT EXISTS campaigns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    question TEXT,
    start_date DATE,
    end_date DATE,
    is_active BOOLEAN DEFAULT TRUE,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create nps_responses table
CREATE TABLE IF NOT EXISTS nps_responses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT,
    score INT NOT NULL CHECK (score >= 0 AND score <= 10),
    comment TEXT,
    email VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create settings table
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    description VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default admin user (password: admin123)
INSERT INTO users (username, email, password_hash, full_name, role) VALUES
('admin', 'admin@nps.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrador', 'admin')
ON DUPLICATE KEY UPDATE 
    email = VALUES(email),
    password_hash = VALUES(password_hash),
    full_name = VALUES(full_name),
    role = VALUES(role);

-- Insert default settings
INSERT INTO settings (setting_key, setting_value, description) VALUES
('site_name', 'NPS Survey System', 'Nombre del sitio'),
('site_description', 'Sistema de encuestas Net Promoter Score', 'Descripción del sitio'),
('default_campaign_duration', '30', 'Duración por defecto de campañas en días'),
('max_feedback_length', '1000', 'Longitud máxima de feedback en caracteres'),
('enable_ip_tracking', '1', 'Habilitar seguimiento de IP'),
('enable_user_agent_tracking', '1', 'Habilitar seguimiento de User Agent'),
('detractor_threshold', '6', 'Umbral para detractores (0-6)'),
('passive_threshold', '8', 'Umbral para pasivos (7-8)'),
('promoter_threshold', '10', 'Umbral para promotores (9-10)')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);

-- Insert sample campaign
INSERT INTO campaigns (name, description, question, start_date, end_date, created_by, is_active) VALUES
('Encuesta General de Satisfacción', 'Encuesta para medir la satisfacción general de nuestros clientes', '¿Qué tan probable es que recomiendes nuestro servicio a un amigo o colega?', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 1, 1)
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- Create indexes for better performance (MySQL 8.0 compatible)
CREATE INDEX idx_nps_responses_campaign_id ON nps_responses(campaign_id);
CREATE INDEX idx_nps_responses_created_at ON nps_responses(created_at);
CREATE INDEX idx_nps_responses_score ON nps_responses(score);
CREATE INDEX idx_campaigns_is_active ON campaigns(is_active);
CREATE INDEX idx_campaigns_created_by ON campaigns(created_by);
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_username ON users(username);

-- Show created tables
SHOW TABLES;