<?php
/**
 * AI Recommendations API Endpoint
 * Provides personalized event recommendations using machine learning
 */

session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

// Load configuration
require_once __DIR__ . '/../config.php';

// Require the recommendation engine
require_once __DIR__ . '/../classes/RecommendationEngine.php';

try {
    $dbConfig = Config::database();
    $dsn = "mysql:host=" . $dbConfig['host'] . ";dbname=" . $dbConfig['name'] . ";charset=" . $dbConfig['charset'];
    $pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        exit;
    }
    
    $userId = $_SESSION['user_id'];
    $method = $_SERVER['REQUEST_METHOD'];
    
    if ($method === 'GET') {
        $action = $_GET['action'] ?? 'recommend';
        $limit = min(50, max(1, (int)($_GET['limit'] ?? 10))); // Between 1 and 50
        
        $engine = new RecommendationEngine($pdo, $userId);
        
        switch ($action) {
            case 'recommend':
                $excludeViewed = isset($_GET['exclude_viewed']) ? (bool)$_GET['exclude_viewed'] : true;
                $recommendations = $engine->generateRecommendations($limit, $excludeViewed);
                
                // Format for frontend
                $response = [
                    'success' => true,
                    'recommendations' => array_map(function($event) {
                        return [
                            'id' => $event['id'],
                            'title' => $event['title'],
                            'description' => $event['description'],
                            'category' => $event['category'],
                            'venue_name' => $event['venue_name'],
                            'city' => $event['city'],
                            'start_date' => $event['start_date'],
                            'end_date' => $event['end_date'],
                            'price' => $event['price'],
                            'is_free' => (bool)$event['is_free'],
                            'image_url' => $event['image_url'],
                            'ai_score' => round($event['ai_score'], 1),
                            'match_percentage' => round($event['ai_score']),
                            'reasons' => $event['match_reasons'],
                            'created_at' => $event['created_at']
                        ];
                    }, $recommendations),
                    'total_count' => count($recommendations),
                    'user_id' => $userId,
                    'generated_at' => date('Y-m-d H:i:s')
                ];
                
                echo json_encode($response);
                break;
                
            case 'trending':
                $trending = $engine->getTrendingEvents($limit);
                echo json_encode([
                    'success' => true,
                    'trending_events' => $trending,
                    'count' => count($trending)
                ]);
                break;
                
            case 'similar_users':
                $similarUsers = $engine->findSimilarUsers($limit);
                echo json_encode([
                    'success' => true,
                    'similar_users' => $similarUsers,
                    'count' => count($similarUsers)
                ]);
                break;
                
            case 'explain':
                $eventId = (int)($_GET['event_id'] ?? 0);
                if (!$eventId) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Event ID required']);
                    exit;
                }
                
                // Get event details
                $stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
                $stmt->execute([$eventId]);
                $event = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$event) {
                    http_response_code(404);
                    echo json_encode(['error' => 'Event not found']);
                    exit;
                }
                
                $score = $engine->calculateEventScore($event);
                
                echo json_encode([
                    'success' => true,
                    'event_id' => $eventId,
                    'ai_score' => round($score, 1),
                    'explanation' => [
                        'preference_match' => round($engine->calculateEventScore($event) * 0.4, 1),
                        'location_score' => 'Based on proximity to your location',
                        'price_compatibility' => 'Fits within your budget range',
                        'social_signals' => 'Popular among users with similar tastes',
                        'novelty_factor' => 'Balance of familiar and new experiences'
                    ]
                ]);
                break;
                
            default:
                http_response_code(400);
                echo json_encode(['error' => 'Invalid action']);
        }
        
    } elseif ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? '';
        
        $engine = new RecommendationEngine($pdo, $userId);
        
        switch ($action) {
            case 'feedback':
                $eventId = (int)($input['event_id'] ?? 0);
                $rating = (int)($input['rating'] ?? 0);
                $interactionType = $input['interaction_type'] ?? 'view';
                
                if (!$eventId) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Event ID required']);
                    exit;
                }
                
                // Record user interaction
                $stmt = $pdo->prepare("
                    INSERT INTO user_interactions 
                    (user_id, event_id, interaction_type, rating, created_at)
                    VALUES (?, ?, ?, ?, NOW())
                    ON DUPLICATE KEY UPDATE
                    rating = VALUES(rating),
                    created_at = NOW()
                ");
                
                $stmt->execute([
                    $userId,
                    $eventId,
                    $interactionType,
                    $rating > 0 ? $rating : null
                ]);
                
                // Update user preferences based on behavior
                $engine->updatePreferencesFromBehavior();
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Feedback recorded successfully'
                ]);
                break;
                
            case 'batch_feedback':
                $interactions = $input['interactions'] ?? [];
                
                if (empty($interactions)) {
                    http_response_code(400);
                    echo json_encode(['error' => 'No interactions provided']);
                    exit;
                }
                
                $stmt = $pdo->prepare("
                    INSERT INTO user_interactions 
                    (user_id, event_id, interaction_type, rating, metadata, created_at)
                    VALUES (?, ?, ?, ?, ?, NOW())
                ");
                
                $recorded = 0;
                foreach ($interactions as $interaction) {
                    if (!isset($interaction['event_id']) || !isset($interaction['type'])) {
                        continue;
                    }
                    
                    $stmt->execute([
                        $userId,
                        (int)$interaction['event_id'],
                        $interaction['type'],
                        $interaction['rating'] ?? null,
                        isset($interaction['metadata']) ? json_encode($interaction['metadata']) : null
                    ]);
                    $recorded++;
                }
                
                echo json_encode([
                    'success' => true,
                    'message' => "Recorded $recorded interactions"
                ]);
                break;
                
            default:
                http_response_code(400);
                echo json_encode(['error' => 'Invalid action']);
        }
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error',
        'message' => 'Failed to process request'
    ]);
    error_log("AI Recommendations API error: " . $e->getMessage());
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Server error',
        'message' => $e->getMessage()
    ]);
    error_log("AI Recommendations API error: " . $e->getMessage());
}
?>