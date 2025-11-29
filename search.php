<?php
session_start();
require_once __DIR__ . '/config.php';

// Récupérer les paramètres de recherche
$query = $_GET['q'] ?? '';
$filter = $_GET['filter'] ?? 'all';
$category = $_GET['category'] ?? '';

// Charger les événements via l'API aggregator
$apiUrl = '/api/events-aggregator.php?' . http_build_query([
    'location' => 'Paris',
    'limit' => 50,
    'category' => $category
]);

// Essayer de charger les événements
$events = [];
$apiPath = __DIR__ . '/api/events-aggregator.php';
if (file_exists($apiPath)) {
    $_GET['location'] = 'Paris';
    $_GET['limit'] = 50;
    ob_start();
    include $apiPath;
    $response = ob_get_clean();
    $data = json_decode($response, true);
    if (isset($data['events'])) {
        $events = $data['events'];
    }
}

// Filtrer les événements selon la recherche
if (!empty($query)) {
    $events = array_filter($events, function($event) use ($query) {
        $searchIn = strtolower($event['title'] . ' ' . $event['description'] . ' ' . $event['venue'] . ' ' . $event['category']);
        return strpos($searchIn, strtolower($query)) !== false;
    });
}

// Filtrer selon le filtre rapide
$today = date('Y-m-d');
$tomorrow = date('Y-m-d', strtotime('+1 day'));
$weekendStart = date('Y-m-d', strtotime('next Saturday'));
$weekendEnd = date('Y-m-d', strtotime('next Sunday'));

switch($filter) {
    case 'today':
        $events = array_filter($events, function($event) use ($today) {
            return isset($event['date']) && substr($event['date'], 0, 10) === $today;
        });
        break;
    case 'weekend':
        $events = array_filter($events, function($event) use ($weekendStart, $weekendEnd) {
            $eventDate = isset($event['date']) ? substr($event['date'], 0, 10) : '';
            return $eventDate >= $weekendStart && $eventDate <= $weekendEnd;
        });
        break;
    case 'free':
        $events = array_filter($events, function($event) {
            return isset($event['is_free']) && $event['is_free'];
        });
        break;
}

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$userName = $isLoggedIn ? ($_SESSION['user_name'] ?? 'Utilisateur') : '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recherche: <?php echo htmlspecialchars($query); ?> - Culture Radar</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Inter:wght@400;500&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            margin: 0;
            padding: 0;
            background: #f5f5f5;
            min-height: 100vh;
        }
        .search-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }
        .search-title {
            font-size: 2rem;
            margin-bottom: 1rem;
        }
        .search-info {
            opacity: 0.9;
        }
        .filters-bar {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .filter-chips {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
        .filter-chip {
            padding: 0.5rem 1rem;
            border: 2px solid #e0e0e0;
            border-radius: 20px;
            background: white;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            color: #333;
            display: inline-block;
        }
        .filter-chip:hover,
        .filter-chip.active {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        .events-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 4rem;
        }
        .event-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        .event-card:hover {
            transform: translateY(-5px);
        }
        .event-image {
            height: 200px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .event-content {
            padding: 1.5rem;
        }
        .event-category {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            background: #f0f0ff;
            color: #667eea;
            border-radius: 20px;
            font-size: 0.8rem;
            margin-bottom: 0.5rem;
        }
        .event-title {
            font-size: 1.2rem;
            margin: 0.5rem 0;
            color: #333;
        }
        .event-meta {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            margin-top: 1rem;
            color: #666;
            font-size: 0.9rem;
        }
        .event-meta i {
            width: 20px;
        }
        .no-results {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 10px;
        }
        .no-results i {
            font-size: 4rem;
            color: #ccc;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar" style="background: white; padding: 1rem 0; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
        <div class="nav-container" style="max-width: 1200px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; padding: 0 2rem;">
            <a href="/" class="nav-logo" style="font-size: 1.5rem; font-weight: bold; color: #667eea; text-decoration: none;">
                <i class="fas fa-radar"></i> Culture Radar
            </a>
            <div class="nav-actions">
                <?php if($isLoggedIn): ?>
                    <a href="/dashboard.php" class="btn-secondary">Mon Espace</a>
                    <a href="/logout.php" class="btn-secondary">Déconnexion</a>
                <?php else: ?>
                    <a href="/login.php" class="btn-secondary">Connexion</a>
                    <a href="/register.php" class="btn-primary">S'inscrire</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Search Header -->
    <div class="search-header">
        <div class="container">
            <h1 class="search-title">
                <?php if (!empty($query)): ?>
                    Résultats pour "<?php echo htmlspecialchars($query); ?>"
                <?php else: ?>
                    Tous les événements
                <?php endif; ?>
            </h1>
            <p class="search-info">
                <?php echo count($events); ?> événement(s) trouvé(s)
            </p>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container">
        <!-- Search Form -->
        <div class="filters-bar">
            <form method="GET" action="/search.php" style="margin-bottom: 1rem;">
                <div style="display: flex; gap: 1rem;">
                    <input type="text" name="q" value="<?php echo htmlspecialchars($query); ?>" 
                           placeholder="Rechercher..." 
                           style="flex: 1; padding: 0.75rem; border: 2px solid #e0e0e0; border-radius: 10px;">
                    <button type="submit" style="padding: 0.75rem 2rem; background: #667eea; color: white; border: none; border-radius: 10px; cursor: pointer;">
                        <i class="fas fa-search"></i> Rechercher
                    </button>
                </div>
            </form>
            
            <div class="filter-chips">
                <a href="?q=<?php echo urlencode($query); ?>&filter=all" 
                   class="filter-chip <?php echo $filter === 'all' ? 'active' : ''; ?>">Tout</a>
                <a href="?q=<?php echo urlencode($query); ?>&filter=today" 
                   class="filter-chip <?php echo $filter === 'today' ? 'active' : ''; ?>">Aujourd'hui</a>
                <a href="?q=<?php echo urlencode($query); ?>&filter=weekend" 
                   class="filter-chip <?php echo $filter === 'weekend' ? 'active' : ''; ?>">Ce week-end</a>
                <a href="?q=<?php echo urlencode($query); ?>&filter=free" 
                   class="filter-chip <?php echo $filter === 'free' ? 'active' : ''; ?>">Gratuit</a>
            </div>
        </div>

        <!-- Results -->
        <?php if (empty($events)): ?>
            <div class="no-results">
                <i class="fas fa-search"></i>
                <h2>Aucun événement trouvé</h2>
                <p>Essayez avec d'autres mots-clés ou filtres</p>
                <a href="/search.php" class="btn-primary" style="margin-top: 1rem; display: inline-block; padding: 0.75rem 2rem; background: #667eea; color: white; text-decoration: none; border-radius: 50px;">
                    Voir tous les événements
                </a>
            </div>
        <?php else: ?>
            <div class="events-grid">
                <?php foreach ($events as $event): 
                    $priceText = isset($event['is_free']) && $event['is_free'] ? 'Gratuit' : 
                                (isset($event['price']) && $event['price'] ? $event['price'] . '€' : 'Prix libre');
                ?>
                <div class="event-card">
                    <?php if (!empty($event['image'])): ?>
                    <div class="event-image" style="background: url('<?php echo htmlspecialchars($event['image']); ?>') center/cover;">
                    </div>
                    <?php else: ?>
                    <div class="event-image">
                        <i class="fas fa-calendar-alt" style="font-size: 3rem; color: white;"></i>
                    </div>
                    <?php endif; ?>
                    <div class="event-content">
                        <span class="event-category">
                            <?php echo ucfirst($event['category'] ?? 'culture'); ?>
                        </span>
                        <h3 class="event-title">
                            <?php echo htmlspecialchars($event['title']); ?>
                        </h3>
                        <p style="color: #666; margin: 0.5rem 0;">
                            <?php echo htmlspecialchars(substr($event['description'] ?? '', 0, 100)); ?>...
                        </p>
                        <div class="event-meta">
                            <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($event['venue'] ?? 'Lieu non spécifié'); ?></span>
                            <span><i class="fas fa-euro-sign"></i> <?php echo $priceText; ?></span>
                            <?php if (!empty($event['date'])): ?>
                            <span><i class="fas fa-calendar"></i> <?php echo date('d/m/Y', strtotime($event['date'])); ?></span>
                            <?php endif; ?>
                        </div>
                        <a href="/event-details.php?id=<?php echo urlencode($event['id']); ?>" 
                           style="display: inline-block; margin-top: 1rem; color: #667eea; text-decoration: none; font-weight: 500;">
                            Voir plus →
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer style="background: #333; color: white; padding: 2rem 0; margin-top: 4rem;">
        <div class="container" style="text-align: center;">
            <p>&copy; 2024 Culture Radar. Projet étudiant fictif.</p>
        </div>
    </footer>
</body>
</html>