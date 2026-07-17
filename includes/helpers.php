<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Récupère les paramètres du plugin
 */
function waoc_get_settings(): array
{
    return get_option(
        'waoc_settings',
        []
    );
}

/**
 * Numéro WhatsApp configuré
 */
function waoc_get_whatsapp_number(): string
{
    $settings = waoc_get_settings();

    return preg_replace(
        '/[^0-9]/',
        '',
        $settings['whatsapp_number'] ?? ''
    );
}

/**
 * PDF activé ?
 */
function waoc_pdf_enabled(): bool
{
    $settings = waoc_get_settings();

    return !empty(
        $settings['enable_pdf']
    );
}

/**
 * URL PDF d'une commande
 */
function waoc_get_pdf_url(int $order_id): string
{
    $order = wc_get_order($order_id);

    if (!$order) {
        return '';
    }

    return (string) $order->get_meta(
        '_waoc_pdf_url'
    );
}

/**
 * PDF généré ?
 */
function waoc_has_pdf(int $order_id): bool
{
    return !empty(
        waoc_get_pdf_url($order_id)
    );
}

/**
 * WhatsApp envoyé ?
 */
function waoc_whatsapp_sent(int $order_id): bool
{
    $order = wc_get_order($order_id);

    if (!$order) {
        return false;
    }

    return !empty(
        $order->get_meta(
            '_waoc_whatsapp_sent'
        )
    );
}

/**
 * Date d'envoi WhatsApp
 */
function waoc_whatsapp_sent_date(
    int $order_id
): string {

    $order = wc_get_order($order_id);

    if (!$order) {
        return '';
    }

    return (string) $order->get_meta(
        '_waoc_whatsapp_sent'
    );
}

/**
 * Référence commande WAOC
 */
function waoc_reference(
    WC_Order $order
): string {

    return sprintf(
        'WOC-%06d',
        $order->get_id()
    );
}

/**
 * Dossier PDF
 */
function waoc_pdf_directory(): string
{
    $upload_dir = wp_upload_dir();

    return trailingslashit(
        $upload_dir['basedir']
    ) . 'whatsapp-orders/';
}

/**
 * URL du dossier PDF
 */
function waoc_pdf_baseurl(): string
{
    $upload_dir = wp_upload_dir();

    return trailingslashit(
        $upload_dir['baseurl']
    ) . 'whatsapp-orders/';
}

/**
 * Crée le dossier PDF si nécessaire
 */
function waoc_create_pdf_directory(): void
{
    $dir = waoc_pdf_directory();

    if (!file_exists($dir)) {
        wp_mkdir_p($dir);
    }
}

/**
 * Vérifie WooCommerce
 */
function waoc_is_woocommerce_active(): bool
{
    return class_exists('WooCommerce');
}

/**
 * Statut WhatsApp
 */
function waoc_order_is_pending_whatsapp(
    WC_Order $order
): bool {

    return (
        $order->get_status()
        === 'whatsapp-pending'
    );
}

/**
 * Génère le lien WhatsApp
 */
function waoc_order_whatsapp_link(
    WC_Order $order
): string {

    if (
        !class_exists(
            'WAOC_WhatsApp'
        )
    ) {
        return '';
    }

    return WAOC_WhatsApp::generate_order_link(
        $order
    );
}

/**
 * Écrit un message dans les logs WooCommerce
 */
function waoc_log(
    string $message
): void {

    if (
        !function_exists(
            'wc_get_logger'
        )
    ) {
        return;
    }

    $logger = wc_get_logger();

    $logger->info(
        $message,
        [
            'source' => 'waoc'
        ]
    );
}