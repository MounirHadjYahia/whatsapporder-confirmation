<?php

if (!defined('ABSPATH')) {
    exit;
}

class WAOC_Settings {

    /**
     * Instance singleton
     */
    private static ?WAOC_Settings $instance = null;

    /**
     * Singleton
     */
    public static function instance(): WAOC_Settings {

        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Constructeur
     */
    private function __construct() {

        add_action(
            'admin_menu',
            [$this, 'add_menu']
        );

        add_action(
            'admin_init',
            [$this, 'register_settings']
        );
    }

    /**
     * Menu WooCommerce
     */
    public function add_menu(): void {

        add_submenu_page(
            'woocommerce',
            __('WhatsApp Confirmation', 'whatsapporder-confirmation'),
            __('WhatsApp Confirmation', 'whatsapporder-confirmation'),
            'manage_woocommerce',
            'waoc-settings',
            [$this, 'settings_page']
        );
    }

    /**
     * Enregistrement des réglages
     */
    public function register_settings(): void {

        register_setting(
            'waoc_settings_group',
            'waoc_settings',
            [$this, 'sanitize_settings']
        );

        add_settings_section(
            'waoc_general_section',
            __('Paramètres généraux', 'whatsapporder-confirmation'),
            '__return_false',
            'waoc-settings'
        );

        /*
         * Numéro WhatsApp
         */
        add_settings_field(
            'whatsapp_number',
            __('Numéro WhatsApp', 'whatsapporder-confirmation'),
            [$this, 'whatsapp_number_callback'],
            'waoc-settings',
            'waoc_general_section'
        );

        /*
         * PDF
         */
        add_settings_field(
            'enable_pdf',
            __('Activer la génération PDF', 'whatsapporder-confirmation'),
            [$this, 'enable_pdf_callback'],
            'waoc-settings',
            'waoc_general_section'
        );

        /*
         * Message personnalisé
         */
        add_settings_field(
            'custom_message',
            __('Message WhatsApp', 'whatsapporder-confirmation'),
            [$this, 'custom_message_callback'],
            'waoc-settings',
            'waoc_general_section'
        );

            /*
             * API WhatsApp Cloud
             */
            add_settings_field(
                'enable_api',
                __('Activer WhatsApp API', 'whatsapporder-confirmation'),
                [$this, 'enable_api_callback'],
                'waoc-settings',
                'waoc_general_section'
            );

            add_settings_field(
                'whatsapp_api_token',
                __('WhatsApp API Token', 'whatsapporder-confirmation'),
                [$this, 'whatsapp_api_token_callback'],
                'waoc-settings',
                'waoc_general_section'
            );

            add_settings_field(
                'whatsapp_phone_id',
                __('WhatsApp Phone ID', 'whatsapporder-confirmation'),
                [$this, 'whatsapp_phone_id_callback'],
                'waoc-settings',
                'waoc_general_section'
            );
    }

    /**
     * Validation
     */
    public function sanitize_settings(array $input): array {

        return [

            'whatsapp_number' => sanitize_text_field(
                $input['whatsapp_number'] ?? ''
            ),

            'enable_pdf' => isset(
                $input['enable_pdf']
            ) ? 1 : 0,

            'custom_message' => sanitize_textarea_field(
                $input['custom_message'] ?? ''
            )

                ,

                'enable_api' => isset($input['enable_api']) ? 1 : 0,

                'whatsapp_api_token' => sanitize_text_field(
                    $input['whatsapp_api_token'] ?? ''
                ),

                'whatsapp_phone_id' => sanitize_text_field(
                    $input['whatsapp_phone_id'] ?? ''
                )

        ];
    }

    /**
     * Champ WhatsApp
     */
    public function whatsapp_number_callback(): void {

        $options = get_option('waoc_settings', []);

        ?>
        <input
            type="text"
            name="waoc_settings[whatsapp_number]"
            value="<?php echo esc_attr($options['whatsapp_number'] ?? ''); ?>"
            class="regular-text"
            placeholder="213555123456"
        />

        <p class="description">
            <?php esc_html_e(
                'Format international sans + ni espaces.',
                'whatsapporder-confirmation'
            ); ?>
        </p>
        <?php
    }

    /**
     * Champ PDF
     */
    public function enable_pdf_callback(): void {

        $options = get_option('waoc_settings', []);

        ?>
        <label>
            <input
                type="checkbox"
                name="waoc_settings[enable_pdf]"
                value="1"
                <?php checked(
                    $options['enable_pdf'] ?? 1,
                    1
                ); ?>
            />

            <?php esc_html_e(
                'Générer automatiquement un PDF.',
                'whatsapporder-confirmation'
            ); ?>
        </label>
        <?php
    }

    /**
     * Message WhatsApp
     */
    public function custom_message_callback(): void {

        $options = get_option('waoc_settings', []);

        $default_message =
            "Bonjour,\n\n" .
            "Je confirme ma commande {order_number}.\n\n" .
            "Montant : {order_total}\n\n" .
            "PDF : {pdf_url}";

        ?>
        <textarea
            name="waoc_settings[custom_message]"
            rows="8"
            cols="60"
        ><?php echo esc_textarea(
            $options['custom_message'] ?? $default_message
        ); ?></textarea>

        <p class="description">
            Variables disponibles :
            <code>{order_number}</code>,
            <code>{order_total}</code>,
            <code>{pdf_url}</code>
        </p>
        <?php
    }

        /**
         * Activer l'API
         */
        public function enable_api_callback(): void {

            $options = get_option('waoc_settings', []);

            ?>
            <label>
                <input
                    type="checkbox"
                    name="waoc_settings[enable_api]"
                    value="1"
                    <?php checked(
                        $options['enable_api'] ?? 0,
                        1
                    ); ?>
                />

                <?php esc_html_e(
                    'Utiliser l\'API WhatsApp Cloud pour envoyer automatiquement les messages.',
                    'whatsapporder-confirmation'
                ); ?>
            </label>
            <?php
        }

        public function whatsapp_api_token_callback(): void {

            $options = get_option('waoc_settings', []);

            ?>
            <input
                type="text"
                name="waoc_settings[whatsapp_api_token]"
                value="<?php echo esc_attr($options['whatsapp_api_token'] ?? ''); ?>"
                class="regular-text"
            />

            <p class="description">
                <?php esc_html_e(
                    'Jeton d\'accès Bearer pour l\'API WhatsApp Cloud.',
                    'whatsapporder-confirmation'
                ); ?>
            </p>
            <?php
        }

        public function whatsapp_phone_id_callback(): void {

            $options = get_option('waoc_settings', []);

            ?>
            <input
                type="text"
                name="waoc_settings[whatsapp_phone_id]"
                value="<?php echo esc_attr($options['whatsapp_phone_id'] ?? ''); ?>"
                class="regular-text"
            />

            <p class="description">
                <?php esc_html_e(
                    'Identifiant du numéro (Phone ID) fourni par la WhatsApp Cloud API.',
                    'whatsapporder-confirmation'
                ); ?>
            </p>
            <?php
        }

    /**
     * Page réglages
     */
    public function settings_page(): void {

        if (!current_user_can('manage_woocommerce')) {
            return;
        }

        ?>
        <div class="wrap">

            <h1>
                <?php esc_html_e(
                    'WhatsAppOrder Confirmation',
                    'whatsapporder-confirmation'
                ); ?>
            </h1>

            <form method="post" action="options.php">

                <?php

                settings_fields(
                    'waoc_settings_group'
                );

                do_settings_sections(
                    'waoc-settings'
                );

                submit_button();

                ?>

            </form>

        </div>
        <?php
    }
}