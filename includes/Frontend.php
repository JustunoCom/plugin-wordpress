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
                            url: "/?pagename=justuno-sync-job&type=cart",
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
    if ($_GET['justuno-debug'] == true) {
        $order_id = absint($order_get_id);
        echo $order_id . ':::';
        $order = wc_get_order($order_id);
        print_r($order);
        exit;
    }
    try {
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
    } catch (\Error $e) {
        if ($_GET['debug'] == true) {
            print_r($e);
            exit;
        }
    }
}
;

// add the action 
add_action('woocommerce_thankyou', 'action_woocommerce_thankyou', 10, 1);