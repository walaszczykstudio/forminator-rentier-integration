<?php
// Sprawdzenie czy WordPress jest załadowany
if (!defined('ABSPATH')) {
    exit;
}

class Forminator_Rentier_Settings {
    public function init() {
        add_action('admin_menu', array($this, 'add_menu_page'));
        add_action('admin_init', array($this, 'register_settings'));
    }

    public function add_menu_page() {
        add_options_page(
            'Ustawienia Rentier.io', // Tytuł strony
            'Rentier.io', // Nazwa w menu
            'manage_options', // Wymagane uprawnienia
            'rentier-settings', // Slug strony
            array($this, 'render_settings_page') // Callback renderujący stronę
        );
    }

    public function register_settings() {
        // Rejestracja sekcji ustawień
        add_settings_section(
            'rentier_api_section',
            'Ustawienia API Rentier.io',
            array($this, 'section_description'),
            'rentier-settings'
        );

        // Rejestracja pól
        add_settings_field(
            'fri_api_token',
            'Token API',
            array($this, 'token_field_html'),
            'rentier-settings',
            'rentier_api_section'
        );

        add_settings_field(
            'fri_form_id',
            'ID Formularza Forminator',
            array($this, 'form_id_field_html'),
            'rentier-settings',
            'rentier_api_section'
        );

        // Dodaj nowe pole dla BCC
        add_settings_field(
            'fri_bcc_email',
            'Email do kopii wiadomości (BCC)',
            array($this, 'bcc_email_field_html'),
            'rentier-settings',
            'rentier_api_section'
        );

        // Rejestracja ustawień
        register_setting('rentier-settings', 'fri_api_token');
        register_setting('rentier-settings', 'fri_form_id');
        register_setting('rentier-settings', 'fri_bcc_email');
    }

    public function section_description() {
        echo '<p>Skonfiguruj integrację z API Rentier.io</p>';
    }

    public function token_field_html() {
        $value = get_option('fri_api_token');
        echo '<input type="text" id="fri_api_token" name="fri_api_token" value="' . esc_attr($value) . '" class="regular-text">';
        echo '<p class="description">Wprowadź token API z panelu Rentier.io</p>';
    }

    public function form_id_field_html() {
        $value = get_option('fri_form_id');
        echo '<input type="text" id="fri_form_id" name="fri_form_id" value="' . esc_attr($value) . '" class="regular-text">';
        echo '<p class="description">Wprowadź ID formularza Forminator, który ma być zintegrowany</p>';
    }

    public function bcc_email_field_html() {
        $value = get_option('fri_bcc_email');
        echo '<input type="email" id="fri_bcc_email" name="fri_bcc_email" value="' . esc_attr($value) . '" class="regular-text">';
        echo '<p class="description">Na ten adres będą wysyłane kopie wszystkich wycen. Zostaw puste, aby wyłączyć.</p>';
    }

    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('rentier-settings');
                do_settings_sections('rentier-settings');
                submit_button('Zapisz ustawienia');
                ?>
            </form>
        </div>
        <?php
    }
} 