<?php
/**
 * CultureRadar Badge System
 * Manages the "CultureRadar" badge for independent cultural venues
 */

class BadgeSystem {
    private $pdo;
    private $badgeCriteria;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->badgeCriteria = [
            'min_events' => 3,              // Minimum events hosted
            'min_rating' => 4.0,            // Minimum average rating
            'min_interactions' => 50,       // Minimum total user interactions
            'verification_required' => true, // Manual verification required
            'independent_only' => true,     // Only for independent venues
            'min_age_days' => 30           // Venue must be active for 30 days
        ];
    }
    
    /**
     * Evaluate venue for badge eligibility
     */
    public function evaluateVenue($venueId) {
        try {
            $venue = $this->getVenueData($venueId);
            
            if (!$venue) {
                return [
                    'eligible' => false,
                    'reason' => 'Venue not found',
                    'score' => 0
                ];
            }
            
            $criteria = $this->checkAllCriteria($venue);
            $score = $this->calculateBadgeScore($criteria);
            
            return [
                'eligible' => $criteria['overall_eligible'],
                'score' => $score,
                'criteria' => $criteria,
                'venue' => $venue,
                'badge_level' => $this->determineBadgeLevel($score),
                'benefits' => $this->getBadgeBenefits($score)
            ];
            
        } catch (Exception $e) {
            error_log("Badge evaluation error: " . $e->getMessage());
            return ['eligible' => false, 'reason' => 'Evaluation error', 'score' => 0];
        }
    }
    
    /**
     * Get comprehensive venue data for evaluation
     */
    private function getVenueData($venueId) {
        $stmt = $this->pdo->prepare("
            SELECT 
                v.*,
                COUNT(DISTINCT e.id) as total_events,
                COUNT(DISTINCT CASE WHEN e.start_date >= NOW() THEN e.id END) as upcoming_events,
                COUNT(DISTINCT ui.id) as total_interactions,
                AVG(ui.rating) as average_rating,
                COUNT(DISTINCT ui.user_id) as unique_visitors,
                COUNT(DISTINCT CASE WHEN ui.interaction_type = 'save' THEN ui.user_id END) as saves,
                COUNT(DISTINCT CASE WHEN ui.interaction_type = 'share' THEN ui.user_id END) as shares,
                DATEDIFF(NOW(), v.created_at) as days_active
            FROM venues v
            LEFT JOIN events e ON v.id = e.venue_id OR (v.name = e.venue_name AND v.city = e.city)
            LEFT JOIN user_interactions ui ON e.id = ui.event_id
            WHERE v.id = ? AND v.is_active = 1
            GROUP BY v.id
        ");
        
        $stmt->execute([$venueId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Check all badge criteria for a venue
     */
    private function checkAllCriteria($venue) {
        $results = [];
        
        // Events criteria
        $results['events'] = [
            'required' => $this->badgeCriteria['min_events'],
            'actual' => (int)$venue['total_events'],
            'passed' => $venue['total_events'] >= $this->badgeCriteria['min_events'],
            'weight' => 0.25
        ];
        
        // Rating criteria
        $avgRating = (float)$venue['average_rating'];
        $results['rating'] = [
            'required' => $this->badgeCriteria['min_rating'],
            'actual' => $avgRating,
            'passed' => $avgRating >= $this->badgeCriteria['min_rating'] || $avgRating == 0, // Allow if no ratings yet
            'weight' => 0.20
        ];
        
        // Interactions criteria
        $results['interactions'] = [
            'required' => $this->badgeCriteria['min_interactions'],
            'actual' => (int)$venue['total_interactions'],
            'passed' => $venue['total_interactions'] >= $this->badgeCriteria['min_interactions'],
            'weight' => 0.20
        ];
        
        // Age criteria
        $results['age'] = [
            'required' => $this->badgeCriteria['min_age_days'],
            'actual' => (int)$venue['days_active'],
            'passed' => $venue['days_active'] >= $this->badgeCriteria['min_age_days'],
            'weight' => 0.10
        ];
        
        // Independence criteria (based on organizer data)
        $results['independence'] = [
            'required' => 'Independent venue',
            'actual' => $this->checkVenueIndependence($venue),
            'passed' => $this->checkVenueIndependence($venue),
            'weight' => 0.15
        ];
        
        // Engagement quality
        $engagementScore = $this->calculateEngagementScore($venue);
        $results['engagement'] = [
            'required' => 0.6,
            'actual' => $engagementScore,
            'passed' => $engagementScore >= 0.6,
            'weight' => 0.10
        ];
        
        // Overall eligibility
        $passedCount = array_sum(array_column($results, 'passed'));
        $results['overall_eligible'] = $passedCount >= 5; // Must pass at least 5/6 criteria
        
        return $results;
    }
    
    /**
     * Check if venue is independent (not part of large chain)
     */
    private function checkVenueIndependence($venue) {
        // Check if venue has corporate markers
        $corporateMarkers = [
            'SA', 'SAS', 'SARL', 'Groupe', 'Chain', 'Network'
        ];
        
        $venueName = strtoupper($venue['name']);
        $description = strtoupper($venue['description'] ?? '');
        
        foreach ($corporateMarkers as $marker) {
            if (strpos($venueName, strtoupper($marker)) !== false || 
                strpos($description, strtoupper($marker)) !== false) {
                return false;
            }
        }
        
        // Check if organizer has too many venues (potential chain)
        if ($venue['organizer_id']) {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as venue_count 
                FROM venues 
                WHERE organizer_id = ? AND is_active = 1
            ");
            $stmt->execute([$venue['organizer_id']]);
            $venueCount = $stmt->fetchColumn();
            
            // If organizer has more than 5 venues, likely not independent
            if ($venueCount > 5) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Calculate engagement quality score
     */
    private function calculateEngagementScore($venue) {
        $interactions = (float)$venue['total_interactions'];
        $saves = (float)$venue['saves'];
        $shares = (float)$venue['shares'];
        $uniqueVisitors = (float)$venue['unique_visitors'];
        
        if ($interactions == 0) {
            return 0;
        }
        
        // Calculate various engagement metrics
        $saveRate = $saves / max(1, $uniqueVisitors);
        $shareRate = $shares / max(1, $uniqueVisitors);
        $returnVisitorRate = $interactions / max(1, $uniqueVisitors);
        
        // Weighted engagement score
        $score = ($saveRate * 0.4) + ($shareRate * 0.3) + ($returnVisitorRate * 0.3);
        
        return min(1.0, $score); // Cap at 1.0
    }
    
    /**
     * Calculate overall badge score (0-100)
     */
    private function calculateBadgeScore($criteria) {
        $totalScore = 0;
        $totalWeight = 0;
        
        foreach ($criteria as $key => $criterion) {
            if ($key === 'overall_eligible') continue;
            
            $weight = $criterion['weight'];
            $score = $criterion['passed'] ? 1.0 : 0.0;
            
            // Bonus for exceeding requirements
            if ($criterion['passed'] && is_numeric($criterion['actual']) && is_numeric($criterion['required'])) {
                $excess = $criterion['actual'] / $criterion['required'];
                $bonus = min(0.5, ($excess - 1) * 0.1); // Up to 50% bonus
                $score += $bonus;
            }
            
            $totalScore += $score * $weight;
            $totalWeight += $weight;
        }
        
        return round(($totalScore / $totalWeight) * 100, 1);
    }
    
    /**
     * Determine badge level based on score
     */
    private function determineBadgeLevel($score) {
        if ($score >= 90) return 'platinum';
        if ($score >= 80) return 'gold';
        if ($score >= 70) return 'silver';
        if ($score >= 60) return 'bronze';
        return 'none';
    }
    
    /**
     * Get benefits for badge level
     */
    private function getBadgeBenefits($score) {
        $level = $this->determineBadgeLevel($score);
        
        $benefits = [
            'bronze' => [
                '🏆 Badge CultureRadar Bronze',
                '⭐ Mise en avant dans les résultats de recherche',
                '📈 Statistiques détaillées',
                '💌 Newsletter mensuelle partenaires'
            ],
            'silver' => [
                '🥈 Badge CultureRadar Argent',
                '⭐ Priorité dans les recommandations IA',
                '📈 Analytics avancées',
                '💌 Newsletter hebdomadaire',
                '🎯 Support prioritaire'
            ],
            'gold' => [
                '🥇 Badge CultureRadar Or',
                '⭐ Placement premium dans l\'app',
                '📈 Tableau de bord personnalisé',
                '💌 Communication directe utilisateurs',
                '🎯 Support prioritaire',
                '🎨 Personnalisation de profil'
            ],
            'platinum' => [
                '💎 Badge CultureRadar Platine',
                '⭐ Visibilité maximale',
                '📈 Analytics temps réel',
                '💌 Campagnes marketing dédiées',
                '🎯 Account manager dédié',
                '🎨 Branding personnalisé',
                '🤝 Partenariats exclusifs'
            ]
        ];
        
        return $benefits[$level] ?? [];
    }
    
    /**
     * Award badge to venue
     */
    public function awardBadge($venueId, $adminUserId, $level = null, $notes = '') {
        try {
            $evaluation = $this->evaluateVenue($venueId);
            
            if (!$evaluation['eligible'] && !$level) {
                return [
                    'success' => false,
                    'message' => 'Venue does not meet badge criteria'
                ];
            }
            
            $badgeLevel = $level ?: $evaluation['badge_level'];
            
            // Update venue badge status
            $stmt = $this->pdo->prepare("
                UPDATE venues 
                SET has_culture_radar_badge = 1,
                    badge_level = ?,
                    badge_awarded_at = NOW(),
                    badge_awarded_by = ?,
                    badge_notes = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            
            $stmt->execute([$badgeLevel, $adminUserId, $notes, $venueId]);
            
            // Log badge award
            $this->logBadgeActivity($venueId, 'awarded', $badgeLevel, $adminUserId, $notes);
            
            // Send notification to venue owner
            $this->notifyVenueOwner($venueId, 'badge_awarded', $badgeLevel);
            
            return [
                'success' => true,
                'message' => 'Badge awarded successfully',
                'level' => $badgeLevel,
                'benefits' => $this->getBadgeBenefits($evaluation['score'])
            ];
            
        } catch (Exception $e) {
            error_log("Badge award error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error awarding badge'
            ];
        }
    }
    
    /**
     * Revoke badge from venue
     */
    public function revokeBadge($venueId, $adminUserId, $reason = '') {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE venues 
                SET has_culture_radar_badge = 0,
                    badge_level = NULL,
                    badge_revoked_at = NOW(),
                    badge_revoked_by = ?,
                    badge_revoked_reason = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            
            $stmt->execute([$adminUserId, $reason, $venueId]);
            
            // Log badge revocation
            $this->logBadgeActivity($venueId, 'revoked', null, $adminUserId, $reason);
            
            // Notify venue owner
            $this->notifyVenueOwner($venueId, 'badge_revoked', null, $reason);
            
            return [
                'success' => true,
                'message' => 'Badge revoked successfully'
            ];
            
        } catch (Exception $e) {
            error_log("Badge revoke error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error revoking badge'
            ];
        }
    }
    
    /**
     * Get all badged venues
     */
    public function getBadgedVenues($level = null, $city = null, $limit = 50) {
        $whereConditions = ['v.has_culture_radar_badge = 1', 'v.is_active = 1'];
        $params = [];
        
        if ($level) {
            $whereConditions[] = 'v.badge_level = ?';
            $params[] = $level;
        }
        
        if ($city) {
            $whereConditions[] = 'v.city LIKE ?';
            $params[] = '%' . $city . '%';
        }
        
        $stmt = $this->pdo->prepare("
            SELECT 
                v.*,
                COUNT(DISTINCT e.id) as total_events,
                COUNT(DISTINCT ui.id) as total_interactions,
                AVG(ui.rating) as average_rating
            FROM venues v
            LEFT JOIN events e ON v.id = e.venue_id OR (v.name = e.venue_name AND v.city = e.city)
            LEFT JOIN user_interactions ui ON e.id = ui.event_id
            WHERE " . implode(' AND ', $whereConditions) . "
            GROUP BY v.id
            ORDER BY 
                CASE v.badge_level 
                    WHEN 'platinum' THEN 4
                    WHEN 'gold' THEN 3
                    WHEN 'silver' THEN 2
                    WHEN 'bronze' THEN 1
                    ELSE 0
                END DESC,
                v.badge_awarded_at DESC
            LIMIT ?
        ");
        
        $params[] = $limit;
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get venues eligible for badge review
     */
    public function getEligibleVenues($limit = 20) {
        $stmt = $this->pdo->prepare("
            SELECT 
                v.*,
                COUNT(DISTINCT e.id) as total_events,
                COUNT(DISTINCT ui.id) as total_interactions,
                AVG(ui.rating) as average_rating,
                DATEDIFF(NOW(), v.created_at) as days_active
            FROM venues v
            LEFT JOIN events e ON v.id = e.venue_id OR (v.name = e.venue_name AND v.city = e.city)
            LEFT JOIN user_interactions ui ON e.id = ui.event_id
            WHERE v.has_culture_radar_badge = 0 
                AND v.is_active = 1
                AND DATEDIFF(NOW(), v.created_at) >= ?
            GROUP BY v.id
            HAVING total_events >= ? AND total_interactions >= ?
            ORDER BY total_interactions DESC, average_rating DESC
            LIMIT ?
        ");
        
        $stmt->execute([
            $this->badgeCriteria['min_age_days'],
            $this->badgeCriteria['min_events'],
            $this->badgeCriteria['min_interactions'],
            $limit
        ]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Log badge-related activity
     */
    private function logBadgeActivity($venueId, $action, $level, $adminUserId, $notes) {
        $stmt = $this->pdo->prepare("
            INSERT INTO badge_logs (venue_id, action, badge_level, admin_user_id, notes, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([$venueId, $action, $level, $adminUserId, $notes]);
    }
    
    /**
     * Notify venue owner about badge changes
     */
    private function notifyVenueOwner($venueId, $type, $level = null, $reason = null) {
        // Get venue and organizer information
        $stmt = $this->pdo->prepare("
            SELECT v.name as venue_name, o.email, o.name as organizer_name
            FROM venues v
            LEFT JOIN organizers o ON v.organizer_id = o.id
            WHERE v.id = ?
        ");
        
        $stmt->execute([$venueId]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$data || !$data['email']) {
            return false;
        }
        
        $subject = '';
        $message = '';
        
        switch ($type) {
            case 'badge_awarded':
                $subject = "🏆 Félicitations ! Votre lieu a reçu le badge CultureRadar";
                $message = "Bonjour {$data['organizer_name']},\n\n";
                $message .= "Nous avons le plaisir de vous informer que votre lieu \"{$data['venue_name']}\" ";
                $message .= "a reçu le badge CultureRadar niveau {$level} !\n\n";
                $message .= "Ce badge reconnaît votre engagement envers la culture indépendante ";
                $message .= "et la qualité de vos événements.\n\n";
                $message .= "Avantages de votre badge :\n";
                $benefits = $this->getBadgeBenefits(85); // Approximate score for benefits
                foreach ($benefits as $benefit) {
                    $message .= "- {$benefit}\n";
                }
                break;
                
            case 'badge_revoked':
                $subject = "Badge CultureRadar - Mise à jour de statut";
                $message = "Bonjour {$data['organizer_name']},\n\n";
                $message .= "Nous vous informons que le badge CultureRadar de votre lieu ";
                $message .= "\"{$data['venue_name']}\" a été revu.\n\n";
                if ($reason) {
                    $message .= "Raison : {$reason}\n\n";
                }
                $message .= "Pour toute question, n'hésitez pas à nous contacter.\n\n";
                break;
        }
        
        // Here you would implement actual email sending
        // For now, we'll just log the notification
        error_log("Badge notification sent to {$data['email']}: {$subject}");
        
        return true;
    }
    
    /**
     * Get badge statistics
     */
    public function getBadgeStatistics() {
        $stmt = $this->pdo->prepare("
            SELECT 
                COUNT(*) as total_badged_venues,
                COUNT(CASE WHEN badge_level = 'bronze' THEN 1 END) as bronze_count,
                COUNT(CASE WHEN badge_level = 'silver' THEN 1 END) as silver_count,
                COUNT(CASE WHEN badge_level = 'gold' THEN 1 END) as gold_count,
                COUNT(CASE WHEN badge_level = 'platinum' THEN 1 END) as platinum_count,
                AVG(DATEDIFF(badge_awarded_at, created_at)) as avg_days_to_badge
            FROM venues
            WHERE has_culture_radar_badge = 1 AND is_active = 1
        ");
        
        $stmt->execute();
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get eligible venues count
        $eligibleVenues = $this->getEligibleVenues(1000);
        $stats['eligible_venues'] = count($eligibleVenues);
        
        return $stats;
    }
}

// Create badge_logs table if it doesn't exist
try {
    require_once __DIR__ . '/../config.php';
    $dbConfig = Config::database();
    $dsn = "mysql:host=" . $dbConfig['host'] . ";dbname=" . $dbConfig['name'] . ";charset=" . $dbConfig['charset'];
    $pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS badge_logs (
            id INT PRIMARY KEY AUTO_INCREMENT,
            venue_id INT NOT NULL,
            action ENUM('awarded', 'revoked', 'reviewed') NOT NULL,
            badge_level VARCHAR(20),
            admin_user_id INT,
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (venue_id) REFERENCES venues(id) ON DELETE CASCADE,
            INDEX idx_venue_id (venue_id),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // Add badge columns to venues table if they don't exist
    $pdo->exec("
        ALTER TABLE venues 
        ADD COLUMN IF NOT EXISTS badge_level VARCHAR(20) NULL,
        ADD COLUMN IF NOT EXISTS badge_awarded_at TIMESTAMP NULL,
        ADD COLUMN IF NOT EXISTS badge_awarded_by INT NULL,
        ADD COLUMN IF NOT EXISTS badge_notes TEXT NULL,
        ADD COLUMN IF NOT EXISTS badge_revoked_at TIMESTAMP NULL,
        ADD COLUMN IF NOT EXISTS badge_revoked_by INT NULL,
        ADD COLUMN IF NOT EXISTS badge_revoked_reason TEXT NULL
    ");
    
} catch (PDOException $e) {
    error_log("Badge system table creation error: " . $e->getMessage());
}
?>