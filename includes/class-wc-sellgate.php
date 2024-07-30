<?php
if (!defined('ABSPATH')) {
    exit;
}

class WC_Sellgate {
    public function __construct() {
        $this->init();
    }

    public function init() {
        include_once dirname(__FILE__) . '/gateways/class-wc-gateway-sellgate.php';
    }
}