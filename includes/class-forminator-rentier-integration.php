<?php
// Sprawdzenie czy WordPress jest załadowany
if (!defined('ABSPATH')) {
    exit;
}

class Forminator_Rentier_Integration {
    private $api;
    private $settings;
    
    public function init() {
        // Inicjalizacja klas
        $this->api = new Forminator_Rentier_API();
        $this->settings = new Forminator_Rentier_Settings();
        $this->settings->init();
        
        error_log('Rentier: Inicjalizacja integracji');
        
        // Główny hook do przetwarzania formularza
        add_action('forminator_custom_form_after_save_entry', array($this, 'process_form_submission'), 10, 3);
        
        // Debug dla żądań AJAX
        add_action('wp_ajax_forminator_submit_form_custom-forms', function() {
            error_log('Rentier: Debug - Wykryto żądanie AJAX formularza');
            error_log('Rentier: POST data raw: ' . print_r($_POST, true));
            
            // Sprawdź czy email jest w danych
            if (isset($_POST['email-1'])) {
                error_log('Rentier: Znaleziono email w POST: ' . $_POST['email-1']);
            } else {
                error_log('Rentier: Brak emaila w POST');
                error_log('Rentier: Dostępne pola: ' . implode(', ', array_keys($_POST)));
            }
            
            if (!isset($_POST['form_id'])) {
                error_log('Rentier: Brak form_id w żądaniu AJAX');
                return;
            }
            
            error_log('Rentier: Form ID: ' . $_POST['form_id']);
            
            // Przekształć dane POST na format oczekiwany przez handler
            $form_data = array(
                'address-1-street_address' => isset($_POST['address-1-street_address']) ? $_POST['address-1-street_address'] : '',
                'address-1-city' => isset($_POST['address-1-city']) ? $_POST['address-1-city'] : '',
                'number-4' => isset($_POST['number-4']) ? $_POST['number-4'] : '',
                'number-2' => isset($_POST['number-2']) ? $_POST['number-2'] : '',
                'number-1' => isset($_POST['number-1']) ? $_POST['number-1'] : '',
                'number-5' => isset($_POST['number-5']) ? $_POST['number-5'] : '',
                'select-1' => isset($_POST['select-1']) ? $_POST['select-1'] : '',
                'checkbox-1' => isset($_POST['checkbox-1']) ? $_POST['checkbox-1'] : array()
            );
            
            // Wywołaj handler
            $this->handle_form_submission($_POST['form_id'], $form_data);
        });
        
        add_action('wp_ajax_nopriv_forminator_submit_form_custom-forms', function() {
            // To samo co powyżej dla niezalogowanych użytkowników
            if (!isset($_POST['form_id'])) {
                error_log('Rentier: Brak form_id w żądaniu AJAX');
                return;
            }
            
            error_log('Rentier: Debug - Wykryto żądanie AJAX formularza (niezalogowany)');
            error_log('Rentier: Form ID: ' . $_POST['form_id']);
            error_log('Rentier: POST data: ' . print_r($_POST, true));
            
            $form_data = array(
                'address-1-street_address' => isset($_POST['address-1-street_address']) ? $_POST['address-1-street_address'] : '',
                'address-1-city' => isset($_POST['address-1-city']) ? $_POST['address-1-city'] : '',
                'number-4' => isset($_POST['number-4']) ? $_POST['number-4'] : '',
                'number-2' => isset($_POST['number-2']) ? $_POST['number-2'] : '',
                'number-1' => isset($_POST['number-1']) ? $_POST['number-1'] : '',
                'number-5' => isset($_POST['number-5']) ? $_POST['number-5'] : '',
                'select-1' => isset($_POST['select-1']) ? $_POST['select-1'] : '',
                'checkbox-1' => isset($_POST['checkbox-1']) ? $_POST['checkbox-1'] : array()
            );
            
            $this->handle_form_submission($_POST['form_id'], $form_data);
        });
        
        error_log('Rentier: Hooki zainicjalizowane');
    }

    public function process_form_submission($form_id, $response, $type) {
        error_log('Rentier: process_form_submission wywołany');
        error_log('Rentier: Form ID: ' . $form_id);
        error_log('Rentier: Response type: ' . $type);
        error_log('Rentier: Response data: ' . print_r($response, true));

        $target_form_id = get_option('fri_form_id', '');
        error_log('Rentier: Target Form ID: ' . $target_form_id);
        
        if ($form_id == $target_form_id) {
            error_log('Rentier: ID formularza zgodne, przetwarzam dane');
            // Pobierz dane z odpowiedzi
            $form_data = array(
                'address-1-street_address' => $response['address-1-street_address']['value'],
                'address-1-city' => $response['address-1-city']['value'],
                'number-4' => $response['number-4']['value'],
                'number-2' => $response['number-2']['value'],
                'number-1' => $response['number-1']['value'],
                'number-5' => $response['number-5']['value'],
                'select-1' => $response['select-1']['value'],
                'checkbox-1' => isset($response['checkbox-1']['value']) ? $response['checkbox-1']['value'] : array()
            );

            // Użyj istniejącej logiki przetwarzania
            $this->handle_form_submission($form_id, $form_data);
        } else {
            error_log('Rentier: ID formularza niezgodne, pomijam');
        }
    }

    public function handle_form_submission($form_id, $form_data) {
        // Loguj tylko start i ID formularza
        error_log('Rentier: Przetwarzanie formularza ID: ' . $form_id);
        
        $target_form_id = get_option('fri_form_id', '');
        
        if ($form_id == $target_form_id) {
            // Loguj tylko błędy geokodowania
            $geocoder = new Forminator_Rentier_Geocoder();
            $coordinates = $geocoder->geocode_address(
                $form_data['address-1-street_address'],
                $form_data['address-1-city']
            );

            if (!$coordinates) {
                error_log('Rentier Error: Błąd geokodowania adresu');
                return;
            }
            
            error_log('Rentier: Uzyskano koordynaty: ' . print_r($coordinates, true));
            
            // Przetwarzanie checkboxów dodatkowych opcji
            $checkbox_values = isset($form_data['checkbox-1']) ? (array)$form_data['checkbox-1'] : array();
            
            error_log('Rentier: Wartości checkboxów: ' . print_r($checkbox_values, true));

            // Mapowanie nazw checkboxów
            $checkbox_mapping = array(
                'one' => 'Balkon',
                'two' => 'Winda',
                'Miejsce-Postojowe' => 'Miejsce Postojowe',
                'Komórka-Piwnica' => 'Komórka lub Piwnica'
            );

            // Mapowanie standardu wykończenia
            $standard_mapping = array(
                'one' => 3, // Do Remontu
                'two' => 3, // Do Odświeżenia
                'Stan-Surowy-Deweloperski' => 3, // Stan Surowy/Deweloperski
                'Gotowe-do-Wprowadzenia' => 2, // Gotowe do Wprowadzenia
                'Wysoki-Standard-Apartament' => 1 // Wysoki Standard/Apartament
            );

            // Mapowanie typu budynku
            $building_type_mapping = array(
                'Bliźniak' => 2,
                'Kamienica' => 7,
                'Apartamentowiec' => 8,
                'Blok' => 9,
                'Szeregowiec' => 10,
                'Wolnostojący' => 11
            );

            // Określenie typu rynku na podstawie standardu
            $market_type = 2; // Domyślnie rynek wtórny
            if ($form_data['select-1'] === 'Stan-Surowy-Deweloperski') {
                $market_type = 1; // Rynek pierwotny dla stanu deweloperskiego
            }

            // Mapowanie pól formularza do formatu API
            $mapped_data = array(
                // Obowiązkowe pola
                'geo_point' => array(
                    'type' => 'Point',
                    'coordinates' => array(
                        floatval($coordinates['longitude']),
                        floatval($coordinates['latitude'])
                    )
                ),
                'area' => floatval($form_data['number-4']), // Metraż
                'market_type' => $market_type,

                // Opcjonalne pola
                'realestate_type' => 6, // Mieszkanie
                'rooms' => intval($form_data['number-2']), // Liczba pokoi
                'total_floors' => intval($form_data['number-1']), // Liczba pięter
                'build_year' => intval($form_data['number-5']), // Rok budowy
                'standard' => isset($standard_mapping[$form_data['select-1']]) 
                    ? $standard_mapping[$form_data['select-1']] 
                    : 2, // Domyślnie dobry standard

                // Opcje dodatkowe (checkboxy)
                'with_balcony' => in_array('one', $checkbox_values, true),
                'with_elevator' => in_array('two', $checkbox_values, true),
                'with_park_place' => in_array('Miejsce-Postojowe', $checkbox_values, true),
                'with_additional_space' => in_array('Komórka-Piwnica', $checkbox_values, true)
            );

            error_log('Rentier: Zmapowane dane: ' . print_r($mapped_data, true));

            // Dodanie typu budynku jeśli jest w formularzu
            if (isset($form_data['building_type']) && isset($building_type_mapping[$form_data['building_type']])) {
                $mapped_data['building_type'] = $building_type_mapping[$form_data['building_type']];
            }

            $result = $this->api->send_data($mapped_data);
            
            if ($result['success']) {
                $api_response = $result['data'];
                
                // Wyliczenie średniej ceny
                $avm_price_raw = isset($api_response['avm_price_raw']) ? $api_response['avm_price_raw'] : 0;
                $avm_price = isset($api_response['avm_price']) ? $api_response['avm_price'] : 0;
                $avg_price = isset($api_response['avg_price']) ? $api_response['avg_price'] : 0;
                $average_price = ($avm_price_raw + $avm_price) / 2;
                
                error_log('Rentier: AVM PRICE RAW ' . $avm_price_raw);
                error_log('Rentier: AVM PRICE ' . $avm_price);
                error_log('Rentier: AVG PRICE ' . $avg_price);

                error_log('Rentier: Wyliczona średnia cena: ' . $average_price);

                // Pobierz email z oryginalnego żądania POST
                $email = isset($_POST['email-1']) ? $_POST['email-1'] : '';
                error_log('Rentier: Email z POST: ' . $email);

                // Przygotuj treść maila
                $message = "Szanowni Państwo,\n\n";
                $message .= "Dziękujemy za skorzystanie z usług HomePrice. W załączeniu przesyłamy darmową szacunkową kalkulację dla Państwa nieruchomości.\n";
                $message .= "W razie jakichkolwiek pytań lub potrzeby dokładniejszej wyceny, zachęcamy do kontaktu z naszymi ekspertami.\n\n";
                $message .= "Z poważaniem,\n";
                $message .= "Zespół HomePrice.pl\n";
                $message .= "www.homeprice.pl";

                error_log('Rentier: Przygotowana treść maila: ' . $message);

                // Przygotuj dane do PDF
                $pdf_data = array(
                    'location' => array(
                        'city' => $form_data['address-1-city'],
                        'street' => $form_data['address-1-street_address']
                    ),
                    'area' => $form_data['number-4'],
                    'rooms' => $form_data['number-2'],
                    'standard' => $form_data['select-1'],
                    'estimated_price' => number_format($average_price, 2, ',', ' ')
                );

                error_log('Rentier: Próba generowania PDF z danymi: ' . print_r($pdf_data, true));

                // Generuj PDF
                try {
                    $pdf_generator = new Forminator_Rentier_PDF();
                    $pdf_file = $pdf_generator->generate_pdf($pdf_data);
                    
                    error_log('Rentier: PDF wygenerowany: ' . ($pdf_file ? $pdf_file : 'BŁĄD'));

                    if ($pdf_file && file_exists($pdf_file)) {
                        error_log('Rentier: Plik PDF istnieje: ' . $pdf_file);
                        
                        // Wyślij email z załącznikiem (główna wysyłka)
                        $bcc_email = get_option('fri_bcc_email');
                        $headers = array(
                            'Content-Type: text/plain; charset=UTF-8',
                            'From: HomePrice <noreply@homeprice.wdev.pl>'
                        );
                        if (!empty($bcc_email)) {
                            $headers[] = 'Bcc: ' . $bcc_email;
                        }
                        
                        $sent = wp_mail(
                            $email,
                            'Wycena nieruchomości - HomePrice',
                            $message,
                            $headers,
                            array($pdf_file)
                        );
                        
                        error_log('Rentier: Wynik wysyłki maila z PDF: ' . ($sent ? 'sukces' : 'błąd'));
                        
                        // Usuń tymczasowy plik
                        unlink($pdf_file);
                    } else {
                        error_log('Rentier Error: Nie udało się wygenerować pliku PDF');
                        
                        // Wyślij email bez załącznika (gdy nie ma PDF)
                        $bcc_email = get_option('fri_bcc_email');
                        $headers = array(
                            'Content-Type: text/plain; charset=UTF-8',
                            'From: HomePrice <noreply@homeprice.wdev.pl>'
                        );
                        if (!empty($bcc_email)) {
                            $headers[] = 'Bcc: ' . $bcc_email;
                        }
                        
                        wp_mail($email, 'Wycena nieruchomości - HomePrice', $message, $headers);
                    }
                } catch (Exception $e) {
                    error_log('Rentier Error: Błąd podczas generowania PDF: ' . $e->getMessage());
                    
                    // Wyślij email bez załącznika w przypadku błędu (gdy nie ma PDF)
                    $bcc_email = get_option('fri_bcc_email');
                    $headers = array(
                        'Content-Type: text/plain; charset=UTF-8',
                        'From: HomePrice <noreply@homeprice.wdev.pl>'
                    );
                    if (!empty($bcc_email)) {
                        $headers[] = 'Bcc: ' . $bcc_email;
                    }
                    
                    wp_mail($email, 'Wycena nieruchomości - HomePrice', $message, $headers);
                }

                // Loguj tylko błędy wysyłki maila
                if (!$sent) {
                    error_log('Rentier Error: Błąd wysyłki maila');
                }

                // Loguj tylko błędy generowania PDF
                if (!$pdf_file) {
                    error_log('Rentier Error: Błąd generowania PDF');
                }

                // Zapisz wynik do meta danych formularza
                if (function_exists('forminator_get_form_entry_meta')) {
                    $rentier_result = array(
                        'status' => 'success',
                        'timestamp' => current_time('mysql'),
                        'wycena' => array(
                            'avm_price_raw' => $avm_price_raw,
                            'avm_price' => $avm_price,
                            'average_price' => $average_price
                        )
                    );
                    forminator_get_form_entry_meta($form_id, 'rentier_response', $rentier_result);
                }
            } else {
                $error_result = array(
                    'status' => 'error',
                    'timestamp' => current_time('mysql'),
                    'message' => $result['message']
                );

                if (function_exists('forminator_get_form_entry_meta')) {
                    forminator_get_form_entry_meta($form_id, 'rentier_response', $error_result);
                }

                do_action('fri_api_error', $error_result, $form_data);
                
                // Dodaj komunikat o błędzie do formularza
                if (function_exists('forminator_get_form_notifications')) {
                    $notification = array(
                        'type' => 'error',
                        'message' => 'Wystąpił błąd podczas wyceny: ' . $result['message']
                    );
                    do_action('forminator_form_notification', $form_id, $notification);
                }
            }
        } else {
            error_log('Rentier: Niezgodne ID formularza');
        }
    }

    public function register_addon($addons) {
        // Tutaj później dodamy rejestrację dodatku
        return $addons;
    }

    public function init_settings() {
        // Tutaj później dodamy inicjalizację ustawień
    }

    public function enqueue_admin_assets() {
        wp_enqueue_style(
            'fri-admin-css',
            FRI_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            FRI_VERSION
        );

        wp_enqueue_script(
            'fri-admin-js',
            FRI_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            FRI_VERSION,
            true
        );
    }

    private function format_price($price) {
        return number_format($price, 2, ',', ' ') . ' PLN';
    }

    private function get_detailed_result_message($result) {
        if ($result['status'] !== 'success') {
            return $result['message'];
        }

        $message = sprintf(
            "Wycena nieruchomości:\n" .
            "- Wartość szacunkowa: %s\n" .
            "- Przedział wartości: %s - %s\n" .
            "- Dokładność wyceny: %s%%\n" .
            "- Liczba podobnych ofert: %d\n" .
            "- Promień wyszukiwania: %d m",
            $this->format_price($result['wycena']['avm_price']),
            $this->format_price($result['wycena']['deviation_price_min']),
            $this->format_price($result['wycena']['deviation_price_max']),
            $result['analiza']['accuracy'],
            $result['analiza']['real_estates'],
            $result['analiza']['distance']
        );

        return $message;
    }
} 