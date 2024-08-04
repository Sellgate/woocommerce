<?php
/**
 * Plugin Name: Sellgate
 * Plugin URI: https://github.com/Sellgate/woocommerce
 * Description: Accept a variety of crypto payments on your Woocommerce store.
 * Version: 1.0.0
 * Author: Sellgate
 * Author URI: https://sellgate.io/
 * Text Domain: sellgate
 * Requires at least: 5.0
 * Tested up to: 6.6.1
 * WC requires at least: 5.8
 * WC tested up to: 9.1.4
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Define plugin constants
define('SELLGATE_VERSION', '1.0.0');
define('SELLGATE_PLUGIN_DIR', untrailingslashit(plugin_dir_path(__FILE__)));
define('SELLGATE_PLUGIN_URL', untrailingslashit(plugins_url('', __FILE__)));
define('SELLGATE_BASE_URL', SELLGATE_PLUGIN_URL);
define('SELLGATE_BASE_PATH', plugin_dir_path(__FILE__));

add_action( 'before_woocommerce_init', function() {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	}
} );

// Check if WooCommerce is active
function sellgate_check_woocommerce_active() {
    return in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')));
}

// Include the main Sellgate class
function sellgate_include_gateway_class() {
    if (class_exists('WC_Payment_Gateway')) {
        include_once dirname(__FILE__) . '/includes/checkout.php';
    } else {
        error_log('Sellgate: WC_Payment_Gateway class not found. WooCommerce might not be active or fully loaded.');
    }
}

// Initialize the plugin
function wc_sellgate_init() {
    if (!sellgate_check_woocommerce_active()) {
        add_action('admin_notices', 'sellgate_woocommerce_missing_notice');
        return;
    }

    sellgate_include_gateway_class();

    if (class_exists('WC_Gateway_Sellgate')) {
        add_filter('woocommerce_payment_gateways', 'wc_sellgate_add_to_gateways');
    }
}

// Admin notice for missing WooCommerce
function sellgate_woocommerce_missing_notice() {
    echo '<div class="error"><p>' . __('Sellgate requires WooCommerce to be installed and active.', 'sellgate') . '</p></div>';
}

// Add the gateway to WooCommerce
function wc_sellgate_add_to_gateways($gateways) {
    $gateways[] = 'WC_Gateway_Sellgate';
    return $gateways;
}

// Initialize blocks support
add_action('woocommerce_blocks_loaded', 'sellgate_init_blocks_support');
function sellgate_init_blocks_support() {
    if (class_exists('Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) {
        require_once dirname(__FILE__) . '/includes/blocks.php';
        add_action(
            'woocommerce_blocks_payment_method_type_registration',
            function(Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry) {
                $payment_method_registry->register( new WC_Sellgate_Blocks_Support );
            }
        );
    } else {
        error_log(print_r('Sellgate: WooCommerce Blocks support is not available.', true));
    }
}

// Hook into WordPress init action
add_action('plugins_loaded', 'wc_sellgate_init');