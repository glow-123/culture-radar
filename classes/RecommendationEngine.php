<?php
/**
 * CultureRadar AI Recommendation Engine
 * 
 * This class implements a machine learning-inspired recommendation system
 * that analyzes user preferences, behavior, and context to suggest cultural events.
 */

class RecommendationEngine {
    private $pdo;
    private $userId;
    private $userProfile;
    private $userPreferences;
    private $userInteractions;
    
    // Scoring weights for different factors
    private $weights = [
        'preference_match' => 0.4,     // How well event matches user's cultural preferences
        'location_proximity' => 0.25,  // Geographic distance from user
        'price_compatibility' => 0.15, // Price fits user's budget
        'time_preference' => 0.1,      // Time/day preferences
        'social_signals' => 0.05,      // Popularity among similar users
        'novelty_factor' => 0.05       // New vs familiar event types
    ];
    
    public function __construct($pdo, $userId) {
        $this->pdo = $pdo;
        $this->userId = $userId;
        $this->loadUserData();
    }
    
    /**
     * Load user profile and interaction history
     */
    private function loadUserData() {
        // Get user profile
        $stmt = $this->pdo->prepare("
            SELECT up.*, u.name, u.created_at as user_since
            FROM user_profiles up 
            JOIN users u ON up.user_id = u.id 
            WHERE up.user_id = ?
        ");
        $stmt->execute([$this->userId]);
        $this->userProfile = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($this->userProfile) {
            $this->userPreferences = json_decode($this->userProfile['preferences'], true) ?: [];
        }
        
        // Get user interactions for behavior analysis
        $stmt = $this->pdo->prepare("
            SELECT e.category, ui.interaction_type, ui.rating, ui.created_at,
                   e.price, e.venue_name, e.city
            FROM user_interactions ui
            JOIN events e ON ui.event_id = e.id
            WHERE ui.user_id = ?
            ORDER BY ui.created_at DESC
            LIMIT 100
        ");
        $stmt->execute([$this->userId]);
        $this->userInteractions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Generate personalized event recommendations
     */
    public function generateRecommendations($limit = 10, $excludeViewed = true) {
        // Get all active events
        $excludeClause = $excludeViewed ? "AND e.id NOT IN (
            SELECT DISTINCT event_id FROM user_interactions 
            WHERE user_id = ? AND interaction_type IN ('view', 'click')
        )" : "";
        
        $stmt = $this->pdo->prepare("
            SELECT e.*, v.latitude as venue_lat, v.longitude as venue_lng
            FROM events e
            LEFT JOIN venues v ON e.venue_name = v.name AND e.city = v.city
            WHERE e.is_active = 1 AND e.start_date > NOW()
            $excludeClause
            ORDER BY e.created_at DESC
        ");
        
        $params = $excludeViewed ? [$this->userId] : [];
        $stmt->execute($params);
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Score each event
        $scoredEvents = [];
        foreach ($events as $event) {
            $score = $this->calculateEventScore($event);
            if ($score > 0) {
                $event['ai_score'] = $score;
                $event['match_reasons'] = $this->getMatchReasons($event, $score);
                $scoredEvents[] = $event;
            }
        }
        
        // Sort by AI score and limit results
        usort($scoredEvents, function($a, $b) {
            return $b['ai_score'] <=> $a['ai_score'];
        });
        
        $recommendations = array_slice($scoredEvents, 0, $limit);
        
        // Save recommendations to database
        $this->saveRecommendations($recommendations);
        
        return $recommendations;
    }
    
    /**
     * Calculate AI score for a specific event
     */
    public function calculateEventScore($event) {
        $scores = [
            'preference_match' => $this->calculatePreferenceMatch($event),
            'location_proximity' => $this->calculateLocationScore($event),
            'price_compatibility' => $this->calculatePriceScore($event),
            'time_preference' => $this->calculateTimeScore($event),
            'social_signals' => $this->calculateSocialScore($event),
            'novelty_factor' => $this->calculateNoveltyScore($event)
        ];
        
        // Calculate weighted final score
        $finalScore = 0;
        foreach ($scores as $factor => $score) {
            $finalScore += $score * $this->weights[$factor];
        }
        
        // Apply behavioral learning adjustments
        $finalScore = $this->applyBehavioralLearning($event, $finalScore);
        
        return min(100, max(0, $finalScore * 100)); // Convert to 0-100 scale
    }
    
    /**
     * Calculate how well event matches user's cultural preferences
     */
    private function calculatePreferenceMatch($event) {
        if (empty($this->userPreferences)) {
            return 0.5; // Neutral score if no preferences set
        }
        
        $category = $event['category'];
        
        // Direct preference match
        if (in_array($category, $this->userPreferences)) {
            return 1.0;
        }
        
        // Category similarity mapping
        $categoryGroups = [
            'performing_arts' => ['theater', 'dance', 'music'],
            'visual_arts' => ['art', 'cinema'],
            'cultural_heritage' => ['heritage', 'literature'],
            'festivals' => ['festival']
        ];
        
        foreach ($categoryGroups as $group => $categories) {
            if (in_array($category, $categories)) {
                foreach ($this->userPreferences as $userPref) {
                    if (in_array($userPref, $categories)) {
                        return 0.7; // Related category bonus
                    }
                }
            }
        }
        
        return 0.2; // Low score for unrelated categories
    }
    
    /**
     * Calculate location-based score
     */
    private function calculateLocationScore($event) {
        if (!$this->userProfile['location']) {
            return 0.5; // Neutral if no location set
        }
        
        // Simple city matching for now (can be enhanced with real geolocation)
        $userCity = strtolower(trim($this->userProfile['location']));
        $eventCity = strtolower(trim($event['city']));
        
        if (strpos($userCity, $eventCity) !== false || strpos($eventCity, $userCity) !== false) {
            return 1.0; // Same city
        }
        
        // Enhanced: Calculate actual distance if coordinates available
        if ($event['venue_lat'] && $event['venue_lng'] && $this->userProfile['latitude'] && $this->userProfile['longitude']) {
            $distance = $this->calculateDistance(
                $this->userProfile['latitude'], 
                $this->userProfile['longitude'],
                $event['venue_lat'], 
                $event['venue_lng']
            );
            
            // Score based on distance (closer = better)
            if ($distance <= 5) return 1.0;      // Within 5km
            if ($distance <= 15) return 0.8;     // Within 15km
            if ($distance <= 30) return 0.6;     // Within 30km
            if ($distance <= 50) return 0.4;     // Within 50km
            return 0.2;                          // Further away
        }
        
        return 0.3; // Different city, no coordinates
    }
    
    /**
     * Calculate price compatibility score
     */
    private function calculatePriceScore($event) {
        $userBudget = (float) $this->userProfile['budget_max'];
        $eventPrice = (float) $event['price'];
        
        // Free events are always good
        if ($event['is_free'] || $eventPrice == 0) {
            return 1.0;
        }
        
        // No budget set - neutral scoring
        if ($userBudget <= 0) {
            return 0.5;
        }
        
        // Price within budget
        if ($eventPrice <= $userBudget) {
            // Score based on price efficiency (cheaper relative to budget = better)
            $efficiency = 1 - ($eventPrice / $userBudget);
            return 0.6 + (0.4 * $efficiency); // Scale from 0.6 to 1.0
        }
        
        // Price exceeds budget
        $overage = ($eventPrice - $userBudget) / $userBudget;
        if ($overage <= 0.2) return 0.4; // Slightly over budget
        if ($overage <= 0.5) return 0.2; // Moderately over budget
        return 0.1; // Way over budget
    }
    
    /**
     * Calculate time-based preference score
     */
    private function calculateTimeScore($event) {
        $eventTime = new DateTime($event['start_date']);
        $dayOfWeek = $eventTime->format('w'); // 0 = Sunday, 6 = Saturday
        $hour = (int) $eventTime->format('H');
        
        $score = 0.5; // Base score
        
        // Weekend preference (slight bonus)
        if ($dayOfWeek == 0 || $dayOfWeek == 6) {
            $score += 0.1;
        }
        
        // Evening events are generally preferred for cultural activities
        if ($hour >= 18 && $hour <= 22) {
            $score += 0.2;
        } elseif ($hour >= 14 && $hour <= 17) {
            $score += 0.1; // Afternoon events
        }
        
        // Analyze user's historical preferences
        $timePreference = $this->analyzeUserTimePreferences();
        if ($timePreference) {
            if ($timePreference['preferred_day_type'] == 'weekend' && ($dayOfWeek == 0 || $dayOfWeek == 6)) {
                $score += 0.2;
            }
            if ($timePreference['preferred_time_range'] == 'evening' && $hour >= 18) {
                $score += 0.2;
            }
        }
        
        return min(1.0, $score);
    }
    
    /**
     * Calculate social signals score
     */
    private function calculateSocialScore($event) {
        // Get event popularity metrics
        $stmt = $this->pdo->prepare("
            SELECT 
                COUNT(*) as total_interactions,
                COUNT(CASE WHEN interaction_type = 'save' THEN 1 END) as saves,
                COUNT(CASE WHEN interaction_type = 'click' THEN 1 END) as clicks,
                AVG(CASE WHEN rating IS NOT NULL THEN rating END) as avg_rating
            FROM user_interactions 
            WHERE event_id = ?
        ");
        $stmt->execute([$event['id']]);
        $social = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $score = 0.5; // Base score
        
        // Popular events get bonus
        if ($social['total_interactions'] > 10) $score += 0.2;
        if ($social['saves'] > 5) $score += 0.2;
        if ($social['avg_rating'] > 4) $score += 0.1;
        
        return min(1.0, $score);
    }
    
    /**
     * Calculate novelty factor
     */
    private function calculateNoveltyScore($event) {
        // Check if user has engaged with this category before
        $categoryExperience = 0;
        foreach ($this->userInteractions as $interaction) {
            if ($interaction['category'] == $event['category']) {
                $categoryExperience++;
            }
        }
        
        // Balance between familiarity and novelty
        if ($categoryExperience == 0) {
            return 0.8; // High novelty
        } elseif ($categoryExperience <= 3) {
            return 0.6; // Some experience
        } else {
            return 0.4; // Very familiar
        }
    }
    
    /**
     * Apply machine learning from user behavior
     */
    private function applyBehavioralLearning($event, $baseScore) {
        if (empty($this->userInteractions)) {
            return $baseScore; // No behavioral data yet
        }
        
        // Analyze user's interaction patterns
        $behaviorScore = $baseScore;
        
        // Category preference learning
        $categoryFeedback = $this->getCategoryFeedback($event['category']);
        if ($categoryFeedback !== null) {
            // Adjust score based on user's historical satisfaction with this category
            $behaviorScore *= (0.7 + 0.6 * $categoryFeedback); // Scale between 0.7 and 1.3
        }
        
        // Price range learning
        $priceRangeFeedback = $this->getPriceRangeFeedback($event['price']);
        if ($priceRangeFeedback !== null) {
            $behaviorScore *= (0.8 + 0.4 * $priceRangeFeedback); // Scale between 0.8 and 1.2
        }
        
        // Venue/location learning
        $venueFeedback = $this->getVenueFeedback($event['venue_name']);
        if ($venueFeedback !== null) {
            $behaviorScore *= (0.9 + 0.2 * $venueFeedback); // Scale between 0.9 and 1.1
        }
        
        return $behaviorScore;
    }
    
    /**
     * Get user feedback for specific category based on ratings and interactions
     */
    private function getCategoryFeedback($category) {
        $categoryInteractions = array_filter($this->userInteractions, function($interaction) use ($category) {
            return $interaction['category'] == $category;
        });
        
        if (empty($categoryInteractions)) {
            return null;
        }
        
        $totalScore = 0;
        $count = 0;
        
        foreach ($categoryInteractions as $interaction) {
            $score = 0;
            
            // Explicit ratings
            if ($interaction['rating']) {
                $score = ($interaction['rating'] - 3) / 2; // Convert 1-5 to -1 to 1
                $count++;
            } else {
                // Implicit feedback from interaction type
                switch ($interaction['interaction_type']) {
                    case 'save': $score = 0.5; $count++; break;
                    case 'click': $score = 0.2; $count++; break;
                    case 'share': $score = 0.7; $count++; break;
                    case 'view': $score = 0.1; $count++; break;
                }
            }
            
            $totalScore += $score;
        }
        
        return $count > 0 ? $totalScore / $count : null;
    }
    
    /**
     * Get feedback for price ranges
     */
    private function getPriceRangeFeedback($eventPrice) {
        $priceRange = $this->getPriceRange($eventPrice);
        $rangeInteractions = array_filter($this->userInteractions, function($interaction) use ($priceRange) {
            return $this->getPriceRange($interaction['price']) == $priceRange;
        });
        
        if (empty($rangeInteractions)) {
            return null;
        }
        
        // Similar feedback calculation as category
        $totalScore = 0;
        $count = 0;
        
        foreach ($rangeInteractions as $interaction) {
            if ($interaction['rating']) {
                $totalScore += ($interaction['rating'] - 3) / 2;
                $count++;
            }
        }
        
        return $count > 0 ? $totalScore / $count : null;
    }
    
    /**
     * Get venue feedback
     */
    private function getVenueFeedback($venueName) {
        $venueInteractions = array_filter($this->userInteractions, function($interaction) use ($venueName) {
            return $interaction['venue_name'] == $venueName;
        });
        
        if (empty($venueInteractions)) {
            return null;
        }
        
        $totalScore = 0;
        $count = 0;
        
        foreach ($venueInteractions as $interaction) {
            if ($interaction['rating']) {
                $totalScore += ($interaction['rating'] - 3) / 2;
                $count++;
            }
        }
        
        return $count > 0 ? $totalScore / $count : null;
    }
    
    /**
     * Helper: Get price range category
     */
    private function getPriceRange($price) {
        if ($price == 0) return 'free';
        if ($price <= 10) return 'low';
        if ($price <= 25) return 'medium';
        if ($price <= 50) return 'high';
        return 'premium';
    }
    
    /**
     * Analyze user's time preferences from history
     */
    private function analyzeUserTimePreferences() {
        if (empty($this->userInteractions)) {
            return null;
        }
        
        $weekendCount = 0;
        $weekdayCount = 0;
        $eveningCount = 0;
        $otherTimeCount = 0;
        
        foreach ($this->userInteractions as $interaction) {
            $date = new DateTime($interaction['created_at']);
            $dayOfWeek = $date->format('w');
            $hour = (int) $date->format('H');
            
            if ($dayOfWeek == 0 || $dayOfWeek == 6) {
                $weekendCount++;
            } else {
                $weekdayCount++;
            }
            
            if ($hour >= 18 && $hour <= 22) {
                $eveningCount++;
            } else {
                $otherTimeCount++;
            }
        }
        
        return [
            'preferred_day_type' => $weekendCount > $weekdayCount ? 'weekend' : 'weekday',
            'preferred_time_range' => $eveningCount > $otherTimeCount ? 'evening' : 'other'
        ];
    }
    
    /**
     * Calculate distance between two coordinates (Haversine formula)
     */
    private function calculateDistance($lat1, $lon1, $lat2, $lon2) {
        $earthRadius = 6371; // Earth's radius in kilometers
        
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        
        $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        
        return $earthRadius * $c;
    }
    
    /**
     * Generate explanation of why an event was recommended
     */
    private function getMatchReasons($event, $score) {
        $reasons = [];
        
        // Category match
        if (in_array($event['category'], $this->userPreferences)) {
            $reasons[] = "Correspond à vos goûts culturels";
        }
        
        // Location
        if ($this->userProfile['location'] && 
            strpos(strtolower($this->userProfile['location']), strtolower($event['city'])) !== false) {
            $reasons[] = "Proche de votre localisation";
        }
        
        // Price
        if ($event['is_free']) {
            $reasons[] = "Événement gratuit";
        } elseif ($event['price'] <= $this->userProfile['budget_max']) {
            $reasons[] = "Dans votre budget";
        }
        
        // Time
        $eventTime = new DateTime($event['start_date']);
        $dayOfWeek = $eventTime->format('w');
        if ($dayOfWeek == 0 || $dayOfWeek == 6) {
            $reasons[] = "Le week-end";
        }
        
        // High score
        if ($score > 80) {
            $reasons[] = "Recommandation forte de l'IA";
        }
        
        return $reasons;
    }
    
    /**
     * Save recommendations to database for tracking
     */
    private function saveRecommendations($recommendations) {
        // Clear old recommendations
        $stmt = $this->pdo->prepare("DELETE FROM user_recommendations WHERE user_id = ?");
        $stmt->execute([$this->userId]);
        
        // Insert new recommendations
        $stmt = $this->pdo->prepare("
            INSERT INTO user_recommendations (user_id, event_id, match_score, reasons, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        
        foreach ($recommendations as $event) {
            $reasons = json_encode($event['match_reasons']);
            $stmt->execute([
                $this->userId,
                $event['id'],
                $event['ai_score'],
                $reasons
            ]);
        }
    }
    
    /**
     * Update user preferences based on implicit feedback
     */
    public function updatePreferencesFromBehavior() {
        if (empty($this->userInteractions)) {
            return;
        }
        
        // Analyze which categories user engages with most
        $categoryEngagement = [];
        foreach ($this->userInteractions as $interaction) {
            $category = $interaction['category'];
            if (!isset($categoryEngagement[$category])) {
                $categoryEngagement[$category] = 0;
            }
            
            // Weight different interactions
            $weight = 1;
            switch ($interaction['interaction_type']) {
                case 'save': $weight = 3; break;
                case 'share': $weight = 4; break;
                case 'click': $weight = 2; break;
                case 'view': $weight = 1; break;
            }
            
            if ($interaction['rating']) {
                $weight *= ($interaction['rating'] / 3); // Scale by rating
            }
            
            $categoryEngagement[$category] += $weight;
        }
        
        // Update preferences if user shows strong engagement with new categories
        arsort($categoryEngagement);
        $topCategories = array_slice(array_keys($categoryEngagement), 0, 5);
        
        $updatedPreferences = array_unique(array_merge($this->userPreferences, $topCategories));
        
        // Save updated preferences
        $stmt = $this->pdo->prepare("
            UPDATE user_profiles 
            SET preferences = ?, updated_at = NOW() 
            WHERE user_id = ?
        ");
        $stmt->execute([json_encode($updatedPreferences), $this->userId]);
        
        $this->userPreferences = $updatedPreferences;
    }
    
    /**
     * Get similar users for collaborative filtering
     */
    public function findSimilarUsers($limit = 10) {
        $stmt = $this->pdo->prepare("
            SELECT u.id, u.name, up.preferences, up.location,
                   COUNT(CASE WHEN ui1.event_id = ui2.event_id THEN 1 END) as common_events
            FROM users u
            JOIN user_profiles up ON u.id = up.user_id
            LEFT JOIN user_interactions ui1 ON u.id = ui1.user_id
            LEFT JOIN user_interactions ui2 ON ui2.user_id = ? AND ui1.event_id = ui2.event_id
            WHERE u.id != ?
            GROUP BY u.id
            HAVING common_events > 0
            ORDER BY common_events DESC
            LIMIT ?
        ");
        
        $stmt->execute([$this->userId, $this->userId, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get trending events based on recent activity
     */
    public function getTrendingEvents($limit = 5) {
        $stmt = $this->pdo->prepare("
            SELECT e.*, COUNT(ui.id) as interaction_count,
                   COUNT(CASE WHEN ui.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as recent_interactions
            FROM events e
            LEFT JOIN user_interactions ui ON e.id = ui.event_id
            WHERE e.is_active = 1 AND e.start_date > NOW()
            GROUP BY e.id
            HAVING recent_interactions > 0
            ORDER BY recent_interactions DESC, interaction_count DESC
            LIMIT ?
        ");
        
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>