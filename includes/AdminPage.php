<?php
add_action('admin_menu', 'justuno_plugin_menu');
if (!function_exists('justuno_plugin_menu')) {
    function justuno_plugin_menu()
    {
        add_options_page('Justuno', 'Justuno', 'manage_options', 'justuno-settings-conf', 'justuno_plugin_page');
    }
}

if (!function_exists('justuno_plugin_page')) {
    function justuno_plugin_page()
    {
        $link = 'http://www.justuno.com/getstarted.html';
?>
        <div class="wrap">
            <?php screen_icon('plugins'); ?> <h2>Justuno</h2>
            <form action="options.php" method="post">
                <?php settings_fields('justuno_base_settings'); ?>
                <?php do_settings_sections('justuno_base_settings'); ?>
                <input name="Submit" class="button button-primary" type="submit" value="Save Changes" />
                <?php if (class_exists('WooCommerce')) : ?>
                    <input name="button" class="button button-secondary" type="button" onclick="justuno_generate_random_token()" value="Regenerate Token" />
                <?php endif; ?>
            </form>
        </div>
<?php
    }
}

add_filter('admin_enqueue_scripts', 'justuno_admin_js_files');
if (!function_exists('justuno_admin_js_files')) {
    function justuno_admin_js_files($files)
    {
        wp_enqueue_script('my_custom_script', plugins_url('/js/admin.js', __FILE__));
    }
}

add_action("admin_init", "justuno_display_options");
if (!function_exists('justuno_display_options')) {
    function justuno_display_options()
    {
        add_settings_section(
            'justuno_api_key',
            'Integration Settings',
            'justuno_api_key_description',
            'justuno_base_settings'
        );

        // Register a callback
        register_setting(
            'justuno_base_settings',
            'justuno_api_key',
            'trim'
        );

        add_settings_field(
            'justuno_api_key',
            'Justuno Account Number',
            'justuno_api_key_field',
            'justuno_base_settings',
            'justuno_api_key',
            array('label_for' => 'justuno_api_key')
        );

        // -----------------------------------------

        add_settings_section(
            'justuno_sub_domain',
            'Subdomain URL',
            'justuno_sub_domain_description',
            'justuno_base_settings'
        );

        // Register a callback
        register_setting(
            'justuno_base_settings',
            'justuno_sub_domain',
            'trim'
        );

        add_settings_field(
            'justuno_sub_domain',
            'Justuno Subdomain URL',
            'justuno_sub_domain_field',
            'justuno_base_settings',
            'justuno_sub_domain',
            array('label_for_sub_domain' => 'justuno_sub_domain')
        );

      // -----------------------------------------
        if (class_exists('WooCommerce')) {
            add_settings_section(
                'justuno_woocommerce_token',
                'WooCommerce Token',
                'justuno_woocommerce_token_description',
                'justuno_base_settings'
            );

            // Register a callback
            register_setting(
                'justuno_base_settings',
                'justuno_woocommerce_token',
                'trim'
            );

            add_settings_field(
                'justuno_woocommerce_token',
                'WooCommerce Token',
                'justuno_woocommerce_token_field',
                'justuno_base_settings',
                'justuno_woocommerce_token',
                array('label_for' => 'justuno_woocommerce_token')
            );
        }
    }

    function justuno_api_key_description()
    {
        echo '<p>You need to have an existing account at justuno.com.<br /><a target="_blank" href="https://portal.justuno.com/">Click here</a> to create a Free Trial account if needed.</p><p style="margin-bottom: 25px;">For more help with this screen, <a target="_blank" href="https://support.justuno.com/install-justuno-on-woocommerce">click here</a><br /></p>';
    }

    function justuno_api_key_field($args)
    {
        $data = esc_attr(get_option('justuno_api_key', ''));

        printf(
            '<input type="text" name="justuno_api_key" value="%1$s" class="all-options" id="%2$s" /><a style="margin-left: 20px" class="button button-primary" target="_blank" href="https://portal.justuno.com/app/account/embed-code">Find My Justuno Account Number</a>',
            $data,
            $args['label_for']
        );
    }
    
    // ------------------------------------------------
   function justuno_sub_domain_description()
    {
        echo '<p>A subdomain will act as proxy to our server that would server all the files and API endpoints to your website<br /></p>';
    }



   function justuno_sub_domain_field($args)
    {
        $result_data = esc_attr(get_option('justuno_sub_domain', ''));

        printf(
            '<input type="text" name="justuno_sub_domain" value="%3$s" class="all-options" id="%4$s" /><a style="margin-left: 20px" class="button button-primary">Add Subdomain URL</a>',
            $result_data,
            $args['label_for_sub_domain']
        );
    }
    // ------------------------------------------------

    function justuno_woocommerce_token_description()
    {
        echo '<p>This is an autogenerated token for you WooCommerce data in Justuno.<br /> Please place this token inside your dashboard to begin the data collection process.</p>';
    }

    function justuno_woocommerce_token_field($args)
    {
        $data = esc_attr(get_option('justuno_woocommerce_token', ''));

        printf(
            '<input type="text" name="justuno_woocommerce_token" class="all-options" value="%1$s" id="%2$s" />',
            $data,
            $args['label_for']
        );
    }
}
