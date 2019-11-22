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
            <?php screen_icon('plugins');?> <h2>Justuno</h2>
            <form action="options.php" method="post">
                <?php settings_fields('justuno_base_settings');?>
                <?php do_settings_sections('justuno_base_settings');?>
                <input name="Submit" class="button button-primary" type="submit" value="Save Changes" />
                <input name="button" class="button button-secondary" type="button" onclick="justuno_generate_random_token()" value="Regenerate Token" />
            </form>
            <br /><br />
            <a class="button button-primary" href="<?php echo $link; ?>" target="_blank">Justuno Dashboard</a>
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
        echo '<p class="description">You need to have an account at justuno.com in order to have the API access.</p>';
    }

    function justuno_api_key_field($args)
    {
        $data = esc_attr(get_option('justuno_api_key', ''));

        printf(
            '<input type="text" name="justuno_api_key" value="%1$s" class="all-options" id="%2$s" />',
            $data,
            $args['label_for']
        );
    }

    function justuno_woocommerce_token_description()
    {
        echo '<p class="description">This is an autogenerated token for you WooCommerce data in Justuno. Please place this token inside your dashboard to begin the data collection process.</p>';
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