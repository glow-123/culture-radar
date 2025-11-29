<?php
/**
 * Railway Health Check Endpoint
 * Vérifie que l'application est opérationnelle
 */

header('Content-Type: application/json');

$health = [
    'status' => 'healthy',
    'timestamp' => date('c'),
    'checks' => []
];

// Vérification PHP
$health['checks']['php'] = [
    'status' => 'ok',
    'version' => PHP_VERSION
];

// Vérification de la configuration
if (file_exists(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
    $health['checks']['config'] = ['status' => 'ok'];
    
    // Test de connexion à la base de données
    try {
        $dbConfig = Config::database();
        $dsn = "mysql:host=" . $dbConfig['host'] . ";port=" . $dbConfig['port'] . ";dbname=" . $dbConfig['name'] . ";charset=" . $dbConfig['charset'];
        $pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['pass']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Test simple query
        $stmt = $pdo->query("SELECT 1");
        if ($stmt) {
            $health['checks']['database'] = [
                'status' => 'ok',
                'host' => $dbConfig['host']
            ];
        }
    } catch (Exception $e) {
        $health['checks']['database'] = [
            'status' => 'error',
            'message' => 'Database connection failed'
        ];
        $health['status'] = 'degraded';
    }
} else {
    $health['checks']['config'] = ['status' => 'missing'];
    $health['status'] = 'unhealthy';
}

// Vérification des fichiers critiques
$criticalFiles = ['index.php', '.htaccess'];
foreach ($criticalFiles as $file) {
    $health['checks']['files'][$file] = file_exists(__DIR__ . '/' . $file) ? 'ok' : 'missing';
    if (!file_exists(__DIR__ . '/' . $file)) {
        $health['status'] = 'degraded';
    }
}

// Définir le code HTTP approprié
http_response_code($health['status'] === 'healthy' ? 200 : ($health['status'] === 'degraded' ? 200 : 503));

echo json_encode($health, JSON_PRETTY_PRINT);
?>