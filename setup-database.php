<?php
/**
 * Database Setup Script for Culture Radar
 * Run this once to create the necessary database tables
 */

// Load configuration
require_once __DIR__ . '/config.php';

try {
    $dbConfig = Config::database();
    
    // Connect to MySQL server (without database)
    $host = str_replace(':' . $dbConfig['port'], '', $dbConfig['host']); // Remove port from host
    $pdo = new PDO("mysql:host=" . $host . ";port=" . $dbConfig['port'] . ";charset=" . $dbConfig['charset'], $dbConfig['user'], $dbConfig['pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS " . $dbConfig['name'] . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    
    // Switch to the database
    $pdo->exec("USE " . $dbConfig['name']);
    
    // Create users table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            accepts_newsletter BOOLEAN DEFAULT FALSE,
            is_active BOOLEAN DEFAULT TRUE,
            onboarding_completed BOOLEAN DEFAULT FALSE,
            last_login TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_email (email),
            INDEX idx_active (is_active),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // Create user_profiles table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS user_profiles (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            preferences JSON DEFAULT NULL,
            location VARCHAR(255) DEFAULT '',
            budget_max DECIMAL(8,2) DEFAULT 0.00,
            notification_settings JSON DEFAULT NULL,
            onboarding_completed BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_user_id (user_id),
            INDEX idx_location (location)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // Create events table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS events (
            id INT PRIMARY KEY AUTO_INCREMENT,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            category VARCHAR(100) NOT NULL,
            venue_name VARCHAR(255),
            address TEXT,
            city VARCHAR(100),
            postal_code VARCHAR(20),
            latitude DECIMAL(10, 8) NULL,
            longitude DECIMAL(11, 8) NULL,
            start_date DATETIME NOT NULL,
            end_date DATETIME NULL,
            price DECIMAL(8,2) DEFAULT 0.00,
            is_free BOOLEAN DEFAULT FALSE,
            image_url VARCHAR(500),
            external_url VARCHAR(500),
            organizer_id INT NULL,
            is_active BOOLEAN DEFAULT TRUE,
            featured BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_category (category),
            INDEX idx_city (city),
            INDEX idx_start_date (start_date),
            INDEX idx_price (price),
            INDEX idx_active (is_active),
            INDEX idx_featured (featured),
            INDEX idx_location (latitude, longitude)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // Create venues table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS venues (
            id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            address TEXT,
            city VARCHAR(100),
            postal_code VARCHAR(20),
            latitude DECIMAL(10, 8) NULL,
            longitude DECIMAL(11, 8) NULL,
            phone VARCHAR(20),
            email VARCHAR(255),
            website VARCHAR(500),
            category VARCHAR(100),
            has_culture_radar_badge BOOLEAN DEFAULT FALSE,
            organizer_id INT NULL,
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_city (city),
            INDEX idx_category (category),
            INDEX idx_active (is_active),
            INDEX idx_badge (has_culture_radar_badge),
            INDEX idx_location (latitude, longitude)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // Create user_recommendations table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS user_recommendations (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            event_id INT NOT NULL,
            match_score DECIMAL(5,2) NOT NULL,
            reasons JSON DEFAULT NULL,
            is_viewed BOOLEAN DEFAULT FALSE,
            is_clicked BOOLEAN DEFAULT FALSE,
            is_saved BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
            UNIQUE KEY unique_user_event (user_id, event_id),
            INDEX idx_user_score (user_id, match_score DESC),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // Create user_interactions table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS user_interactions (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            event_id INT NOT NULL,
            interaction_type ENUM('view', 'click', 'save', 'unsave', 'share', 'rate') NOT NULL,
            rating INT NULL CHECK (rating >= 1 AND rating <= 5),
            metadata JSON DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
            INDEX idx_user_id (user_id),
            INDEX idx_event_id (event_id),
            INDEX idx_interaction_type (interaction_type),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // Create organizers table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS organizers (
            id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            organization_name VARCHAR(255),
            description TEXT,
            phone VARCHAR(20),
            website VARCHAR(500),
            is_verified BOOLEAN DEFAULT FALSE,
            subscription_type ENUM('free', 'basic', 'premium') DEFAULT 'free',
            subscription_expires_at TIMESTAMP NULL,
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_email (email),
            INDEX idx_active (is_active),
            INDEX idx_subscription (subscription_type)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // Insert sample data
    
    // Sample events
    $sampleEvents = [
        [
            'title' => 'Exposition Photo "Paris Nocturne"',
            'description' => 'Une exposition photographique captivante explorant la beauté mystérieuse de Paris la nuit.',
            'category' => 'art',
            'venue_name' => 'Galerie Temps d\'Art',
            'address' => '15 rue de la République',
            'city' => 'Paris',
            'postal_code' => '75011',
            'start_date' => '2024-02-01 10:00:00',
            'end_date' => '2024-02-28 19:00:00',
            'price' => 0.00,
            'is_free' => true
        ],
        [
            'title' => 'Concert Jazz Intimiste',
            'description' => 'Soirée jazz dans une ambiance feutrée avec des musiciens de renommée internationale.',
            'category' => 'music',
            'venue_name' => 'Le Sunset',
            'address' => '60 rue des Lombards',
            'city' => 'Paris',
            'postal_code' => '75001',
            'start_date' => '2024-02-15 20:30:00',
            'end_date' => '2024-02-15 23:00:00',
            'price' => 15.00,
            'is_free' => false
        ],
        [
            'title' => 'Théâtre d\'Improvisation',
            'description' => 'Spectacle d\'improvisation théâtrale avec la participation du public.',
            'category' => 'theater',
            'venue_name' => 'Café Théâtre',
            'address' => '25 boulevard Saint-Germain',
            'city' => 'Paris',
            'postal_code' => '75005',
            'start_date' => '2024-02-10 21:00:00',
            'end_date' => '2024-02-10 22:30:00',
            'price' => 12.00,
            'is_free' => false
        ]
    ];
    
    $insertEvent = $pdo->prepare("
        INSERT INTO events (title, description, category, venue_name, address, city, postal_code, start_date, end_date, price, is_free) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    foreach ($sampleEvents as $event) {
        $insertEvent->execute([
            $event['title'],
            $event['description'],
            $event['category'],
            $event['venue_name'],
            $event['address'],
            $event['city'],
            $event['postal_code'],
            $event['start_date'],
            $event['end_date'],
            $event['price'],
            $event['is_free'] ? 1 : 0  // Convert boolean to integer
        ]);
    }
    
    echo "✅ Database setup completed successfully!\n\n";
    echo "📊 Created tables:\n";
    echo "   - users\n";
    echo "   - user_profiles\n";
    echo "   - events\n";
    echo "   - venues\n";
    echo "   - user_recommendations\n";
    echo "   - user_interactions\n";
    echo "   - organizers\n\n";
    echo "🎭 Sample events added:\n";
    echo "   - Exposition Photo \"Paris Nocturne\"\n";
    echo "   - Concert Jazz Intimiste\n";
    echo "   - Théâtre d'Improvisation\n\n";
    echo "🚀 You can now use the Culture Radar application!\n";
    
} catch (PDOException $e) {
    echo "❌ Database setup failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>