<?php
/*
Plugin Name: Naro Initial Configurator
Description: Automates initial WordPress setup (general settings, permalinks, plugins).
Version: 0.1.20250815.165547
Author: Naro
*/

// Enable WP_DEBUG for this plugin execution
if (!defined('WP_DEBUG')) define('WP_DEBUG', true);
if (!defined('WP_DEBUG_DISPLAY')) define('WP_DEBUG_DISPLAY', true);
ini_set('display_errors', 1);

// Only run for admins
add_action('admin_menu', function() {
    add_menu_page('Naro Config', 'Naro Config', 'manage_options', 'naro-config', 'naro_config_page');
});

function naro_config_page() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }

    if (isset($_POST['naro_run'])) {
        // Example: Update site title
        update_option('blogname', 'My Automated Site');
        // Example: Set permalink structure
        update_option('permalink_structure', '/%postname%/');
        flush_rewrite_rules();

        // Example: Install and activate a plugin
        include_once ABSPATH . 'wp-admin/includes/plugin-install.php'; // <-- Add this line
        include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        include_once ABSPATH . 'wp-admin/includes/plugin.php';
        $plugin_slug = 'hello-dolly'; // Example plugin
        $api = plugins_api('plugin_information', array('slug' => $plugin_slug));
        $upgrader = new Plugin_Upgrader();
        $upgrader->install($api->download_link);
        activate_plugin($plugin_slug . '/' . $plugin_slug . '.php');

        echo '<div class="updated"><p>Configuration applied!</p></div>';
    }
    ?>
    <div class="wrap">
        <h1>Naro Initial Configurator</h1>
        <form method="post">
            <input type="hidden" name="naro_run" value="1" />
            <button class="button button-primary">Run Initial Configuration</button>
        </form>
    </div>
    <?php
}
