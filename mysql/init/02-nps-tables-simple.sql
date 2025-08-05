-- Simplified NPS Application Database Tables
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
    score INT NOT NULL,
    feedback TEXT,
    category VARCHAR(20),
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
INSERT INTO campaigns (name, description, start_date, end_date) VALUES
('Encuesta General de Satisfacción', 'Encuesta para medir la satisfacción general de nuestros clientes', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY))
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- Show created tables
SHOW TABLES; 