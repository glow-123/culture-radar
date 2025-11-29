<?php
/**
 * CultureRadar Configuration Manager
 * Handles environment variables and application configuration
 */

// Configure session settings ONLY if session hasn't started yet
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', 0); // Mettre à 1 en HTTPS uniquement
    ini_set('session.use_strict_mode', 1);
}

class Config {
    private static $config = [];
    private static $loaded = false;
    
    /**
     * Load configuration from .env file
     */
    public static function load() {
        if (self::$loaded) {
            return;
        }
        
        $envFile = __DIR__ . '/.env';
        
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            
            foreach ($lines as $line) {
                // Skip comments
                if (strpos(trim($line), '#') === 0) {
                    continue;
                }
                
                // Parse key=value pairs
                if (strpos($line, '=') !== false) {
                    list($key, $value) = explode('=', $line, 2);
                    $key = trim($key);
                    $value = trim($value);
                    
                    // Remove quotes if present
                    $value = trim($value, '"\'');
                    
                    self::$config[$key] = $value;
                    
                    // Set as environment variable if not already set
                    if (!isset($_ENV[$key])) {
                        $_ENV[$key] = $value;
                        putenv("$key=$value");
                    }
                }
            }
        }
        
        // Set defaults for missing values
        self::setDefaults();
        self::$loaded = true;
    }
    
    /**
     * Get configuration value
     */
    public static function get($key, $default = null) {
        self::load();
        
        // Check environment variable first
        $envValue = getenv($key);
        if ($envValue !== false) {
            return self::parseValue($envValue);
        }
        
        // Check config array
        if (isset(self::$config[$key])) {
            return self::parseValue(self::$config[$key]);
        }
        
        return $default;
    }
    
    /**
     * Set configuration value
     */
    public static function set($key, $value) {
        self::$config[$key] = $value;
        $_ENV[$key] = $value;
        putenv("$key=$value");
    }
    
    /**
     * Parse configuration value (handle booleans, numbers, etc.)
     */
    private static function parseValue($value) {
        // Handle booleans
        if (strtolower($value) === 'true') return true;
        if (strtolower($value) === 'false') return false;
        
        // Handle null
        if (strtolower($value) === 'null') return null;
        
        // Handle numbers
        if (is_numeric($value)) {
            return strpos($value, '.') !== false ? (float)$value : (int)$value;
        }
        
        return $value;
    }
    
    /**
     * Set default configuration values
     */
    private static function setDefaults() {
        $defaults = [
            'APP_NAME' => 'Culture Radar',
            'APP_ENV' => 'development',
            'APP_DEBUG' => 'true',
            'APP_URL' => 'http://localhost:8080',
            
            // Default to local development database
            'DB_HOST' => 'localhost',
            'DB_NAME' => 'culture_radar',
            'DB_USER' => 'root',
            'DB_PASS' => 'root',
            'DB_PORT' => '8889',
            
            'CACHE_DRIVER' => 'file',
            'CACHE_TTL' => '3600',
            'UPLOAD_MAX_SIZE' => '10485760',
            'ALLOWED_IMAGE_TYPES' => 'jpg,jpeg,png,webp',
            'UPLOAD_PATH' => '/uploads',
            'RATE_LIMIT_ENABLED' => 'true',
            'RATE_LIMIT_REQUESTS' => '100',
            'RATE_LIMIT_WINDOW' => '3600',
            'AI_TRAINING_ENABLED' => 'true',
            'AI_MIN_INTERACTIONS' => '10',
            'ERROR_REPORTING' => 'true',
            'LOG_LEVEL' => 'warning'
        ];
        
        foreach ($defaults as $key => $value) {
            if (!isset(self::$config[$key]) && getenv($key) === false) {
                self::$config[$key] = $value;
            }
        }
    }
    
    /**
     * Get database configuration
     */
    public static function database() {
        return [
            'host' => self::get('DB_HOST'),
            'name' => self::get('DB_NAME'),
            'user' => self::get('DB_USER'),
            'pass' => self::get('DB_PASS'),
            'port' => self::get('DB_PORT'),
            'charset' => 'utf8mb4'
        ];
    }
    
    /**
     * Get PDO DSN string for database connection
     */
    public static function getDSN() {
        $db = self::database();
        return "mysql:host={$db['host']};port={$db['port']};dbname={$db['name']};charset={$db['charset']}";
    }
    
    /**
     * Create and return a PDO instance
     */
    public static function getPDO() {
        try {
            $db = self::database();
            $dsn = self::getDSN();
            
            $pdo = new PDO($dsn, $db['user'], $db['pass']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            
            // Set UTF8MB4 charset
            $pdo->exec("SET NAMES utf8mb4");
            $pdo->exec("SET CHARACTER SET utf8mb4");
            $pdo->exec("SET COLLATION_CONNECTION = 'utf8mb4_unicode_ci'");
            
            return $pdo;
        } catch (PDOException $e) {
            if (self::isDebug()) {
                echo "Database connection error: " . $e->getMessage();
                throw $e;
            } else {
                error_log("Database connection failed: " . $e->getMessage());
                die("Database connection failed. Please check your configuration.");
            }
        }
    }
    
    /**
     * Get email configuration
     */
    public static function mail() {
        return [
            'driver' => self::get('MAIL_DRIVER', 'smtp'),
            'host' => self::get('MAIL_HOST'),
            'port' => self::get('MAIL_PORT', 587),
            'username' => self::get('MAIL_USERNAME'),
            'password' => self::get('MAIL_PASSWORD'),
            'encryption' => self::get('MAIL_ENCRYPTION', 'tls'),
            'from_address' => self::get('MAIL_FROM_ADDRESS'),
            'from_name' => self::get('MAIL_FROM_NAME', self::get('APP_NAME'))
        ];
    }
    
    /**
     * Check if application is in debug mode
     */
    public static function isDebug() {
        return self::get('APP_DEBUG', false);
    }
    
    /**
     * Check if application is in production
     */
    public static function isProduction() {
        return self::get('APP_ENV') === 'production';
    }
    
    /**
     * Check if application is in development
     */
    public static function isDevelopment() {
        return self::get('APP_ENV') === 'development';
    }
    
    /**
     * Get all configuration as array
     */
    public static function all() {
        self::load();
        return array_merge($_ENV, self::$config);
    }
    
    /**
     * Validate database connection
     */
    public static function testDatabaseConnection() {
        try {
            $pdo = self::getPDO();
            $stmt = $pdo->query("SELECT 1");
            return $stmt !== false;
        } catch (Exception $e) {
            return false;
        }
    }
}

// Auto-load configuration
Config::load();

// Set PHP error reporting based on environment
if (Config::isDebug()) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('log_errors', 1);
    if (!is_dir(__DIR__ . '/logs')) {
        @mkdir(__DIR__ . '/logs', 0755, true);
    }
    ini_set('error_log', __DIR__ . '/logs/error.log');
} else {
    error_reporting(E_ERROR | E_WARNING | E_PARSE);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    if (!is_dir(__DIR__ . '/logs')) {
        @mkdir(__DIR__ . '/logs', 0755, true);
    }
    ini_set('error_log', __DIR__ . '/logs/error.log');
}

// Set timezone
date_default_timezone_set('Europe/Paris');

// Define application constants
if (!defined('APP_NAME')) {
    define('APP_NAME', Config::get('APP_NAME'));
    define('APP_VERSION', '1.0.0');
    define('APP_URL', Config::get('APP_URL'));
    define('APP_ENV', Config::get('APP_ENV'));
}

// Database constants (for backward compatibility)
if (!defined('DB_HOST')) {
    define('DB_HOST', Config::get('DB_HOST'));
    define('DB_NAME', Config::get('DB_NAME'));
    define('DB_USER', Config::get('DB_USER'));
    define('DB_PASS', Config::get('DB_PASS'));
    define('DB_PORT', Config::get('DB_PORT'));
}

// Path constants
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__FILE__));
    define('UPLOAD_PATH', ROOT_PATH . Config::get('UPLOAD_PATH'));
    define('LOG_PATH', ROOT_PATH . '/logs');
}

// Create necessary directories if they don't exist
if (!file_exists(UPLOAD_PATH)) {
    @mkdir(UPLOAD_PATH, 0755, true);
}
if (!file_exists(LOG_PATH)) {
    @mkdir(LOG_PATH, 0755, true);
}
?>
