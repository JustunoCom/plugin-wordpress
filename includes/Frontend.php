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
            echo '  <script
    data-cfasync="false"> window.ju_num="' . $data . '"; window.ju_asset_host = "https://staging.justone.ai/embed"; (function (i, s, o, g, r, a, m) { i[r] = i[r] || function () { (i[r].q = i[r].q || []).push(arguments); }; (a = s.createElement(o)), (m = s.getElementsByTagName(o)[0]); a.async = 1; a.src = g; m.parentNode.insertBefore(a, m); })(window, document, "script", ju_asset_host + "/ju_woocommerce_init.js?v=2", "juapp");' . $code . '</script>
';
        }
    }
}
// ------------------------------------------------
if (!function_exists('justuno_script_for_subdomain')) 
{
    function justuno_script_for_subdomain()
    {
        $baseURL = "https://justone.ai";
        $apiURL. = "https://api.justuno.com";
        $objRESTManager = new Integrations\JustRESTManager();
        $code = $objRESTManager->getConversionTrackingCodes();
        $data_field_result =get_option('justuno_sub_domain','');
        if($data_field_result)
        {
             $baseURL = get_option('justuno_sub_domain','');

        }
         global $post;
         echo '<script data-cfasync="false">window.ju4_num="07229e4953ab4497bd5af214ee2ba6b2";window.ju4_asset_host="' . $baseURL.'/embed";window.ju4_pApi="' . $baseURL.'";window.ju4_api="' . $apiURL . '";(function(i,s,o,g,r,a,m){i[r]=i[r]||function(){(i[r].q=i[r].q||[]).push(arguments)};a=s.createElement(o),m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)})(window,document,"script",ju4_asset_host+"/ju_init.js?v=2','ju4app');'</script>'
}

// ------------------------------------------------











// define the woocommerce_thankyou callback 
function action_woocommerce_thankyou($order_get_id)
{
    $code = '';
    $order_id = absint($order_get_id);
    if ($order_id > 0) {
        $order = wc_get_order($order_id);
        $code .= '
juapp("order", {
    orderID: "' . $order->get_id() . '",
    grandTotal:' . floatval($order->get_total()) . ',
    subTotal:' . floatval($order->get_subtotal()) . ',
    tax:' . floatval($order->get_total_tax()) . ',
    shipping:' . floatval($order->get_shipping_total()) . ',
    discount: ' . floatval($order->get_discount_total()) . ',
    currency: "' . $order->get_currency() . '",
    discountCodes: [' . json_encode($coupons) . '],
});';
        foreach ($order->get_items() as $item) {
            $tmpCode = '';
            foreach ($item->get_formatted_meta_data() as $meta) {
                $tmpCode .= str_replace("pa_", "", $meta->key) . ':"' . $meta->value . '",';
            }
            $code .= 'juapp("orderItem", {
productID:' . $item->get_product_id() . ',
variationID:' . ($item->get_variation_id() > 0 ? $item->get_variation_id() : $item->get_product_id()) . ',
sku:"' . $item->get_product()->get_sku() . '",
name:"' . $item->get_name() . '",
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
