-- Table for storing contact form submissions
CREATE TABLE IF NOT EXISTS contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    subject VARCHAR(255) NOT NULL,
    category VARCHAR(50) DEFAULT 'general',
    message TEXT NOT NULL,
    ip_address VARCHAR(45),
    is_read BOOLEAN DEFAULT FALSE,
    is_replied BOOLEAN DEFAULT FALSE,
    admin_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_created (created_at),
    INDEX idx_read (is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add some demo data
INSERT INTO contact_messages (name, email, phone, subject, category, message, is_read, is_replied) VALUES
('Marie Dupont', 'marie.dupont@email.fr', '0612345678', 'Suggestion pour améliorer les filtres', 'feature', 'Bonjour, j\'aimerais suggérer l\'ajout de filtres par prix et par accessibilité PMR. Ce serait vraiment utile pour les utilisateurs. Merci pour votre excellent travail !', TRUE, TRUE),
('Jean Martin', 'jean.martin@email.fr', NULL, 'Problème de connexion', 'bug', 'Je n\'arrive pas à me connecter avec mon compte Google. Le bouton ne répond pas. Pouvez-vous m\'aider ?', TRUE, FALSE),
('Sophie Bernard', 'sophie.b@email.fr', '0698765432', 'Partenariat culturel', 'partnership', 'Bonjour, je représente le Théâtre de la Ville et nous aimerions discuter d\'un partenariat pour promouvoir nos événements sur votre plateforme. Pouvons-nous organiser une réunion ?', FALSE, FALSE);