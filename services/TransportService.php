<?php
/**
 * Transport Service for Culture Radar
 * Handles public transport information (RATP, etc.)
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/GoogleMapsService.php';

class TransportService {
    private $ratpApiKey;
    private $navitiaApiKey;
    private $googleMapsService;
    private $cacheDirectory;
    
    public function __construct() {
        $this->ratpApiKey = Config::get('RATP_API_KEY');
        $this->navitiaApiKey = Config::get('NAVITIA_API_KEY');
        $this->googleMapsService = new GoogleMapsService();
        $this->cacheDirectory = __DIR__ . '/../cache/transport/';
        
        if (!file_exists($this->cacheDirectory)) {
            mkdir($this->cacheDirectory, 0755, true);
        }
    }
    
    /**
     * Get transport information for an event
     */
    public function getTransportForEvent($eventData, $userLocation = null) {
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
                'coordinates' => $this->googleMapsService->geocodeAddress($destination),
                'public_transport' => $this->getPublicTransportRoutes($userLocation, $destination),
                'parking' => $this->getNearbyParking($destination),
                'accessibility' => $this->getAccessibilityInfo(),
                'recommendations' => $this->getTransportRecommendations($destination, $userLocation)
            ];
            
            // Cache for 2 hours
            $this->saveToCache($cacheKey, $transportData, 7200);
            
            return $transportData;
            
        } catch (Exception $e) {
            error_log("Transport service error: " . $e->getMessage());
            return $this->getDefaultTransportData();
        }
    }
    
    /**
     * Get public transport routes
     */
    private function getPublicTransportRoutes($origin, $destination) {
        if (!$origin) {
            return $this->getGeneralTransportInfo();
        }
        
        // Try Google Maps Directions API for transit
        $directions = $this->googleMapsService->getDirections($origin, $destination, 'transit');
        
        if ($directions) {
            return [
                'available' => true,
                'duration' => $directions['duration'],
                'steps' => $directions['steps'],
                'source' => 'google_maps'
            ];
        }
        
        return $this->getGeneralTransportInfo();
    }
    
    /**
     * Get general transport information for Paris
     */
    private function getGeneralTransportInfo() {
        return [
            'available' => false,
            'general_info' => [
                'metro' => 'Métro parisien - Réseau RATP',
                'bus' => 'Bus RATP et Île-de-France Mobilités',
                'rer' => 'RER A, B, C, D, E',
                'recommendation' => 'Utilisez l\'application Citymapper ou RATP pour les itinéraires en temps réel'
            ],
            'apps_recommended' => [
                'Citymapper' => 'Application complète pour tous transports',
                'RATP' => 'Application officielle RATP',
                'Bonjour RATP' => 'Info trafic en temps réel'
            ]
        ];
    }
    
    /**
     * Get nearby parking
     */
    private function getNearbyParking($destination) {
        $coordinates = $this->googleMapsService->geocodeAddress($destination);
        
        if ($coordinates) {
            return $this->googleMapsService->findNearbyParking($coordinates);
        }
        
        return [];
    }
    
    /**
     * Get accessibility information
     */
    private function getAccessibilityInfo() {
        return [
            'metro' => 'Consultez le plan des stations accessibles sur ratp.fr',
            'bus' => 'La plupart des bus sont accessibles aux personnes à mobilité réduite',
            'parking' => 'Places réservées disponibles dans la plupart des parkings publics',
            'website' => 'https://www.ratp.fr/accessibilite'
        ];
    }
    
    /**
     * Get transport recommendations
     */
    private function getTransportRecommendations($destination, $userLocation) {
        $recommendations = [];
        
        if (!$userLocation) {
            $recommendations[] = "📱 Téléchargez Citymapper pour des itinéraires personnalisés";
        }
        
        $recommendations[] = "🎫 Pensez au pass Navigo pour économiser sur vos trajets";
        $recommendations[] = "⏰ Vérifiez les horaires en temps réel avant de partir";
        $recommendations[] = "🚶‍♂️ Prévoir 5-10 minutes de marche depuis l'arrêt le plus proche";
        
        return $recommendations;
    }
    
    /**
     * Build address string
     */
    private function buildAddress($venue, $address, $city) {
        $parts = array_filter([$venue, $address, $city]);
        return implode(', ', $parts);
    }
    
    /**
     * Get default transport data when APIs fail
     */
    private function getDefaultTransportData() {
        return [
            'destination' => null,
            'coordinates' => null,
            'public_transport' => $this->getGeneralTransportInfo(),
            'parking' => [],
            'accessibility' => $this->getAccessibilityInfo(),
            'recommendations' => [
                "📱 Utilisez Citymapper ou Google Maps pour vos itinéraires",
                "🚇 Consultez les sites RATP et Île-de-France Mobilités"
            ]
        ];
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