<?php
/**
 * Weather and Transport Integration Service
 * Provides real-time weather data and public transport information for events
 */

class WeatherTransportService {
    private $openWeatherApiKey;
    private $googleMapsApiKey;
    private $cacheDir;
    private $cacheTtl = 3600; // 1 hour cache
    
    public function __construct() {
        $this->openWeatherApiKey = $_ENV['OPENWEATHER_API_KEY'] ?? '';
        $this->googleMapsApiKey = $_ENV['GOOGLE_MAPS_API_KEY'] ?? '';
        $this->cacheDir = __DIR__ . '/../cache/weather_transport/';
        
        // Create cache directory if it doesn't exist
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }
    
    /**
     * Get weather information for an event
     */
    public function getEventWeather($eventData) {
        if (!$this->openWeatherApiKey) {
            return $this->getDefaultWeatherData();
        }
        
        $city = $eventData['city'] ?? '';
        $eventDate = $eventData['start_date'] ?? '';
        
        if (empty($city) || empty($eventDate)) {
            return $this->getDefaultWeatherData();
        }
        
        $cacheKey = 'weather_' . md5($city . date('Y-m-d', strtotime($eventDate)));
        $cachedData = $this->getFromCache($cacheKey);
        
        if ($cachedData) {
            return $cachedData;
        }
        
        try {
            $eventTimestamp = strtotime($eventDate);
            $now = time();
            $daysDifference = ($eventTimestamp - $now) / (24 * 3600);
            
            if ($daysDifference <= 5 && $daysDifference >= 0) {
                // Use 5-day forecast for events within 5 days
                $weatherData = $this->getForecastWeather($city, $eventDate);
            } else {
                // Use current weather as approximation
                $weatherData = $this->getCurrentWeather($city);
            }
            
            $this->saveToCache($cacheKey, $weatherData);
            return $weatherData;
            
        } catch (Exception $e) {
            error_log("Weather API error: " . $e->getMessage());
            return $this->getDefaultWeatherData();
        }
    }
    
    /**
     * Get current weather for a city
     */
    private function getCurrentWeather($city) {
        $url = "https://api.openweathermap.org/data/2.5/weather";
        $params = [
            'q' => $city . ',FR',
            'appid' => $this->openWeatherApiKey,
            'units' => 'metric',
            'lang' => 'fr'
        ];
        
        $response = $this->makeApiRequest($url . '?' . http_build_query($params));
        
        if (!$response) {
            throw new Exception("Failed to fetch current weather");
        }
        
        return [
            'type' => 'current',
            'temperature' => round($response['main']['temp']),
            'description' => ucfirst($response['weather'][0]['description']),
            'icon' => $this->getWeatherIcon($response['weather'][0]['icon']),
            'humidity' => $response['main']['humidity'],
            'wind_speed' => round($response['wind']['speed'] * 3.6), // Convert m/s to km/h
            'pressure' => $response['main']['pressure'],
            'visibility' => isset($response['visibility']) ? round($response['visibility'] / 1000, 1) : null,
            'recommendation' => $this->getWeatherRecommendation($response)
        ];
    }
    
    /**
     * Get forecast weather for a specific date
     */
    private function getForecastWeather($city, $eventDate) {
        $url = "https://api.openweathermap.org/data/2.5/forecast";
        $params = [
            'q' => $city . ',FR',
            'appid' => $this->openWeatherApiKey,
            'units' => 'metric',
            'lang' => 'fr'
        ];
        
        $response = $this->makeApiRequest($url . '?' . http_build_query($params));
        
        if (!$response) {
            throw new Exception("Failed to fetch weather forecast");
        }
        
        // Find forecast closest to event time
        $eventTimestamp = strtotime($eventDate);
        $closestForecast = null;
        $minDifference = PHP_INT_MAX;
        
        foreach ($response['list'] as $forecast) {
            $forecastTimestamp = $forecast['dt'];
            $difference = abs($eventTimestamp - $forecastTimestamp);
            
            if ($difference < $minDifference) {
                $minDifference = $difference;
                $closestForecast = $forecast;
            }
        }
        
        if (!$closestForecast) {
            throw new Exception("No suitable forecast found");
        }
        
        return [
            'type' => 'forecast',
            'temperature' => round($closestForecast['main']['temp']),
            'description' => ucfirst($closestForecast['weather'][0]['description']),
            'icon' => $this->getWeatherIcon($closestForecast['weather'][0]['icon']),
            'humidity' => $closestForecast['main']['humidity'],
            'wind_speed' => round($closestForecast['wind']['speed'] * 3.6),
            'pressure' => $closestForecast['main']['pressure'],
            'rain_probability' => isset($closestForecast['pop']) ? round($closestForecast['pop'] * 100) : 0,
            'recommendation' => $this->getWeatherRecommendation($closestForecast)
        ];
    }
    
    /**
     * Get public transport information for an event
     */
    public function getTransportInfo($eventData, $userLocation = null) {
        if (!$this->googleMapsApiKey) {
            return $this->getDefaultTransportData();
        }
        
        $venue = $eventData['venue_name'] ?? '';
        $address = $eventData['address'] ?? '';
        $city = $eventData['city'] ?? '';
        
        $destination = $this->buildAddress($venue, $address, $city);
        
        if (empty($destination)) {
            return $this->getDefaultTransportData();
        }
        
        $cacheKey = 'transport_' . md5($destination . ($userLocation ?? 'default'));
        $cachedData = $this->getFromCache($cacheKey);
        
        if ($cachedData) {
            return $cachedData;
        }
        
        try {
            $transportData = [
                'destination' => $destination,
                'coordinates' => $this->getCoordinates($destination),
                'public_transport' => $this->getPublicTransportRoutes($userLocation, $destination),
                'parking' => $this->getNearbyParking($destination),
                'accessibility' => $this->getAccessibilityInfo($destination)
            ];
            
            $this->saveToCache($cacheKey, $transportData, 7200); // Cache for 2 hours
            return $transportData;
            
        } catch (Exception $e) {
            error_log("Transport API error: " . $e->getMessage());
            return $this->getDefaultTransportData();
        }
    }
    
    /**
     * Get coordinates for an address
     */
    private function getCoordinates($address) {
        $url = "https://maps.googleapis.com/maps/api/geocode/json";
        $params = [
            'address' => $address,
            'key' => $this->googleMapsApiKey,
            'region' => 'fr'
        ];
        
        $response = $this->makeApiRequest($url . '?' . http_build_query($params));
        
        if ($response && !empty($response['results'])) {
            $location = $response['results'][0]['geometry']['location'];
            return [
                'lat' => $location['lat'],
                'lng' => $location['lng']
            ];
        }
        
        return null;
    }
    
    /**
     * Get public transport routes
     */
    private function getPublicTransportRoutes($origin, $destination) {
        if (!$origin) {
            // Return general public transport info for Paris
            return $this->getParisTransportInfo($destination);
        }
        
        $url = "https://maps.googleapis.com/maps/api/directions/json";
        $params = [
            'origin' => $origin,
            'destination' => $destination,
            'mode' => 'transit',
            'key' => $this->googleMapsApiKey,
            'region' => 'fr',
            'language' => 'fr'
        ];
        
        $response = $this->makeApiRequest($url . '?' . http_build_query($params));
        
        if ($response && !empty($response['routes'])) {
            $route = $response['routes'][0];
            $leg = $route['legs'][0];
            
            return [
                'duration' => $leg['duration']['text'],
                'distance' => $leg['distance']['text'],
                'steps' => $this->parseTransitSteps($leg['steps']),
                'departure_time' => isset($leg['departure_time']) ? $leg['departure_time']['text'] : null,
                'arrival_time' => isset($leg['arrival_time']) ? $leg['arrival_time']['text'] : null
            ];
        }
        
        return $this->getParisTransportInfo($destination);
    }
    
    /**
     * Parse transit steps from Google Directions
     */
    private function parseTransitSteps($steps) {
        $transitSteps = [];
        
        foreach ($steps as $step) {
            if ($step['travel_mode'] === 'TRANSIT') {
                $transitDetails = $step['transit_details'];
                $transitSteps[] = [
                    'type' => strtolower($transitDetails['line']['vehicle']['type']),
                    'line_name' => $transitDetails['line']['short_name'] ?? $transitDetails['line']['name'],
                    'departure_stop' => $transitDetails['departure_stop']['name'],
                    'arrival_stop' => $transitDetails['arrival_stop']['name'],
                    'duration' => $step['duration']['text'],
                    'color' => $transitDetails['line']['color'] ?? '#007cbb'
                ];
            }
        }
        
        return $transitSteps;
    }
    
    /**
     * Get general Paris transport information
     */
    private function getParisTransportInfo($destination) {
        return [
            'general_info' => [
                'metro' => 'Métro parisien - Réseau RATP',
                'bus' => 'Bus RATP et Île-de-France Mobilités',
                'rer' => 'RER A, B, C, D, E',
                'recommendation' => 'Utilisez l\'application Citymapper ou RATP pour les itinéraires en temps réel'
            ],
            'nearby_stations' => $this->findNearbyStations($destination)
        ];
    }
    
    /**
     * Find nearby metro/RER stations
     */
    private function findNearbyStations($destination) {
        // Simplified station finder - in production, use RATP API
        $commonStations = [
            'Châtelet-Les Halles',
            'République',
            'Bastille',
            'Opéra',
            'Montparnasse',
            'Gare du Nord',
            'Nation',
            'Belleville'
        ];
        
        return array_slice($commonStations, 0, 3);
    }
    
    /**
     * Get nearby parking information
     */
    private function getNearbyParking($destination) {
        $url = "https://maps.googleapis.com/maps/api/place/nearbysearch/json";
        $coordinates = $this->getCoordinates($destination);
        
        if (!$coordinates) {
            return [];
        }
        
        $params = [
            'location' => $coordinates['lat'] . ',' . $coordinates['lng'],
            'radius' => 1000,
            'type' => 'parking',
            'key' => $this->googleMapsApiKey
        ];
        
        $response = $this->makeApiRequest($url . '?' . http_build_query($params));
        
        if ($response && !empty($response['results'])) {
            $parkingSpots = [];
            
            foreach (array_slice($response['results'], 0, 3) as $place) {
                $parkingSpots[] = [
                    'name' => $place['name'],
                    'rating' => $place['rating'] ?? null,
                    'vicinity' => $place['vicinity'],
                    'is_open' => isset($place['opening_hours']) ? $place['opening_hours']['open_now'] : null
                ];
            }
            
            return $parkingSpots;
        }
        
        return [];
    }
    
    /**
     * Get accessibility information
     */
    private function getAccessibilityInfo($destination) {
        // Basic accessibility recommendations
        return [
            'general' => 'Vérifiez l\'accessibilité du lieu sur le site de l\'organisateur',
            'transport' => 'Stations de métro accessibles : consultez le plan RATP',
            'parking' => 'Places réservées aux personnes à mobilité réduite disponibles'
        ];
    }
    
    /**
     * Get weather recommendation based on conditions
     */
    private function getWeatherRecommendation($weatherData) {
        $temp = $weatherData['main']['temp'];
        $condition = $weatherData['weather'][0]['main'];
        $windSpeed = $weatherData['wind']['speed'] * 3.6; // km/h
        
        $recommendations = [];
        
        // Temperature recommendations
        if ($temp < 5) {
            $recommendations[] = "🧥 Habillez-vous chaudement, il fait très froid";
        } elseif ($temp < 15) {
            $recommendations[] = "🧥 Prévoyez une veste, il fait frais";
        } elseif ($temp > 25) {
            $recommendations[] = "👕 Habillez-vous léger, il fait chaud";
        }
        
        // Weather condition recommendations
        switch (strtolower($condition)) {
            case 'rain':
            case 'drizzle':
                $recommendations[] = "☔ N'oubliez pas votre parapluie ou imperméable";
                break;
            case 'snow':
                $recommendations[] = "❄️ Attention aux conditions hivernales, chaussures antidérapantes recommandées";
                break;
            case 'thunderstorm':
                $recommendations[] = "⛈️ Orage prévu, restez à l'abri si possible";
                break;
            case 'fog':
            case 'mist':
                $recommendations[] = "🌫️ Visibilité réduite, soyez prudent dans vos déplacements";
                break;
        }
        
        // Wind recommendations
        if ($windSpeed > 20) {
            $recommendations[] = "💨 Vent fort prévu, attention aux objets légers";
        }
        
        return !empty($recommendations) ? $recommendations : ["☀️ Conditions météo favorables pour votre sortie culturelle"];
    }
    
    /**
     * Convert OpenWeather icon to emoji/description
     */
    private function getWeatherIcon($iconCode) {
        $iconMap = [
            '01d' => '☀️', '01n' => '🌙',
            '02d' => '⛅', '02n' => '☁️',
            '03d' => '☁️', '03n' => '☁️',
            '04d' => '☁️', '04n' => '☁️',
            '09d' => '🌧️', '09n' => '🌧️',
            '10d' => '🌦️', '10n' => '🌧️',
            '11d' => '⛈️', '11n' => '⛈️',
            '13d' => '❄️', '13n' => '❄️',
            '50d' => '🌫️', '50n' => '🌫️'
        ];
        
        return $iconMap[$iconCode] ?? '🌤️';
    }
    
    /**
     * Build full address string
     */
    private function buildAddress($venue, $address, $city) {
        $parts = array_filter([$venue, $address, $city]);
        return implode(', ', $parts);
    }
    
    /**
     * Make HTTP API request
     */
    private function makeApiRequest($url) {
        $context = stream_context_create([
            'http' => [
                'timeout' => 10,
                'user_agent' => 'CultureRadar/1.0'
            ]
        ]);
        
        $response = @file_get_contents($url, false, $context);
        
        if ($response === false) {
            return false;
        }
        
        return json_decode($response, true);
    }
    
    /**
     * Get data from cache
     */
    private function getFromCache($key) {
        $cacheFile = $this->cacheDir . $key . '.json';
        
        if (!file_exists($cacheFile)) {
            return false;
        }
        
        $cacheData = json_decode(file_get_contents($cacheFile), true);
        
        if (!$cacheData || $cacheData['expires'] < time()) {
            @unlink($cacheFile);
            return false;
        }
        
        return $cacheData['data'];
    }
    
    /**
     * Save data to cache
     */
    private function saveToCache($key, $data, $ttl = null) {
        $cacheFile = $this->cacheDir . $key . '.json';
        $ttl = $ttl ?: $this->cacheTtl;
        
        $cacheData = [
            'data' => $data,
            'expires' => time() + $ttl
        ];
        
        file_put_contents($cacheFile, json_encode($cacheData));
    }
    
    /**
     * Get default weather data when API is unavailable
     */
    private function getDefaultWeatherData() {
        return [
            'type' => 'unavailable',
            'temperature' => null,
            'description' => 'Informations météo non disponibles',
            'icon' => '🌤️',
            'recommendation' => ['Consultez la météo avant de partir']
        ];
    }
    
    /**
     * Get default transport data when API is unavailable
     */
    private function getDefaultTransportData() {
        return [
            'destination' => '',
            'general_info' => [
                'recommendation' => 'Consultez Citymapper ou Google Maps pour les itinéraires'
            ],
            'accessibility' => [
                'general' => 'Vérifiez l\'accessibilité du lieu directement avec l\'organisateur'
            ]
        ];
    }
    
    /**
     * Get combined weather and transport data for an event
     */
    public function getEventTravelInfo($eventData, $userLocation = null) {
        return [
            'weather' => $this->getEventWeather($eventData),
            'transport' => $this->getTransportInfo($eventData, $userLocation),
            'recommendations' => $this->getCombinedRecommendations($eventData)
        ];
    }
    
    /**
     * Get combined recommendations based on weather and transport
     */
    private function getCombinedRecommendations($eventData) {
        $recommendations = [];
        
        // Time-based recommendations
        $eventHour = (int)date('H', strtotime($eventData['start_date']));
        
        if ($eventHour >= 19) {
            $recommendations[] = "🌃 Événement en soirée - vérifiez les horaires de transport de retour";
        }
        
        if ($eventHour <= 9) {
            $recommendations[] = "🌅 Événement matinal - pensez aux horaires de transport";
        }
        
        // Weekend recommendations
        $eventDay = date('N', strtotime($eventData['start_date']));
        if ($eventDay >= 6) {
            $recommendations[] = "🎉 Événement de week-end - affluence possible dans les transports";
        }
        
        // Default recommendation
        if (empty($recommendations)) {
            $recommendations[] = "📍 Préparez votre itinéraire à l'avance pour profiter pleinement de l'événement";
        }
        
        return $recommendations;
    }
}
?>