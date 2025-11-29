<?php
/**
 * Events Aggregator API
 * Combine plusieurs sources d'événements en une seule API
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../config.php';

// Paramètres
$location = $_GET['location'] ?? 'Paris';
$limit = intval($_GET['limit'] ?? 50);
$category = $_GET['category'] ?? null;
$source = $_GET['source'] ?? 'all'; // all, openagenda, paris, google

$allEvents = [];
$errors = [];

// Fonction pour normaliser les événements
function normalizeEvent($event, $source) {
    return [
        'id' => $event['id'] ?? uniqid(),
        'title' => $event['title'] ?? $event['name'] ?? 'Événement',
        'description' => $event['description'] ?? $event['excerpt'] ?? '',
        'category' => $event['category'] ?? 'culture',
        'venue' => $event['venue'] ?? $event['venue_name'] ?? $event['location'] ?? '',
        'address' => $event['address'] ?? '',
        'city' => $event['city'] ?? 'Paris',
        'date' => $event['date'] ?? $event['date_start'] ?? $event['start_date'] ?? date('Y-m-d'),
        'time' => $event['time'] ?? $event['start_time'] ?? '',
        'price' => isset($event['price']) ? floatval($event['price']) : 0,
        'is_free' => isset($event['is_free']) ? $event['is_free'] : ($event['price'] == 0),
        'image' => $event['image'] ?? $event['thumbnail'] ?? $event['cover_url'] ?? null,
        'link' => $event['link'] ?? $event['url'] ?? '#',
        'source' => $source,
        'lat' => $event['lat'] ?? $event['latitude'] ?? null,
        'lng' => $event['lng'] ?? $event['longitude'] ?? null
    ];
}

// 1. OpenAgenda Events
if ($source === 'all' || $source === 'openagenda') {
    if (file_exists(__DIR__ . '/../services/OpenAgendaService.php')) {
        try {
            require_once __DIR__ . '/../services/OpenAgendaService.php';
            $openAgenda = new OpenAgendaService();
            $oaEvents = $openAgenda->getEventsByLocation([
                'city' => $location,
                'additional' => ['size' => $limit]
            ]);
            
            foreach ($oaEvents as $event) {
                $allEvents[] = normalizeEvent($event, 'OpenAgenda');
            }
        } catch (Exception $e) {
            $errors[] = 'OpenAgenda: ' . $e->getMessage();
        }
    }
}

// 2. Paris Open Data Events
if ($source === 'all' || $source === 'paris') {
    try {
        $parisUrl = 'https://opendata.paris.fr/api/records/1.0/search/';
        $params = [
            'dataset' => 'que-faire-a-paris-',
            'rows' => $limit,
            'facet' => ['category', 'tags', 'address_zipcode', 'price_type'],
            'timezone' => 'Europe/Paris',
            'lang' => 'fr',
            'q' => 'date_start>=' . date('Y-m-d')
        ];
        
        if ($category) {
            $params['refine.category'] = $category;
        }
        
        $url = $parisUrl . '?' . http_build_query($params);
        $response = @file_get_contents($url);
        
        if ($response) {
            $data = json_decode($response, true);
            if (isset($data['records'])) {
                foreach ($data['records'] as $record) {
                    $fields = $record['fields'] ?? [];
                    
                    $event = [
                        'id' => 'paris-' . ($record['recordid'] ?? uniqid()),
                        'title' => $fields['title'] ?? '',
                        'description' => $fields['lead_text'] ?? $fields['description'] ?? '',
                        'category' => $fields['category'] ?? 'culture',
                        'venue' => $fields['address_name'] ?? '',
                        'address' => $fields['address_street'] ?? '',
                        'city' => $fields['address_city'] ?? 'Paris',
                        'date_start' => $fields['date_start'] ?? date('Y-m-d'),
                        'price' => 0,
                        'is_free' => stripos($fields['price_type'] ?? '', 'gratuit') !== false,
                        'image' => $fields['cover_url'] ?? null,
                        'link' => $fields['url'] ?? '#'
                    ];
                    
                    // Coordonnées
                    if (isset($fields['lat_lon'])) {
                        $event['lat'] = $fields['lat_lon'][0] ?? null;
                        $event['lng'] = $fields['lat_lon'][1] ?? null;
                    }
                    
                    $allEvents[] = normalizeEvent($event, 'Paris Open Data');
                }
            }
        }
    } catch (Exception $e) {
        $errors[] = 'Paris Open Data: ' . $e->getMessage();
    }
}

// 3. Google Events (si configuré)
if ($source === 'all' || $source === 'google') {
    $serpApiKey = Config::get('SERPAPI_KEY', '');
    if (!empty($serpApiKey)) {
        try {
            $googleUrl = 'http://localhost/api/google-events.php?' . http_build_query([
                'location' => $location,
                'limit' => $limit
            ]);
            
            $response = @file_get_contents($googleUrl);
            if ($response) {
                $data = json_decode($response, true);
                if (isset($data['events'])) {
                    foreach ($data['events'] as $event) {
                        $allEvents[] = normalizeEvent($event, 'Google Events');
                    }
                }
            }
        } catch (Exception $e) {
            $errors[] = 'Google Events: ' . $e->getMessage();
        }
    }
}

// 4. Événements de démonstration si aucun événement trouvé
if (empty($allEvents)) {
    $demoEvents = [
        [
            'id' => 'demo-1',
            'title' => 'Concert Jazz au Sunset',
            'description' => 'Une soirée jazz exceptionnelle',
            'category' => 'concert',
            'venue' => 'Le Sunset-Sunside',
            'address' => '60 Rue des Lombards',
            'city' => $location,
            'date_start' => date('Y-m-d'),
            'time' => '21:00',
            'price' => 25,
            'is_free' => false,
            'image' => 'https://images.unsplash.com/photo-1415201364774-f6f0bb35f28f?w=400'
        ],
        [
            'id' => 'demo-2',
            'title' => 'Exposition Art Moderne',
            'description' => 'Découvrez les artistes contemporains',
            'category' => 'exposition',
            'venue' => 'Centre Pompidou',
            'address' => 'Place Georges-Pompidou',
            'city' => $location,
            'date_start' => date('Y-m-d', strtotime('+1 day')),
            'time' => '10:00',
            'price' => 0,
            'is_free' => true,
            'image' => 'https://images.unsplash.com/photo-1554907984-15263bfd63bd?w=400'
        ],
        [
            'id' => 'demo-3',
            'title' => 'Pièce de Théâtre',
            'description' => 'Une comédie hilarante',
            'category' => 'théâtre',
            'venue' => 'Théâtre de la Ville',
            'address' => '2 Place du Châtelet',
            'city' => $location,
            'date_start' => date('Y-m-d', strtotime('+2 days')),
            'time' => '20:00',
            'price' => 30,
            'is_free' => false,
            'image' => 'https://images.unsplash.com/photo-1503095396549-807759245b35?w=400'
        ],
        [
            'id' => 'demo-4',
            'title' => 'Festival de Street Art',
            'description' => 'Art urbain et performances',
            'category' => 'festival',
            'venue' => 'Belleville',
            'address' => 'Quartier Belleville',
            'city' => $location,
            'date_start' => date('Y-m-d', strtotime('+3 days')),
            'time' => 'Toute la journée',
            'price' => 0,
            'is_free' => true,
            'image' => 'https://images.unsplash.com/photo-1499781350541-7783f6c6a0c8?w=400'
        ]
    ];
    
    foreach ($demoEvents as $event) {
        $allEvents[] = normalizeEvent($event, 'Demo');
    }
}

// Trier par date
usort($allEvents, function($a, $b) {
    return strtotime($a['date']) - strtotime($b['date']);
});

// Limiter le nombre de résultats
$allEvents = array_slice($allEvents, 0, $limit);

// Réponse
echo json_encode([
    'success' => true,
    'events' => $allEvents,
    'total' => count($allEvents),
    'sources' => array_unique(array_column($allEvents, 'source')),
    'errors' => $errors,
    'location' => $location,
    'timestamp' => date('c')
]);
?>