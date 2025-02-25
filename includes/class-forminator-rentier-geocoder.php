<?php
// Sprawdzenie czy WordPress jest załadowany
if (!defined('ABSPATH')) {
    exit;
}

class Forminator_Rentier_Geocoder {
    private $nominatim_url = 'https://nominatim.openstreetmap.org/search';

    public function geocode_address($street, $city) {
        $address = urlencode($street . ', ' . $city . ', Poland');
        
        $response = wp_remote_get($this->nominatim_url . '?q=' . $address . '&format=json&limit=1', array(
            'headers' => array(
                'User-Agent' => 'WordPress/Forminator-Rentier-Integration'
            )
        ));

        if (is_wp_error($response)) {
            error_log('Nominatim Geocoding Error: ' . $response->get_error_message());
            return false;
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);
        
        if (empty($data)) {
            error_log('Nominatim Geocoding Error: No results found');
            return false;
        }

        return array(
            'latitude' => $data[0]['lat'],
            'longitude' => $data[0]['lon']
        );
    }
} 