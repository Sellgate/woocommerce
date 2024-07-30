<?php
/**
 * Plugin Name: Sellgate
 * Plugin URI: https://sellgate.io/
 * Description: Accept a variety of crypto payments on your Woocommerce store.
 * Version: 1.0.0
 * Author: Sellgate
 * Author URI: https://sellgate.io/
 * Text Domain: sellgate
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * WC requires at least: 4.0
 * WC tested up to: 8.4
 * High-Performance Order Storage: true
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Define plugin constants
define('SELLGATE_VERSION', '1.0.0');
define('SELLGATE_PLUGIN_DIR', untrailingslashit(plugin_dir_path(__FILE__)));
define('SELLGATE_PLUGIN_URL', untrailingslashit(plugins_url('', __FILE__)));
define('SELLGATE_BASE_URL', SELLGATE_PLUGIN_URL);

// Check if WooCommerce is active
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    return;
}

// Include the main Sellgate class
if (!class_exists('WC_Sellgate')) {
    include_once dirname(__FILE__) . '/includes/class-wc-sellgate.php';
}

// Initialize the plugin
function wc_sellgate_init() {
    if (class_exists('WC_Payment_Gateway')) {
        new WC_Sellgate();
    }
}
add_action('plugins_loaded', 'wc_sellgate_init');

// Add the gateway to WooCommerce
function wc_sellgate_add_to_gateways($gateways) {
    $gateways[] = 'WC_Gateway_Sellgate';
    return $gateways;
}
add_filter('woocommerce_payment_gateways', 'wc_sellgate_add_to_gateways');