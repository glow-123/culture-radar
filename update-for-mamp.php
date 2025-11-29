<?php
/**
 * Update all PHP files to use MAMP configuration
 * Run this once after copying files to MAMP directory
 */

// Define the files that need updating
$filesToUpdate = [
    'discover.php',
    'onboarding.php',
    'organizer/dashboard.php',
    'organizer/events.php',
    'admin/badges.php'
];

$replacements = [
    // Replace old database definitions
    [
        'from' => '// Database configuration
define(\'DB_HOST\', \'localhost\');
define(\'DB_NAME\', \'culture_radar\');
define(\'DB_USER\', \'root\');
define(\'DB_PASS\', \'\');',
        'to' => '// Load configuration
require_once __DIR__ . \'/config.php\';'
    ],
    [
        'from' => '// Database configuration
define(\'DB_HOST\', \'localhost\');
define(\'DB_NAME\', \'culture_radar\');
define(\'DB_USER\', \'root\');
define(\'DB_PASS\', \'\');',
        'to' => '// Load configuration
require_once __DIR__ . \'/../config.php\';'
    ],
    // Replace PDO connections
    [
        'from' => 'new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS)',
        'to' => 'new PDO("mysql:host=" . Config::get(\'DB_HOST\') . ";dbname=" . Config::get(\'DB_NAME\') . ";charset=utf8mb4", Config::get(\'DB_USER\'), Config::get(\'DB_PASS\'))'
    ]
];

echo "🔧 Updating files for MAMP configuration...\n\n";

foreach ($filesToUpdate as $file) {
    $filePath = __DIR__ . '/' . $file;
    
    if (!file_exists($filePath)) {
        echo "⚠️  File not found: $file\n";
        continue;
    }
    
    $content = file_get_contents($filePath);
    $originalContent = $content;
    
    // Apply replacements
    foreach ($replacements as $replacement) {
        $content = str_replace($replacement['from'], $replacement['to'], $content);
    }
    
    // Check if file was modified
    if ($content !== $originalContent) {
        file_put_contents($filePath, $content);
        echo "✅ Updated: $file\n";
    } else {
        echo "✅ Already up to date: $file\n";
    }
}

echo "\n🎉 All files updated for MAMP!\n";
echo "\nNext steps:\n";
echo "1. Copy .env.mamp to .env\n";
echo "2. Start MAMP servers\n";
echo "3. Create database: http://localhost:8888/phpMyAdmin\n";
echo "4. Run: php setup-database.php\n";
echo "5. Visit: http://localhost:8888/culture-radar/\n";
?>