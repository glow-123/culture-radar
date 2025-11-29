<?php
// Sécurité HTTP headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: no-referrer');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');

session_start();

// Load configuration
require_once __DIR__ . '/config.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit();
}

$error = '';
$success = '';
$user = [];
$userProfile = [];
$recommendedEvents = [];
$userStats = [];

// Check for welcome message
$showWelcome = isset($_GET['welcome']) && $_GET['welcome'] === '1';

try {
    $dbConfig = Config::database();
    $dsn = "mysql:host=" . $dbConfig['host'] . ";dbname=" . $dbConfig['name'] . ";charset=" . $dbConfig['charset'];
    $pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get user information
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get user profile
    $stmt = $pdo->prepare("SELECT * FROM user_profiles WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $userProfile = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get AI-powered recommended events
    require_once __DIR__ . '/classes/RecommendationEngine.php';
    $aiEngine = new RecommendationEngine($pdo, $_SESSION['user_id']);
    
    // Generate fresh recommendations
    $aiRecommendations = $aiEngine->generateRecommendations(6, true);
    
    // Format for display
    $recommendedEvents = array_map(function($event) {
        $event['match_score'] = $event['ai_score'];
        $event['ai_reasons'] = $event['match_reasons'];
        return $event;
    }, $aiRecommendations);
    
    // Get user statistics
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(DISTINCT ui.event_id) as events_viewed,
            COUNT(DISTINCT CASE WHEN ui.interaction_type = 'save' THEN ui.event_id END) as events_saved,
            COUNT(DISTINCT CASE WHEN ui.interaction_type = 'click' THEN ui.event_id END) as events_clicked
        FROM user_interactions ui 
        WHERE ui.user_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $userStats = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['events_viewed' => 0, 'events_saved' => 0, 'events_clicked' => 0];
    
} catch (PDOException $e) {
    $error = 'Erreur lors du chargement des données.';
    error_log("Dashboard error: " . $e->getMessage());
}

// Parse user preferences
$preferences = [];
if ($userProfile && $userProfile['preferences']) {
    $preferences = json_decode($userProfile['preferences'], true) ?: [];
}

// Parse notification settings
$notificationSettings = [];
if ($userProfile && $userProfile['notification_settings']) {
    $notificationSettings = json_decode($userProfile['notification_settings'], true) ?: [];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- SEO & Meta -->
    <title>Tableau de bord - Culture Radar | Vos recommandations culturelles</title>
    <meta name="description" content="Découvrez vos recommandations culturelles personnalisées, gérez vos préférences et explorez les événements qui vous correspondent.">
    <meta name="robots" content="noindex, nofollow">
    
    <?php include 'includes/favicon.php'; ?>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800;900&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Styles -->
    <link rel="stylesheet" href="assets/css/style.css">
    
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* Dashboard specific styles */
        .dashboard-container {
            padding-top: 100px;
            min-height: 100vh;
        }
        
        .dashboard-header {
            background: var(--glass);
            backdrop-filter: blur(30px);
            border: 1px solid var(--glass-border);
            border-radius: var(--border-radius);
            padding: 2rem;
            margin-bottom: 2rem;
            text-align: center;
        }
        
        .welcome-message {
            background: var(--success);
            color: white;
            padding: 1rem 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            text-align: center;
            font-weight: 600;
            animation: fade-in-up 0.6s ease-out;
        }
        
        .dashboard-greeting {
            font-size: 2rem;
            font-weight: 800;
            color: white;
            margin-bottom: 0.5rem;
        }
        
        .dashboard-subtitle {
            color: rgba(255, 255, 255, 0.8);
            font-size: 1.1rem;
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr 300px;
            gap: 2rem;
            margin-bottom: 3rem;
        }
        
        .main-content {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }
        
        .sidebar {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }
        
        .section-card {
            background: var(--glass);
            backdrop-filter: blur(30px);
            border: 1px solid var(--glass-border);
            border-radius: var(--border-radius);
            padding: 2rem;
            position: relative;
            overflow: hidden;
        }
        
        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: white;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .section-icon {
            width: 40px;
            height: 40px;
            background: var(--accent);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: white;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-item {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
            transition: var(--transition);
        }
        
        .stat-item:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateY(-2px);
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 800;
            color: white;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.9rem;
        }
        
        .events-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 1.5rem;
        }
        
        .event-card {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--glass-border);
            border-radius: 15px;
            padding: 1.5rem;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }
        
        .event-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.05), transparent);
            transition: all 0.8s;
        }
        
        .event-card:hover::before {
            left: 100%;
        }
        
        .event-card:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateY(-5px);
            box-shadow: var(--shadow-xl);
        }
        
        .event-category {
            background: var(--accent);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 1rem;
        }
        
        .event-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: white;
            margin-bottom: 0.75rem;
            line-height: 1.3;
        }
        
        .event-description {
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.9rem;
            line-height: 1.5;
            margin-bottom: 1rem;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .event-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1rem;
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.7);
        }
        
        .event-meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .event-actions {
            display: flex;
            gap: 0.75rem;
            justify-content: space-between;
            align-items: center;
        }
        
        .match-score {
            background: var(--success);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.8rem;
        }
        
        .btn-small {
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
            border-radius: 10px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: var(--transition);
            border: none;
        }
        
        .btn-outline {
            background: transparent;
            color: rgba(255, 255, 255, 0.8);
            border: 1px solid var(--glass-border);
        }
        
        .btn-outline:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            transform: translateY(-2px);
        }
        
        .profile-summary {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .profile-avatar {
            width: 80px;
            height: 80px;
            background: var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            font-weight: 800;
            color: white;
            margin: 0 auto 1rem;
        }
        
        .profile-name {
            font-size: 1.3rem;
            font-weight: 700;
            color: white;
            margin-bottom: 0.5rem;
        }
        
        .profile-info {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.9rem;
        }
        
        .preferences-list {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        
        .preference-tag {
            background: rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.9);
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .quick-actions {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }
        
        .action-btn {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid var(--glass-border);
            color: white;
            padding: 0.75rem 1rem;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .action-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateX(5px);
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: rgba(255, 255, 255, 0.7);
        }
        
        .empty-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        @media (max-width: 1024px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            
            .sidebar {
                order: -1;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .dashboard-container {
                padding-top: 80px;
            }
            
            .events-grid {
                grid-template-columns: 1fr;
            }
            
            .event-actions {
                flex-direction: column;
                gap: 0.5rem;
                align-items: stretch;
            }
        }
    </style>
</head>
<body>
    <!-- Skip to content for accessibility -->
    <a href="#main-content" class="skip-to-content">Aller au contenu principal</a>
    
    <!-- Animated Background -->
    <div class="animated-bg" aria-hidden="true">
        <div class="stars"></div>
        <div class="floating-shapes"></div>
    </div>
    
    <!-- Header -->
    <header class="header" role="banner">
        <nav class="nav" role="navigation" aria-label="Navigation principale">
            <a href="/" class="logo" aria-label="Culture Radar - Retour à l'accueil">
                <div class="location-pin-icon" aria-hidden="true"></div>
                Culture Radar
            </a>
            
            <ul class="nav-links" role="menubar">
                <li role="none"><a href="/discover.php" role="menuitem">Découvrir</a></li>
                <li role="none"><a href="/events.php" role="menuitem">Événements</a></li>
                <li role="none"><a href="/dashboard.php" role="menuitem" class="active">Mon Espace</a></li>
            </ul>
            
            <div class="nav-actions">
                <div class="user-menu">
                    <button class="user-avatar" aria-label="Menu utilisateur">
                        <?php echo strtoupper(substr($user['name'] ?? 'U', 0, 1)); ?>
                    </button>
                    <div class="user-dropdown">
                        <a href="/dashboard.php">Mon tableau de bord</a>
                        <a href="/settings.php">Paramètres</a>
                        <a href="/logout.php">Déconnexion</a>
                    </div>
                </div>
            </div>
        </nav>
    </header>
    
    <!-- Main Content -->
    <main id="main-content" class="dashboard-container">
        <div class="container">
            <?php if ($showWelcome): ?>
                <div class="welcome-message">
                    🎉 Bienvenue sur Culture Radar ! Votre profil est maintenant configuré et nous avons préparé vos premières recommandations.
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <div class="dashboard-header">
                <h1 class="dashboard-greeting">
                    Bonjour <?php echo htmlspecialchars(explode(' ', $user['name'])[0]); ?> ! 👋
                </h1>
                <p class="dashboard-subtitle">
                    Découvrez vos recommandations culturelles personnalisées
                </p>
            </div>
            
            <div class="dashboard-grid">
                <div class="main-content">
                    <!-- Statistics -->
                    <div class="section-card">
                        <h2 class="section-title">
                            <span class="section-icon"><i class="fas fa-chart-bar"></i></span>
                            Votre activité
                        </h2>
                        
                        <div class="stats-grid">
                            <div class="stat-item">
                                <div class="stat-number"><?php echo $userStats['events_viewed']; ?></div>
                                <div class="stat-label">Événements vus</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-number"><?php echo $userStats['events_saved']; ?></div>
                                <div class="stat-label">Favoris</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-number"><?php echo count($preferences); ?></div>
                                <div class="stat-label">Préférences</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Recommended Events -->
                    <div class="section-card">
                        <h2 class="section-title">
                            <span class="section-icon"><i class="fas fa-sparkles"></i></span>
                            Recommandations pour vous
                        </h2>
                        
                        <?php if (!empty($recommendedEvents)): ?>
                            <div class="events-grid">
                                <?php foreach ($recommendedEvents as $event): ?>
                                    <article class="event-card">
                                        <div class="event-category">
                                            <?php 
                                            $categoryNames = [
                                                'art' => 'Art & Expo',
                                                'music' => 'Musique',
                                                'theater' => 'Théâtre',
                                                'cinema' => 'Cinéma',
                                                'literature' => 'Littérature',
                                                'heritage' => 'Patrimoine',
                                                'dance' => 'Danse',
                                                'festival' => 'Festival'
                                            ];
                                            echo $categoryNames[$event['category']] ?? ucfirst($event['category']);
                                            ?>
                                        </div>
                                        
                                        <h3 class="event-title"><?php echo htmlspecialchars($event['title']); ?></h3>
                                        
                                        <?php if ($event['description']): ?>
                                            <p class="event-description"><?php echo htmlspecialchars($event['description']); ?></p>
                                        <?php endif; ?>
                                        
                                        <div class="event-meta">
                                            <?php if ($event['venue_name']): ?>
                                                <div class="event-meta-item">
                                                    <i class="fas fa-map-marker-alt"></i>
                                                    <?php echo htmlspecialchars($event['venue_name']); ?>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <div class="event-meta-item">
                                                <i class="fas fa-calendar"></i>
                                                <?php echo date('d/m/Y', strtotime($event['start_date'])); ?>
                                            </div>
                                            
                                            <div class="event-meta-item">
                                                <i class="fas fa-euro-sign"></i>
                                                <?php echo $event['is_free'] ? 'Gratuit' : number_format($event['price'], 2) . ' €'; ?>
                                            </div>
                                        </div>
                                        
                                        <?php if (!empty($event['ai_reasons'])): ?>
                                            <div class="ai-reasons" style="margin-bottom: 1rem;">
                                                <div style="font-size: 0.8rem; color: rgba(255, 255, 255, 0.6); margin-bottom: 0.5rem;">
                                                    <i class="fas fa-robot"></i> Pourquoi cette recommandation :
                                                </div>
                                                <div style="display: flex; flex-wrap: wrap; gap: 0.25rem;">
                                                    <?php foreach (array_slice($event['ai_reasons'], 0, 3) as $reason): ?>
                                                        <span style="background: rgba(79, 172, 254, 0.2); color: #4facfe; padding: 0.2rem 0.5rem; border-radius: 8px; font-size: 0.7rem;">
                                                            <?php echo htmlspecialchars($reason); ?>
                                                        </span>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="event-actions">
                                            <div class="match-score" title="Score de compatibilité IA">
                                                <i class="fas fa-brain" style="margin-right: 0.25rem; opacity: 0.8;"></i>
                                                <?php echo round($event['match_score']); ?>% match
                                            </div>
                                            
                                            <div>
                                                <button class="btn-small btn-outline" onclick="saveEvent(<?php echo $event['id']; ?>)">
                                                    <i class="fas fa-heart"></i>
                                                </button>
                                                <a href="/event.php?id=<?php echo $event['id']; ?>" class="btn-small btn-primary">
                                                    Voir détails
                                                </a>
                                            </div>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <div class="empty-icon">🎭</div>
                                <h3>Aucune recommandation pour le moment</h3>
                                <p>Nous préparons des suggestions personnalisées pour vous.</p>
                                <a href="/discover.php" class="btn-primary" style="margin-top: 1rem;">
                                    Explorer les événements
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Sidebar -->
                <div class="sidebar">
                    <!-- Profile Summary -->
                    <div class="section-card">
                        <div class="profile-summary">
                            <div class="profile-avatar">
                                <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                            </div>
                            <div class="profile-name"><?php echo htmlspecialchars($user['name']); ?></div>
                            <div class="profile-info">
                                Membre depuis <?php echo date('M Y', strtotime($user['created_at'])); ?>
                                <?php if ($userProfile['location']): ?>
                                    <br><?php echo htmlspecialchars($userProfile['location']); ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <?php if (!empty($preferences)): ?>
                            <div>
                                <h4 style="color: white; margin-bottom: 1rem; font-size: 1rem;">Vos préférences</h4>
                                <div class="preferences-list">
                                    <?php foreach ($preferences as $pref): ?>
                                        <span class="preference-tag">
                                            <?php 
                                            $prefNames = [
                                                'art' => '🎨 Art',
                                                'music' => '🎵 Musique', 
                                                'theater' => '🎭 Théâtre',
                                                'cinema' => '🎬 Cinéma',
                                                'literature' => '📚 Littérature',
                                                'heritage' => '🏛️ Patrimoine',
                                                'dance' => '💃 Danse',
                                                'festival' => '🎪 Festival'
                                            ];
                                            echo $prefNames[$pref] ?? ucfirst($pref);
                                            ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div class="section-card">
                        <h3 class="section-title">
                            <span class="section-icon"><i class="fas fa-bolt"></i></span>
                            Actions rapides
                        </h3>
                        
                        <div class="quick-actions">
                            <a href="/discover.php" class="action-btn">
                                <i class="fas fa-search"></i>
                                Explorer les événements
                            </a>
                            <a href="/settings.php" class="action-btn">
                                <i class="fas fa-cog"></i>
                                Modifier mes préférences
                            </a>
                            <a href="/favorites.php" class="action-btn">
                                <i class="fas fa-heart"></i>
                                Mes favoris
                            </a>
                            <a href="/calendar.php" class="action-btn">
                                <i class="fas fa-calendar"></i>
                                Mon calendrier culturel
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <!-- Scripts -->
    <script>
        // Save event to favorites
        function saveEvent(eventId) {
            fetch('/api/save-event.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ event_id: eventId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Événement ajouté aux favoris !', 'success');
                } else {
                    showNotification('Erreur lors de l\'ajout aux favoris', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Erreur lors de l\'ajout aux favoris', 'error');
            });
        }
        
        // Show notification function
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.innerHTML = `
                <div class="notification-content" style="background: ${type === 'success' ? 'var(--success)' : type === 'error' ? 'var(--warning)' : 'var(--primary)'}; color: white; padding: 1rem 1.5rem; border-radius: 12px; box-shadow: var(--shadow-xl); position: fixed; top: 100px; right: 20px; z-index: 1002; transform: translateX(100%); transition: transform 0.3s ease; max-width: 400px;">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
                    <span>${message}</span>
                </div>
            `;
            
            document.body.appendChild(notification);
            
            // Animate in
            setTimeout(() => {
                notification.querySelector('.notification-content').style.transform = 'translateX(0)';
            }, 100);
            
            // Remove after delay
            setTimeout(() => {
                notification.querySelector('.notification-content').style.transform = 'translateX(100%)';
                setTimeout(() => {
                    document.body.removeChild(notification);
                }, 300);
            }, 5000);
        }
        
        // Auto-hide welcome message
        <?php if ($showWelcome): ?>
            setTimeout(() => {
                const welcomeMsg = document.querySelector('.welcome-message');
                if (welcomeMsg) {
                    welcomeMsg.style.opacity = '0';
                    welcomeMsg.style.transform = 'translateY(-20px)';
                    setTimeout(() => {
                        welcomeMsg.remove();
                    }, 500);
                }
            }, 8000);
        <?php endif; ?>
    </script>
    <script src="assets/js/main.js"></script>
    <script src="assets/js/ai-recommendations.js"></script>
</body>
</html>