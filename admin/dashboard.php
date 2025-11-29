<?php
session_start();

// Load configuration
require_once __DIR__ . '/../config.php';

// Check if user is admin
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit();
}

// Check admin status
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

$error = '';
$analytics = [];

try {
    // Get overall statistics
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(DISTINCT u.id) as total_users,
            COUNT(DISTINCT CASE WHEN u.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN u.id END) as new_users_30d,
            COUNT(DISTINCT o.id) as total_organizers,
            COUNT(DISTINCT e.id) as total_events,
            COUNT(DISTINCT CASE WHEN e.start_date >= NOW() THEN e.id END) as upcoming_events,
            COUNT(DISTINCT ui.id) as total_interactions,
            COUNT(DISTINCT CASE WHEN ui.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN ui.id END) as interactions_7d
        FROM users u
        CROSS JOIN organizers o
        CROSS JOIN events e
        CROSS JOIN user_interactions ui
    ");
    $stmt->execute();
    $analytics['overview'] = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get user growth data (last 30 days)
    $stmt = $pdo->prepare("
        SELECT 
            DATE(created_at) as date,
            COUNT(*) as registrations
        FROM users 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY DATE(created_at)
        ORDER BY date ASC
    ");
    $stmt->execute();
    $analytics['user_growth'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get popular categories
    $stmt = $pdo->prepare("
        SELECT 
            e.category,
            COUNT(ui.id) as interactions,
            COUNT(DISTINCT ui.user_id) as unique_users,
            AVG(ui.rating) as avg_rating
        FROM events e
        LEFT JOIN user_interactions ui ON e.id = ui.event_id
        GROUP BY e.category
        ORDER BY interactions DESC
        LIMIT 8
    ");
    $stmt->execute();
    $analytics['categories'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get top cities
    $stmt = $pdo->prepare("
        SELECT 
            e.city,
            COUNT(DISTINCT e.id) as event_count,
            COUNT(ui.id) as interactions
        FROM events e
        LEFT JOIN user_interactions ui ON e.id = ui.event_id
        WHERE e.city IS NOT NULL AND e.city != ''
        GROUP BY e.city
        ORDER BY interactions DESC
        LIMIT 10
    ");
    $stmt->execute();
    $analytics['cities'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get AI performance metrics
    $stmt = $pdo->prepare("
        SELECT 
            AVG(match_score) as avg_match_score,
            COUNT(*) as total_recommendations,
            COUNT(CASE WHEN ur.is_clicked = 1 THEN 1 END) as clicked_recommendations,
            COUNT(CASE WHEN ur.is_saved = 1 THEN 1 END) as saved_recommendations
        FROM user_recommendations ur
        WHERE ur.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ");
    $stmt->execute();
    $analytics['ai_performance'] = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get recent activity
    $stmt = $pdo->prepare("
        SELECT 
            'user_registration' as type,
            u.name as title,
            u.email as subtitle,
            u.created_at as timestamp
        FROM users u
        WHERE u.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        
        UNION ALL
        
        SELECT 
            'event_creation' as type,
            e.title as title,
            CONCAT('Par ', o.name) as subtitle,
            e.created_at as timestamp
        FROM events e
        JOIN organizers o ON e.organizer_id = o.id
        WHERE e.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        
        UNION ALL
        
        SELECT 
            'high_engagement' as type,
            e.title as title,
            CONCAT(COUNT(ui.id), ' interactions') as subtitle,
            MAX(ui.created_at) as timestamp
        FROM events e
        JOIN user_interactions ui ON e.id = ui.event_id
        WHERE ui.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY e.id
        HAVING COUNT(ui.id) >= 10
        
        ORDER BY timestamp DESC
        LIMIT 20
    ");
    $stmt->execute();
    $analytics['recent_activity'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get system health metrics
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_errors
        FROM error_logs 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ");
    $stmt->execute();
    $errorCount = $stmt->fetchColumn();
    
    $analytics['system_health'] = [
        'database_status' => 'healthy',
        'error_count_24h' => $errorCount ?: 0,
        'cache_status' => 'active',
        'ai_status' => 'operational'
    ];
    
} catch (PDOException $e) {
    $error = 'Erreur lors du chargement des données analytiques.';
    error_log("Admin dashboard error: " . $e->getMessage());
}

$categoryNames = [
    'art' => '🎨 Art & Expo',
    'music' => '🎵 Musique',
    'theater' => '🎭 Théâtre',
    'cinema' => '🎬 Cinéma',
    'literature' => '📚 Littérature',
    'heritage' => '🏛️ Patrimoine',
    'dance' => '💃 Danse',
    'festival' => '🎪 Festival'
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration - Culture Radar</title>
    
    <?php include '../includes/favicon.php'; ?>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        .admin-container {
            padding-top: 100px;
            min-height: 100vh;
        }
        
        .admin-header {
            background: var(--glass);
            backdrop-filter: blur(30px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            text-align: center;
        }
        
        .admin-title {
            font-size: 2.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 50%, #10ac84 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.5rem;
        }
        
        .admin-subtitle {
            color: rgba(255, 255, 255, 0.8);
            font-size: 1.1rem;
        }
        
        .stats-overview {
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
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            margin: 0 auto 1rem;
        }
        
        .stat-icon.users { background: var(--primary); }
        .stat-icon.organizers { background: var(--accent); }
        .stat-icon.events { background: var(--success); }
        .stat-icon.interactions { background: var(--warning); }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            color: white;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }
        
        .stat-change {
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .stat-change.positive { color: #10ac84; }
        .stat-change.negative { color: #ee5a24; }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
            margin-bottom: 3rem;
        }
        
        .chart-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .chart-card {
            background: var(--glass);
            backdrop-filter: blur(30px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 2rem;
        }
        
        .chart-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: white;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        
        .chart-container {
            position: relative;
            height: 300px;
        }
        
        .activity-feed {
            background: var(--glass);
            backdrop-filter: blur(30px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 2rem;
            max-height: 600px;
            overflow-y: auto;
        }
        
        .activity-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: white;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .activity-item {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            padding: 1rem 0;
            border-bottom: 1px solid var(--glass-border);
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            color: white;
            flex-shrink: 0;
        }
        
        .activity-icon.user { background: var(--primary); }
        .activity-icon.event { background: var(--success); }
        .activity-icon.engagement { background: var(--warning); }
        
        .activity-content {
            flex: 1;
        }
        
        .activity-title-text {
            color: white;
            font-weight: 600;
            margin-bottom: 0.25rem;
            font-size: 0.9rem;
        }
        
        .activity-subtitle {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.8rem;
            margin-bottom: 0.25rem;
        }
        
        .activity-time {
            color: rgba(255, 255, 255, 0.5);
            font-size: 0.75rem;
        }
        
        .health-indicators {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .health-card {
            background: var(--glass);
            backdrop-filter: blur(30px);
            border: 1px solid var(--glass-border);
            border-radius: 15px;
            padding: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .health-status {
            width: 12px;
            height: 12px;
            border-radius: 50%;
        }
        
        .health-status.healthy { background: #10ac84; }
        .health-status.warning { background: #f39c12; }
        .health-status.error { background: #e74c3c; }
        
        .cities-list {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.5rem;
            margin-top: 1rem;
        }
        
        .city-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            color: white;
            font-size: 0.9rem;
        }
        
        .city-count {
            background: var(--accent);
            color: white;
            padding: 0.2rem 0.6rem;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        @media (max-width: 1024px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            
            .chart-section {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .admin-container {
                padding-top: 80px;
            }
            
            .stats-overview {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .health-indicators {
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
                <li><a href="/admin/dashboard.php" class="active">Tableau de bord</a></li>
                <li><a href="/admin/users.php">Utilisateurs</a></li>
                <li><a href="/admin/events.php">Événements</a></li>
                <li><a href="/admin/organizers.php">Organisateurs</a></li>
                <li><a href="/admin/settings.php">Paramètres</a></li>
            </ul>
            
            <div class="nav-actions">
                <a href="/dashboard.php" class="btn-outline" style="margin-right: 1rem;">
                    <i class="fas fa-user"></i> Vue utilisateur
                </a>
                <div class="user-menu">
                    <button class="user-avatar">
                        <i class="fas fa-user-shield"></i>
                    </button>
                    <div class="user-dropdown">
                        <a href="/admin/profile.php">Mon profil</a>
                        <a href="/logout.php">Déconnexion</a>
                    </div>
                </div>
            </div>
        </nav>
    </header>
    
    <!-- Main Content -->
    <main class="admin-container">
        <div class="container">
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <!-- Header -->
            <div class="admin-header">
                <h1 class="admin-title">Administration Culture Radar</h1>
                <p class="admin-subtitle">
                    Tableau de bord analytique et gestion de la plateforme
                </p>
            </div>
            
            <!-- Overview Stats -->
            <div class="stats-overview">
                <div class="stat-card">
                    <div class="stat-icon users">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-number"><?php echo number_format($analytics['overview']['total_users'] ?? 0); ?></div>
                    <div class="stat-label">Utilisateurs</div>
                    <div class="stat-change positive">
                        +<?php echo $analytics['overview']['new_users_30d'] ?? 0; ?> ce mois
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon organizers">
                        <i class="fas fa-building"></i>
                    </div>
                    <div class="stat-number"><?php echo number_format($analytics['overview']['total_organizers'] ?? 0); ?></div>
                    <div class="stat-label">Organisateurs</div>
                    <div class="stat-change positive">Actifs</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon events">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div class="stat-number"><?php echo number_format($analytics['overview']['total_events'] ?? 0); ?></div>
                    <div class="stat-label">Événements</div>
                    <div class="stat-change positive">
                        <?php echo $analytics['overview']['upcoming_events'] ?? 0; ?> à venir
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon interactions">
                        <i class="fas fa-mouse-pointer"></i>
                    </div>
                    <div class="stat-number"><?php echo number_format($analytics['overview']['total_interactions'] ?? 0); ?></div>
                    <div class="stat-label">Interactions</div>
                    <div class="stat-change positive">
                        +<?php echo $analytics['overview']['interactions_7d'] ?? 0; ?> cette semaine
                    </div>
                </div>
            </div>
            
            <!-- System Health -->
            <div class="health-indicators">
                <div class="health-card">
                    <div class="health-status healthy"></div>
                    <div>
                        <div style="color: white; font-weight: 600;">Base de données</div>
                        <div style="color: rgba(255, 255, 255, 0.7); font-size: 0.9rem;">Opérationnelle</div>
                    </div>
                </div>
                
                <div class="health-card">
                    <div class="health-status <?php echo ($analytics['system_health']['error_count_24h'] ?? 0) > 10 ? 'error' : 'healthy'; ?>"></div>
                    <div>
                        <div style="color: white; font-weight: 600;">Erreurs (24h)</div>
                        <div style="color: rgba(255, 255, 255, 0.7); font-size: 0.9rem;">
                            <?php echo $analytics['system_health']['error_count_24h'] ?? 0; ?> erreurs
                        </div>
                    </div>
                </div>
                
                <div class="health-card">
                    <div class="health-status healthy"></div>
                    <div>
                        <div style="color: white; font-weight: 600;">IA Recommandations</div>
                        <div style="color: rgba(255, 255, 255, 0.7); font-size: 0.9rem;">
                            <?php echo round($analytics['ai_performance']['avg_match_score'] ?? 0, 1); ?>% score moyen
                        </div>
                    </div>
                </div>
                
                <div class="health-card">
                    <div class="health-status healthy"></div>
                    <div>
                        <div style="color: white; font-weight: 600;">Cache système</div>
                        <div style="color: rgba(255, 255, 255, 0.7); font-size: 0.9rem;">Actif</div>
                    </div>
                </div>
            </div>
            
            <!-- Charts and Activity -->
            <div class="dashboard-grid">
                <div>
                    <!-- Charts Section -->
                    <div class="chart-section">
                        <div class="chart-card">
                            <h3 class="chart-title">Catégories populaires</h3>
                            <div class="chart-container">
                                <canvas id="categoriesChart"></canvas>
                            </div>
                        </div>
                        
                        <div class="chart-card">
                            <h3 class="chart-title">Croissance utilisateurs (30j)</h3>
                            <div class="chart-container">
                                <canvas id="userGrowthChart"></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Cities Stats -->
                    <div class="chart-card">
                        <h3 class="chart-title">Villes les plus actives</h3>
                        <div class="cities-list">
                            <?php foreach (array_slice($analytics['cities'] ?? [], 0, 8) as $city): ?>
                                <div class="city-item">
                                    <span><?php echo htmlspecialchars($city['city']); ?></span>
                                    <span class="city-count"><?php echo $city['interactions']; ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Activity Feed -->
                <div class="activity-feed">
                    <h3 class="activity-title">
                        <i class="fas fa-activity"></i>
                        Activité récente
                    </h3>
                    
                    <?php foreach ($analytics['recent_activity'] ?? [] as $activity): ?>
                        <div class="activity-item">
                            <div class="activity-icon <?php echo $activity['type'] === 'user_registration' ? 'user' : ($activity['type'] === 'event_creation' ? 'event' : 'engagement'); ?>">
                                <i class="fas fa-<?php 
                                    echo $activity['type'] === 'user_registration' ? 'user-plus' : 
                                        ($activity['type'] === 'event_creation' ? 'calendar-plus' : 'fire'); 
                                ?>"></i>
                            </div>
                            <div class="activity-content">
                                <div class="activity-title-text"><?php echo htmlspecialchars($activity['title']); ?></div>
                                <div class="activity-subtitle"><?php echo htmlspecialchars($activity['subtitle']); ?></div>
                                <div class="activity-time">
                                    <?php 
                                    $time = new DateTime($activity['timestamp']);
                                    echo $time->format('d/m/Y H:i');
                                    ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </main>
    
    <script>
        // Categories Chart
        const categoriesData = <?php echo json_encode($analytics['categories'] ?? []); ?>;
        const categoryNames = <?php echo json_encode($categoryNames); ?>;
        
        if (categoriesData.length > 0) {
            const categoriesCtx = document.getElementById('categoriesChart').getContext('2d');
            new Chart(categoriesCtx, {
                type: 'doughnut',
                data: {
                    labels: categoriesData.map(cat => categoryNames[cat.category] || cat.category),
                    datasets: [{
                        data: categoriesData.map(cat => cat.interactions),
                        backgroundColor: [
                            '#667eea', '#764ba2', '#f093fb', '#f5576c',
                            '#4facfe', '#00f2fe', '#43e97b', '#38f9d7'
                        ],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                color: 'white',
                                font: { size: 12 }
                            }
                        }
                    }
                }
            });
        }
        
        // User Growth Chart
        const userGrowthData = <?php echo json_encode($analytics['user_growth'] ?? []); ?>;
        
        if (userGrowthData.length > 0) {
            const userGrowthCtx = document.getElementById('userGrowthChart').getContext('2d');
            new Chart(userGrowthCtx, {
                type: 'line',
                data: {
                    labels: userGrowthData.map(day => {
                        const date = new Date(day.date);
                        return date.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit' });
                    }),
                    datasets: [{
                        label: 'Nouvelles inscriptions',
                        data: userGrowthData.map(day => day.registrations),
                        borderColor: '#4facfe',
                        backgroundColor: 'rgba(79, 172, 254, 0.1)',
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            labels: {
                                color: 'white'
                            }
                        }
                    },
                    scales: {
                        x: {
                            ticks: { color: 'rgba(255, 255, 255, 0.7)' },
                            grid: { color: 'rgba(255, 255, 255, 0.1)' }
                        },
                        y: {
                            ticks: { color: 'rgba(255, 255, 255, 0.7)' },
                            grid: { color: 'rgba(255, 255, 255, 0.1)' }
                        }
                    }
                }
            });
        }
    </script>
    <script src="../assets/js/main.js"></script>
</body>
</html>