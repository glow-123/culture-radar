<?php
/**
 * Google Maps Service for Culture Radar
 * Handles geocoding, directions, and places
 */

require_once __DIR__ . '/../config.php';

class GoogleMapsService {
    private $apiKey;
    private $geocodingBaseUrl;
    private $directionsBaseUrl;
    private $placesBaseUrl;
    private $cacheDirectory;
    
    public function __construct() {
        $this->apiKey = Config::get('GOOGLE_MAPS_API_KEY');
        $this->geocodingBaseUrl = 'https://maps.googleapis.com/maps/api/geocode/json';
        $this->directionsBaseUrl = 'https://maps.googleapis.com/maps/api/directions/json';
        $this->placesBaseUrl = 'https://maps.googleapis.com/maps/api/place';
        $this->cacheDirectory = __DIR__ . '/../cache/maps/';
        
        if (!file_exists($this->cacheDirectory)) {
            mkdir($this->cacheDirectory, 0755, true);
        }
        
        if (empty($this->apiKey)) {
            error_log('Google Maps API key is missing');
        }
    }
    
    /**
     * Get coordinates for an address
     */
    public function geocodeAddress($address) {
        if (empty($this->apiKey)) {
            return null;
        }
        
        $cacheKey = 'geocode_' . md5($address);
        $cachedData = $this->getFromCache($cacheKey);
        
        if ($cachedData) {
            return $cachedData;
        }
        
        try {
            $params = [
                'address' => $address,
                'key' => $this->apiKey,
                'region' => 'fr'
            ];
            
            $response = $this->makeApiRequest($this->geocodingBaseUrl . '?' . http_build_query($params));
            
            if ($response['status'] === 'OK' && !empty($response['results'])) {
                $result = $response['results'][0];
                $coordinates = [
                    'lat' => $result['geometry']['location']['lat'],
                    'lng' => $result['geometry']['location']['lng'],
                    'formatted_address' => $result['formatted_address']
                ];
                
                // Cache for 24 hours
                $this->saveToCache($cacheKey, $coordinates, 86400);
                
                return $coordinates;
            }
            
        } catch (Exception $e) {
            error_log("Geocoding error: " . $e->getMessage());
        }
        
        return null;
    }
    
    /**
     * Get directions between two points
     */
    public function getDirections($origin, $destination, $mode = 'transit') {
        if (empty($this->apiKey)) {
            return null;
        }
        
        $cacheKey = 'directions_' . md5($origin . $destination . $mode);
        $cachedData = $this->getFromCache($cacheKey);
        
        if ($cachedData) {
            return $cachedData;
        }
        
        try {
            $params = [
                'origin' => $origin,
                'destination' => $destination,
                'mode' => $mode,
                'key' => $this->apiKey,
                'region' => 'fr',
                'language' => 'fr'
            ];
            
            $response = $this->makeApiRequest($this->directionsBaseUrl . '?' . http_build_query($params));
            
            if ($response['status'] === 'OK' && !empty($response['routes'])) {
                $route = $response['routes'][0];
                $leg = $route['legs'][0];
                
                $directions = [
                    'duration' => $leg['duration']['text'],
                    'duration_seconds' => $leg['duration']['value'],
                    'distance' => $leg['distance']['text'],
                    'distance_meters' => $leg['distance']['value'],
                    'start_address' => $leg['start_address'],
                    'end_address' => $leg['end_address'],
                    'steps' => $this->parseSteps($leg['steps'])
                ];
                
                // Cache for 2 hours
                $this->saveToCache($cacheKey, $directions, 7200);
                
                return $directions;
            }
            
        } catch (Exception $e) {
            error_log("Directions error: " . $e->getMessage());
        }
        
        return null;
    }
    
    /**
     * Find nearby parking
     */
    public function findNearbyParking($coordinates, $radius = 1000) {
        if (empty($this->apiKey)) {
            return [];
        }
        
        $location = $coordinates['lat'] . ',' . $coordinates['lng'];
        $cacheKey = 'parking_' . md5($location . $radius);
        $cachedData = $this->getFromCache($cacheKey);
        
        if ($cachedData) {
            return $cachedData;
        }
        
        try {
            $params = [
                'location' => $location,
                'radius' => $radius,
                'type' => 'parking',
                'key' => $this->apiKey
            ];
            
            $url = $this->placesBaseUrl . '/nearbysearch/json';
            $response = $this->makeApiRequest($url . '?' . http_build_query($params));
            
            if ($response['status'] === 'OK') {
                $parkingSpots = [];
                
                foreach (array_slice($response['results'], 0, 5) as $place) {
                    $parkingSpots[] = [
                        'name' => $place['name'],
                        'rating' => $place['rating'] ?? null,
                        'vicinity' => $place['vicinity'],
                        'is_open' => isset($place['opening_hours']) ? $place['opening_hours']['open_now'] : null,
                        'place_id' => $place['place_id']
                    ];
                }
                
                // Cache for 4 hours
                $this->saveToCache($cacheKey, $parkingSpots, 14400);
                
                return $parkingSpots;
            }
            
        } catch (Exception $e) {
            error_log("Parking search error: " . $e->getMessage());
        }
        
        return [];
    }
    
    /**
     * Parse direction steps
     */
    private function parseSteps($steps) {
        $parsedSteps = [];
        
        foreach ($steps as $step) {
            $parsedStep = [
                'instruction' => strip_tags($step['html_instructions']),
                'distance' => $step['distance']['text'],
                'duration' => $step['duration']['text'],
                'travel_mode' => $step['travel_mode']
            ];
            
            // Add transit details if available
            if (isset($step['transit_details'])) {
                $transit = $step['transit_details'];
                $parsedStep['transit'] = [
                    'line_name' => $transit['line']['name'] ?? null,
                    'line_color' => $transit['line']['color'] ?? null,
                    'departure_stop' => $transit['departure_stop']['name'] ?? null,
                    'arrival_stop' => $transit['arrival_stop']['name'] ?? null,
                    'num_stops' => $transit['num_stops'] ?? null
                ];
            }
            
            $parsedSteps[] = $parsedStep;
        }
        
        return $parsedSteps;
    }
    
    /**
     * Make API request
     */
    private function makeApiRequest($url) {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 10,
                'header' => [
                    'User-Agent: CultureRadar/1.0'
                ]
            ]
        ]);
        
        $response = file_get_contents($url, false, $context);
        
        if ($response === false) {
            throw new Exception('Failed to fetch data from Google Maps API');
        }
        
        $data = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON response from Google Maps API');
        }
        
        return $data;
    }
    
    /**
     * Cache management
     */
    private function getFromCache($key) {
        $cacheFile = $this->cacheDirectory . md5($key) . '.json';
        
        if (file_exists($cacheFile)) {
            $cacheData = json_decode(file_get_contents($cacheFile), true);
            
            if ($cacheData && $cacheData['expires'] > time()) {
                return $cacheData['data'];
            }
        }
        
        return null;
    }
    
    private function saveToCache($key, $data, $duration) {
        $cacheFile = $this->cacheDirectory . md5($key) . '.json';
        $cacheData = [
            'data' => $data,
            'expires' => time() + $duration
        ];
        
        file_put_contents($cacheFile, json_encode($cacheData));
    }
}
?>