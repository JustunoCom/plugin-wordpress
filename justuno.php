<?php
/*
Plugin Name: Justuno
Plugin URI: https://www.justuno.com
Description: Grow your social audience, email subscribers & sales!
Version: 2.0
Author: Justuno
Author URI: http://www.justuno.com
License: GPLv2 or later
 */

include_once dirname(__FILE__) . '/includes/AdminPage.php';

if (!function_exists('justuno_activation')) {
    register_activation_hook(__FILE__, 'justuno_activation');
    function justuno_activation()
    {
        // send any api calls when activation
        update_option('justuno_api_key', '');
        update_option('justuno_woocommerce_token', '');
    }
}

if (!function_exists('justuno_deactivation')) {
    register_deactivation_hook(__FILE__, 'justuno_deactivation');
    function justuno_deactivation()
    {
        delete_option('justuno_options');
    }
}

if (is_admin()) {
    require_once dirname(__FILE__) . '/includes/AdminPage.php';
} else {
    require_once dirname(__FILE__) . '/includes/Frontend.php';
}
