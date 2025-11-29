<?php
/**
 * Setup authentication system for Railway
 */

require_once __DIR__ . '/config.php';

echo "Configuration du système d'authentification...\n\n";

try {
    // Connexion à la base de données
    $pdo = Config::getPDO();
    
    if (!$pdo) {
        die("Erreur: Impossible de se connecter à la base de données\n");
    }
    
    echo "✅ Connexion à la base de données réussie\n";
    
    // Créer la table users si elle n'existe pas
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        preferences JSON,
        profile_complete BOOLEAN DEFAULT FALSE,
        INDEX idx_email (email)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    echo "✅ Table 'users' créée/vérifiée\n";
    
    // Créer la table sessions si elle n'existe pas
    $sql = "CREATE TABLE IF NOT EXISTS user_sessions (
        id VARCHAR(128) PRIMARY KEY,
        user_id INT NOT NULL,
        data TEXT,
        last_access TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_user_id (user_id),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    echo "✅ Table 'user_sessions' créée/vérifiée\n";
    
    // Créer un utilisateur de test
    $testEmail = 'test@culture-radar.fr';
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$testEmail]);
    
    if (!$stmt->fetch()) {
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
        $stmt->execute([
            'Utilisateur Test',
            $testEmail,
            password_hash('password123', PASSWORD_DEFAULT)
        ]);
        echo "✅ Utilisateur de test créé:\n";
        echo "   Email: test@culture-radar.fr\n";
        echo "   Mot de passe: password123\n";
    } else {
        echo "ℹ️ L'utilisateur de test existe déjà\n";
    }
    
    // Créer la table events si elle n'existe pas
    $sql = "CREATE TABLE IF NOT EXISTS events (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        category VARCHAR(50),
        venue VARCHAR(255),
        address VARCHAR(255),
        city VARCHAR(100),
        date_start DATETIME,
        date_end DATETIME,
        price DECIMAL(10,2),
        is_free BOOLEAN DEFAULT FALSE,
        image_url VARCHAR(500),
        link VARCHAR(500),
        source VARCHAR(50),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_date (date_start),
        INDEX idx_city (city),
        INDEX idx_category (category)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    echo "✅ Table 'events' créée/vérifiée\n";
    
    // Créer la table user_favorites si elle n'existe pas
    $sql = "CREATE TABLE IF NOT EXISTS user_favorites (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        event_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_favorite (user_id, event_id),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    echo "✅ Table 'user_favorites' créée/vérifiée\n";
    
    echo "\n✅ Système d'authentification configuré avec succès!\n";
    echo "Vous pouvez maintenant vous connecter avec:\n";
    echo "Email: test@culture-radar.fr\n";
    echo "Mot de passe: password123\n";
    
} catch (PDOException $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
}