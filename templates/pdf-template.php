<?php

if (!defined('ABSPATH')) {
    exit;
}

/** @var WC_Order $order */

$customer_name = trim(
    $order->get_billing_first_name() .
    ' ' .
    $order->get_billing_last_name()
);

$reference = function_exists('waoc_reference')
    ? waoc_reference($order)
    : 'WOC-' . $order->get_id();

?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">

<style>

body{
    font-family: DejaVu Sans, sans-serif;
    font-size:12px;
    color:#333;
    margin:30px;
}

.header{
    text-align:center;
    margin-bottom:30px;
}

.logo{
    margin-bottom:15px;
}

h1{
    margin:0;
    font-size:24px;
}

.subtitle{
    color:#777;
    font-size:13px;
}

.section{
    margin-bottom:25px;
}

.section-title{
    font-size:16px;
    font-weight:bold;
    margin-bottom:10px;
    border-bottom:1px solid #ddd;
    padding-bottom:5px;
}

table{
    width:100%;
    border-collapse:collapse;
}

table th{
    background:#f5f5f5;
}

table th,
table td{
    border:1px solid #ddd;
    padding:8px;
}

.text-right{
    text-align:right;
}

.total-box{
    margin-top:20px;
    text-align:right;
}

.total{
    font-size:18px;
    font-weight:bold;
}

.footer{
    margin-top:40px;
    text-align:center;
    font-size:11px;
    color:#777;
}

</style>

</head>

<body>

<div class="header">

    <h1>Bon de commande</h1>

    <div class="subtitle">
        WhatsAppOrder Confirmation
    </div>

</div>

<div class="section">

    <div class="section-title">
        Informations commande
    </div>

    <p>
        <strong>Référence :</strong>
        <?php echo esc_html($reference); ?>
    </p>

    <p>
        <strong>Commande WooCommerce :</strong>
        #<?php echo esc_html($order->get_order_number()); ?>
    </p>

    <p>
        <strong>Date :</strong>
        <?php echo esc_html(
            $order->get_date_created()
                ? $order->get_date_created()->date('d/m/Y H:i')
                : current_time('mysql')
        ); ?>
    </p>

</div>

<div class="section">

    <div class="section-title">
        Client
    </div>

    <p>
        <strong>Nom :</strong>
        <?php echo esc_html($customer_name); ?>
    </p>

    <p>
        <strong>Téléphone :</strong>
        <?php echo esc_html(
            $order->get_billing_phone()
        ); ?>
    </p>

    <p>
        <strong>Email :</strong>
        <?php echo esc_html(
            $order->get_billing_email()
        ); ?>
    </p>

    <p>
        <strong>Adresse :</strong><br>

        <?php echo nl2br(
            esc_html(
                $order->get_formatted_billing_address()
            )
        ); ?>
    </p>

</div>

<div class="section">

    <div class="section-title">
        Produits commandés
    </div>

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

<div class="total-box">

    <div class="total">
        Total :
        <?php echo wp_strip_all_tags(
            $order->get_formatted_order_total()
        ); ?>
    </div>

</div>

<div class="footer">

    Généré automatiquement le
    <?php echo esc_html(
        current_time('d/m/Y H:i')
    ); ?>

    <br><br>

    <?php echo esc_html(
        get_bloginfo('name')
    ); ?>

</div>

</body>
</html>