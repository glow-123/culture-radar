<?php
/**
 * CultureRadar AI Training Script
 * 
 * This script analyzes user behavior patterns and updates the AI recommendation weights
 * to improve accuracy over time. Run this periodically (e.g., daily via cron job).
 */

require_once __DIR__ . '/../classes/RecommendationEngine.php';

// Load configuration
require_once __DIR__ . '/../config.php';

class AITrainer {
    private $pdo;
    private $weights;
    private $trainingData;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->weights = [
            'preference_match' => 0.4,
            'location_proximity' => 0.25,
            'price_compatibility' => 0.15,
            'time_preference' => 0.1,
            'social_signals' => 0.05,
            'novelty_factor' => 0.05
        ];
    }
    
    /**
     * Main training function
     */
    public function train() {
        echo "🤖 Starting AI Training Session...\n\n";
        
        // Load training data
        $this->loadTrainingData();
        
        if (empty($this->trainingData)) {
            echo "❌ No training data available. Need more user interactions.\n";
            return;
        }
        
        echo "📊 Found " . count($this->trainingData) . " training examples\n";
        
        // Analyze user satisfaction patterns
        $this->analyzeUserSatisfaction();
        
        // Optimize recommendation weights
        $this->optimizeWeights();
        
        // Update category preferences
        $this->updateCategoryInsights();
        
        // Generate performance report
        $this->generateReport();
        
        echo "\n✅ AI Training completed successfully!\n";
    }
    
    /**
     * Load user interaction data for training
     */
    private function loadTrainingData() {
        $stmt = $this->pdo->prepare("
            SELECT 
                ui.user_id,
                ui.event_id,
                ui.interaction_type,
                ui.rating,
                ui.created_at,
                e.category,
                e.price,
                e.is_free,
                e.city,
                e.venue_name,
                up.preferences,
                up.location,
                up.budget_max,
                ur.match_score as predicted_score
            FROM user_interactions ui
            JOIN events e ON ui.event_id = e.id
            JOIN user_profiles up ON ui.user_id = up.user_id
            LEFT JOIN user_recommendations ur ON ui.user_id = ur.user_id AND ui.event_id = ur.event_id
            WHERE ui.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            AND ui.interaction_type IN ('view', 'click', 'save', 'rate')
            ORDER BY ui.created_at DESC
        ");
        
        $stmt->execute();
        $this->trainingData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Analyze which recommendations led to user satisfaction
     */
    private function analyzeUserSatisfaction() {
        echo "📈 Analyzing user satisfaction patterns...\n";
        
        $satisfactionLevels = [
            'high' => [], // Explicit 4-5 star ratings or save actions
            'medium' => [], // 3 star ratings or click actions
            'low' => [] // 1-2 star ratings or view-only
        ];
        
        foreach ($this->trainingData as $interaction) {
            $satisfaction = $this->calculateSatisfactionLevel($interaction);
            $satisfactionLevels[$satisfaction][] = $interaction;
        }
        
        echo sprintf(
            "   High satisfaction: %d interactions\n",
            count($satisfactionLevels['high'])
        );
        echo sprintf(
            "   Medium satisfaction: %d interactions\n",
            count($satisfactionLevels['medium'])
        );
        echo sprintf(
            "   Low satisfaction: %d interactions\n",
            count($satisfactionLevels['low'])
        );
        
        // Analyze patterns in high satisfaction events
        $this->findSuccessPatterns($satisfactionLevels['high']);
    }
    
    /**
     * Calculate user satisfaction level from interaction
     */
    private function calculateSatisfactionLevel($interaction) {
        if ($interaction['rating']) {
            if ($interaction['rating'] >= 4) return 'high';
            if ($interaction['rating'] == 3) return 'medium';
            return 'low';
        }
        
        switch ($interaction['interaction_type']) {
            case 'save':
            case 'share':
                return 'high';
            case 'click':
                return 'medium';
            case 'view':
            default:
                return 'low';
        }
    }
    
    /**
     * Find patterns in successful recommendations
     */
    private function findSuccessPatterns($highSatisfactionData) {
        if (empty($highSatisfactionData)) return;
        
        echo "🔍 Analyzing success patterns...\n";
        
        // Category success rates
        $categorySuccess = [];
        foreach ($highSatisfactionData as $interaction) {
            $category = $interaction['category'];
            if (!isset($categorySuccess[$category])) {
                $categorySuccess[$category] = 0;
            }
            $categorySuccess[$category]++;
        }
        
        arsort($categorySuccess);
        echo "   Top performing categories:\n";
        foreach (array_slice($categorySuccess, 0, 3, true) as $category => $count) {
            echo "     - {$category}: {$count} positive interactions\n";
        }
        
        // Price range analysis
        $priceRanges = [
            'free' => 0,
            'low' => 0,
            'medium' => 0,
            'high' => 0
        ];
        
        foreach ($highSatisfactionData as $interaction) {
            $range = $this->getPriceRange($interaction['price'], $interaction['is_free']);
            $priceRanges[$range]++;
        }
        
        echo "   Price range preferences:\n";
        foreach ($priceRanges as $range => $count) {
            echo "     - {$range}: {$count} positive interactions\n";
        }
    }
    
    /**
     * Optimize recommendation weights using performance data
     */
    private function optimizeWeights() {
        echo "⚖️ Optimizing recommendation weights...\n";
        
        // Simple weight adjustment based on success rates
        $categoryPerformance = $this->calculateCategoryPerformance();
        $locationPerformance = $this->calculateLocationPerformance();
        $pricePerformance = $this->calculatePricePerformance();
        
        // Adjust weights based on performance
        $newWeights = $this->weights;
        
        if ($categoryPerformance > 0.7) {
            $newWeights['preference_match'] = min(0.5, $this->weights['preference_match'] + 0.05);
        } elseif ($categoryPerformance < 0.3) {
            $newWeights['preference_match'] = max(0.2, $this->weights['preference_match'] - 0.05);
        }
        
        if ($locationPerformance > 0.6) {
            $newWeights['location_proximity'] = min(0.35, $this->weights['location_proximity'] + 0.05);
        }
        
        if ($pricePerformance > 0.8) {
            $newWeights['price_compatibility'] = min(0.25, $this->weights['price_compatibility'] + 0.05);
        }
        
        // Normalize weights to sum to 1
        $total = array_sum($newWeights);
        foreach ($newWeights as $key => $weight) {
            $newWeights[$key] = $weight / $total;
        }
        
        $this->weights = $newWeights;
        
        // Save updated weights to database/config
        $this->saveWeights();
        
        echo "   Updated weights:\n";
        foreach ($this->weights as $factor => $weight) {
            echo sprintf("     - %s: %.3f\n", $factor, $weight);
        }
    }
    
    /**
     * Calculate performance metrics
     */
    private function calculateCategoryPerformance() {
        $correct = 0;
        $total = 0;
        
        foreach ($this->trainingData as $interaction) {
            if ($interaction['rating'] || in_array($interaction['interaction_type'], ['save', 'share'])) {
                $preferences = json_decode($interaction['preferences'], true) ?: [];
                $category = $interaction['category'];
                
                if (in_array($category, $preferences)) {
                    $correct++;
                }
                $total++;
            }
        }
        
        return $total > 0 ? $correct / $total : 0;
    }
    
    private function calculateLocationPerformance() {
        $correct = 0;
        $total = 0;
        
        foreach ($this->trainingData as $interaction) {
            if ($interaction['rating'] >= 4 || $interaction['interaction_type'] === 'save') {
                $userLocation = strtolower($interaction['location']);
                $eventLocation = strtolower($interaction['city']);
                
                if (strpos($userLocation, $eventLocation) !== false || 
                    strpos($eventLocation, $userLocation) !== false) {
                    $correct++;
                }
                $total++;
            }
        }
        
        return $total > 0 ? $correct / $total : 0;
    }
    
    private function calculatePricePerformance() {
        $correct = 0;
        $total = 0;
        
        foreach ($this->trainingData as $interaction) {
            if ($interaction['rating'] >= 4 || $interaction['interaction_type'] === 'save') {
                $budget = (float)$interaction['budget_max'];
                $price = (float)$interaction['price'];
                
                if ($interaction['is_free'] || $price <= $budget) {
                    $correct++;
                }
                $total++;
            }
        }
        
        return $total > 0 ? $correct / $total : 0;
    }
    
    /**
     * Update category insights based on training data
     */
    private function updateCategoryInsights() {
        echo "🎯 Updating category insights...\n";
        
        // Calculate category popularity and satisfaction
        $categoryStats = [];
        
        foreach ($this->trainingData as $interaction) {
            $category = $interaction['category'];
            
            if (!isset($categoryStats[$category])) {
                $categoryStats[$category] = [
                    'total_interactions' => 0,
                    'positive_interactions' => 0,
                    'average_rating' => 0,
                    'rating_count' => 0
                ];
            }
            
            $categoryStats[$category]['total_interactions']++;
            
            if ($interaction['rating'] >= 4 || in_array($interaction['interaction_type'], ['save', 'share'])) {
                $categoryStats[$category]['positive_interactions']++;
            }
            
            if ($interaction['rating']) {
                $categoryStats[$category]['average_rating'] += $interaction['rating'];
                $categoryStats[$category]['rating_count']++;
            }
        }
        
        // Calculate final statistics
        foreach ($categoryStats as $category => &$stats) {
            $stats['satisfaction_rate'] = $stats['total_interactions'] > 0 ? 
                $stats['positive_interactions'] / $stats['total_interactions'] : 0;
                
            $stats['average_rating'] = $stats['rating_count'] > 0 ? 
                $stats['average_rating'] / $stats['rating_count'] : 0;
        }
        
        // Save insights to database
        $this->saveCategoryInsights($categoryStats);
        
        echo "   Category insights updated for " . count($categoryStats) . " categories\n";
    }
    
    /**
     * Generate training performance report
     */
    private function generateReport() {
        echo "📋 Generating performance report...\n";
        
        $totalInteractions = count($this->trainingData);
        $ratedInteractions = count(array_filter($this->trainingData, function($i) { 
            return $i['rating'] > 0; 
        }));
        
        $averageRating = 0;
        if ($ratedInteractions > 0) {
            $totalRating = array_sum(array_column(
                array_filter($this->trainingData, function($i) { return $i['rating'] > 0; }),
                'rating'
            ));
            $averageRating = $totalRating / $ratedInteractions;
        }
        
        $saveRate = count(array_filter($this->trainingData, function($i) { 
            return $i['interaction_type'] === 'save'; 
        })) / $totalInteractions;
        
        $report = [
            'training_date' => date('Y-m-d H:i:s'),
            'total_interactions' => $totalInteractions,
            'rated_interactions' => $ratedInteractions,
            'average_rating' => round($averageRating, 2),
            'save_rate' => round($saveRate * 100, 1) . '%',
            'category_performance' => round($this->calculateCategoryPerformance() * 100, 1) . '%',
            'location_performance' => round($this->calculateLocationPerformance() * 100, 1) . '%',
            'price_performance' => round($this->calculatePricePerformance() * 100, 1) . '%'
        ];
        
        // Save report
        $this->saveReport($report);
        
        echo "\n📊 Performance Report:\n";
        foreach ($report as $metric => $value) {
            echo sprintf("   %s: %s\n", ucwords(str_replace('_', ' ', $metric)), $value);
        }
    }
    
    /**
     * Helper functions
     */
    private function getPriceRange($price, $isFree) {
        if ($isFree || $price == 0) return 'free';
        if ($price <= 10) return 'low';
        if ($price <= 25) return 'medium';
        return 'high';
    }
    
    private function saveWeights() {
        // Save weights to a config file or database
        $stmt = $this->pdo->prepare("
            INSERT INTO ai_config (config_key, config_value, updated_at)
            VALUES ('recommendation_weights', ?, NOW())
            ON DUPLICATE KEY UPDATE
            config_value = VALUES(config_value),
            updated_at = NOW()
        ");
        
        $stmt->execute([json_encode($this->weights)]);
    }
    
    private function saveCategoryInsights($categoryStats) {
        $stmt = $this->pdo->prepare("
            INSERT INTO ai_config (config_key, config_value, updated_at)
            VALUES ('category_insights', ?, NOW())
            ON DUPLICATE KEY UPDATE
            config_value = VALUES(config_value),
            updated_at = NOW()
        ");
        
        $stmt->execute([json_encode($categoryStats)]);
    }
    
    private function saveReport($report) {
        $stmt = $this->pdo->prepare("
            INSERT INTO ai_training_reports (report_data, created_at)
            VALUES (?, NOW())
        ");
        
        $stmt->execute([json_encode($report)]);
    }
}

// Initialize database connection
try {
    $dbConfig = Config::database();
    $dsn = "mysql:host=" . $dbConfig['host'] . ";dbname=" . $dbConfig['name'] . ";charset=" . $dbConfig['charset'];
    $pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create necessary tables for AI config and reports
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS ai_config (
            id INT PRIMARY KEY AUTO_INCREMENT,
            config_key VARCHAR(100) UNIQUE NOT NULL,
            config_value JSON NOT NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS ai_training_reports (
            id INT PRIMARY KEY AUTO_INCREMENT,
            report_data JSON NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // Run training
    $trainer = new AITrainer($pdo);
    $trainer->train();
    
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "❌ Training error: " . $e->getMessage() . "\n";
    exit(1);
}
?>