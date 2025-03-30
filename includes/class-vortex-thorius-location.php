<?php
/**
 * Thorius Location Class
 * 
 * Handles location awareness for Thorius
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Thorius Location Class
 */
class Vortex_Thorius_Location {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Register AJAX handlers
        add_action('wp_ajax_vortex_thorius_update_location', array($this, 'update_location'));
        add_action('wp_ajax_nopriv_vortex_thorius_update_location', array($this, 'update_location'));
    }
    
    /**
     * Get user location
     *
     * @return array Location data
     */
    public function get_user_location() {
        // Check for cached location
        $location = $this->get_cached_location();
        
        if (!empty($location)) {
            return $location;
        }
        
        // Try to get location from browser geolocation (this happens client-side)
        $location = array(
            'source' => 'none',
            'country' => '',
            'country_code' => '',
            'region' => '',
            'city' => '',
            'latitude' => 0,
            'longitude' => 0,
            'timezone' => ''
        );
        
        // Cache empty location to avoid repeated attempts in same session
        $this->cache_location($location);
        
        return $location;
    }
    
    /**
     * Get cached location
     *
     * @return array|false Cached location data or false if not found
     */
    private function get_cached_location() {
        // Get from session for logged-in users
        if (is_user_logged_in()) {
            $user_id = get_current_user_id();
            $location = get_user_meta($user_id, 'vortex_thorius_location', true);
            
            if (!empty($location)) {
                return $location;
            }
        }
        
        // Get from cookie for all users
        if (isset($_COOKIE['vortex_thorius_location'])) {
            $cookie_data = sanitize_text_field($_COOKIE['vortex_thorius_location']);
            $location = json_decode(base64_decode($cookie_data), true);
            
            if (!empty($location)) {
                return $location;
            }
        }
        
        return false;
    }
    
    /**
     * Cache location
     *
     * @param array $location Location data
     */
    public function cache_location($location) {
        // Cache in user meta for logged-in users
        if (is_user_logged_in()) {
            $user_id = get_current_user_id();
            update_user_meta($user_id, 'vortex_thorius_location', $location);
        }
        
        // Cache in cookie for all users
        $cookie_data = base64_encode(json_encode($location));
        setcookie('vortex_thorius_location', $cookie_data, time() + (86400 * 30), '/'); // 30 days
    }
    
    /**
     * Update user location via AJAX
     */
    public function update_location() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vortex_thorius_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed', 'vortex-ai-marketplace')));
            return;
        }
        
        // Get location data
        $latitude = isset($_POST['latitude']) ? floatval($_POST['latitude']) : 0;
        $longitude = isset($_POST['longitude']) ? floatval($_POST['longitude']) : 0;
        
        if (empty($latitude) || empty($longitude)) {
            wp_send_json_error(array('message' => __('Invalid location data', 'vortex-ai-marketplace')));
            return;
        }
        
        // Get location details from coordinates
        $location = $this->get_location_from_coordinates($latitude, $longitude);
        
        // Cache location
        $this->cache_location($location);
        
        wp_send_json_success(array(
            'message' => __('Location updated', 'vortex-ai-marketplace'),
            'location' => $location
        ));
    }
    
    /**
     * Get location details from coordinates
     *
     * @param float $latitude Latitude
     * @param float $longitude Longitude
     * @return array Location data
     */
    private function get_location_from_coordinates($latitude, $longitude) {
        // Default location data
        $location = array(
            'source' => 'coordinates',
            'country' => '',
            'country_code' => '',
            'region' => '',
            'city' => '',
            'latitude' => $latitude,
            'longitude' => $longitude,
            'timezone' => ''
        );
        
        // Try to get location from geocoding service
        $geocoding_url = add_query_arg(
            array(
                'lat' => $latitude,
                'lon' => $longitude,
                'format' => 'json'
            ),
            'https://nominatim.openstreetmap.org/reverse'
        );
        
        $response = wp_remote_get($geocoding_url, array(
            'timeout' => 5,
            'headers' => array(
                'User-Agent' => 'VORTEX AI Marketplace'
            )
        ));
        
        if (is_wp_error($response)) {
            return $location;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['address'])) {
            $location['country'] = isset($data['address']['country']) ? sanitize_text_field($data['address']['country']) : '';
            $location['country_code'] = isset($data['address']['country_code']) ? sanitize_text_field(strtoupper($data['address']['country_code'])) : '';
            $location['region'] = isset($data['address']['state']) ? sanitize_text_field($data['address']['state']) : '';
            $location['city'] = isset($data['address']['city']) ? sanitize_text_field($data['address']['city']) : '';
            
            // If city is not available, try town, village, or hamlet
            if (empty($location['city'])) {
                foreach (array('town', 'village', 'hamlet') as $key) {
                    if (isset($data['address'][$key])) {
                        $location['city'] = sanitize_text_field($data['address'][$key]);
                        break;
                    }
                }
            }
        }
        
        // Try to get timezone
        $location['timezone'] = $this->get_timezone_from_coordinates($latitude, $longitude);
        
        return $location;
    }
    
    /**
     * Get timezone from coordinates
     *
     * @param float $latitude Latitude
     * @param float $longitude Longitude
     * @return string Timezone
     */
    private function get_timezone_from_coordinates($latitude, $longitude) {
        // Try to get from WordPress settings first
        $timezone = get_option('timezone_string');
        if (!empty($timezone)) {
            return $timezone;
        }
        
        // Try to get using external service
        $timezone_url = add_query_arg(
            array(
                'lat' => $latitude,
                'lng' => $longitude,
                'format' => 'json',
                'username' => 'vortex_ai_marketplace' // Replace with actual account username
            ),
            'http://api.geonames.org/timezoneJSON'
        );
        
        $response = wp_remote_get($timezone_url, array('timeout' => 5));
        
        if (is_wp_error($response)) {
            return '';
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['timezoneId'])) {
            return sanitize_text_field($data['timezoneId']);
        }
        
        return '';
    }
    
    /**
     * Get nearby events
     *
     * @param float $latitude User latitude
     * @param float $longitude User longitude
     * @param int $radius Search radius in kilometers
     * @param string $type Event type
     * @param int $limit Maximum number of events to return
     * @return array Events found near the coordinates
     */
    public function get_nearby_events($latitude, $longitude, $radius = 50, $type = '', $limit = 10) {
        global $wpdb;
        
        // Validate coordinates
        if (empty($latitude) || empty($longitude)) {
            return array();
        }
        
        // Calculate bounding box for initial filtering
        $km_per_degree_lat = 111.32; // km per degree latitude
        $km_per_degree_lon = 111.32 * cos(deg2rad($latitude)); // km per degree longitude at this latitude
        
        $lat_min = $latitude - ($radius / $km_per_degree_lat);
        $lat_max = $latitude + ($radius / $km_per_degree_lat);
        $lon_min = $longitude - ($radius / $km_per_degree_lon);
        $lon_max = $longitude + ($radius / $km_per_degree_lon);
        
        // Get events
        $events_table = $wpdb->prefix . 'vortex_events';
        $meta_table = $wpdb->prefix . 'vortex_event_meta';
        
        // Check if the tables exist
        if ($wpdb->get_var("SHOW TABLES LIKE '$events_table'") != $events_table) {
            return array();
        }
        
        // Base query
        $query = "SELECT e.*, 
                       m1.meta_value as latitude, 
                       m2.meta_value as longitude
                FROM $events_table e
                JOIN $meta_table m1 ON e.id = m1.event_id AND m1.meta_key = 'latitude'
                JOIN $meta_table m2 ON e.id = m2.event_id AND m2.meta_key = 'longitude'
                WHERE m1.meta_value BETWEEN %f AND %f
                AND m2.meta_value BETWEEN %f AND %f
                AND e.start_date >= %s";
        
        $args = array(
            $lat_min,
            $lat_max,
            $lon_min,
            $lon_max,
            current_time('mysql')
        );
        
        // Add event type filter if provided
        if (!empty($type)) {
            $query .= " AND e.type = %s";
            $args[] = $type;
        }
        
        // Order by distance and date
        $query .= " ORDER BY
                     (POW(%f - m1.meta_value, 2) + POW(%f - m2.meta_value, 2)) ASC,
                     e.start_date ASC
                   LIMIT %d";
        
        $args[] = $latitude;
        $args[] = $longitude;
        $args[] = $limit;
        
        // Prepare and execute query
        $query = $wpdb->prepare($query, $args);
        $events = $wpdb->get_results($query, ARRAY_A);
        
        // Calculate exact distances and add additional data
        foreach ($events as &$event) {
            $event_lat = floatval($event['latitude']);
            $event_lon = floatval($event['longitude']);
            
            // Calculate distance in kilometers using Haversine formula
            $distance = $this->calculate_distance($latitude, $longitude, $event_lat, $event_lon);
            $event['distance'] = round($distance, 2);
            
            // Add additional metadata
            $event['meta'] = $this->get_event_meta($event['id']);
        }
        
        return $events;
    }
    
    /**
     * Calculate distance between two coordinates using Haversine formula
     *
     * @param float $lat1 Latitude of first point
     * @param float $lon1 Longitude of first point
     * @param float $lat2 Latitude of second point
     * @param float $lon2 Longitude of second point
     * @return float Distance in kilometers
     */
    private function calculate_distance($lat1, $lon1, $lat2, $lon2) {
        $earth_radius = 6371; // Radius of the earth in km
        
        $lat_delta = deg2rad($lat2 - $lat1);
        $lon_delta = deg2rad($lon2 - $lon1);
        
        $a = sin($lat_delta/2) * sin($lat_delta/2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * 
             sin($lon_delta/2) * sin($lon_delta/2);
             
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        $distance = $earth_radius * $c;
        
        return $distance;
    }
    
    /**
     * Get event metadata
     *
     * @param int $event_id Event ID
     * @return array Event metadata
     */
    private function get_event_meta($event_id) {
        global $wpdb;
        
        $meta_table = $wpdb->prefix . 'vortex_event_meta';
        
        $query = "SELECT meta_key, meta_value FROM $meta_table WHERE event_id = %d";
        $results = $wpdb->get_results($wpdb->prepare($query, $event_id), ARRAY_A);
        
        $meta = array();
        foreach ($results as $row) {
            $meta[$row['meta_key']] = $row['meta_value'];
        }
        
        return $meta;
    }
} 