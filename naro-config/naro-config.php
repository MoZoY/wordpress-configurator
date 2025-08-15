<?php
/*
Plugin Name: Naro Initial Configurator
Description: Automates initial WordPress setup (general settings, permalinks, plugins).
Version: 0.1.20250815.174106
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

    // List of plugins to manage
    $plugins = [
        'HelloDolly'     => 'hello-dolly/hello.php',
        'Elementor'      => 'elementor/elementor.php',
        'UpdraftBackup'  => 'updraftplus/updraftplus.php',
        'RankMath'       => 'seo-by-rank-math/rank-math.php',
    ];

    // Slugs for install
    $plugin_slugs = [
        'HelloDolly'     => 'hello-dolly',
        'Elementor'      => 'elementor',
        'UpdraftBackup'  => 'updraftplus',
        'RankMath'       => 'seo-by-rank-math',
    ];

    // Get installed plugins and active plugins
    include_once ABSPATH . 'wp-admin/includes/plugin.php';
    $all_plugins = get_plugins();
    $active_plugins = get_option('active_plugins', []);

    // Handle form submission
    if (isset($_POST['naro_run'])) {
        include_once ABSPATH . 'wp-admin/includes/plugin-install.php';
        include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

        foreach ($plugins as $name => $main_file) {
            $action = $_POST['plugin_action'][$name] ?? 'uninstall';
            $slug = $plugin_slugs[$name];

            $is_installed = isset($all_plugins[$main_file]);
            $is_active = in_array($main_file, $active_plugins);

            if ($action === 'install' || $action === 'install_activate') {
                if (!$is_installed) {
                    $api = plugins_api('plugin_information', array('slug' => $slug));
                    $upgrader = new Plugin_Upgrader();
                    $upgrader->install($api->download_link);
                    // Refresh plugin list after install
                    $all_plugins = get_plugins();
                }
                if ($action === 'install_activate' && isset($all_plugins[$main_file]) && !is_plugin_active($main_file)) {
                    activate_plugin($main_file);
                }
            } elseif ($action === 'uninstall') {
                if ($is_active) {
                    deactivate_plugins($main_file);
                }
                if ($is_installed) {
                    delete_plugins([$main_file]);
                }
            } elseif ($action === 'deactivate') {
                if ($is_active) {
                    deactivate_plugins($main_file);
                }
            }
        }

        echo '<div class="updated"><p>Configuration applied!</p></div>';
        // Refresh plugin state after changes
        $all_plugins = get_plugins();
        $active_plugins = get_option('active_plugins', []);
    }

    ?>
    <div class="wrap">
        <h1>Naro Initial Configurator</h1>
        <form method="post">
            <h2>Plugin Management</h2>
            <table class="form-table">
                <tr>
                    <th>Plugin</th>
                    <th>Action</th>
                </tr>
                <?php foreach ($plugins as $name => $main_file): 
                    $is_installed = isset($all_plugins[$main_file]);
                    $is_active = in_array($main_file, $active_plugins);
                    $selected = 'uninstall';
                    if ($is_installed && $is_active) {
                        $selected = 'install_activate';
                    } elseif ($is_installed && !$is_active) {
                        $selected = 'deactivate';
                    }
                ?>
                <tr>
                    <td><?php echo esc_html($name); ?></td>
                    <td>
                        <select name="plugin_action[<?php echo esc_attr($name); ?>]">
                            <option value="install" <?php selected($selected, 'install'); ?>>Install</option>
                            <option value="install_activate" <?php selected($selected, 'install_activate'); ?>>Install & Activate</option>
                            <option value="uninstall" <?php selected($selected, 'uninstall'); ?>>Uninstall</option>
                            <option value="deactivate" <?php selected($selected, 'deactivate'); ?>>Deactivate</option>
                        </select>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
            <input type="hidden" name="naro_run" value="1" />
            <button class="button button-primary">Run Configuration</button>
        </form>
    </div>
    <?php
}
