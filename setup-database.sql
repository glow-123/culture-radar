-- Script de configuration de la base de données Culture Radar
-- Pour stocker les événements scrappés

CREATE DATABASE IF NOT EXISTS culture_radar 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE culture_radar;

-- Table principale des événements
CREATE TABLE IF NOT EXISTS events (
    id VARCHAR(64) PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    category VARCHAR(50),
    venue_name VARCHAR(255),
    address TEXT,
    city VARCHAR(100),
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    start_date DATETIME,
    end_date DATETIME,
    price DECIMAL(10, 2),
    is_free BOOLEAN DEFAULT 0,
    image_url TEXT,
    external_url TEXT,
    ticket_links JSON,
    venue_rating DECIMAL(2, 1),
    venue_reviews INT,
    ai_score INT,
    source VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_scraped TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT 1,
    INDEX idx_city (city),
    INDEX idx_category (category),
    INDEX idx_start_date (start_date),
    INDEX idx_ai_score (ai_score),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table de logs pour le scraping
CREATE TABLE IF NOT EXISTS scraping_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    run_date DATE NOT NULL,
    city VARCHAR(100),
    total_fetched INT DEFAULT 0,
    new_events INT DEFAULT 0,
    updated_events INT DEFAULT 0,
    errors INT DEFAULT 0,
    execution_time FLOAT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_run_date (run_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table pour suivre les sources d'événements
CREATE TABLE IF NOT EXISTS event_sources (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    api_key VARCHAR(255),
    base_url VARCHAR(255),
    is_active BOOLEAN DEFAULT 1,
    last_successful_fetch TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insérer les sources par défaut
INSERT INTO event_sources (name, base_url, is_active) VALUES
('google_events', 'https://serpapi.com/search.json', 1),
('openagenda', 'https://api.openagenda.com/v2/events', 0),
('eventbrite', 'https://www.eventbriteapi.com/v3/', 0)
ON DUPLICATE KEY UPDATE name=name;

-- Table pour les catégories d'événements
CREATE TABLE IF NOT EXISTS event_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    icon VARCHAR(10),
    color VARCHAR(7),
    priority INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insérer les catégories par défaut
INSERT INTO event_categories (slug, name, icon, color, priority) VALUES
('concert', 'Concerts & Musique', '🎵', '#667eea', 1),
('exposition', 'Expositions', '🎨', '#f6ad55', 2),
('theatre', 'Théâtre', '🎭', '#ed64a6', 3),
('danse', 'Danse', '💃', '#9f7aea', 4),
('festival', 'Festivals', '🎪', '#48bb78', 5),
('conference', 'Conférences', '🎤', '#4299e1', 6),
('cinema', 'Cinéma', '🎬', '#38b2ac', 7),
('autre', 'Autres', '✨', '#718096', 8)
ON DUPLICATE KEY UPDATE name=name;

-- Vue pour les événements actifs à venir
CREATE OR REPLACE VIEW upcoming_events AS
SELECT 
    e.*,
    ec.name as category_name,
    ec.icon as category_icon,
    ec.color as category_color
FROM events e
LEFT JOIN event_categories ec ON e.category = ec.slug
WHERE e.is_active = 1 
AND (e.start_date >= NOW() OR e.start_date IS NULL)
ORDER BY e.start_date ASC, e.ai_score DESC;

-- Vue pour les statistiques par ville
CREATE OR REPLACE VIEW city_stats AS
SELECT 
    city,
    COUNT(*) as total_events,
    SUM(is_free) as free_events,
    COUNT(DISTINCT category) as categories,
    AVG(ai_score) as avg_score,
    MAX(last_scraped) as last_update
FROM events
WHERE is_active = 1
GROUP BY city;

-- Procédure stockée pour nettoyer les anciens événements
DELIMITER $$
CREATE PROCEDURE IF NOT EXISTS cleanup_old_events()
BEGIN
    -- Désactiver les événements de plus de 30 jours
    UPDATE events 
    SET is_active = 0 
    WHERE start_date < DATE_SUB(NOW(), INTERVAL 30 DAY) 
    AND is_active = 1;
    
    -- Supprimer les événements de plus de 90 jours
    DELETE FROM events 
    WHERE start_date < DATE_SUB(NOW(), INTERVAL 90 DAY);
    
    -- Log the cleanup
    INSERT INTO scraping_logs (run_date, city, total_fetched, new_events, updated_events, errors)
    VALUES (CURDATE(), 'CLEANUP', 0, 0, 0, 0);
END$$
DELIMITER ;

-- Fonction pour calculer le score AI d'un événement
DELIMITER $$
CREATE FUNCTION IF NOT EXISTS calculate_ai_score(
    p_category VARCHAR(50),
    p_venue_rating DECIMAL(2,1),
    p_is_free BOOLEAN,
    p_venue_reviews INT
) RETURNS INT
DETERMINISTIC
BEGIN
    DECLARE score INT DEFAULT 70;
    
    -- Bonus pour les catégories populaires
    IF p_category IN ('concert', 'exposition', 'festival') THEN
        SET score = score + 10;
    END IF;
    
    -- Bonus pour la note du lieu
    IF p_venue_rating IS NOT NULL THEN
        SET score = score + (p_venue_rating * 3);
    END IF;
    
    -- Bonus pour les événements gratuits
    IF p_is_free = 1 THEN
        SET score = score + 8;
    END IF;
    
    -- Bonus pour les lieux populaires
    IF p_venue_reviews > 1000 THEN
        SET score = score + 5;
    END IF;
    
    -- Limiter entre 0 et 100
    IF score > 100 THEN
        SET score = 100;
    END IF;
    
    RETURN score;
END$$
DELIMITER ;

-- Index pour améliorer les performances
CREATE INDEX idx_events_upcoming ON events(start_date, is_active, city);
CREATE INDEX idx_events_search ON events(title, venue_name, category);

-- Créer un utilisateur pour le cron job
-- CREATE USER IF NOT EXISTS 'culture_cron'@'localhost' IDENTIFIED BY 'secure_password_here';
-- GRANT SELECT, INSERT, UPDATE, DELETE ON culture_radar.* TO 'culture_cron'@'localhost';
-- FLUSH PRIVILEGES;