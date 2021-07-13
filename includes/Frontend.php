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
