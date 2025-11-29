<?php
session_start();

// Load configuration
require_once __DIR__ . '/../config.php';

// Check if user is admin
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit();
}

try {
    $dbConfig = Config::database();
    $dsn = "mysql:host=" . $dbConfig['host'] . ";dbname=" . $dbConfig['name'] . ";charset=" . $dbConfig['charset'];
    $pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->prepare("SELECT is_admin FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user || !$user['is_admin']) {
        header('Location: /dashboard.php');
        exit();
    }
} catch (PDOException $e) {
    header('Location: /login.php');
    exit();
}

require_once __DIR__ . '/../classes/BadgeSystem.php';

$error = '';
$success = '';
$badgeSystem = new BadgeSystem($pdo);

// Handle badge actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'award_badge') {
        $venueId = (int)($_POST['venue_id'] ?? 0);
        $level = $_POST['level'] ?? '';
        $notes = $_POST['notes'] ?? '';
        
        if ($venueId && $level) {
            $result = $badgeSystem->awardBadge($venueId, $_SESSION['user_id'], $level, $notes);
            if ($result['success']) {
                $success = 'Badge attribué avec succès !';
            } else {
                $error = $result['message'];
            }
        }
    } elseif ($action === 'revoke_badge') {
        $venueId = (int)($_POST['venue_id'] ?? 0);
        $reason = $_POST['reason'] ?? '';
        
        if ($venueId) {
            $result = $badgeSystem->revokeBadge($venueId, $_SESSION['user_id'], $reason);
            if ($result['success']) {
                $success = 'Badge révoqué avec succès !';
            } else {
                $error = $result['message'];
            }
        }
    }
}

// Get badge statistics
$badgeStats = $badgeSystem->getBadgeStatistics();

// Get badged venues
$badgedVenues = $badgeSystem->getBadgedVenues();

// Get eligible venues
$eligibleVenues = $badgeSystem->getEligibleVenues();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des badges - Culture Radar Admin</title>
    
    <?php include '../includes/favicon.php'; ?>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        .badges-container {
            padding-top: 100px;
            min-height: 100vh;
        }
        
        .badges-header {
            background: var(--glass);
            backdrop-filter: blur(30px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            text-align: center;
        }
        
        .badges-title {
            font-size: 2.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, #ffd700 0%, #ffb300 50%, #ff8f00 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.5rem;
        }
        
        .badge-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }
        
        .stat-card {
            background: var(--glass);
            backdrop-filter: blur(30px);
            border: 1px solid var(--glass-border);
            border-radius: 15px;
            padding: 1.5rem;
            text-align: center;
            transition: var(--transition);
        }
        
        .badge-level-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin: 0 auto 1rem;
        }
        
        .badge-level-icon.bronze { background: linear-gradient(135deg, #cd7f32 0%, #a0522d 100%); }
        .badge-level-icon.silver { background: linear-gradient(135deg, #c0c0c0 0%, #808080 100%); }
        .badge-level-icon.gold { background: linear-gradient(135deg, #ffd700 0%, #b8860b 100%); }
        .badge-level-icon.platinum { background: linear-gradient(135deg, #e5e4e2 0%, #9c9a9a 100%); }
        .badge-level-icon.total { background: var(--primary); }
        .badge-level-icon.eligible { background: var(--accent); }
        
        .tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .tab {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            padding: 1rem 2rem;
            color: rgba(255, 255, 255, 0.8);
            cursor: pointer;
            transition: var(--transition);
            font-weight: 600;
        }
        
        .tab.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        
        .tab:hover {
            background: rgba(255, 255, 255, 0.2);
            color: white;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .venues-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 1.5rem;
        }
        
        .venue-card {
            background: var(--glass);
            backdrop-filter: blur(30px);
            border: 1px solid var(--glass-border);
            border-radius: 15px;
            padding: 1.5rem;
            transition: var(--transition);
        }
        
        .venue-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-xl);
        }
        
        .venue-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }
        
        .venue-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            color: white;
        }
        
        .venue-badge.bronze { background: linear-gradient(135deg, #cd7f32 0%, #a0522d 100%); }
        .venue-badge.silver { background: linear-gradient(135deg, #c0c0c0 0%, #808080 100%); }
        .venue-badge.gold { background: linear-gradient(135deg, #ffd700 0%, #b8860b 100%); }
        .venue-badge.platinum { background: linear-gradient(135deg, #e5e4e2 0%, #9c9a9a 100%); }
        .venue-badge.eligible { background: var(--accent); }
        
        .venue-name {
            font-size: 1.2rem;
            font-weight: 700;
            color: white;
            margin-bottom: 0.5rem;
        }
        
        .venue-info {
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }
        
        .venue-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.5rem;
            margin-bottom: 1rem;
        }
        
        .venue-stat {
            background: rgba(255, 255, 255, 0.05);
            padding: 0.5rem;
            border-radius: 8px;
            text-align: center;
            font-size: 0.8rem;
        }
        
        .venue-stat-number {
            font-weight: 700;
            color: white;
            font-size: 1rem;
        }
        
        .venue-stat-label {
            color: rgba(255, 255, 255, 0.7);
        }
        
        .venue-actions {
            display: flex;
            gap: 0.5rem;
            justify-content: center;
        }
        
        .evaluation-score {
            background: var(--success);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 12px;
            font-weight: 600;
            text-align: center;
            margin-bottom: 1rem;
        }
        
        .criteria-list {
            margin-bottom: 1rem;
        }
        
        .criteria-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem;
            margin-bottom: 0.25rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            font-size: 0.8rem;
        }
        
        .criteria-status {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
        }
        
        .criteria-status.passed {
            background: var(--success);
            color: white;
        }
        
        .criteria-status.failed {
            background: var(--warning);
            color: white;
        }
        
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            padding: 2rem;
        }
        
        .modal-content {
            background: var(--glass);
            backdrop-filter: blur(30px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 2rem;
            max-width: 500px;
            width: 100%;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .modal-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: white;
        }
        
        .close-btn {
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            opacity: 0.7;
        }
        
        .close-btn:hover {
            opacity: 1;
        }
        
        @media (max-width: 768px) {
            .badges-container {
                padding-top: 80px;
            }
            
            .tabs {
                flex-direction: column;
            }
            
            .venues-grid {
                grid-template-columns: 1fr;
            }
            
            .venue-stats {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="animated-bg" aria-hidden="true">
        <div class="stars"></div>
        <div class="floating-shapes"></div>
    </div>
    
    <!-- Header -->
    <header class="header" role="banner">
        <nav class="nav" role="navigation">
            <a href="/admin/" class="logo">
                <div class="location-pin-icon"></div>
                Culture Radar <span style="color: #ff6b6b; font-size: 0.8rem;">Admin</span>
            </a>
            
            <ul class="nav-links">
                <li><a href="/admin/dashboard.php">Tableau de bord</a></li>
                <li><a href="/admin/badges.php" class="active">Badges</a></li>
                <li><a href="/admin/users.php">Utilisateurs</a></li>
                <li><a href="/admin/events.php">Événements</a></li>
            </ul>
            
            <div class="nav-actions">
                <div class="user-menu">
                    <button class="user-avatar">
                        <i class="fas fa-user-shield"></i>
                    </button>
                    <div class="user-dropdown">
                        <a href="/logout.php">Déconnexion</a>
                    </div>
                </div>
            </div>
        </nav>
    </header>
    
    <!-- Main Content -->
    <main class="badges-container">
        <div class="container">
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            
            <!-- Header -->
            <div class="badges-header">
                <h1 class="badges-title">🏆 Gestion des badges CultureRadar</h1>
                <p style="color: rgba(255, 255, 255, 0.8);">
                    Système de reconnaissance pour les lieux culturels indépendants d'excellence
                </p>
            </div>
            
            <!-- Statistics -->
            <div class="badge-stats">
                <div class="stat-card">
                    <div class="badge-level-icon total">
                        <i class="fas fa-award"></i>
                    </div>
                    <div class="stat-number"><?php echo $badgeStats['total_badged_venues'] ?? 0; ?></div>
                    <div class="stat-label">Total badges</div>
                </div>
                
                <div class="stat-card">
                    <div class="badge-level-icon bronze">
                        🥉
                    </div>
                    <div class="stat-number"><?php echo $badgeStats['bronze_count'] ?? 0; ?></div>
                    <div class="stat-label">Bronze</div>
                </div>
                
                <div class="stat-card">
                    <div class="badge-level-icon silver">
                        🥈
                    </div>
                    <div class="stat-number"><?php echo $badgeStats['silver_count'] ?? 0; ?></div>
                    <div class="stat-label">Argent</div>
                </div>
                
                <div class="stat-card">
                    <div class="badge-level-icon gold">
                        🥇
                    </div>
                    <div class="stat-number"><?php echo $badgeStats['gold_count'] ?? 0; ?></div>
                    <div class="stat-label">Or</div>
                </div>
                
                <div class="stat-card">
                    <div class="badge-level-icon platinum">
                        💎
                    </div>
                    <div class="stat-number"><?php echo $badgeStats['platinum_count'] ?? 0; ?></div>
                    <div class="stat-label">Platine</div>
                </div>
                
                <div class="stat-card">
                    <div class="badge-level-icon eligible">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="stat-number"><?php echo $badgeStats['eligible_venues'] ?? 0; ?></div>
                    <div class="stat-label">Éligibles</div>
                </div>
            </div>
            
            <!-- Tabs -->
            <div class="tabs">
                <div class="tab active" onclick="switchTab('badged')">
                    <i class="fas fa-award"></i> Lieux badgés
                </div>
                <div class="tab" onclick="switchTab('eligible')">
                    <i class="fas fa-star"></i> Candidats éligibles
                </div>
            </div>
            
            <!-- Badged Venues Tab -->
            <div id="badged-tab" class="tab-content active">
                <div class="venues-grid">
                    <?php foreach ($badgedVenues as $venue): ?>
                        <div class="venue-card">
                            <div class="venue-header">
                                <div>
                                    <h3 class="venue-name"><?php echo htmlspecialchars($venue['name']); ?></h3>
                                    <div class="venue-info">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <?php echo htmlspecialchars($venue['city']); ?>
                                    </div>
                                </div>
                                <div class="venue-badge <?php echo $venue['badge_level']; ?>">
                                    <?php 
                                    $badgeLabels = [
                                        'bronze' => '🥉 Bronze',
                                        'silver' => '🥈 Argent', 
                                        'gold' => '🥇 Or',
                                        'platinum' => '💎 Platine'
                                    ];
                                    echo $badgeLabels[$venue['badge_level']] ?? 'Badge';
                                    ?>
                                </div>
                            </div>
                            
                            <div class="venue-stats">
                                <div class="venue-stat">
                                    <div class="venue-stat-number"><?php echo $venue['total_events'] ?? 0; ?></div>
                                    <div class="venue-stat-label">Événements</div>
                                </div>
                                <div class="venue-stat">
                                    <div class="venue-stat-number"><?php echo $venue['total_interactions'] ?? 0; ?></div>
                                    <div class="venue-stat-label">Interactions</div>
                                </div>
                                <div class="venue-stat">
                                    <div class="venue-stat-number">
                                        <?php echo $venue['average_rating'] ? round($venue['average_rating'], 1) : 'N/A'; ?>
                                    </div>
                                    <div class="venue-stat-label">Note moyenne</div>
                                </div>
                            </div>
                            
                            <div class="venue-info" style="font-size: 0.8rem; margin-bottom: 1rem;">
                                Badge attribué le <?php echo date('d/m/Y', strtotime($venue['badge_awarded_at'])); ?>
                            </div>
                            
                            <div class="venue-actions">
                                <button class="btn-small btn-outline" onclick="revokeBadge(<?php echo $venue['id']; ?>, '<?php echo addslashes($venue['name']); ?>')">
                                    <i class="fas fa-times"></i> Révoquer
                                </button>
                                <button class="btn-small btn-primary" onclick="viewVenueDetails(<?php echo $venue['id']; ?>)">
                                    <i class="fas fa-eye"></i> Détails
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Eligible Venues Tab -->
            <div id="eligible-tab" class="tab-content">
                <div class="venues-grid">
                    <?php foreach ($eligibleVenues as $venue): ?>
                        <?php 
                        $evaluation = $badgeSystem->evaluateVenue($venue['id']);
                        ?>
                        <div class="venue-card">
                            <div class="venue-header">
                                <div>
                                    <h3 class="venue-name"><?php echo htmlspecialchars($venue['name']); ?></h3>
                                    <div class="venue-info">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <?php echo htmlspecialchars($venue['city']); ?>
                                    </div>
                                </div>
                                <div class="venue-badge eligible">
                                    ⭐ Éligible
                                </div>
                            </div>
                            
                            <div class="evaluation-score">
                                Score: <?php echo $evaluation['score']; ?>/100
                                (<?php echo ucfirst($evaluation['badge_level']); ?>)
                            </div>
                            
                            <div class="venue-stats">
                                <div class="venue-stat">
                                    <div class="venue-stat-number"><?php echo $venue['total_events']; ?></div>
                                    <div class="venue-stat-label">Événements</div>
                                </div>
                                <div class="venue-stat">
                                    <div class="venue-stat-number"><?php echo $venue['total_interactions']; ?></div>
                                    <div class="venue-stat-label">Interactions</div>
                                </div>
                                <div class="venue-stat">
                                    <div class="venue-stat-number">
                                        <?php echo $venue['average_rating'] ? round($venue['average_rating'], 1) : 'N/A'; ?>
                                    </div>
                                    <div class="venue-stat-label">Note moyenne</div>
                                </div>
                            </div>
                            
                            <div class="criteria-list">
                                <?php foreach ($evaluation['criteria'] as $key => $criterion): ?>
                                    <?php if ($key === 'overall_eligible') continue; ?>
                                    <div class="criteria-item">
                                        <span style="color: rgba(255, 255, 255, 0.8);">
                                            <?php 
                                            $criteriaLabels = [
                                                'events' => 'Événements',
                                                'rating' => 'Note moyenne',
                                                'interactions' => 'Interactions',
                                                'age' => 'Ancienneté',
                                                'independence' => 'Indépendance',
                                                'engagement' => 'Engagement'
                                            ];
                                            echo $criteriaLabels[$key] ?? $key;
                                            ?>
                                        </span>
                                        <div class="criteria-status <?php echo $criterion['passed'] ? 'passed' : 'failed'; ?>">
                                            <i class="fas fa-<?php echo $criterion['passed'] ? 'check' : 'times'; ?>"></i>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="venue-actions">
                                <button class="btn-small btn-primary" onclick="awardBadge(<?php echo $venue['id']; ?>, '<?php echo addslashes($venue['name']); ?>', '<?php echo $evaluation['badge_level']; ?>')">
                                    <i class="fas fa-award"></i> Attribuer badge
                                </button>
                                <button class="btn-small btn-outline" onclick="viewEvaluation(<?php echo $venue['id']; ?>)">
                                    <i class="fas fa-chart-line"></i> Évaluation
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </main>
    
    <!-- Award Badge Modal -->
    <div id="awardBadgeModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Attribuer un badge</h3>
                <button class="close-btn" onclick="closeModal('awardBadgeModal')">&times;</button>
            </div>
            
            <form method="POST" id="awardBadgeForm">
                <input type="hidden" name="action" value="award_badge">
                <input type="hidden" name="venue_id" id="awardVenueId">
                
                <div class="form-group">
                    <label class="form-label">Lieu</label>
                    <div id="awardVenueName" style="color: white; font-weight: 600; padding: 0.5rem; background: rgba(255, 255, 255, 0.1); border-radius: 8px;"></div>
                </div>
                
                <div class="form-group">
                    <label for="level" class="form-label">Niveau de badge</label>
                    <select name="level" id="level" class="form-select" required>
                        <option value="bronze">🥉 Bronze</option>
                        <option value="silver">🥈 Argent</option>
                        <option value="gold">🥇 Or</option>
                        <option value="platinum">💎 Platine</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="notes" class="form-label">Notes (optionnel)</label>
                    <textarea name="notes" id="notes" class="form-textarea" rows="3" placeholder="Commentaires sur l'attribution du badge..."></textarea>
                </div>
                
                <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                    <button type="button" class="btn-outline" onclick="closeModal('awardBadgeModal')">Annuler</button>
                    <button type="submit" class="btn-primary">Attribuer le badge</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Revoke Badge Modal -->
    <div id="revokeBadgeModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Révoquer un badge</h3>
                <button class="close-btn" onclick="closeModal('revokeBadgeModal')">&times;</button>
            </div>
            
            <form method="POST" id="revokeBadgeForm">
                <input type="hidden" name="action" value="revoke_badge">
                <input type="hidden" name="venue_id" id="revokeVenueId">
                
                <div class="form-group">
                    <label class="form-label">Lieu</label>
                    <div id="revokeVenueName" style="color: white; font-weight: 600; padding: 0.5rem; background: rgba(255, 255, 255, 0.1); border-radius: 8px;"></div>
                </div>
                
                <div class="form-group">
                    <label for="reason" class="form-label">Raison de la révocation</label>
                    <textarea name="reason" id="reason" class="form-textarea" rows="3" placeholder="Expliquez pourquoi le badge est révoqué..." required></textarea>
                </div>
                
                <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                    <button type="button" class="btn-outline" onclick="closeModal('revokeBadgeModal')">Annuler</button>
                    <button type="submit" class="btn-primary" style="background: var(--warning);">Révoquer le badge</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function switchTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById(tabName + '-tab').classList.add('active');
            event.target.classList.add('active');
        }
        
        function awardBadge(venueId, venueName, suggestedLevel) {
            document.getElementById('awardVenueId').value = venueId;
            document.getElementById('awardVenueName').textContent = venueName;
            document.getElementById('level').value = suggestedLevel;
            document.getElementById('awardBadgeModal').style.display = 'flex';
        }
        
        function revokeBadge(venueId, venueName) {
            document.getElementById('revokeVenueId').value = venueId;
            document.getElementById('revokeVenueName').textContent = venueName;
            document.getElementById('revokeBadgeModal').style.display = 'flex';
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        function viewVenueDetails(venueId) {
            // This would open a detailed view of the venue
            alert('Fonctionnalité à venir : Vue détaillée du lieu ' + venueId);
        }
        
        function viewEvaluation(venueId) {
            // This would show detailed evaluation criteria
            alert('Fonctionnalité à venir : Évaluation détaillée du lieu ' + venueId);
        }
        
        // Close modals when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
    </script>
    <script src="../assets/js/main.js"></script>
</body>
</html>