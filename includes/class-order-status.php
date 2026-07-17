<?php

if (!defined('ABSPATH')) {
    exit;
}

class WAOC_Order_Status {

    /**
     * Instance Singleton
     */
    private static ?WAOC_Order_Status $instance = null;

    /**
     * Singleton
     */
    public static function instance(): WAOC_Order_Status {

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
            'init',
            [$this, 'register_status']
        );

        add_filter(
            'wc_order_statuses',
            [$this, 'add_status']
        );

        add_action(
            'woocommerce_order_status_changed',
            [$this, 'log_status_change'],
            10,
            4
        );
    }

    /**
     * Enregistrement du statut
     */
    public function register_status(): void {

        register_post_status(
            'wc-whatsapp-pending',
            [
                'label'                     => __('En attente WhatsApp', 'whatsapporder-confirmation'),
                'public'                    => true,
                'exclude_from_search'       => false,
                'show_in_admin_all_list'    => true,
                'show_in_admin_status_list' => true,

                'label_count' => _n_noop(
                    'En attente WhatsApp (%s)',
                    'En attente WhatsApp (%s)',
                    'whatsapporder-confirmation'
                )
            ]
        );
    }

    /**
     * Ajout à la liste WooCommerce
     */
    public function add_status(array $statuses): array {

        $new_statuses = [];

        foreach ($statuses as $key => $label) {

            $new_statuses[$key] = $label;

            /*
             * Position après Pending Payment
             */
            if ($key === 'wc-pending') {

                $new_statuses['wc-whatsapp-pending']
                    = __('En attente WhatsApp', 'whatsapporder-confirmation');
            }
        }

        return $new_statuses;
    }

    /**
     * Historique commande
     */
    public function log_status_change(
        int $order_id,
        string $old_status,
        string $new_status,
        WC_Order $order
    ): void {

        if ($new_status !== 'whatsapp-pending') {
            return;
        }

        $order->add_order_note(
            __('Commande en attente de confirmation WhatsApp.', 'whatsapporder-confirmation')
        );

        // Tenter envoi automatique via WhatsApp
        if (class_exists('WAOC_WhatsApp')) {
            try {
                WAOC_WhatsApp::send_order_message($order);
            } catch (\Throwable $e) {
                // Ne pas casser le flux si erreur
                waoc_log('Error sending WhatsApp message: ' . $e->getMessage());
            }
        }
    }

    /**
     * Définir le statut automatiquement
     */
    public static function set_default_status(int $order_id): void {

        $order = wc_get_order($order_id);

        if (!$order) {
            return;
        }

        $order->set_status('whatsapp-pending');

        $order->save();
    }
}