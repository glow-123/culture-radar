<?php
/**
 * Paris Open Data Events API
 * Free, no API key required
 * Real-time events from Paris official data
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Get parameters
$location = $_GET['location'] ?? 'Paris';
$limit = intval($_GET['limit'] ?? 20);
$category = $_GET['category'] ?? null;

// Paris Open Data API endpoint for events
$apiUrl = 'https://opendata.paris.fr/api/records/1.0/search/';

// Different datasets for different types of events
$datasets = [
    'que-faire-a-paris-' => 'general', // General events
    'evenements-a-paris' => 'events',   // Cultural events
];

$allEvents = [];

// Try each dataset
foreach ($datasets as $dataset => $type) {
    $params = [
        'dataset' => $dataset,
        'rows' => $limit,
        'facet' => ['category', 'tags', 'address_zipcode', 'price_type'],
        'timezone' => 'Europe/Paris',
        'lang' => 'fr'
    ];
    
    // Add date filter for upcoming events
    $today = date('Y-m-d');
    $params['q'] = •date_start>=$today•;
    
    // Add location filter if not Paris
    if ($location !== 'Paris') {
        $params['q'] .= • AND (address_city=$location OR title=$location)•;
    }
    
    $url = $apiUrl . '?' . http_build_query($params);
    
    // Make request
    $opts = [
        •http• => [
            •method• => •GET•,
            •header• => •Accept: application/json\r\n•,
            •timeout• => 5
        ]
    ];
    
    $context = stream_context_create($opts);
    $response = @file_get_contents($url, false, $context);
    
    if ($response !== false) {
        $data = json_decode($response, true);
        
        if (isset($data['records'])) {
            foreach ($data['records'] as $record) {
                $fields = $record['fields'] ?? [];
                
                // Skip if no title
                if (empty($fields['title'])) continue;
                
                // Parse dates
                $startDate = $fields['date_start'] ?? $fields['date'] ?? null;
                $endDate = $fields['date_end'] ?? null;
                
                // Determine if free
                $isFree = false;
                if (isset($fields['price_type'])) {
                    $isFree = stripos($fields['price_type'], 'gratuit') !== false;
                } elseif (isset($fields['price_detail'])) {
                    $isFree = stripos($fields['price_detail'], 'gratuit') !== false;
                }
                
                // Get image
                $image = $fields['cover_url'] ?? $fields['image'] ?? null;
                
                // Get coordinates
                $coords = $fields['lat_lon'] ?? $fields['geo_point_2d'] ?? null;
                $lat = null;
                $lng = null;
                if ($coords) {
                    if (is_array($coords)) {
                        $lat = $coords[0] ?? null;
                        $lng = $coords[1] ?? null;
                    } elseif (is_string($coords)) {
                        $parts = explode(',', $coords);
                        $lat = trim($parts[0] ?? '');
                        $lng = trim($parts[1] ?? '');
                    }
                }
                
                $allEvents[] = [
                    'id' => $record['recordid'] ?? uniqid(),
                    'title' => $fields['title'],
                    'description' => strip_tags($fields['description'] ?? $fields['lead_text'] ?? ''),
                    'category' => $fields['category'] ?? 'culture',
                    'venue_name' => $fields['address_name'] ?? $fields['placename'] ?? 'Paris',
                    'address' => $fields['address_street'] ?? $fields['address'] ?? '',
                    'city' => $fields['address_city'] ?? 'Paris',
                    'postal_code' => $fields['address_zipcode'] ?? '',
                    'latitude' => $lat,
                    'longitude' => $lng,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'price' => $isFree ? 0 : null,
                    'price_detail' => $fields['price_detail'] ?? '',
                    'is_free' => $isFree,
                    'image_url' => $image,
                    'external_url' => $fields['url'] ?? $fields['link'] ?? null,
                    'tags' => $fields['tags'] ?? [],
                    'source' => 'paris_opendata'
                ];
            }
        }
    }
    
    if (count($allEvents) >= $limit) break;
}

// Alternative: Try •Que faire à Paris• API
if (empty($allEvents)) {
    $url = 'https://opendata.paris.fr/api/explore/v2.1/catalog/datasets/que-faire-a-paris-/records?limit=' . $limit;
    
    $response = @file_get_contents($url, false, $context);
    
    if ($response !== false) {
        $data = json_decode($response, true);
        
        if (isset($data['results'])) {
            foreach ($data['results'] as $event) {
                if (empty($event['title'])) continue;
                
                $allEvents[] = [
                    'id' => $event['id'] ?? uniqid(),
                    'title' => $event['title'],
                    'description' => strip_tags($event['description'] ?? ''),
                    'category' => $event['category'] ?? 'culture',
                    'venue_name' => $event['address_name'] ?? 'Paris',
                    'address' => $event['address_street'] ?? '',
                    'city' => $event['address_city'] ?? 'Paris',
                    'postal_code' => $event['address_zipcode'] ?? '',
                    'start_date' => $event['date_start'] ?? date('Y-m-d'),
                    'end_date' => $event['date_end'] ?? null,
                    'price' => 0,
                    'is_free' => stripos($event['price_type'] ?? '', 'gratuit') !== false,
                    'image_url' => $event['cover_url'] ?? null,
                    'external_url' => $event['url'] ?? null,
                    'source' => 'paris_opendata'
                ];
            }
        }
    }
}

// If still no events, use mock data
if (empty($allEvents)) {
    $allEvents = [
        [
            'id' => 'mock1',
            'title' => 'Exposition Temporaire au Centre Pompidou',
            'description' => 'Découvrez l\'art contemporain dans cette exposition unique.',
            'category' => 'exposition',
            'venue_name' => 'Centre Pompidou',
            'address' => 'Place Georges-Pompidou',
            'city' => 'Paris',
            'postal_code' => '75004',
            'start_date' => date('Y-m-d'),
            'price' => 14,
            'is_free' => false,
            'source' => 'mock'
        ],
        [
            'id' => 'mock2',
            'title' => 'Concert Gratuit au Parc',
            'description' => 'Musique en plein air pour tous.',
            'category' => 'musique',
            'venue_name' => 'Parc de la Villette',
            'address' => '211 Avenue Jean Jaurès',
            'city' => 'Paris',
            'postal_code' => '75019',
            'start_date' => date('Y-m-d', strtotime('next sunday')),
            'price' => 0,
            'is_free' => true,
            'source' => 'mock'
        ]
    ];
}

// Limit results
$allEvents = array_slice($allEvents, 0, $limit);

// Return response
echo json_encode([
    'success' => true,
    'location' => $location,
    'total' => count($allEvents),
    'events' => $allEvents,
    'source' => !empty($allEvents) && $allEvents[0]['source'] !== 'mock' ? 'paris_opendata' : 'mock'
]);
?>