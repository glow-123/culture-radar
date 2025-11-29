<?php
// Sécurité HTTP headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: no-referrer');

session_start();

// Load configuration
require_once __DIR__ . '/config.php';

// Try to load OpenAgenda service if it exists
$hasOpenAgenda = false;
if (file_exists(__DIR__ . '/services/OpenAgendaService.php')) {
    require_once __DIR__ . '/services/OpenAgendaService.php';
    $hasOpenAgenda = true;
}

// Initialize database connection
try {
    $dbConfig = Config::database();
    $dsn = "mysql:host=" . $dbConfig['host'] . ";port=" . $dbConfig['port'] . ";dbname=" . $dbConfig['name'] . ";charset=" . $dbConfig['charset'];
    $pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    // Database doesn't exist, we'll create it later
    $pdo = null;
}

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$userName = $isLoggedIn ? ($_SESSION['user_name'] ?? 'Utilisateur') : '';

// Fetch real events from different cities
$realEvents = [];
$cities = ['Paris', 'Lyon', 'Bordeaux', 'Toulouse'];

if ($hasOpenAgenda) {
    try {
        $openAgendaService = new OpenAgendaService();
        
        foreach ($cities as $city) {
            $cityEvents = $openAgendaService->getEventsByLocation([
                'city' => $city,
                'additional' => ['size' => 1] // Get 1 event per city
            ]);
            
            if (!empty($cityEvents)) {
                $event = $cityEvents[0];
                $event['display_city'] = $city; // Add city for display
                $realEvents[] = $event;
            }
        }
        
        // If we don't have 4 events, fill with more from Paris
        while (count($realEvents) < 4) {
            $parisEvents = $openAgendaService->getEventsByLocation([
                'city' => 'Paris',
                'additional' => ['size' => 4]
            ]);
            
            foreach ($parisEvents as $event) {
                if (count($realEvents) >= 4) break;
                
                // Check if we already have this event
                $eventExists = false;
                foreach ($realEvents as $existingEvent) {
                    if ($existingEvent['id'] === $event['id']) {
                        $eventExists = true;
                        break;
                    }
                }
                
                if (!$eventExists) {
                    $event['display_city'] = 'Paris';
                    $realEvents[] = $event;
                }
            }
            break; // Prevent infinite loop
        }
        
    } catch (Exception $e) {
        error_log("Error fetching events for landing page: " . $e->getMessage());
        // Fallback to demo events will be used
    }
}

// Demo events as fallback
$demoEvents = [
    [
        'id' => 'demo-1',
        'title' => 'Concert de Jazz au Sunset',
        'description' => 'Une soirée jazz exceptionnelle avec des artistes internationaux',
        'date_start' => date('Y-m-d'),
        'time' => '21:00',
        'venue_name' => 'Le Sunset-Sunside',
        'address' => '60 Rue des Lombards',
        'city' => 'Paris',
        'display_city' => 'Paris',
        'is_free' => false,
        'price' => 25,
        'category' => 'concert',
        'image' => 'https://images.unsplash.com/photo-1415201364774-f6f0bb35f28f?w=400'
    ],
    [
        'id' => 'demo-2',
        'title' => 'Exposition Monet',
        'description' => 'Les Nymphéas de Claude Monet',
        'date_start' => date('Y-m-d', strtotime('+1 day')),
        'time' => '10:00 - 18:00',
        'venue_name' => 'Musée de l\'Orangerie',
        'address' => 'Jardin des Tuileries',
        'city' => 'Paris',
        'display_city' => 'Paris',
        'is_free' => false,
        'price' => 12,
        'category' => 'exposition',
        'image' => 'https://images.unsplash.com/photo-1554907984-15263bfd63bd?w=400'
    ],
    [
        'id' => 'demo-3',
        'title' => 'Théâtre: Le Malade Imaginaire',
        'description' => 'La célèbre pièce de Molière',
        'date_start' => date('Y-m-d', strtotime('+2 days')),
        'time' => '20:00',
        'venue_name' => 'Comédie-Française',
        'address' => '1 Place Colette',
        'city' => 'Paris',
        'display_city' => 'Paris',
        'is_free' => false,
        'price' => 35,
        'category' => 'théâtre',
        'image' => 'https://images.unsplash.com/photo-1503095396549-807759245b35?w=400'
    ],
    [
        'id' => 'demo-4',
        'title' => 'Festival de Street Art',
        'description' => 'Découvrez les artistes urbains du moment',
        'date_start' => date('Y-m-d', strtotime('+3 days')),
        'time' => 'Toute la journée',
        'venue_name' => 'Belleville',
        'address' => 'Quartier Belleville',
        'city' => 'Paris',
        'display_city' => 'Paris',
        'is_free' => true,
        'price' => 0,
        'category' => 'festival',
        'image' => 'https://images.unsplash.com/photo-1499781350541-7783f6c6a0c8?w=400'
    ]
];

// Use demo events if no real events
if (empty($realEvents)) {
    $realEvents = $demoEvents;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- SEO & Meta -->
    <title>Culture Radar - Votre boussole culturelle intelligente | Découverte culturelle IA</title>
    <meta name="description" content="Découvrez les trésors culturels cachés de votre ville avec Culture Radar. Intelligence artificielle + géolocalisation pour des recommandations culturelles personnalisées. 50,000+ explorateurs nous font confiance.">
    <meta name="keywords" content="culture, événements, intelligence artificielle, recommandations culturelles, spectacles, expositions, géolocalisation culture">
    
    <!-- AMÉLIORATION RATTRAPAGE GLORIA: Canonical URL pour éviter le duplicate content (SEO) -->
    <link rel="canonical" href="https://culture-radar.fr/">
    
    <!-- Open Graph / Social - Amélioré pour meilleur partage réseaux sociaux -->
    <meta property="og:title" content="Culture Radar - Votre boussole culturelle révolutionnaire">
    <meta property="og:description" content="L'IA qui révolutionne la découverte culturelle. Trouvez instantanément les événements qui vous correspondent.">
    <meta property="og:image" content="/assets/og-image.jpg">
    <meta property="og:url" content="https://culture-radar.fr/">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="fr_FR">
    <meta property="og:site_name" content="Culture Radar">
    
    <!-- Twitter Card - AMÉLIORATION RATTRAPAGE GLORIA -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Culture Radar - Votre boussole culturelle">
    <meta name="twitter:description" content="Découvrez les événements culturels qui vous correspondent grâce à l'IA.">
    
    <?php if (file_exists(__DIR__ . '/includes/favicon.php')): ?>
        <?php include 'includes/favicon.php'; ?>
    <?php else: ?>
        <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
        <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <?php endif; ?>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800;900&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Styles -->
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/accessibility.css">
    
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- UNIVERSITY PROJECT NOTICE -->
    <div style="background: linear-gradient(90deg, #8B5CF6, #3B82F6); color: white; text-align: center; padding: 0.5rem; font-size: 0.9rem; font-weight: 500; position: relative; z-index: 9999;">
        🎓 Projet Universitaire - Site de démonstration à des fins éducatives uniquement
    </div>
    
    <!-- Skip to content for accessibility -->
    <a href="#main-content" class="skip-to-content">Aller au contenu principal</a>
    
    <!-- Animated Background -->
    <div class="animated-bg" aria-hidden="true">
        <div class="stars"></div>
        <div class="floating-shapes"></div>
    </div>
    
    <!-- Header -->
    <header class="header" role="banner">
        <nav class="nav" role="navigation" aria-label="Navigation principale" itemscope itemtype="http://schema.org/SiteNavigationElement">
            <a href="/" class="logo" aria-label="Culture Radar - Retour à l'accueil">
                <div class="location-pin-icon" aria-hidden="true"></div>
                Culture Radar
            </a>
            
            <ul class="nav-links" role="menubar">
                <li role="none"><a href="#discover" role="menuitem">Découvrir</a></li>
                <li role="none"><a href="#categories" role="menuitem">Catégories</a></li>
                <li role="none"><a href="#features" role="menuitem">Fonctionnalités</a></li>
                <li role="none"><a href="#how" role="menuitem">Comment ça marche</a></li>
                <li role="none"><a href="/contact.php" role="menuitem">Contact</a></li>
                <?php if($isLoggedIn): ?>
                    <li role="none"><a href="/dashboard.php" role="menuitem">Mon Espace</a></li>
                <?php endif; ?>
            </ul>
            
            <div class="nav-actions">
                <?php if($isLoggedIn): ?>
                    <div class="user-menu">
                        <button class="user-avatar" aria-label="Menu utilisateur">
                            <?php echo substr($userName, 0, 1); ?>
                        </button>
                        <div class="user-dropdown">
                            <a href="/dashboard.php">Mon tableau de bord</a>
                            <a href="/settings.php">Paramètres</a>
                            <a href="/logout.php">Déconnexion</a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="/login-simple.php" class="btn-secondary">Connexion</a>
                    <a href="/register.php" class="cta-button">Commencer</a>
                <?php endif; ?>
            </div>
            
            <button class="mobile-menu-toggle" aria-label="Menu mobile">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </nav>
    </header>
    
    <!-- Main Content -->
    <main id="main-content">
        <!-- Hero Section -->
        <section class="hero" role="banner" aria-labelledby="hero-title">
            <div class="hero-content">
                <div class="hero-badge" role="text">
                    <i class="fas fa-sparkles"></i> Intelligence artificielle culturelle
                </div>
                
                <h1 id="hero-title" class="font-bold">
                    Votre boussole culturelle<br>
                    <span class="gradient-text">révolutionnaire</span>
                </h1>
                
                <p class="hero-subtitle">
                    Découvrez les trésors culturels cachés de votre ville grâce à l'intelligence artificielle. 
                    Des recommandations ultra-personnalisées qui transforment votre exploration urbaine.
                </p>
                
                <div class="hero-cta">
                    <?php if($isLoggedIn): ?>
                        <a href="/discover.php" class="btn-primary">
                            <i class="fas fa-compass"></i> Explorer maintenant
                        </a>
                        <a href="/dashboard.php" class="btn-secondary">
                            <i class="fas fa-user"></i> Mon espace
                        </a>
                    <?php else: ?>
                        <a href="/register.php" class="btn-primary">
                            <i class="fas fa-rocket"></i> Découvrir maintenant
                        </a>
                        <a href="#demo" class="btn-secondary">
                            <i class="fas fa-play"></i> Voir la démo
                        </a>
                    <?php endif; ?>
                </div>
                
                <!-- Live Demo Preview -->
                <div class="hero-demo" role="region" aria-labelledby="demo-title">
                    <div class="demo-header">
                        <h2 id="demo-title" class="sr-only">Aperçu en temps réel</h2>
                        <span class="location-tag">
                            <i class="fas fa-map-marker-alt"></i> 
                            <?php if (!empty($realEvents) && $hasOpenAgenda): ?>
                                Événements en temps réel • <?php echo count(array_unique(array_column($realEvents, 'display_city'))); ?> villes
                            <?php else: ?>
                                Paris • Événements de démonstration
                            <?php endif; ?>
                        </span>
                        <span class="time-tag">Personnalisé pour vous</span>
                    </div>
                    
                    <div class="demo-events" role="list">
                        <?php foreach (array_slice($realEvents, 0, 4) as $index => $event): ?>
                            <?php 
                            $categoryIcons = [
                                'musique' => '🎵',
                                'concert' => '🎷',
                                'théâtre' => '🎭',
                                'theater' => '🎭',
                                'exposition' => '🎨',
                                'art' => '🎨',
                                'danse' => '💃',
                                'cinéma' => '🎬',
                                'festival' => '🎪',
                                'conférence' => '🎤'
                            ];
                            
                            $category = $event['category'] ?? 'culture';
                            $icon = $categoryIcons[strtolower($category)] ?? '🎯';
                            $priceText = isset($event['is_free']) && $event['is_free'] ? 'Gratuit' : 
                                        (isset($event['price']) && $event['price'] ? $event['price'] . '€' : 'Prix libre');
                            $venue = $event['venue_name'] ?? ($event['venue'] ?? 'Lieu culturel');
                            $city = $event['display_city'] ?? ($event['city'] ?? 'Paris');
                            ?>
                            <div class="event-card demo-event" role="listitem">
                                <div class="event-category-tag"><?php echo ucfirst($category); ?></div>
                                <h3 class="event-title">
                                    <?php echo $icon . ' ' . htmlspecialchars(substr($event['title'], 0, 40)) . (strlen($event['title']) > 40 ? '...' : ''); ?>
                                </h3>
                                <div class="event-meta">
                                    <span><i class="fas fa-location-dot"></i> <?php echo htmlspecialchars($venue . ', ' . $city); ?></span>
                                    <span><i class="fas fa-euro-sign"></i> <?php echo $priceText; ?></span>
                                    <?php if (!empty($event['date_start'])): ?>
                                        <span><i class="fas fa-calendar"></i> <?php echo date('d/m', strtotime($event['date_start'])); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </section>
        
        <!-- Quick Search Section -->
        <section class="quick-search" id="discover">
            <div class="container">
                <h2 class="section-title">Que cherchez-vous aujourd'hui ?</h2>
                
                <form class="search-form" action="/search.php" method="GET">
                    <div class="search-input-group">
                        <input type="text" name="q" placeholder="Rechercher un événement, un lieu, un artiste..." 
                               class="search-input" aria-label="Recherche">
                        <button type="submit" class="search-button" aria-label="Rechercher">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                    
                    <div class="quick-filters">
                        <button type="button" class="filter-chip active" data-filter="all">Tout</button>
                        <button type="button" class="filter-chip" data-filter="today">Aujourd'hui</button>
                        <button type="button" class="filter-chip" data-filter="weekend">Ce week-end</button>
                        <button type="button" class="filter-chip" data-filter="free">Gratuit</button>
                        <button type="button" class="filter-chip" data-filter="nearby">À proximité</button>
                    </div>
                </form>
                
                <!-- Événements populaires -->
                <div class="popular-events" style="margin-top: 3rem;">
                    <h3 style="text-align: center; margin-bottom: 2rem; color: #333;">Événements populaires aujourd'hui</h3>
                    <div class="events-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.5rem;">
                        <?php 
                        // Afficher les premiers événements
                        $popularEvents = array_slice($realEvents, 0, 8);
                        foreach ($popularEvents as $event): 
                            $category = $event['category'] ?? 'culture';
                            $priceText = isset($event['is_free']) && $event['is_free'] ? 'Gratuit' : 
                                        (isset($event['price']) && $event['price'] ? $event['price'] . '€' : 'Prix libre');
                            $venue = $event['venue_name'] ?? ($event['venue'] ?? 'Lieu culturel');
                            $city = $event['display_city'] ?? ($event['city'] ?? 'Paris');
                        ?>
                        <div class="event-card" style="background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); transition: transform 0.3s; cursor: pointer;">
                            <?php 
                            $imageUrl = $event['image'] ?? '';
                            // Vérifier si c'est une URL valide
                            if (!empty($imageUrl) && (strpos($imageUrl, 'http') === 0 || strpos($imageUrl, '//') === 0)): 
                            ?>
                            <div class="event-image" style="height: 150px; background-image: url('<?php echo htmlspecialchars($imageUrl); ?>'); background-size: cover; background-position: center;">
                            </div>
                            <?php else: ?>
                            <div class="event-image" style="height: 150px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-calendar-alt" style="font-size: 3rem; color: white;"></i>
                            </div>
                            <?php endif; ?>
                            <div class="event-content" style="padding: 1.5rem;">
                                <span class="event-category" style="display: inline-block; padding: 0.25rem 0.75rem; background: #f0f0ff; color: #667eea; border-radius: 20px; font-size: 0.8rem; margin-bottom: 0.5rem;">
                                    <?php echo ucfirst($category); ?>
                                </span>
                                <h4 style="margin: 0.5rem 0; color: #333; font-size: 1.1rem;">
                                    <?php echo htmlspecialchars(substr($event['title'], 0, 50)) . (strlen($event['title']) > 50 ? '...' : ''); ?>
                                </h4>
                                <div class="event-meta" style="display: flex; flex-direction: column; gap: 0.5rem; margin-top: 1rem; color: #666; font-size: 0.9rem;">
                                    <span><i class="fas fa-map-marker-alt" style="width: 20px;"></i> <?php echo htmlspecialchars($venue); ?></span>
                                    <span><i class="fas fa-euro-sign" style="width: 20px;"></i> <?php echo $priceText; ?></span>
                                    <?php if (!empty($event['date_start'])): ?>
                                    <span><i class="fas fa-calendar" style="width: 20px;"></i> <?php echo date('d/m H:i', strtotime($event['date_start'])); ?></span>
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
                    
                    <div style="text-align: center; margin-top: 2rem;">
                        <a href="/discover.php" class="btn-primary" style="display: inline-block; padding: 0.75rem 2rem; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; text-decoration: none; border-radius: 50px; font-weight: 600;">
                            Voir tous les événements →
                        </a>
                    </div>
                </div>
            </div>
        </section>
        
        <!-- Categories Section -->
        <section id="categories" class="categories-section" role="region" aria-labelledby="categories-title">
            <div class="container">
                <div class="section-header">
                    <h2 id="categories-title" class="section-title">Explorer par catégorie</h2>
                    <p class="section-subtitle">Découvrez la richesse culturelle de votre ville</p>
                </div>
                
                <div class="categories-grid">
                    <a href="explore.php?category=theater" class="category-card theater">
                        <div class="category-icon">🎭</div>
                        <div class="category-info">
                            <h3>Spectacles & Théâtre</h3>
                            <p>Pièces, comédies musicales, one-man-shows</p>
                        </div>
                        <div class="category-arrow">→</div>
                    </a>
                    
                    <a href="explore.php?category=music" class="category-card music">
                        <div class="category-icon">🎵</div>
                        <div class="category-info">
                            <h3>Musique & Concerts</h3>
                            <p>Jazz, classique, électro, world music</p>
                        </div>
                        <div class="category-arrow">→</div>
                    </a>
                    
                    <a href="explore.php?category=museum" class="category-card museum">
                        <div class="category-icon">🖼️</div>
                        <div class="category-info">
                            <h3>Expositions & Musées</h3>
                            <p>Art contemporain, collections permanentes</p>
                        </div>
                        <div class="category-arrow">→</div>
                    </a>
                    
                    <a href="explore.php?category=heritage" class="category-card heritage">
                        <div class="category-icon">🏛️</div>
                        <div class="category-info">
                            <h3>Patrimoine & Visites</h3>
                            <p>Histoire, architecture, visites guidées</p>
                        </div>
                        <div class="category-arrow">→</div>
                    </a>
                    
                    <a href="explore.php?category=cinema" class="category-card cinema">
                        <div class="category-icon">🎬</div>
                        <div class="category-info">
                            <h3>Cinéma & Projections</h3>
                            <p>Films d'auteur, séances spéciales</p>
                        </div>
                        <div class="category-arrow">→</div>
                    </a>
                    
                    <a href="explore.php?category=workshop" class="category-card workshop">
                        <div class="category-icon">🎨</div>
                        <div class="category-info">
                            <h3>Ateliers & Rencontres</h3>
                            <p>Cours, masterclass, échanges culturels</p>
                        </div>
                        <div class="category-arrow">→</div>
                    </a>
                </div>
            </div>
        </section>
        
        <!-- Features Section -->
        <section id="features" class="features" role="region" aria-labelledby="features-title">
            <div class="container">
                <div class="section-header">
                    <h2 id="features-title" class="section-title scroll-reveal">
                        Pourquoi Culture Radar révolutionne la découverte ?
                    </h2>
                    <p class="section-subtitle scroll-reveal">
                        Une approche innovante qui combine IA, données ouvertes et passion culturelle
                    </p>
                </div>
                
                <div class="features-grid">
                    <article class="feature-card scroll-reveal">
                        <div class="feature-icon">
                            <i class="fas fa-brain"></i>
                        </div>
                        <h3 class="feature-title">IA Prédictive Avancée</h3>
                        <p class="feature-description">
                            Notre algorithme analyse vos préférences et votre contexte pour prédire 
                            avec précision les expériences culturelles qui vous passionneront.
                        </p>
                    </article>
                    
                    <article class="feature-card scroll-reveal">
                        <div class="feature-icon">
                            <i class="fas fa-gem"></i>
                        </div>
                        <h3 class="feature-title">Trésors Cachés</h3>
                        <p class="feature-description">
                            Découvrez les pépites culturelles invisibles : galeries indépendantes, 
                            concerts secrets, expositions confidentielles hors des circuits touristiques.
                        </p>
                    </article>
                    
                    <article class="feature-card scroll-reveal">
                        <div class="feature-icon">
                            <i class="fas fa-bolt"></i>
                        </div>
                        <h3 class="feature-title">Temps Réel Intelligent</h3>
                        <p class="feature-description">
                            Adaptation instantanée selon la météo, les transports, vos disponibilités 
                            et même votre humeur du moment pour des suggestions toujours pertinentes.
                        </p>
                    </article>
                    
                    <article class="feature-card scroll-reveal">
                        <div class="feature-icon">
                            <i class="fas fa-universal-access"></i>
                        </div>
                        <h3 class="feature-title">Inclusion Universelle</h3>
                        <p class="feature-description">
                            Accessibilité complète avec informations PMR, audiodescription, 
                            langue des signes et adaptation aux besoins spécifiques.
                        </p>
                    </article>
                    
                    <article class="feature-card scroll-reveal">
                        <div class="feature-icon">
                            <i class="fas fa-sparkles"></i>
                        </div>
                        <h3 class="feature-title">Curateur Personnel</h3>
                        <p class="feature-description">
                            Votre assistant culturel apprend continuellement de vos goûts pour 
                            vous surprendre avec des découvertes alignées sur vos passions.
                        </p>
                    </article>
                    
                    <article class="feature-card scroll-reveal">
                        <div class="feature-icon">
                            <i class="fas fa-network-wired"></i>
                        </div>
                        <h3 class="feature-title">Écosystème Complet</h3>
                        <p class="feature-description">
                            Connectez-vous avec d'autres passionnés, partagez vos découvertes 
                            et créez votre communauté culturelle personnalisée.
                        </p>
                    </article>
                </div>
            </div>
        </section>
        
        <!-- How it Works -->
        <section id="how" class="how-it-works" role="region" aria-labelledby="how-title">
            <div class="container">
                <div class="section-header">
                    <h2 id="how-title" class="section-title scroll-reveal">La magie en 3 étapes</h2>
                    <p class="section-subtitle scroll-reveal">
                        Simple, rapide et terriblement efficace
                    </p>
                </div>
                
                <div class="steps">
                    <article class="step scroll-reveal">
                        <div class="step-number">1</div>
                        <h3 class="step-title">Créez votre profil culturel</h3>
                        <p class="step-description">
                            En 2 minutes, notre questionnaire intelligent comprend vos goûts, 
                            vos envies et vos contraintes pour créer votre ADN culturel unique.
                        </p>
                    </article>
                    
                    <article class="step scroll-reveal">
                        <div class="step-number">2</div>
                        <h3 class="step-title">Recevez vos recommandations</h3>
                        <p class="step-description">
                            L'IA analyse en temps réel des milliers d'événements pour vous proposer 
                            exactement ce qui vous correspond, où que vous soyez.
                        </p>
                    </article>
                    
                    <article class="step scroll-reveal">
                        <div class="step-number">3</div>
                        <h3 class="step-title">Vivez et partagez</h3>
                        <p class="step-description">
                            Réservez en un clic, invitez vos amis et enrichissez votre carnet culturel. 
                            Plus vous explorez, plus les suggestions deviennent précises.
                        </p>
                    </article>
                </div>
            </div>
        </section>
        
        <!-- Stats Section -->
        <section class="stats-section">
            <div class="container">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number" data-count="50000">0</div>
                        <div class="stat-label">Explorateurs actifs</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number" data-count="12000">0</div>
                        <div class="stat-label">Événements référencés</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number" data-count="95">0</div>
                        <div class="stat-label">% de satisfaction</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number" data-count="24">0</div>
                        <div class="stat-label">Villes couvertes</div>
                    </div>
                </div>
            </div>
        </section>
        
        <!-- CTA Section -->
        <section class="cta-section" role="region" aria-labelledby="cta-title">
            <div class="container">
                <h2 id="cta-title">Prêt pour une révolution culturelle ?</h2>
                <p>Rejoignez les milliers d'explorateurs qui redécouvrent leur ville</p>
                <a href="/register.php" class="btn-white">
                    <i class="fas fa-rocket"></i> Commencer l'aventure
                </a>
            </div>
        </section>
    </main>
    
    <!-- Footer -->
    <footer id="footer" class="footer" role="contentinfo">
        <div class="footer-content">
            <div class="footer-section">
                <h3>Culture Radar</h3>
                <p>La révolution de la découverte culturelle. Votre boussole intelligente vers l'art, 
                   la culture et l'émerveillement.</p>
                <div class="social-links">
                    <a href="#" aria-label="Facebook"><i class="fab fa-facebook"></i></a>
                    <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                    <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                    <a href="#" aria-label="LinkedIn"><i class="fab fa-linkedin"></i></a>
                </div>
            </div>
            
            <div class="footer-section">
                <h3>Découvrir</h3>
                <a href="/events.php">Tous les événements</a>
                <a href="/venues.php">Lieux culturels</a>
                <a href="/artists.php">Artistes</a>
                <a href="/calendar.php">Calendrier</a>
            </div>
            
            <div class="footer-section">
                <h3>Ressources</h3>
                <a href="/about.php">À propos</a>
                <a href="/contact.php">Contact</a>
                <a href="/help.php">Centre d'aide</a>
                <a href="/blog.php">Blog</a>
                <a href="/partners.php">Partenaires</a>
            </div>
            
            <div class="footer-section">
                <h3>Légal</h3>
                <a href="/privacy.php">Confidentialité</a>
                <a href="/terms.php">Conditions d'utilisation</a>
                <a href="/legal.php">Mentions légales</a>
                <a href="/cookies.php">Cookies</a>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p>&copy; 2024 Culture Radar - Projet Universitaire</p>
            <p style="font-size: 0.85rem; opacity: 0.8; margin-top: 0.5rem;">
                Ce site est un projet académique réalisé dans le cadre d'études universitaires. 
                Aucune transaction réelle n'est effectuée. À des fins éducatives uniquement.
            </p>
        </div>
    </footer>
    
    <!-- Cookie Banner -->
    <div id="cookie-banner" class="cookie-banner" role="dialog" aria-labelledby="cookie-title">
        <div class="cookie-content">
            <h3 id="cookie-title">🍪 Respect de votre vie privée</h3>
            <p>Nous utilisons des cookies pour améliorer votre expérience et personnaliser vos recommandations culturelles.</p>
            <div class="cookie-buttons">
                <button onclick="acceptAllCookies()" class="btn-primary">Accepter tout</button>
                <button onclick="rejectCookies()" class="btn-secondary">Refuser</button>
                <a href="/privacy.php" class="cookie-link">Personnaliser</a>
            </div>
        </div>
    </div>
    
    <!-- Scripts -->
    <script src="assets/js/main.js"></script>
    <script src="assets/js/accessibility.js"></script>
    <script>
    // Gestion des filtres de recherche
    document.addEventListener('DOMContentLoaded', function() {
        const filterChips = document.querySelectorAll('.filter-chip');
        const searchForm = document.querySelector('.search-form');
        
        filterChips.forEach(chip => {
            chip.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Retirer la classe active de tous les filtres
                filterChips.forEach(c => c.classList.remove('active'));
                
                // Ajouter la classe active au filtre cliqué
                this.classList.add('active');
                
                // Récupérer le filtre
                const filter = this.getAttribute('data-filter');
                
                // Soumettre le formulaire avec le filtre
                const input = searchForm.querySelector('input[name="q"]');
                const filterInput = document.createElement('input');
                filterInput.type = 'hidden';
                filterInput.name = 'filter';
                filterInput.value = filter;
                searchForm.appendChild(filterInput);
                searchForm.submit();
            });
        });
        
        // Animation des cartes d'événements
        const eventCards = document.querySelectorAll('.event-card');
        eventCards.forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-5px)';
                this.style.boxShadow = '0 4px 20px rgba(0,0,0,0.15)';
            });
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = '0 2px 10px rgba(0,0,0,0.1)';
            });
        });
    });
    </script>
</body>
</html>