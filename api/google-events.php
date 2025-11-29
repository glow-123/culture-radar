<?php
/**
 * Google Events API via SerpAPI
 * Récupère les événements depuis Google Events
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../config.php';

// Récupérer la clé SerpAPI depuis la config
$serpApiKey = Config::get('SERPAPI_KEY', '');

// Paramètres de recherche
$location = $_GET['location'] ?? 'Paris, France';
$query = $_GET['query'] ?? 'événements culturels';
$limit = intval($_GET['limit'] ?? 20);
$date = $_GET['date'] ?? 'today';

// Si pas de clé API, retourner des événements de démonstration
if (empty($serpApiKey)) {
    $demoEvents = [
        [
            'id' => 'google-1',
            'title' => 'Festival de Jazz de Paris',
            'description' => 'Un festival de jazz exceptionnel dans la capitale',
            'venue' => 'La Villette',
            'city' => 'Paris',
            'date' => date('Y-m-d'),
            'time' => '20:00',
            'price' => 25,
            'is_free' => false,
            'category' => 'concert',
            'source' => 'Google Events (Demo)',
            'link' => '#'
        ],
        [
            'id' => 'google-2',
            'title' => 'Exposition Art Contemporain',
            'description' => 'Découvrez les artistes émergents',
            'venue' => 'Centre Pompidou',
            'city' => 'Paris',
            'date' => date('Y-m-d', strtotime('+1 day')),
            'time' => '10:00',
            'price' => 0,
            'is_free' => true,
            'category' => 'exposition',
            'source' => 'Google Events (Demo)',
            'link' => '#'
        ],
        [
            'id' => 'google-3',
            'title' => 'Pièce de Théâtre: Hamlet',
            'description' => 'La célèbre pièce de Shakespeare',
            'venue' => 'Théâtre de l\'Odéon',
            'city' => 'Paris',
            'date' => date('Y-m-d', strtotime('+2 days')),
            'time' => '19:30',
            'price' => 35,
            'is_free' => false,
            'category' => 'théâtre',
            'source' => 'Google Events (Demo)',
            'link' => '#'
        ],
        [
            'id' => 'google-4',
            'title' => 'Marché de Noël',
            'description' => 'Artisanat et gastronomie locale',
            'venue' => 'Champs-Élysées',
            'city' => 'Paris',
            'date' => date('Y-m-d', strtotime('+3 days')),
            'time' => 'Toute la journée',
            'price' => 0,
            'is_free' => true,
            'category' => 'marché',
            'source' => 'Google Events (Demo)',
            'link' => '#'
        ]
    ];
    
    echo json_encode([
        'success' => true,
        'source' => 'demo',
        'message' => 'Mode démonstration - Configurez SERPAPI_KEY pour des événements réels',
        'events' => array_slice($demoEvents, 0, $limit),
        'total' => count($demoEvents)
    ]);
    exit;
}

// Construction de l'URL SerpAPI
$params = [
    'api_key' => $serpApiKey,
    'engine' => 'google_events',
    'q' => $query,
    'location' => $location,
    'hl' => 'fr',
    'gl' => 'fr'
];

// Ajouter le filtre de date si spécifié
if ($date === 'today') {
    $params['htichips'] = 'date:today';
} elseif ($date === 'tomorrow') {
    $params['htichips'] = 'date:tomorrow';
} elseif ($date === 'week') {
    $params['htichips'] = 'date:week';
} elseif ($date === 'weekend') {
    $params['htichips'] = 'date:weekend';
} elseif ($date === 'month') {
    $params['htichips'] = 'date:month';
}

$url = 'https://serpapi.com/search.json?' . http_build_query($params);

// Faire la requête
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200 || !$response) {
    echo json_encode([
        'success' => false,
        'error' => 'Erreur lors de la récupération des événements',
        'http_code' => $httpCode
    ]);
    exit;
}

$data = json_decode($response, true);

if (!isset($data['events_results'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Aucun événement trouvé',
        'debug' => $data
    ]);
    exit;
}

// Formater les événements
$events = [];
foreach ($data['events_results'] as $event) {
    // Extraire le prix si disponible
    $price = 0;
    $isFree = true;
    if (isset($event['ticket_info'])) {
        foreach ($event['ticket_info'] as $ticket) {
            if (isset($ticket['price'])) {
                $priceText = $ticket['price'];
                // Essayer d'extraire un nombre
                if (preg_match('/(\d+)/', $priceText, $matches)) {
                    $price = intval($matches[1]);
                    $isFree = false;
                } elseif (stripos($priceText, 'gratuit') !== false || stripos($priceText, 'free') !== false) {
                    $isFree = true;
                }
                break;
            }
        }
    }
    
    // Déterminer la catégorie
    $title = strtolower($event['title'] ?? '');
    $description = strtolower($event['description'] ?? '');
    $category = 'culture';
    
    if (strpos($title, 'concert') !== false || strpos($title, 'musique') !== false) {
        $category = 'concert';
    } elseif (strpos($title, 'exposition') !== false || strpos($title, 'expo') !== false) {
        $category = 'exposition';
    } elseif (strpos($title, 'théâtre') !== false || strpos($title, 'pièce') !== false) {
        $category = 'théâtre';
    } elseif (strpos($title, 'festival') !== false) {
        $category = 'festival';
    } elseif (strpos($title, 'cinéma') !== false || strpos($title, 'film') !== false) {
        $category = 'cinéma';
    } elseif (strpos($title, 'danse') !== false || strpos($title, 'ballet') !== false) {
        $category = 'danse';
    }
    
    $events[] = [
        'id' => 'google-' . ($event['event_id'] ?? uniqid()),
        'title' => $event['title'] ?? 'Événement',
        'description' => $event['description'] ?? '',
        'venue' => $event['venue']['name'] ?? $event['address'][0] ?? 'Lieu non spécifié',
        'address' => implode(', ', $event['address'] ?? []),
        'city' => $location,
        'date' => $event['date']['start_date'] ?? date('Y-m-d'),
        'time' => $event['date']['when'] ?? '',
        'price' => $price,
        'is_free' => $isFree,
        'category' => $category,
        'thumbnail' => $event['thumbnail'] ?? null,
        'link' => $event['link'] ?? '#',
        'source' => 'Google Events'
    ];
    
    if (count($events) >= $limit) {
        break;
    }
}

echo json_encode([
    'success' => true,
    'source' => 'serpapi',
    'events' => $events,
    'total' => count($events),
    'search_metadata' => $data['search_metadata'] ?? []
]);
?>