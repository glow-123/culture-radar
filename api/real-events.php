<?php
/**
 * Real Events API Integration
 * Connects to OpenAgenda API to fetch actual events
 */

session_start();
require_once dirname(__DIR__) . '/config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Get API key from config
$openAgendaKey = Config::env('OPENAGENDA_API_KEY');

if (!$openAgendaKey || $openAgendaKey === 'YOUR_OPENAGENDA_KEY_HERE') {
    // Fallback to mock data if no API key
    include __DIR__ . '/events-data.php';
    exit;
}

// Get parameters
$location = $_GET['location'] ?? $_GET['city'] ?? 'Paris';
$category = $_GET['category'] ?? null;
$limit = intval($_GET['limit'] ?? 20);
$offset = intval($_GET['offset'] ?? 0);

// Map our categories to OpenAgenda categories
$categoryMap = [
    'art' => 'arts-visuels',
    'music' => 'musique',
    'theater' => 'spectacle',
    'cinema' => 'cinema',
    'literature' => 'litterature',
    'heritage' => 'patrimoine',
    'dance' => 'danse',
    'festival' => 'festival'
];

// Build OpenAgenda API URL
$apiUrl = 'https://api.openagenda.com/v2/events';
$params = [
    'key' => $openAgendaKey,
    'size' => $limit,
    'from' => $offset,
    'relative' => ['current', 'upcoming'], // Current and upcoming events
    'sort' => 'timingsWithFeatured'
];

// Add location filter
if ($location) {
    // Search in a 20km radius around the city
    $params['locationQuery'] = $location;
    $params['radius'] = 20;
}

// Add category filter
if ($category && isset($categoryMap[$category])) {
    $params['categories'] = $categoryMap[$category];
}

// Build query string
$queryString = http_build_query($params);
$fullUrl = $apiUrl . '?' . $queryString;

// Initialize cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $fullUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'User-Agent: CultureRadar/1.0'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200 || !$response) {
    // Error or no response - use fallback data
    include __DIR__ . '/events-data.php';
    exit;
}

$data = json_decode($response, true);

if (!$data || !isset($data['events'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Aucun événement trouvé',
        'events' => []
    ]);
    exit;
}

// Transform OpenAgenda events to our format
$events = [];
foreach ($data['events'] as $event) {
    // Get first timing
    $timing = $event['timings'][0] ?? null;
    $startDate = $timing ? date('Y-m-d H:i:s', strtotime($timing['start'])) : date('Y-m-d H:i:s');
    $endDate = $timing ? date('Y-m-d H:i:s', strtotime($timing['end'])) : null;
    
    // Get location info
    $locationData = $event['location'] ?? [];
    
    // Get price info
    $isFree = isset($event['conditions']) && stripos($event['conditions'], 'gratuit') !== false;
    $price = $isFree ? 0 : null;
    
    // Get image
    $image = $event['image'] ?? null;
    if (is_array($image)) {
        $image = $image['base'] . $image['filename'] ?? null;
    }
    
    // Build our event object
    $events[] = [
        'id' => $event['uid'] ?? uniqid(),
        'title' => $event['title']['fr'] ?? $event['title'] ?? 'Sans titre',
        'description' => $event['description']['fr'] ?? $event['description'] ?? '',
        'category' => $event['categories'][0] ?? 'autre',
        'venue_name' => $locationData['name'] ?? 'Lieu non précisé',
        'address' => $locationData['address'] ?? '',
        'city' => $locationData['city'] ?? $location,
        'postal_code' => $locationData['postalCode'] ?? '',
        'latitude' => $locationData['latitude'] ?? null,
        'longitude' => $locationData['longitude'] ?? null,
        'start_date' => $startDate,
        'end_date' => $endDate,
        'price' => $price,
        'is_free' => $isFree,
        'image_url' => $image,
        'external_url' => $event['registration'][0]['value'] ?? $event['onlineAccessLink'] ?? null,
        'tags' => $event['keywords']['fr'] ?? [],
        'ai_score' => rand(70, 95) // Placeholder AI score
    ];
}

// Return formatted response
echo json_encode([
    'success' => true,
    'location' => $location,
    'total' => $data['total'] ?? count($events),
    'events' => $events,
    'source' => 'openagenda'
]);
?>