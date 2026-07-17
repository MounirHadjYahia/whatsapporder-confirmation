<?php
/**
 * Plugin Name: WhatsAppOrder Confirmation
 * Plugin URI: https://sidraweb.com
 * Description: Confirmation des commandes WooCommerce via WhatsApp avec génération PDF.
 * Version: 1.0.0
 * Author: Sidraweb
 * Author URI: https://sidraweb.com
 * Text Domain: whatsapporder-confirmation
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * WC requires at least: 8.0
 * WC tested up to: 10.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/*
|--------------------------------------------------------------------------
| Constantes
|--------------------------------------------------------------------------
*/

define('WAOC_VERSION', '1.0.0');
define('WAOC_PLUGIN_FILE', __FILE__);
define('WAOC_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('WAOC_PLUGIN_URL', plugin_dir_url(__FILE__));

/*
|--------------------------------------------------------------------------
| Autoload Composer (Dompdf)
|--------------------------------------------------------------------------
*/

if (file_exists(WAOC_PLUGIN_PATH . 'vendor/autoload.php')) {
    require_once WAOC_PLUGIN_PATH . 'vendor/autoload.php';
}

/*
|--------------------------------------------------------------------------
| Classe principale
|--------------------------------------------------------------------------
*/

final class WAOC_Plugin {

    /**
     * Instance unique
     *
     * @var WAOC_Plugin|null
     */
    private static $instance = null;

    /**
     * Singleton
     */
    public static function instance(): WAOC_Plugin {

        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Constructeur
     */
    private function __construct() {

        add_action('before_woocommerce_init', [$this, 'declare_hpos_compatibility']);

        add_action('plugins_loaded', [$this, 'init']);

        register_activation_hook(
            WAOC_PLUGIN_FILE,
            [$this, 'activate']
        );

        register_deactivation_hook(
            WAOC_PLUGIN_FILE,
            [$this, 'deactivate']
        );
    }

    /**
     * Compatibilité HPOS
     */
    public function declare_hpos_compatibility(): void {

        if (
            class_exists(
                \Automattic\WooCommerce\Utilities\FeaturesUtil::class
            )
        ) {

            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
                'custom_order_tables',
                WAOC_PLUGIN_FILE,
                true
            );
        }
    }

    /**
     * Initialisation
     */
    public function init(): void {

        if (!class_exists('WooCommerce')) {

            add_action(
                'admin_notices',
                [$this, 'woocommerce_missing_notice']
            );

            return;
        }

        $this->load_textdomain();

        $this->includes();

        $this->init_classes();
    }

    /**
     * Chargement des traductions
     */
    private function load_textdomain(): void {

        load_plugin_textdomain(
            'whatsapporder-confirmation',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages'
        );
    }

    /**
     * Chargement des fichiers
     */
    private function includes(): void {

        $files = [

            'includes/helpers.php',

            'includes/class-settings.php',

            'includes/class-order-status.php',

            'includes/class-pdf-generator.php',

            'includes/class-whatsapp.php',

            'includes/class-thankyou.php',

        ];

        foreach ($files as $file) {

            $path = WAOC_PLUGIN_PATH . $file;

            if (file_exists($path)) {
                require_once $path;
            }
        }
    }

    /**
     * Initialisation des classes
     */
    private function init_classes(): void {

        if (class_exists('WAOC_Settings')) {
            WAOC_Settings::instance();
        }

        if (class_exists('WAOC_Order_Status')) {
            WAOC_Order_Status::instance();
        }

        if (class_exists('WAOC_PDF_Generator')) {
            WAOC_PDF_Generator::instance();
        }

        if (class_exists('WAOC_WhatsApp')) {
            WAOC_WhatsApp::instance();
        }

        if (class_exists('WAOC_Thankyou')) {
            WAOC_Thankyou::instance();
        }
    }

    /**
     * Activation
     */
    public function activate(): void {

        $upload_dir = wp_upload_dir();

        $pdf_dir = trailingslashit(
            $upload_dir['basedir']
        ) . 'whatsapp-orders';

        if (!file_exists($pdf_dir)) {
            wp_mkdir_p($pdf_dir);
        }

        flush_rewrite_rules();
    }

    /**
     * Désactivation
     */
    public function deactivate(): void {

        flush_rewrite_rules();
    }

    /**
     * Notification WooCommerce manquant
     */
    public function woocommerce_missing_notice(): void {

        ?>
        <div class="notice notice-error">
            <p>
                <?php esc_html_e(
                    'WhatsAppOrder Confirmation nécessite WooCommerce pour fonctionner.',
                    'whatsapporder-confirmation'
                ); ?>
            </p>
        </div>
        <?php
    }
}

/*
|--------------------------------------------------------------------------
| Démarrage du plugin
|--------------------------------------------------------------------------
*/

add_action('wp_enqueue_scripts', function () {

    wp_enqueue_style(
        'waoc-frontend',
        WAOC_PLUGIN_URL . 'assets/css/frontend.css',
        [],
        WAOC_VERSION
    );

    wp_enqueue_script(
        'waoc-frontend',
        WAOC_PLUGIN_URL . 'assets/js/frontend.js',
        [],
        WAOC_VERSION,
        true
    );

});

add_action('admin_enqueue_scripts', function () {

    wp_enqueue_style(
        'waoc-admin',
        WAOC_PLUGIN_URL . 'assets/css/admin.css',
        [],
        WAOC_VERSION
    );

    wp_enqueue_script(
        'waoc-admin',
        WAOC_PLUGIN_URL . 'assets/js/admin.js',
        [],
        WAOC_VERSION,
        true
    );

});

function waoc(): WAOC_Plugin {
    return WAOC_Plugin::instance();
}

waoc();