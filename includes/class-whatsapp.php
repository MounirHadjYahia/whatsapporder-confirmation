<?php

if (!defined('ABSPATH')) {
    exit;
}

class WAOC_WhatsApp {

    /**
     * Singleton
     */
    private static ?WAOC_WhatsApp $instance = null;

    public static function instance(): WAOC_WhatsApp {

        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Constructeur
     */
    private function __construct() {
    }

    /**
     * Retourne le numéro WhatsApp configuré
     */
    public static function get_phone_number(): string {

        $settings = get_option('waoc_settings', []);

        return preg_replace(
            '/[^0-9]/',
            '',
            $settings['whatsapp_number'] ?? ''
        );
    }

    /**
     * Génère le message WhatsApp
     */
    public static function build_message(WC_Order $order): string {

        $settings = get_option('waoc_settings', []);

        $template =
            $settings['custom_message']
            ?? self::default_template();

        $pdf_url = $order->get_meta('_waoc_pdf_url');

        $customer_name = trim(
            $order->get_billing_first_name()
            . ' ' .
            $order->get_billing_last_name()
        );

        $replacements = [

            '{order_number}' =>
                $order->get_order_number(),

            '{order_total}' =>
                wp_strip_all_tags(
                    $order->get_formatted_order_total()
                ),

            '{customer_name}' =>
                $customer_name,

            '{customer_phone}' =>
                $order->get_billing_phone(),

            '{customer_email}' =>
                $order->get_billing_email(),

            '{pdf_url}' =>
                $pdf_url,
        ];

        return str_replace(
            array_keys($replacements),
            array_values($replacements),
            $template
        );
    }

    /**
     * Génère le lien WhatsApp complet
     */
    public static function get_order_url(WC_Order $order): string {

        $phone = self::get_phone_number();

        if (empty($phone)) {
            return '';
        }

        $message = self::build_message($order);

        return sprintf(
            'https://wa.me/%s?text=%s',
            $phone,
            rawurlencode($message)
        );
    }

    /**
     * Alias pour compatibilité : génère le lien WhatsApp complet
     */
    public static function generate_order_link(WC_Order $order): string
    {
        return self::get_order_url($order);
    }

    /**
     * Envoie le message WhatsApp pour une commande (via API si configurée)
     * Retourne true si envoi OK.
     */
    public static function send_order_message(WC_Order $order): bool
    {
        if (!$order) {
            return false;
        }

        $order_id = $order->get_id();

        if (waoc_whatsapp_sent($order_id)) {
            return false;
        }

        $settings = get_option('waoc_settings', []);

        $message = self::build_message($order);

        // Destinataire (client)
        $to = preg_replace('/[^0-9]/', '', (string) $order->get_billing_phone());

        if (empty($to)) {
            waoc_log('WhatsApp send failed: missing customer phone');
            return false;
        }

        // Si API activée, tenter l'envoi via WhatsApp Cloud API
        if (!empty($settings['enable_api'])
            && !empty($settings['whatsapp_api_token'])
            && !empty($settings['whatsapp_phone_id'])) {

            $token = $settings['whatsapp_api_token'];
            $phone_id = $settings['whatsapp_phone_id'];

            $endpoint = sprintf(
                'https://graph.facebook.com/v17.0/%s/messages',
                rawurlencode($phone_id)
            );

            $body = [
                'messaging_product' => 'whatsapp',
                'to' => $to,
                'type' => 'text',
                'text' => [
                    'body' => $message,
                ],
            ];

            $args = [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/json',
                ],
                'body' => wp_json_encode($body),
                'timeout' => 20,
            ];

            $response = wp_remote_post($endpoint, $args);

            if (is_wp_error($response)) {
                waoc_log('WhatsApp API error: ' . $response->get_error_message());
                return false;
            }

            $code = wp_remote_retrieve_response_code($response);

            if ($code >= 200 && $code < 300) {
                // succès
                $order->update_meta_data('_waoc_whatsapp_sent', current_time('mysql'));
                $order->update_meta_data('_waoc_whatsapp_sent_date', current_time('mysql'));
                $order->add_order_note(__('Message WhatsApp envoyé automatiquement.', 'whatsapporder-confirmation'));
                $order->save();

                waoc_log('WhatsApp message sent for order ' . $order_id);

                return true;
            }

            $body_resp = wp_remote_retrieve_body($response);
            waoc_log('WhatsApp API unexpected response: ' . $code . ' ' . $body_resp);
            return false;
        }

        // Sinon, pas d'envoi automatique possible (seulement lien manuel)
        waoc_log('WhatsApp API not configured, cannot send automatically');
        return false;
    }

    /**
     * Vérifie si WhatsApp est configuré
     */
    public static function is_configured(): bool {

        return !empty(
            self::get_phone_number()
        );
    }

    /**
     * Template par défaut
     */
    private static function default_template(): string {

        return
            "Bonjour,\n\n" .
            "Je confirme ma commande {order_number}.\n\n" .
            "Client : {customer_name}\n" .
            "Téléphone : {customer_phone}\n" .
            "Montant : {order_total}\n\n" .
            "Bon de commande :\n" .
            "{pdf_url}";
    }
}