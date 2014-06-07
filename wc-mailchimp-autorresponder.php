<?php
/**
 * Plugin Name: Woocommerce Mailchimp Autoresponder on Cancelled Orders
 * Plugin URI: http://www.victorfalcon.es/wc-mailchimp-autoresponder-cancelled-orders
 * Description: Send an email to customers who doesn't ended the order process. This simple email can improve your conversions rates in more than a 50%.
 * Author: Víctor Falcón
 * Author URI: http://www.victorfalcon.es
 * Version: 1.0
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 */

// Add the integration to WooCommerce
function wc_mailchimp_autorresponder_cancelled_orders( $integrations ) {
  global $woocommerce;

  if ( is_object( $woocommerce ) && version_compare( $woocommerce->version, '2.1', '>=' ) ) {
    include_once( 'includes/wc-mailchim-autorresponder-cancelled-orders.php' );
    $integrations[] = 'WC_Mailchimp_Autoresponder_Cancelled_Orders';
  }

  return $integrations;
}

add_filter( 'woocommerce_integrations', 'wc_mailchimp_autorresponder_cancelled_orders', 10 );