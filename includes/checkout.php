<?php

if (!defined('ABSPATH')) {
    exit;
}

class WC_Gateway_Sellgate extends WC_Payment_Gateway
{
    private $supported_cryptocurrencies;

    public function __construct()
    {
        $this->id = 'sellgate';
        $this->icon = 'yes' === $this->get_option('show_logo') ? SELLGATE_BASE_URL . '/assets/logo.png' : '';
        $this->has_fields = false;
        $this->method_title = __('Sellgate', 'sellgate');
        $this->method_description = __('Accept cryptocurrencies on your WooCommerce store.', 'sellgate');

        $this->supported_cryptocurrencies = [
            ["network" => "BTC", "coin" => "BTC", "min_value" => 0.00008000],
            ["network" => "ETH", "coin" => "ETH", "min_value" => 0.00150000],
            ["network" => "BCH", "coin" => "BCH", "min_value" => 0.00050000],
            ["network" => "LTC", "coin" => "LTC", "min_value" => 0.00200000],
            ["network" => "ERC20", "coin" => "USDC", "min_value" => 10.00000000],
            ["network" => "DOGE", "coin" => "DOGE", "min_value" => 10.00000000],
            ["network" => "TRX", "coin" => "TRX", "min_value" => 10.00000000],
            ["network" => "ERC20", "coin" => "1INCH", "min_value" => 25.00000000],
            ["network" => "ERC20", "coin" => "BNB", "min_value" => 0.03000000],
            ["network" => "ERC20", "coin" => "DAI", "min_value" => 10.00000000],
            ["network" => "ERC20", "coin" => "LINK", "min_value" => 1.50000000],
            ["network" => "ERC20", "coin" => "MKR", "min_value" => 0.01000000],
            ["network" => "ERC20", "coin" => "NEXO", "min_value" => 10.00000000],
            ["network" => "ERC20", "coin" => "TUSD", "min_value" => 10.00000000],
            ["network" => "ERC20", "coin" => "USDT", "min_value" => 10.00000000],
            ["network" => "BEP20", "coin" => "1INCH", "min_value" => 1.00000000],
            ["network" => "BEP20", "coin" => "ADA", "min_value" => 1.00000000],
            ["network" => "BEP20", "coin" => "BNB", "min_value" => 0.00100000],
            ["network" => "BEP20", "coin" => "DAI", "min_value" => 1.00000000],
            ["network" => "BEP20", "coin" => "DOGE", "min_value" => 10.00000000],
            ["network" => "BEP20", "coin" => "ETH", "min_value" => 0.00100000],
            ["network" => "BEP20", "coin" => "LTC", "min_value" => 0.00200000],
            ["network" => "BEP20", "coin" => "MATIC", "min_value" => 1.00000000],
            ["network" => "BEP20", "coin" => "USDC", "min_value" => 1.00000000],
            ["network" => "BEP20", "coin" => "USDT", "min_value" => 1.00000000],
            ["network" => "BEP20", "coin" => "XRP", "min_value" => 2.00000000],
            ["network" => "POLYGON", "coin" => "MATIC", "min_value" => 0.50000000],
            ["network" => "POLYGON", "coin" => "MANA", "min_value" => 0.50000000],
            ["network" => "POLYGON", "coin" => "USDC", "min_value" => 0.50000000],
            ["network" => "POLYGON", "coin" => "USDT", "min_value" => 0.50000000],
            ["network" => "BASE", "coin" => "ETH", "min_value" => 0.00030000],
            ["network" => "BASE", "coin" => "USDC", "min_value" => 3.00000000],
            ["network" => "BASE", "coin" => "DAI", "min_value" => 3.00000000]
        ];

        $this->init_form_fields();
        $this->init_settings();

        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->enabled = $this->get_option('enabled');
        $this->debug_mode = 'yes' === $this->get_option('debug_mode');

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
        add_action('woocommerce_api_sellgate_webhook_handler', [$this, 'webhook_handler']);
    }

    public function init_form_fields()
    {
        $this->form_fields = array(
            'enabled' => array(
                'title' => __('Enable/Disable', 'sellgate'),
                'type' => 'checkbox',
                'label' => __('Enable Sellgate', 'sellgate'),
                'default' => 'yes'
            ),
            'title' => array(
                'title' => __('Title', 'sellgate'),
                'type' => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'sellgate'),
                'default' => __('Cryptocurrency', 'sellgate'),
                'desc_tip' => true,
            ),
            'description' => array(
                'title' => __('Description', 'sellgate'),
                'type' => 'textarea',
                'description' => __('This controls the description which the user sees during checkout.', 'sellgate'),
                'default' => __('Pay with cryptocurrencies.', 'sellgate')
            ),
            'show_logo' => array(
                'title' => __('Show Logo', 'sellgate'),
                'type' => 'checkbox',
                'label' => __('Display Sellgate logo on checkout', 'sellgate'),
                'default' => 'yes',
                'desc_tip' => true,
            ),
            'debug_mode' => array(
                'title' => __('Debug mode', 'sellgate'),
                'label' => __('Enable Debug Mode', 'sellgate'),
                'type' => 'checkbox',
                'description' => __('Enable logging for debugging.', 'sellgate'),
                'default' => 'no',
                'desc_tip' => true,
            ),
        );

        // Add cryptocurrency address fields
        foreach ($this->supported_cryptocurrencies as $crypto) {
            $this->form_fields[strtolower($crypto['network'] . '_' . $crypto['coin'] . '_address')] = array(
                'title' => sprintf(__('%s (%s) Address', 'sellgate'), $crypto['coin'], $crypto['network']),
                'type' => 'text',
                'description' => sprintf(__('Enter your %s (%s) address.', 'sellgate'), $crypto['coin'], $crypto['network']),
                'default' => '',
                'desc_tip' => true,
            );
        }
    }

    public function process_payment($order_id)
    {
        $order = wc_get_order($order_id);
        
        try {
            $payment_url = $this->generate_sellgate_payment($order);

            if ($this->debug_mode) {
                error_log(print_r('Payment process for order ' . $order_id . ' returned: ' . $payment_url, true));
            }

            if ($payment_url) {
                $order->update_status('pending', __('Awaiting Sellgate payment', 'sellgate'));
                return array(
                    'result' => 'success',
                    'redirect' => $payment_url
                );
            } else {
                throw new Exception(__('Payment Gateway Error: Empty response received.', 'sellgate'));
            }
        } catch (Exception $e) {
            $message = $e->getMessage();
            if ($this->debug_mode) {
                error_log($message);
            }

            wc_add_notice($message, 'error');
            return array(
                'result' => 'failure',
                'redirect' => wc_get_checkout_url(),
            );
        }
    }

    private function generate_sellgate_payment($order)
    {
        $api_url = 'https://api.sellgate.io/v1/checkout';

        $crypto = array();
        foreach ($this->supported_cryptocurrencies as $supported_crypto) {
            $address_option = strtolower($supported_crypto['network'] . '_' . $supported_crypto['coin'] . '_address');
            $address = $this->get_option($address_option);
            if ($address) {
                $crypto[] = array(
                    'network' => $supported_crypto['network'],
                    'coin' => $supported_crypto['coin'],
                    'address' => $address
                );
            }
        }

        $supported_currencies = array(
            "USD", "EUR", "GBP", "CAD", "JPY", "AED", "MYR", "IDR", "THB", "CHF",
            "SGD", "RUB", "ZAR", "TRY", "LKR", "RON", "BGN", "HUF", "CZK", "PHP",
            "PLN", "UGX", "MXN", "INR", "HKD", "CNY", "BRL", "DKK", "TWD", "AUD",
            "NGN", "SEK", "NOK"
        );

        $order_currency = $order->get_currency();
        if (!in_array($order_currency, $supported_currencies)) {
            throw new Exception(__('Unsupported currency:', 'sellgate') . ' ' . $order_currency);
        }

        $body = array(
            'title' => get_bloginfo('name') . ' Order #' . $order->get_order_number(),
            'description' => 'Payment for ' . get_bloginfo('name') . ' order #' . $order->get_order_number(),
            'price' => $order->get_total(),
            'currency' => $order_currency,
            'crypto' => $crypto,
            'webhook' => add_query_arg(
                array(
                    'wc-api' => 'sellgate_webhook_handler',
                    'key' => $order->get_id()
                ),
                home_url('/')
            ),
            'return' => $this->get_return_url($order)
        );

        $args = array(
            'body' => json_encode($body),
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
            'timeout' => 60
        );

        $response = wp_remote_post($api_url, $args);

        if (is_wp_error($response)) {
            throw new Exception(__('Payment error:', 'sellgate') . 'Sellgate API error: ' . $response->get_error_message());
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($body['url'])) {
            return $body['url'];
        } else {
            throw new Exception(__('Payment Gateway Error: Invalid response from Sellgate API.', 'sellgate'));
        }
    }

    public function webhook_handler() 
    {
        $data = json_decode(file_get_contents('php://input'), true);
    
        if (!isset($_GET['key'])) {
            wp_die('No key provided', 'Invalid webhook', 400);
        }
    
        $order_key = sanitize_text_field($_REQUEST['key']);
        $order = wc_get_order($order_key);
    
        if (!$order) {
            wp_die('Order not found', 'Invalid webhook', 404);
        }
    
        // Check if this hasn't already been processed
        if ($order->get_status() == 'pending') {
            // Mark payment as complete
            $order->payment_complete(); 
    
            // Update order status
            $order->update_status('completed', __('Order completed via Sellgate payment.', 'sellgate'));
        }
    
        wp_die('Webhook processed successfully', 'Success', 200);
    }
}