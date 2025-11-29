<?php
session_start();

// Load configuration
require_once __DIR__ . '/config.php';

$error = '';
$events = [];
$totalEvents = 0;
$filters = [
    'category' => $_GET['category'] ?? '',
    'city' => $_GET['city'] ?? '',
    'price_min' => $_GET['price_min'] ?? '',
    'price_max' => $_GET['price_max'] ?? '',
    'date_from' => $_GET['date_from'] ?? '',
    'date_to' => $_GET['date_to'] ?? '',
    'is_free' => isset($_GET['is_free']) ? 1 : 0,
    'search' => $_GET['search'] ?? '',
    'sort' => $_GET['sort'] ?? 'relevance'
];

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 12;
$offset = ($page - 1) * $limit;

try {
    $dbConfig = Config::database();
    $dsn = "mysql:host=" . $dbConfig['host'] . ";dbname=" . $dbConfig['name'] . ";charset=" . $dbConfig['charset'];
    $pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Build dynamic query based on filters
    $whereConditions = ['e.is_active = 1', 'e.start_date > NOW()'];
    $params = [];
    
    // Category filter
    if (!empty($filters['category'])) {
        $whereConditions[] = 'e.category = ?';
        $params[] = $filters['category'];
    }
    
    // City filter
    if (!empty($filters['city'])) {
        $whereConditions[] = 'e.city LIKE ?';
        $params[] = '%' . $filters['city'] . '%';
    }
    
    // Price filters
    if (!empty($filters['price_min'])) {
        $whereConditions[] = 'e.price >= ?';
        $params[] = (float)$filters['price_min'];
    }
    
    if (!empty($filters['price_max'])) {
        $whereConditions[] = 'e.price <= ?';
        $params[] = (float)$filters['price_max'];
    }
    
    // Free events filter
    if ($filters['is_free']) {
        $whereConditions[] = 'e.is_free = 1';
    }
    
    // Date range filters
    if (!empty($filters['date_from'])) {
        $whereConditions[] = 'DATE(e.start_date) >= ?';
        $params[] = $filters['date_from'];
    }
    
    if (!empty($filters['date_to'])) {
        $whereConditions[] = 'DATE(e.start_date) <= ?';
        $params[] = $filters['date_to'];
    }
    
    // Search filter
    if (!empty($filters['search'])) {
        $whereConditions[] = '(e.title LIKE ? OR e.description LIKE ? OR e.venue_name LIKE ?)';
        $searchTerm = '%' . $filters['search'] . '%';
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    // Build ORDER BY clause
    $orderBy = 'e.start_date ASC';
    switch ($filters['sort']) {
        case 'date_asc':
            $orderBy = 'e.start_date ASC';
            break;
        case 'date_desc':
            $orderBy = 'e.start_date DESC';
            break;
        case 'price_asc':
            $orderBy = 'e.price ASC';
            break;
        case 'price_desc':
            $orderBy = 'e.price DESC';
            break;
        case 'title':
            $orderBy = 'e.title ASC';
            break;
        case 'relevance':
        default:
            if (!empty($filters['search'])) {
                $orderBy = 'CASE 
                    WHEN e.title LIKE ? THEN 1
                    WHEN e.venue_name LIKE ? THEN 2
                    WHEN e.description LIKE ? THEN 3
                    ELSE 4
                END, e.start_date ASC';
                // Add search term for relevance sorting
                array_unshift($params, $searchTerm, $searchTerm, $searchTerm);
            } else {
                $orderBy = 'e.featured DESC, e.start_date ASC';
            }
            break;
    }
    
    // Get total count for pagination
    $countQuery = "SELECT COUNT(*) FROM events e WHERE " . implode(' AND ', $whereConditions);
    $countParams = $params;
    
    // Remove relevance sorting params from count query
    if ($filters['sort'] === 'relevance' && !empty($filters['search'])) {
        $countParams = array_slice($params, 3);
    }
    
    $stmt = $pdo->prepare($countQuery);
    $stmt->execute($countParams);
    $totalEvents = $stmt->fetchColumn();
    
    // Get events with pagination
    $eventsQuery = "
        SELECT e.*, 
               CASE WHEN e.featured = 1 THEN 'featured' ELSE 'regular' END as event_type
        FROM events e 
        WHERE " . implode(' AND ', $whereConditions) . "
        ORDER BY {$orderBy}
        LIMIT {$limit} OFFSET {$offset}
    ";
    
    $stmt = $pdo->prepare($eventsQuery);
    $stmt->execute($params);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get available categories and cities for filters
    $stmt = $pdo->prepare("SELECT DISTINCT category FROM events WHERE is_active = 1 ORDER BY category");
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $stmt = $pdo->prepare("SELECT DISTINCT city FROM events WHERE is_active = 1 ORDER BY city");
    $stmt->execute();
    $cities = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
} catch (PDOException $e) {
    $error = 'Erreur lors du chargement des événements.';
    error_log("Discover page error: " . $e->getMessage());
}

$totalPages = ceil($totalEvents / $limit);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- SEO & Meta -->
    <title>Découvrir les événements - Culture Radar | Explorez la culture près de vous</title>
    <meta name="description" content="Découvrez les événements culturels près de vous. Filtrez par catégorie, prix, date et trouvez l'expérience culturelle parfaite.">
    
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
        /* Discover page specific styles */
        .discover-container {
            padding-top: 100px;
            min-height: 100vh;
        }
        
        .discover-header {
            text-align: center;
            margin-bottom: 3rem;
        }
        
        .discover-title {
            font-size: 3rem;
            font-weight: 900;
            background: linear-gradient(135deg, #ffffff 0%, #a855f7 50%, #06b6d4 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 1rem;
        }
        
        .discover-subtitle {
            color: rgba(255, 255, 255, 0.8);
            font-size: 1.2rem;
            max-width: 600px;
            margin: 0 auto;
        }
        
        .filters-section {
            background: var(--glass);
            backdrop-filter: blur(30px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 3rem;
        }
        
        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
        }
        
        .filter-label {
            color: white;
            font-weight: 600;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }
        
        .filter-input, .filter-select {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            padding: 0.75rem;
            color: white;
            font-size: 0.9rem;
            transition: var(--transition);
        }
        
        .filter-input::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }
        
        .filter-input:focus, .filter-select:focus {
            outline: none;
            border-color: rgba(79, 172, 254, 0.5);
            box-shadow: 0 0 15px rgba(79, 172, 254, 0.2);
            background: rgba(255, 255, 255, 0.15);
        }
        
        .filter-select option {
            background: #1a1a2e;
            color: white;
        }
        
        .search-box {
            position: relative;
        }
        
        .search-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.7);
        }
        
        .search-box input {
            padding-left: 3rem;
        }
        
        .filter-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .filter-button {
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
        }
        
        .filter-button:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-xl);
        }
        
        .filter-button.secondary {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid var(--glass-border);
        }
        
        .filter-button.secondary:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        
        .results-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .results-count {
            color: rgba(255, 255, 255, 0.8);
            font-size: 1.1rem;
        }
        
        .sort-select {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            padding: 0.5rem 1rem;
            color: white;
            cursor: pointer;
        }
        
        .events-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }
        
        .event-card {
            background: var(--glass);
            backdrop-filter: blur(30px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 1.5rem;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }
        
        .event-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-xl);
            background: rgba(255, 255, 255, 0.15);
        }
        
        .event-featured {
            border: 2px solid var(--accent);
            box-shadow: 0 0 30px rgba(79, 172, 254, 0.3);
        }
        
        .featured-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: var(--accent);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .event-category {
            background: var(--primary);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 1rem;
        }
        
        .event-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: white;
            margin-bottom: 0.75rem;
            line-height: 1.3;
        }
        
        .event-description {
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.95rem;
            line-height: 1.5;
            margin-bottom: 1rem;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .event-meta {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
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
        
        .event-price {
            background: var(--success);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .event-price.paid {
            background: var(--warning);
        }
        
        .event-actions .btn-small {
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
            border-radius: 10px;
            font-weight: 600;
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
            box-shadow: var(--shadow-lg);
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
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 3rem;
        }
        
        .pagination-item {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid var(--glass-border);
            border-radius: 10px;
            padding: 0.75rem 1rem;
            color: white;
            text-decoration: none;
            transition: var(--transition);
        }
        
        .pagination-item:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }
        
        .pagination-item.active {
            background: var(--primary);
            border-color: var(--primary);
        }
        
        .no-results {
            text-align: center;
            padding: 4rem 2rem;
            color: rgba(255, 255, 255, 0.7);
        }
        
        .no-results-icon {
            font-size: 4rem;
            margin-bottom: 1.5rem;
            opacity: 0.5;
        }
        
        .active-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }
        
        .active-filter {
            background: var(--accent);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.8rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .remove-filter {
            background: none;
            border: none;
            color: white;
            cursor: pointer;
            font-size: 1rem;
            line-height: 1;
        }
        
        @media (max-width: 768px) {
            .discover-container {
                padding-top: 80px;
            }
            
            .discover-title {
                font-size: 2.2rem;
            }
            
            .filters-grid {
                grid-template-columns: 1fr;
            }
            
            .results-header {
                flex-direction: column;
                align-items: stretch;
            }
            
            .events-grid {
                grid-template-columns: 1fr;
            }
            
            .event-meta {
                grid-template-columns: 1fr;
            }
            
            .event-actions {
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
            <a href="/" class="logo" aria-label="Culture Radar - Retour à l'accueil">
                <div class="location-pin-icon" aria-hidden="true"></div>
                Culture Radar
            </a>
            
            <ul class="nav-links" role="menubar">
                <li role="none"><a href="/discover.php" role="menuitem" class="active">Découvrir</a></li>
                <li role="none"><a href="/events.php" role="menuitem">Événements</a></li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li role="none"><a href="/dashboard.php" role="menuitem">Mon Espace</a></li>
                <?php endif; ?>
            </ul>
            
            <div class="nav-actions">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="user-menu">
                        <button class="user-avatar" aria-label="Menu utilisateur">
                            <?php echo strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)); ?>
                        </button>
                        <div class="user-dropdown">
                            <a href="/dashboard.php">Mon tableau de bord</a>
                            <a href="/settings.php">Paramètres</a>
                            <a href="/logout.php">Déconnexion</a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="/login.php" class="btn-secondary">Se connecter</a>
                    <a href="/register.php" class="btn-primary">S'inscrire</a>
                <?php endif; ?>
            </div>
        </nav>
    </header>
    
    <!-- Main Content -->
    <main id="main-content" class="discover-container">
        <div class="container">
            <div class="discover-header">
                <h1 class="discover-title">Découvrir les événements</h1>
                <p class="discover-subtitle">
                    Explorez la richesse culturelle de votre région. Filtrez, recherchez et trouvez l'expérience parfaite.
                </p>
            </div>
            
            <!-- Filters Section -->
            <section class="filters-section">
                <form method="GET" action="/discover.php" class="filters-form">
                    <div class="filters-grid">
                        <div class="filter-group search-box">
                            <label for="search" class="filter-label">Rechercher</label>
                            <i class="fas fa-search search-icon"></i>
                            <input 
                                type="text" 
                                id="search" 
                                name="search" 
                                class="filter-input" 
                                placeholder="Titre, lieu, description..."
                                value="<?php echo htmlspecialchars($filters['search']); ?>"
                            >
                        </div>
                        
                        <div class="filter-group">
                            <label for="category" class="filter-label">Catégorie</label>
                            <select id="category" name="category" class="filter-select">
                                <option value="">Toutes les catégories</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo htmlspecialchars($category); ?>" 
                                            <?php echo $filters['category'] === $category ? 'selected' : ''; ?>>
                                        <?php 
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
                                        echo $categoryNames[$category] ?? ucfirst($category);
                                        ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="city" class="filter-label">Ville</label>
                            <select id="city" name="city" class="filter-select">
                                <option value="">Toutes les villes</option>
                                <?php foreach ($cities as $city): ?>
                                    <option value="<?php echo htmlspecialchars($city); ?>" 
                                            <?php echo $filters['city'] === $city ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($city); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="date_from" class="filter-label">Date de début</label>
                            <input 
                                type="date" 
                                id="date_from" 
                                name="date_from" 
                                class="filter-input"
                                value="<?php echo htmlspecialchars($filters['date_from']); ?>"
                            >
                        </div>
                        
                        <div class="filter-group">
                            <label for="date_to" class="filter-label">Date de fin</label>
                            <input 
                                type="date" 
                                id="date_to" 
                                name="date_to" 
                                class="filter-input"
                                value="<?php echo htmlspecialchars($filters['date_to']); ?>"
                            >
                        </div>
                        
                        <div class="filter-group">
                            <label for="price_max" class="filter-label">Prix maximum</label>
                            <input 
                                type="number" 
                                id="price_max" 
                                name="price_max" 
                                class="filter-input" 
                                placeholder="€"
                                min="0"
                                step="0.50"
                                value="<?php echo htmlspecialchars($filters['price_max']); ?>"
                            >
                        </div>
                    </div>
                    
                    <div style="margin-bottom: 1rem;">
                        <label style="display: flex; align-items: center; gap: 0.5rem; color: white; cursor: pointer;">
                            <input 
                                type="checkbox" 
                                name="is_free" 
                                value="1"
                                <?php echo $filters['is_free'] ? 'checked' : ''; ?>
                                style="width: 18px; height: 18px;"
                            >
                            Événements gratuits uniquement
                        </label>
                    </div>
                    
                    <div class="filter-actions">
                        <button type="submit" class="filter-button">
                            <i class="fas fa-search"></i>
                            Rechercher
                        </button>
                        <a href="/discover.php" class="filter-button secondary">
                            <i class="fas fa-times"></i>
                            Effacer les filtres
                        </a>
                    </div>
                </form>
                
                <!-- Active Filters -->
                <?php if (array_filter($filters)): ?>
                    <div class="active-filters">
                        <?php foreach ($filters as $key => $value): ?>
                            <?php if (!empty($value) && $key !== 'sort'): ?>
                                <span class="active-filter">
                                    <?php 
                                    $filterLabels = [
                                        'category' => 'Catégorie: ' . ($categoryNames[$value] ?? $value),
                                        'city' => 'Ville: ' . $value,
                                        'search' => 'Recherche: ' . $value,
                                        'date_from' => 'À partir du: ' . date('d/m/Y', strtotime($value)),
                                        'date_to' => 'Jusqu\'au: ' . date('d/m/Y', strtotime($value)),
                                        'price_max' => 'Prix max: ' . $value . '€',
                                        'is_free' => 'Gratuit uniquement'
                                    ];
                                    echo $filterLabels[$key] ?? $key . ': ' . $value;
                                    ?>
                                    <button type="button" class="remove-filter" onclick="removeFilter('<?php echo $key; ?>')">×</button>
                                </span>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
            
            <!-- Results -->
            <section class="results-section">
                <div class="results-header">
                    <div class="results-count">
                        <?php if ($totalEvents > 0): ?>
                            <strong><?php echo number_format($totalEvents); ?></strong> événement<?php echo $totalEvents > 1 ? 's' : ''; ?> trouvé<?php echo $totalEvents > 1 ? 's' : ''; ?>
                        <?php else: ?>
                            Aucun événement trouvé
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($totalEvents > 0): ?>
                        <form method="GET" class="sort-form" style="display: inline;">
                            <!-- Preserve current filters -->
                            <?php foreach ($filters as $key => $value): ?>
                                <?php if (!empty($value) && $key !== 'sort'): ?>
                                    <input type="hidden" name="<?php echo $key; ?>" value="<?php echo htmlspecialchars($value); ?>">
                                <?php endif; ?>
                            <?php endforeach; ?>
                            
                            <select name="sort" class="sort-select" onchange="this.form.submit()">
                                <option value="relevance" <?php echo $filters['sort'] === 'relevance' ? 'selected' : ''; ?>>Pertinence</option>
                                <option value="date_asc" <?php echo $filters['sort'] === 'date_asc' ? 'selected' : ''; ?>>Date (plus proche)</option>
                                <option value="date_desc" <?php echo $filters['sort'] === 'date_desc' ? 'selected' : ''; ?>>Date (plus lointaine)</option>
                                <option value="price_asc" <?php echo $filters['sort'] === 'price_asc' ? 'selected' : ''; ?>>Prix (croissant)</option>
                                <option value="price_desc" <?php echo $filters['sort'] === 'price_desc' ? 'selected' : ''; ?>>Prix (décroissant)</option>
                                <option value="title" <?php echo $filters['sort'] === 'title' ? 'selected' : ''; ?>>Titre (A-Z)</option>
                            </select>
                        </form>
                    <?php endif; ?>
                </div>
                
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($events)): ?>
                    <div class="events-grid">
                        <?php foreach ($events as $event): ?>
                            <article class="event-card <?php echo $event['event_type'] === 'featured' ? 'event-featured' : ''; ?>">
                                <?php if ($event['featured']): ?>
                                    <div class="featured-badge">✨ Mis en avant</div>
                                <?php endif; ?>
                                
                                <div class="event-category">
                                    <?php 
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
                                        <i class="fas fa-clock"></i>
                                        <?php echo date('H:i', strtotime($event['start_date'])); ?>
                                    </div>
                                    
                                    <div class="event-meta-item">
                                        <i class="fas fa-city"></i>
                                        <?php echo htmlspecialchars($event['city']); ?>
                                    </div>
                                </div>
                                
                                <div class="event-actions">
                                    <div class="event-price <?php echo $event['is_free'] ? '' : 'paid'; ?>">
                                        <?php echo $event['is_free'] ? 'Gratuit' : number_format($event['price'], 2) . ' €'; ?>
                                    </div>
                                    
                                    <div>
                                        <?php if (isset($_SESSION['user_id'])): ?>
                                            <button class="btn-small btn-outline" onclick="saveEvent(<?php echo $event['id']; ?>)">
                                                <i class="fas fa-heart"></i>
                                            </button>
                                        <?php endif; ?>
                                        <a href="/event.php?id=<?php echo $event['id']; ?>" class="btn-small btn-primary">
                                            Voir détails
                                        </a>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <nav class="pagination" aria-label="Navigation par pages">
                            <?php
                            $baseUrl = '/discover.php?' . http_build_query(array_filter($filters));
                            $baseUrl .= empty($filters) ? '?' : '&';
                            ?>
                            
                            <?php if ($page > 1): ?>
                                <a href="<?php echo $baseUrl; ?>page=<?php echo $page - 1; ?>" class="pagination-item">
                                    <i class="fas fa-chevron-left"></i> Précédent
                                </a>
                            <?php endif; ?>
                            
                            <?php
                            $startPage = max(1, $page - 2);
                            $endPage = min($totalPages, $page + 2);
                            
                            for ($i = $startPage; $i <= $endPage; $i++):
                            ?>
                                <a href="<?php echo $baseUrl; ?>page=<?php echo $i; ?>" 
                                   class="pagination-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <a href="<?php echo $baseUrl; ?>page=<?php echo $page + 1; ?>" class="pagination-item">
                                    Suivant <i class="fas fa-chevron-right"></i>
                                </a>
                            <?php endif; ?>
                        </nav>
                    <?php endif; ?>
                    
                <?php else: ?>
                    <div class="no-results">
                        <div class="no-results-icon">🔍</div>
                        <h3>Aucun événement trouvé</h3>
                        <p>Essayez de modifier vos critères de recherche ou explorez toutes les catégories.</p>
                        <a href="/discover.php" class="btn-primary" style="margin-top: 1rem;">
                            Voir tous les événements
                        </a>
                    </div>
                <?php endif; ?>
            </section>
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
        
        // Remove filter function
        function removeFilter(filterKey) {
            const url = new URL(window.location);
            url.searchParams.delete(filterKey);
            window.location.href = url.toString();
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
    </script>
    <script src="assets/js/main.js"></script>
</body>
</html>