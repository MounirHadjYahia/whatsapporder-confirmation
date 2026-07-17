<?php

use Dompdf\Dompdf;
use Dompdf\Options;

if (!defined('ABSPATH')) {
    exit;
}

class WAOC_PDF_Generator {

    /**
     * Singleton
     */
    private static ?WAOC_PDF_Generator $instance = null;

    public static function instance(): WAOC_PDF_Generator {

        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {

        add_action(
            'woocommerce_checkout_order_processed',
            [$this, 'generate_pdf'],
            30,
            1
        );
    }

    /**
     * Génération PDF
     */
    public function generate_pdf(int $order_id): void {

        $settings = get_option('waoc_settings', []);

        if (empty($settings['enable_pdf'])) {
            return;
        }

        $order = wc_get_order($order_id);

        if (!$order) {
            return;
        }

        /*
         * PDF déjà généré
         */
        if ($order->get_meta('_waoc_pdf_url')) {
            return;
        }

        $upload_dir = wp_upload_dir();

        $pdf_directory =
            trailingslashit($upload_dir['basedir']) .
            'whatsapp-orders';

        if (!file_exists($pdf_directory)) {
            wp_mkdir_p($pdf_directory);
        }

        $filename = sprintf(
            'WOC-%s.pdf',
            $order->get_id()
        );

        $filepath =
            trailingslashit($pdf_directory) .
            $filename;

        $html = $this->get_pdf_html($order);

        try {

            $options = new Options();

            $options->set('isRemoteEnabled', true);
            $options->set('defaultFont', 'DejaVu Sans');

            $dompdf = new Dompdf($options);

            $dompdf->loadHtml($html);

            $dompdf->setPaper('A4', 'portrait');

            $dompdf->render();

            file_put_contents(
                $filepath,
                $dompdf->output()
            );

            $pdf_url =
                trailingslashit($upload_dir['baseurl']) .
                'whatsapp-orders/' .
                $filename;

            $order->update_meta_data(
                '_waoc_pdf_url',
                esc_url_raw($pdf_url)
            );

            $order->save();

        } catch (\Exception $e) {

            $order->add_order_note(
                sprintf(
                    'WAOC PDF Error: %s',
                    $e->getMessage()
                )
            );
        }
    }

    /**
     * Construction HTML du PDF
     */
    private function get_pdf_html(WC_Order $order): string {

        ob_start();

        $customer_name =
            trim(
                $order->get_billing_first_name()
                . ' ' .
                $order->get_billing_last_name()
            );

        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">

            <style>

                body{
                    font-family: DejaVu Sans, sans-serif;
                    font-size:12px;
                    color:#333;
                }

                h1{
                    text-align:center;
                    margin-bottom:20px;
                }

                .section{
                    margin-bottom:20px;
                }

                table{
                    width:100%;
                    border-collapse:collapse;
                }

                table th,
                table td{
                    border:1px solid #ddd;
                    padding:8px;
                }

                table th{
                    background:#f5f5f5;
                }

                .total{
                    margin-top:20px;
                    text-align:right;
                    font-size:16px;
                    font-weight:bold;
                }

            </style>
        </head>

        <body>

        <h1>Bon de commande</h1>

        <div class="section">

            <strong>Commande :</strong>
            #<?php echo esc_html($order->get_order_number()); ?>

            <br>

            <strong>Date :</strong>
            <?php echo esc_html(
                $order->get_date_created()
                    ? $order->get_date_created()->date('d/m/Y H:i')
                    : current_time('mysql')
            ); ?>

        </div>

        <div class="section">

            <h3>Client</h3>

            <p>
                <?php echo esc_html($customer_name); ?><br>

                <?php echo esc_html(
                    $order->get_billing_phone()
                ); ?><br>

                <?php echo esc_html(
                    $order->get_billing_email()
                ); ?>
            </p>

        </div>

        <div class="section">

            <h3>Produits</h3>

            <table>

                <thead>
                <tr>
                    <th>Produit</th>
                    <th>Quantité</th>
                    <th>Total</th>
                </tr>
                </thead>

                <tbody>

                <?php foreach ($order->get_items() as $item) : ?>

                    <tr>

                        <td>
                            <?php echo esc_html(
                                $item->get_name()
                            ); ?>
                        </td>

                        <td>
                            <?php echo esc_html(
                                $item->get_quantity()
                            ); ?>
                        </td>

                        <td>
                            <?php echo wp_strip_all_tags(
                                wc_price(
                                    $item->get_total()
                                )
                            ); ?>
                        </td>

                    </tr>

                <?php endforeach; ?>

                </tbody>

            </table>

        </div>

        <div class="total">

            Total :
            <?php echo wp_strip_all_tags(
                $order->get_formatted_order_total()
            ); ?>

        </div>

        </body>
        </html>
        <?php

        return ob_get_clean();
    }
}