<?php
/**
 * Uninstall WhatsAppOrder Confirmation
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

/*
|--------------------------------------------------------------------------
| Suppression des options
|--------------------------------------------------------------------------
*/

delete_option('waoc_settings');

/*
|--------------------------------------------------------------------------
| Suppression des métadonnées commandes
|--------------------------------------------------------------------------
*/

global $wpdb;

$wpdb->query(
    "DELETE FROM {$wpdb->postmeta}
     WHERE meta_key IN (
        '_waoc_pdf_url',
        '_waoc_whatsapp_sent'
     )"
);

/*
|--------------------------------------------------------------------------
| Suppression des PDF générés
|--------------------------------------------------------------------------
*/

$upload_dir = wp_upload_dir();

$pdf_dir = trailingslashit(
    $upload_dir['basedir']
) . 'whatsapp-orders';

if (is_dir($pdf_dir)) {

    $files = glob($pdf_dir . '/*');

    if ($files) {

        foreach ($files as $file) {

            if (is_file($file)) {
                wp_delete_file($file);
            }
        }
    }

    @rmdir($pdf_dir);
}