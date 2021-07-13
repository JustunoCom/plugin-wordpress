<?php
// If uninstall not called from WordPress exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit();
}

// Delete option from options table
delete_option('justuno_api_key');
delete_option('justuno_woocommerce_token');
//remove any additional options and custom tables
