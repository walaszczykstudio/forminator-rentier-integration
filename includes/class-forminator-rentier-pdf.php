<?php
if (!defined('ABSPATH')) {
    exit;
}

require_once(FRI_PLUGIN_DIR . 'vendor/autoload.php');

class Forminator_Rentier_PDF {
    private $mpdf;
    private $template_path;
    
    public function __construct() {
        error_log('Rentier PDF: Inicjalizacja klasy PDF');
        $this->template_path = FRI_PLUGIN_DIR . 'templates/wycena-template.jpg';
        
        // Sprawdź czy szablon istnieje i czy można go odczytać
        if (!file_exists($this->template_path)) {
            error_log('Rentier PDF Error: Brak szablonu JPG: ' . $this->template_path);
        } else {
            error_log('Rentier PDF: Znaleziono szablon: ' . $this->template_path);
            error_log('Rentier PDF: Rozmiar pliku: ' . filesize($this->template_path) . ' bajtów');
            error_log('Rentier PDF: Uprawnienia: ' . substr(sprintf('%o', fileperms($this->template_path)), -4));
        }
    }
    
    public function generate_pdf($data) {
        // Loguj tylko start generowania
        error_log('Rentier PDF: Rozpoczynam generowanie PDF');
        
        try {
            // Konfiguracja mPDF
            $config = [
                'mode' => 'utf-8',
                'format' => 'A4',
                'margin_left' => 0,
                'margin_right' => 0,
                'margin_top' => 0,
                'margin_bottom' => 0
            ];
            
            // Inicjalizacja mPDF
            $mpdf = new \Mpdf\Mpdf($config);
            
            // Dodaj stronę
            $mpdf->AddPage();
            
            // Sprawdź i dodaj tło
            if (file_exists($this->template_path)) {
                error_log('Rentier PDF: Próba dodania tła: ' . $this->template_path);
                $mpdf->SetDefaultBodyCSS('background', "url('" . $this->template_path . "')");
                $mpdf->SetDefaultBodyCSS('background-image-resize', 6);
            } else {
                error_log('Rentier PDF Error: Nie znaleziono pliku tła');
            }

            // HTML z tekstem
            $html = '
            <style>
                body { 
                    margin: 0;
                    padding: 0;
                    width: 210mm;
                    height: 297mm;
                }
                .data-row {
                    position: absolute;
                    width: 210mm;
                    text-align: center;
                    padding-left: 117mm;
                    padding-right: 20mm;
                }
                #location { 
                    left: 0;
                    padding-top: 42mm;
                    padding-bottom: 19mm;
                    top: 0;
                    font-size: 15pt;
                    text-align: center;
                }
                #area { 
                    padding-top: 20mm;
                    padding-bottom: 21mm;
                    left: 0;
                    top: 0;
                    font-size: 15pt;
                }
                #rooms { 
                    padding-top: 22mm;
                    padding-bottom: 20mm;
                    left: 0;
                    top: 0;
                    font-size: 15pt;
                }
                #standard { 
                    padding-top: 20mm;
                    padding-bottom: 20mm;
                    left: 0;
                    top: 0;
                    font-size: 15pt;
                }
                #price {
                    padding-left: 20mm;
                    padding-right: 20mm;
                    padding-top: 10mm;
                    padding-bottom: 20mm;
                    left: 0;
                    top: 0;
                    font-size: 16pt;
                    font-weight: bold;
                }
                
            </style>
            <div class="content">
                <div id="location" class="data-row">
                    ' . $data['location']['city'] . ', ' . $data['location']['street'] . '
                </div>
                <div id="area" class="data-row">
                    ' . $data['area'] . ' m²
                </div>
                <div id="rooms" class="data-row">
                    ' . $data['rooms'] . '
                </div>
                <div id="standard" class="data-row">
                    ' . $this->map_standard($data['standard']) . '
                </div>
                <div id="price" class="data-row">
                    Szacunkowa kalkulacja cenowa: ' . 
                    ($data['estimated_price'] === '0,00' ? 'Brak danych' : $data['estimated_price'] . ' PLN') . '
                </div>
            </div>';
            
            // Dodaj zawartość HTML
            $mpdf->WriteHTML($html);
            
            // Stwórz katalog temp jeśli nie istnieje
            $temp_dir = FRI_PLUGIN_DIR . 'temp';
            if (!file_exists($temp_dir)) {
                mkdir($temp_dir, 0755, true);
            }
            
            // Zapisz PDF
            $temp_file = $temp_dir . '/wycena-' . time() . '.pdf';
            $mpdf->Output($temp_file, 'F');
            
            error_log('Rentier PDF: PDF wygenerowany: ' . $temp_file);
            
            // Loguj tylko błędy
            if (!file_exists($temp_file)) {
                error_log('Rentier PDF Error: Nie udało się zapisać pliku PDF');
                return false;
            }
            
            return $temp_file;
            
        } catch (\Mpdf\MpdfException $e) {
            error_log('Rentier PDF Error: ' . $e->getMessage());
            return false;
        }
    }

    private function map_standard($standard) {
        $standards_map = [
            'one' => 'Do Remontu',
            'two' => 'Do Odświeżenia',
            'Stan-Surowy-Deweloperski' => 'Stan Surowy/Deweloperski',
            'Gotowe-do-Wprowadzenia' => 'Gotowe do Wprowadzenia',
            'Wysoki-Standard-Apartament' => 'Wysoki Standard/Apartament'
        ];

        return isset($standards_map[$standard]) ? $standards_map[$standard] : $standard;
    }
} 