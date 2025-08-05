-- NPS Application Database Tables
-- This script creates all necessary tables for the NPS application

USE nps_db;

-- Table: campaigns (Encuestas NPS)
CREATE TABLE IF NOT EXISTS campaigns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    start_date DATE,
    end_date DATE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: nps_responses (Respuestas de encuestas)
CREATE TABLE IF NOT EXISTS nps_responses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT,
    customer_email VARCHAR(255),
    customer_name VARCHAR(255),
    score INT NOT NULL CHECK (score >= 0 AND score <= 10),
    feedback TEXT,
    category ENUM('detractor', 'passive', 'promoter') GENERATED ALWAYS AS (
        CASE 
            WHEN score <= 6 THEN 'detractor'
            WHEN score <= 8 THEN 'passive'
            ELSE 'promoter'
        END
    ) STORED,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: settings (Configuración del sistema)
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    description VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Update existing users table to add missing columns (MySQL 8.0 compatible)
-- Check if columns exist before adding them
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = 'nps_db' AND TABLE_NAME = 'users' AND COLUMN_NAME = 'username') = 0,
    'ALTER TABLE users ADD COLUMN username VARCHAR(50) UNIQUE, ADD COLUMN password_hash VARCHAR(255), ADD COLUMN role ENUM("admin", "manager", "viewer") DEFAULT "viewer", ADD COLUMN is_active BOOLEAN DEFAULT TRUE, ADD COLUMN last_login TIMESTAMP NULL, ADD COLUMN full_name VARCHAR(255)',
    'SELECT "Columns already exist" as message'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

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

-- Insert default admin user (password: admin123)
INSERT INTO users (username, email, password_hash, full_name, role) VALUES
('admin', 'admin@nps.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrador', 'admin')
ON DUPLICATE KEY UPDATE 
    email = VALUES(email),
    password_hash = VALUES(password_hash),
    full_name = VALUES(full_name),
    role = VALUES(role);

-- Insert sample campaign
INSERT INTO campaigns (name, description, start_date, end_date) VALUES
('Encuesta General de Satisfacción', 'Encuesta para medir la satisfacción general de nuestros clientes', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY))
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- Create indexes for better performance (only if they don't exist)
CREATE INDEX IF NOT EXISTS idx_nps_responses_campaign_id ON nps_responses(campaign_id);
CREATE INDEX IF NOT EXISTS idx_nps_responses_created_at ON nps_responses(created_at);
CREATE INDEX IF NOT EXISTS idx_nps_responses_score ON nps_responses(score);
CREATE INDEX IF NOT EXISTS idx_nps_responses_category ON nps_responses(category);
CREATE INDEX IF NOT EXISTS idx_campaigns_is_active ON campaigns(is_active);
CREATE INDEX IF NOT EXISTS idx_users_email ON users(email);
CREATE INDEX IF NOT EXISTS idx_users_username ON users(username);

-- Show created tables
SHOW TABLES; 