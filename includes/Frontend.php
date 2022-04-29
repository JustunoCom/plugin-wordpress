<?php

require_once dirname(__FILE__) . '/JustRESTManager.php';

add_action('init', function () {
    add_rewrite_endpoint('justuno', EP_PERMALINK);
});

add_action('template_redirect', function () {
    global $wp_query;
    if ($wp_query->query_vars['pagename'] === "justuno-sync-job") {
        header('Content-type: application/json');
        $objRESTManager = new Integrations\JustRESTManager();
        $objRESTManager->entertainCall();
        die;
    }
});

add_action('wp_head', 'justuno_place_script');
if (!function_exists('justuno_place_script')) {
    function justuno_place_script()
    {
        $data = esc_attr(get_option('justuno_api_key', ''));
        $objRESTManager = new Integrations\JustRESTManager();
        $code = $objRESTManager->getConversionTrackingCodes();
        if ($data !== '' && $data !== null) {
            global $post;
            echo '<script data-cfasync="false">window.ju_num="' . $data . '";window.asset_host=\'//cdn.jst.ai/\';(function(i,s,o,g,r,a,m){i[r]=i[r]||function(){(i[r].q=i[r].q||[]).push(arguments)};a=s.createElement(o),m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)})(window,document,\'script\',asset_host+\'vck-wp.js\',\'juapp\');' . $code . ';window.juPlatform=\'wordpress\';</script>
            <script>
                function updateCartX() {
                    setTimeout(function() {
                        jQuery.ajax({
                            url: "http://justuno.bitspro.com/?pagename=justuno-sync-job&type=cart",
                            type: "GET",
                            beforeSend: function(xhr){xhr.setRequestHeader(\'Authorization\', \'Bearer q3q6rvbvjueuzh4wtyzqr9\');},
                            success: function(data) { 
                                console.log(data); 
                                juapp("cart", {
                                    total: data.total,
                                    subtotal: data.subtotal,
                                    tax: data.total_tax,
                                    shipping: data.shipping_total,
                                    currency:"USD",
                                });
                                juapp("cartItems", data.items);
                            }
                        });
                    }, 3000);
                }
                jQuery(document).ready(function() {
                    jQuery(".ajax_add_to_cart, .single_add_to_cart_button, .product-remove .remove").on("click", function(){
                        updateCartX();
                    });
                    jQuery(".woocommerce-cart-form").submit(function() {
                        updateCartX();
                    });
                });
            </script>';
        }
    }
}

// define the woocommerce_thankyou callback 
function action_woocommerce_thankyou($order_get_id)
{
    $code = '';
    $order_id = absint($order_get_id);
    if ($order_id > 0) {
        $order = wc_get_order($order_id);
        $code .= '
juapp("order", "' . $order->get_id() . '", {
total:' . floatval($order->get_total()) . ',
subtotal:' . floatval($order->get_subtotal()) . ',
tax:' . floatval($order->get_total_tax()) . ',
shipping:' . floatval($order->get_shipping_total()) . ',
currency: "' . $order->get_currency() . '"
});';
        foreach ($order->get_items() as $item) {
            $tmpCode = '';
            foreach ($item->get_meta_data() as $meta) {
                if (strpos(strtolower($meta->key), "color") !== FALSE) {
                    $tmpCode .= 'color:`' . $meta->value . '`,';
                    $tmpCode .= "\n";
                }
                if (strpos(strtolower($meta->key), "size") !== FALSE) {
                    $tmpCode .= 'size:`' . $meta->value . '`,';
                }
            }
            $code .= 'juapp("orderItem", {
productid:' . $item->get_product_id() . ',
variationid:' . ($item->get_variation_id() > 0 ? $item->get_variation_id() : $item->get_product_id()) . ',
sku:`' . $item->get_product()->get_sku() . '`,
name:`' . $item->get_name() . '`,
quantity:' . floatval($item->get_quantity()) . ',
' . $tmpCode . '
price:' . floatval($item->get_total()) . '
});';
        }
    }
    echo '<script type="text/javascript">' . $code . '</script>';
};

// add the action 
add_action('woocommerce_thankyou', 'action_woocommerce_thankyou', 10, 1);

add_action('wp_loaded', 'maybe_load_cart', 5);
/**
 * Loads the cart, session and notices should it be required.
 *
 * Note: Only needed should the site be running WooCommerce 3.6
 * or higher as they are not included during a REST request.
 *
 * @see https://plugins.trac.wordpress.org/browser/cart-rest-api-for-woocommerce/trunk/includes/class-cocart-init.php#L145
 * @since   2.0.0
 * @version 2.0.3
 */
function maybe_load_cart()
{
    if (version_compare(WC_VERSION, '3.6.0', '>=') && WC()->is_rest_api_request()) {
        if (empty($_SERVER['REQUEST_URI'])) {
            return;
        }

        require_once WC_ABSPATH . 'includes/wc-cart-functions.php';
        require_once WC_ABSPATH . 'includes/wc-notice-functions.php';

        if (null === WC()->session) {
            $session_class = apply_filters('woocommerce_session_handler', 'WC_Session_Handler'); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound

            // Prefix session class with global namespace if not already namespaced
            if (false === strpos($session_class, '\\')) {
                $session_class = '\\' . $session_class;
            }

            WC()->session = new $session_class();
            WC()->session->init();
        }

        /**
         * For logged in customers, pull data from their account rather than the
         * session which may contain incomplete data.
         */
        if (is_null(WC()->customer)) {
            if (is_user_logged_in()) {
                WC()->customer = new WC_Customer(get_current_user_id());
            } else {
                WC()->customer = new WC_Customer(get_current_user_id(), true);
            }

            // Customer should be saved during shutdown.
            add_action('shutdown', array(WC()->customer, 'save'), 10);
        }

        // Load Cart.
        if (null === WC()->cart) {
            WC()->cart = new WC_Cart();
        }
    }
} //
