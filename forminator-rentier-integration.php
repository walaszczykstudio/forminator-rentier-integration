<?php
// Debugowanie
//error_reporting(E_ALL);
//ini_set('display_errors', 1);

// Sprawdzenie czy WordPress jest załadowany
if (!defined('WPINC')) {
    die;
}

/**
 * Plugin Name: Integracja Forminator z Rentier.io
 * Plugin URI: https://walaszczyk.studio
 * Description: Integracja formularzy Forminator z systemem rentier.io
 * Version: 1.0.0
 * Author: walaszczyk.studio
 * Author URI: https://walaszczyk.studio
 * Text Domain: forminator-rentier-integration
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.2
 * License: GPL v2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */

// Zabezpieczenie przed bezpośrednim dostępem
if (!defined('ABSPATH')) {
    exit;
}

// Sprawdzenie czy jesteśmy w kontekście WordPress
if (!function_exists('add_action')) {
    return;
}

// Definicje stałych
define('FRI_VERSION', '1.0.0');
define('FRI_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('FRI_PLUGIN_URL', plugin_dir_url(__FILE__));

// Debug ścieżek
error_log('Plugin Dir: ' . FRI_PLUGIN_DIR);
error_log('Includes Dir: ' . FRI_PLUGIN_DIR . 'includes/');

// Sprawdzenie czy Forminator jest aktywny
function fri_check_dependencies() {
    if (!class_exists('Forminator')) {
        add_action('admin_notices', function() {
            ?>
            <div class="notice notice-error">
                <p><?php _e('Wtyczka Forminator musi być zainstalowana i aktywna aby używać integracji z Rentier.io', 'forminator-rentier-integration'); ?></p>
            </div>
            <?php
        });
        return false;
    }
    return true;
}

// Bezpośrednie ładowanie klas
if (file_exists(FRI_PLUGIN_DIR . 'includes/class-forminator-rentier-integration.php')) {
    require_once FRI_PLUGIN_DIR . 'includes/class-forminator-rentier-integration.php';
} else {
    error_log('Rentier: Nie znaleziono pliku: ' . FRI_PLUGIN_DIR . 'includes/class-forminator-rentier-integration.php');
}

if (file_exists(FRI_PLUGIN_DIR . 'includes/class-forminator-rentier-api.php')) {
    require_once FRI_PLUGIN_DIR . 'includes/class-forminator-rentier-api.php';
} else {
    error_log('Rentier: Nie znaleziono pliku: ' . FRI_PLUGIN_DIR . 'includes/class-forminator-rentier-api.php');
}

if (file_exists(FRI_PLUGIN_DIR . 'includes/class-forminator-rentier-settings.php')) {
    require_once FRI_PLUGIN_DIR . 'includes/class-forminator-rentier-settings.php';
} else {
    error_log('Rentier: Nie znaleziono pliku: ' . FRI_PLUGIN_DIR . 'includes/class-forminator-rentier-settings.php');
}

// Dodajemy ładowanie klasy geocodera
if (file_exists(FRI_PLUGIN_DIR . 'includes/class-forminator-rentier-geocoder.php')) {
    require_once FRI_PLUGIN_DIR . 'includes/class-forminator-rentier-geocoder.php';
} else {
    error_log('Rentier: Nie znaleziono pliku: ' . FRI_PLUGIN_DIR . 'includes/class-forminator-rentier-geocoder.php');
}

// Ładowanie klas
if (file_exists(FRI_PLUGIN_DIR . 'includes/class-forminator-rentier-pdf.php')) {
    require_once FRI_PLUGIN_DIR . 'includes/class-forminator-rentier-pdf.php';
} else {
    error_log('Rentier: Nie znaleziono pliku: ' . FRI_PLUGIN_DIR . 'includes/class-forminator-rentier-pdf.php');
}

// Inicjalizacja głównej klasy wtyczki
function fri_init() {
    if (fri_check_dependencies()) {
        if (class_exists('Forminator_Rentier_Integration')) {
            $plugin = new Forminator_Rentier_Integration();
            $plugin->init();
        } else {
            error_log('Klasa Forminator_Rentier_Integration nie istnieje');
        }
    }
}

// Hook inicjalizacyjny
add_action('plugins_loaded', 'fri_init');

// Aktywacja wtyczki
register_activation_hook(__FILE__, 'fri_activate');
function fri_activate() {
    // Sprawdzenie wymagań
    if (!fri_check_dependencies()) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die('Proszę najpierw zainstalować i aktywować wtyczkę Forminator.');
    }

    // Tworzenie wymaganych katalogów
    $required_dirs = array(
        FRI_PLUGIN_DIR . 'templates',
        FRI_PLUGIN_DIR . 'temp'
    );

    foreach ($required_dirs as $dir) {
        if (!file_exists($dir)) {
            if (mkdir($dir, 0755, true)) {
                error_log('Rentier: Utworzono katalog: ' . $dir);
            } else {
                error_log('Rentier: Nie udało się utworzyć katalogu: ' . $dir);
            }
        }
    }
}

// Tworzenie katalogu logów i zabezpieczenie
$log_dir = FRI_PLUGIN_DIR . 'logs';
if (!file_exists($log_dir)) {
    // Tworzenie katalogu z odpowiednimi uprawnieniami
    if (mkdir($log_dir, 0755, true)) {
        // Tworzenie .htaccess tylko jeśli katalog został utworzony
        $htaccess_content = "Order deny,allow\nDeny from all";
        $htaccess_file = $log_dir . '/.htaccess';
        file_put_contents($htaccess_file, $htaccess_content);
        
        error_log('Utworzono katalog logów i plik .htaccess');
    } else {
        error_log('Nie udało się utworzyć katalogu logów: ' . $log_dir);
    }
} 