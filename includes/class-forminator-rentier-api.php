<?php
// Sprawdzenie czy WordPress jest załadowany
if (!defined('ABSPATH')) {
    exit;
}

// Sprawdzenie czy jesteśmy w kontekście WordPress
if (!function_exists('add_action')) {
    return;
}

class Forminator_Rentier_API {
    private $api_url = 'https://api.rentier.io/external/api/v1/calculations/avm/';
    private $token;

    public function __construct() {
        // Token będziemy pobierać z ustawień WordPress
        $this->token = get_option('fri_api_token', '');
    }

    public function send_data($form_data) {
        // Sprawdź token przed wysłaniem
        if (empty($this->token)) {
            error_log('Rentier API Error: Brak tokenu API');
            return array(
                'success' => false,
                'message' => 'Brak tokenu API'
            );
        }

        // Dodaj więcej logowania dla debugowania
        error_log('Rentier API: Pełny token: ' . $this->token);
        error_log('Rentier API: Długość tokenu: ' . strlen($this->token));
        
        // Sprawdź czy token nie ma białych znaków
        $cleaned_token = trim($this->token);
        if ($this->token !== $cleaned_token) {
            error_log('Rentier API Warning: Token zawierał białe znaki');
        }

        $headers = array(
            'Authorization' => 'Token ' . $cleaned_token,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        );

        // Loguj pełne nagłówki (bez tokenu)
        error_log('Rentier API: Nagłówki: ' . print_r(array_keys($headers), true));

        $response = wp_remote_post($this->api_url, array(
            'method' => 'POST',
            'timeout' => 30,
            'body' => json_encode($form_data),
            'headers' => $headers
        ));

        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            error_log('Rentier API Error: ' . $error_message);
            
            if (strpos($error_message, 'timed out') !== false) {
                error_log('Rentier API Error: Timeout - spróbuj zwiększyć limit czasu lub sprawdź dostępność API');
            }
            
            return array(
                'success' => false,
                'message' => 'Błąd komunikacji z API: ' . $error_message
            );
        }

        $response_body = wp_remote_retrieve_body($response);
        $response_code = wp_remote_retrieve_response_code($response);
        $data = json_decode($response_body, true);

        // Loguj tylko błędy API
        if ($response_code !== 200) {
            error_log('Rentier API Error: Kod odpowiedzi ' . $response_code . ', treść: ' . $response_body);
            return array(
                'success' => false,
                'message' => 'Błąd API: ' . ($data['message'] ?? 'Nieznany błąd')
            );
        }

        return array(
            'success' => true,
            'data' => $data
        );
    }

    private function log_api_error($message) {
        error_log('Rentier API Error: ' . $message);
    }
} 