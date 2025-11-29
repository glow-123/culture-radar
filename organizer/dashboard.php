<?php
session_start();

// Load configuration
require_once __DIR__ . '/../config.php';

// Check if organizer is logged in
if (!isset($_SESSION['organizer_id'])) {
    header('Location: /organizer/login.php');
    exit();
}

$error = '';
$success = '';
$organizer = [];
$myEvents = [];
$statistics = [];

try {
    $dbConfig = Config::database();
    $dsn = "mysql:host=" . $dbConfig['host'] . ";dbname=" . $dbConfig['name'] . ";charset=" . $dbConfig['charset'];
    $pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get organizer information
    $stmt = $pdo->prepare("SELECT * FROM organizers WHERE id = ?");
    $stmt->execute([$_SESSION['organizer_id']]);
    $organizer = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get organizer's events
    $stmt = $pdo->prepare("
        SELECT e.*, 
               COUNT(ui.id) as total_interactions,
               COUNT(CASE WHEN ui.interaction_type = 'save' THEN 1 END) as saves,
               COUNT(CASE WHEN ui.interaction_type = 'click' THEN 1 END) as clicks
        FROM events e
        LEFT JOIN user_interactions ui ON e.id = ui.event_id
        WHERE e.organizer_id = ?
        GROUP BY e.id
        ORDER BY e.start_date DESC
        LIMIT 10
    ");
    $stmt->execute([$_SESSION['organizer_id']]);
    $myEvents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get statistics
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_events,
            COUNT(CASE WHEN is_active = 1 THEN 1 END) as active_events,
            COUNT(CASE WHEN start_date > NOW() THEN 1 END) as upcoming_events,
            COUNT(CASE WHEN featured = 1 THEN 1 END) as featured_events
        FROM events 
        WHERE organizer_id = ?
    ");
    $stmt->execute([$_SESSION['organizer_id']]);
    $statistics = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get total interactions for all organizer events
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total_interactions
        FROM user_interactions ui
        JOIN events e ON ui.event_id = e.id
        WHERE e.organizer_id = ?
    ");
    $stmt->execute([$_SESSION['organizer_id']]);
    $interactionStats = $stmt->fetch(PDO::FETCH_ASSOC);
    $statistics['total_interactions'] = $interactionStats['total_interactions'];
    
} catch (PDOException $e) {
    $error = 'Erreur lors du chargement des données.';
    error_log("Organizer dashboard error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- SEO & Meta -->
    <title>Tableau de bord organisateur - Culture Radar</title>
    <meta name="description" content="Gérez vos événements culturels, suivez les statistiques et développez votre audience avec Culture Radar.">
    <meta name="robots" content="noindex, nofollow">
    
    <?php include '../includes/favicon.php'; ?>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800;900&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Styles -->
    <link rel="stylesheet" href="../assets/css/style.css">
    
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* Organizer dashboard specific styles */
        .organizer-container {
            padding-top: 100px;
            min-height: 100vh;
        }
        
        .organizer-header {
            background: var(--glass);
            backdrop-filter: blur(30px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            text-align: center;
        }
        
        .organizer-welcome {
            font-size: 2.2rem;
            font-weight: 800;
            color: white;
            margin-bottom: 0.5rem;
        }
        
        .organizer-subtitle {
            color: rgba(255, 255, 255, 0.8);
            font-size: 1.1rem;
        }
        
        .subscription-badge {
            background: var(--success);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 15px;
            font-weight: 600;
            font-size: 0.9rem;
            display: inline-block;
            margin-top: 1rem;
        }
        
        .subscription-badge.basic {
            background: var(--warning);
        }
        
        .subscription-badge.premium {
            background: var(--accent);
        }
        
        .stats-grid {
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
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-xl);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            background: var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            margin: 0 auto 1rem;
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            color: white;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.9rem;
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
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
            border-radius: 20px;
            padding: 2rem;
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
        
        .events-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .event-item {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            padding: 1.5rem;
            transition: var(--transition);
        }
        
        .event-item:hover {
            background: rgba(255, 255, 255, 0.1);
        }
        
        .event-item-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }
        
        .event-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: white;
            margin-bottom: 0.5rem;
        }
        
        .event-meta {
            display: flex;
            gap: 1rem;
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 1rem;
        }
        
        .event-meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .event-stats {
            display: flex;
            gap: 1rem;
            font-size: 0.9rem;
        }
        
        .event-stat {
            background: rgba(255, 255, 255, 0.1);
            padding: 0.5rem 1rem;
            border-radius: 8px;
            color: white;
        }
        
        .event-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }
        
        .btn-small {
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
            border-radius: 8px;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            transition: var(--transition);
            border: none;
        }
        
        .btn-primary {
            background: var(--primary);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
        }
        
        .btn-outline {
            background: transparent;
            color: rgba(255, 255, 255, 0.8);
            border: 1px solid var(--glass-border);
        }
        
        .btn-outline:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }
        
        .quick-actions {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .action-btn {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid var(--glass-border);
            color: white;
            padding: 1rem;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .action-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateX(5px);
        }
        
        .action-icon {
            width: 35px;
            height: 35px;
            background: var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
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
        }
        
        @media (max-width: 768px) {
            .organizer-container {
                padding-top: 80px;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .event-item-header {
                flex-direction: column;
                gap: 1rem;
            }
            
            .event-meta {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .event-stats {
                flex-direction: column;
                gap: 0.5rem;
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
            <a href="/organizer/" class="logo" aria-label="Culture Radar Organisateur">
                <div class="location-pin-icon" aria-hidden="true"></div>
                Culture Radar <span style="font-size: 0.8rem; opacity: 0.8;">Pro</span>
            </a>
            
            <ul class="nav-links" role="menubar">
                <li role="none"><a href="/organizer/dashboard.php" role="menuitem" class="active">Tableau de bord</a></li>
                <li role="none"><a href="/organizer/events.php" role="menuitem">Mes événements</a></li>
                <li role="none"><a href="/organizer/analytics.php" role="menuitem">Statistiques</a></li>
            </ul>
            
            <div class="nav-actions">
                <div class="user-menu">
                    <button class="user-avatar" aria-label="Menu organisateur">
                        <?php echo strtoupper(substr($organizer['name'] ?? 'O', 0, 1)); ?>
                    </button>
                    <div class="user-dropdown">
                        <a href="/organizer/profile.php">Mon profil</a>
                        <a href="/organizer/subscription.php">Abonnement</a>
                        <a href="/organizer/logout.php">Déconnexion</a>
                    </div>
                </div>
            </div>
        </nav>
    </header>
    
    <!-- Main Content -->
    <main id="main-content" class="organizer-container">
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
            <div class="organizer-header">
                <h1 class="organizer-welcome">
                    Bonjour <?php echo htmlspecialchars(explode(' ', $organizer['name'])[0]); ?> ! 👋
                </h1>
                <p class="organizer-subtitle">
                    Gérez vos événements et développez votre audience culturelle
                </p>
                
                <div class="subscription-badge <?php echo $organizer['subscription_type']; ?>">
                    <?php 
                    $subscriptionLabels = [
                        'free' => '🆓 Gratuit',
                        'basic' => '⭐ Basic',
                        'premium' => '💎 Premium'
                    ];
                    echo $subscriptionLabels[$organizer['subscription_type']] ?? 'Abonnement';
                    ?>
                </div>
            </div>
            
            <!-- Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div class="stat-number"><?php echo $statistics['total_events'] ?? 0; ?></div>
                    <div class="stat-label">Événements créés</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-eye"></i>
                    </div>
                    <div class="stat-number"><?php echo $statistics['total_interactions'] ?? 0; ?></div>
                    <div class="stat-label">Vues totales</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-rocket"></i>
                    </div>
                    <div class="stat-number"><?php echo $statistics['upcoming_events'] ?? 0; ?></div>
                    <div class="stat-label">Événements à venir</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="stat-number"><?php echo $statistics['featured_events'] ?? 0; ?></div>
                    <div class="stat-label">Mis en avant</div>
                </div>
            </div>
            
            <!-- Dashboard Grid -->
            <div class="dashboard-grid">
                <div class="main-content">
                    <!-- Recent Events -->
                    <div class="section-card">
                        <h2 class="section-title">
                            <span class="section-icon"><i class="fas fa-calendar"></i></span>
                            Mes événements récents
                        </h2>
                        
                        <?php if (!empty($myEvents)): ?>
                            <div class="events-list">
                                <?php foreach ($myEvents as $event): ?>
                                    <div class="event-item">
                                        <div class="event-item-header">
                                            <div>
                                                <h3 class="event-title"><?php echo htmlspecialchars($event['title']); ?></h3>
                                                <div class="event-meta">
                                                    <div class="event-meta-item">
                                                        <i class="fas fa-calendar"></i>
                                                        <?php echo date('d/m/Y H:i', strtotime($event['start_date'])); ?>
                                                    </div>
                                                    <div class="event-meta-item">
                                                        <i class="fas fa-map-marker-alt"></i>
                                                        <?php echo htmlspecialchars($event['venue_name'] ?? $event['city']); ?>
                                                    </div>
                                                    <div class="event-meta-item">
                                                        <i class="fas fa-tag"></i>
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
                                                </div>
                                            </div>
                                            
                                            <div class="event-stats">
                                                <div class="event-stat">
                                                    <i class="fas fa-eye"></i>
                                                    <?php echo $event['total_interactions']; ?> vues
                                                </div>
                                                <div class="event-stat">
                                                    <i class="fas fa-heart"></i>
                                                    <?php echo $event['saves']; ?> favoris
                                                </div>
                                                <div class="event-stat">
                                                    <i class="fas fa-mouse-pointer"></i>
                                                    <?php echo $event['clicks']; ?> clics
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="event-actions">
                                            <a href="/organizer/events.php?edit=<?php echo $event['id']; ?>" class="btn-small btn-primary">
                                                <i class="fas fa-edit"></i> Modifier
                                            </a>
                                            <a href="/event.php?id=<?php echo $event['id']; ?>" class="btn-small btn-outline" target="_blank">
                                                <i class="fas fa-external-link-alt"></i> Voir
                                            </a>
                                            <?php if (!$event['featured'] && $organizer['subscription_type'] !== 'free'): ?>
                                                <button class="btn-small btn-outline" onclick="promoteEvent(<?php echo $event['id']; ?>)">
                                                    <i class="fas fa-rocket"></i> Promouvoir
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <div class="empty-icon">📅</div>
                                <h3>Aucun événement créé</h3>
                                <p>Commencez par créer votre premier événement culturel.</p>
                                <a href="/organizer/events.php?create=1" class="btn-primary" style="margin-top: 1rem;">
                                    Créer un événement
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Sidebar -->
                <div class="sidebar">
                    <!-- Quick Actions -->
                    <div class="section-card">
                        <h3 class="section-title">
                            <span class="section-icon"><i class="fas fa-bolt"></i></span>
                            Actions rapides
                        </h3>
                        
                        <div class="quick-actions">
                            <a href="/organizer/events.php?create=1" class="action-btn">
                                <div class="action-icon">
                                    <i class="fas fa-plus"></i>
                                </div>
                                <div>
                                    <div style="font-weight: 600;">Créer un événement</div>
                                    <div style="font-size: 0.8rem; opacity: 0.8;">Nouvel événement culturel</div>
                                </div>
                            </a>
                            
                            <a href="/organizer/analytics.php" class="action-btn">
                                <div class="action-icon">
                                    <i class="fas fa-chart-bar"></i>
                                </div>
                                <div>
                                    <div style="font-weight: 600;">Voir les statistiques</div>
                                    <div style="font-size: 0.8rem; opacity: 0.8;">Performances détaillées</div>
                                </div>
                            </a>
                            
                            <a href="/organizer/profile.php" class="action-btn">
                                <div class="action-icon">
                                    <i class="fas fa-user"></i>
                                </div>
                                <div>
                                    <div style="font-weight: 600;">Mon profil</div>
                                    <div style="font-size: 0.8rem; opacity: 0.8;">Informations organisation</div>
                                </div>
                            </a>
                            
                            <?php if ($organizer['subscription_type'] === 'free'): ?>
                                <a href="/organizer/subscription.php" class="action-btn" style="background: var(--accent); border-color: var(--accent);">
                                    <div class="action-icon">
                                        <i class="fas fa-crown"></i>
                                    </div>
                                    <div>
                                        <div style="font-weight: 600;">Passer Premium</div>
                                        <div style="font-size: 0.8rem; opacity: 0.8;">Plus de visibilité</div>
                                    </div>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Tips -->
                    <div class="section-card">
                        <h3 class="section-title">
                            <span class="section-icon"><i class="fas fa-lightbulb"></i></span>
                            Conseils
                        </h3>
                        
                        <div style="color: rgba(255, 255, 255, 0.8); font-size: 0.9rem; line-height: 1.6;">
                            <div style="margin-bottom: 1rem;">
                                <strong>💡 Optimisez vos événements :</strong><br>
                                Ajoutez des descriptions détaillées et des images attractives pour augmenter l'engagement.
                            </div>
                            
                            <div style="margin-bottom: 1rem;">
                                <strong>📈 Suivez vos performances :</strong><br>
                                Consultez régulièrement vos statistiques pour comprendre ce qui fonctionne le mieux.
                            </div>
                            
                            <div>
                                <strong>🎯 Ciblez votre audience :</strong><br>
                                Utilisez les bonnes catégories et mots-clés pour que votre événement soit trouvé facilement.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <!-- Scripts -->
    <script>
        function promoteEvent(eventId) {
            if (confirm('Voulez-vous promouvoir cet événement ? Il sera mis en avant dans les résultats de recherche.')) {
                fetch('/organizer/api/promote-event.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ event_id: eventId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification('Événement promu avec succès !', 'success');
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    } else {
                        showNotification(data.error || 'Erreur lors de la promotion', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Erreur lors de la promotion', 'error');
                });
            }
        }
        
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
            
            setTimeout(() => {
                notification.querySelector('.notification-content').style.transform = 'translateX(0)';
            }, 100);
            
            setTimeout(() => {
                notification.querySelector('.notification-content').style.transform = 'translateX(100%)';
                setTimeout(() => {
                    document.body.removeChild(notification);
                }, 300);
            }, 5000);
        }
    </script>
    <script src="../assets/js/main.js"></script>
</body>
</html>