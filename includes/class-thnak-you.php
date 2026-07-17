<?php

if (!defined('ABSPATH')) {
    exit;
}

class WAOC_Thankyou {

    /**
     * Instance Singleton
     */
    private static ?WAOC_Thankyou $instance = null;

    /**
     * Singleton
     */
    public static function instance(): WAOC_Thankyou {

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
            'woocommerce_thankyou',
            [$this, 'display_whatsapp_button'],
            20
        );

        add_action(
            'woocommerce_checkout_order_processed',
            [$this, 'set_whatsapp_pending_status'],
            20,
            1
        );
    }

    /**
     * Mettre le statut WhatsApp automatiquement
     */
    public function set_whatsapp_pending_status($order_id): void {

        $order = wc_get_order($order_id);

        if (!$order) {
            return;
        }

        if ($order->get_status() === 'whatsapp-pending') {
            return;
        }

        $order->update_status(
            'whatsapp-pending',
            __('En attente de confirmation WhatsApp.', 'whatsapporder-confirmation')
        );
    }

    /**
     * Affichage du bouton WhatsApp
     */
    public function display_whatsapp_button($order_id): void {

        if (!$order_id) {
            return;
        }

        $order = wc_get_order($order_id);

        if (!$order) {
            return;
        }

        $settings = get_option('waoc_settings', []);

        $phone = $settings['whatsapp_number'] ?? '';

        if (empty($phone)) {
            return;
        }

        $message = $this->build_message(
            $order,
            $settings
        );

        $whatsapp_url =
            'https://wa.me/' .
            preg_replace('/[^0-9]/', '', $phone) .
            '?text=' .
            rawurlencode($message);

        ?>
        <div class="waoc-thankyou-box" style="
            margin-top:30px;
            padding:20px;
            border:1px solid #e5e5e5;
            border-radius:8px;
            background:#f9f9f9;
            text-align:center;
        ">

            <h3>
                <?php esc_html_e(
                    'Confirmation de commande',
                    'whatsapporder-confirmation'
                ); ?>
            </h3>

            <p>
                <?php esc_html_e(
                    'Cliquez sur le bouton ci-dessous pour confirmer votre commande via WhatsApp.',
                    'whatsapporder-confirmation'
                ); ?>
            </p>

            <a
                href="<?php echo esc_url($whatsapp_url); ?>"
                class="button alt"
                target="_blank"
                rel="noopener"
                style="
                    background:#25D366;
                    color:#fff;
                    padding:12px 24px;
                    text-decoration:none;
                    border-radius:6px;
                    font-size:16px;
                "
            >
                📱 <?php esc_html_e(
                    'Confirmer via WhatsApp',
                    'whatsapporder-confirmation'
                ); ?>
            </a>

        </div>
        <?php
    }

    /**
     * Construction du message
     */
    private function build_message(
        WC_Order $order,
        array $settings
    ): string {

        $template =
            $settings['custom_message']
            ?? $this->default_message();

        $pdf_url = $order->get_meta('_waoc_pdf_url');

        $replacements = [

            '{order_number}' =>
                $order->get_order_number(),

            '{order_total}' =>
                wp_strip_all_tags(
                    $order->get_formatted_order_total()
                ),

            '{pdf_url}' =>
                $pdf_url,

            '{customer_name}' =>
                trim(
                    $order->get_billing_first_name()
                    . ' ' .
                    $order->get_billing_last_name()
                ),
        ];

        return str_replace(
            array_keys($replacements),
            array_values($replacements),
            $template
        );
    }

    /**
     * Message par défaut
     */
    private function default_message(): string {

        return
            "Bonjour,\n\n" .
            "Je confirme ma commande {order_number}.\n\n" .
            "Client : {customer_name}\n" .
            "Montant : {order_total}\n\n" .
            "Bon de commande :\n" .
            "{pdf_url}";
    }
}