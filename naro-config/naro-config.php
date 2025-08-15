<?php
/*
Plugin Name: Naro Initial Configurator
Description: Automates initial WordPress setup (general settings, permalinks, plugins).
Version: 0.1.20250815.180554
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
        'Elementor'      => 'elementor/elementor.php',
        'UpdraftBackup'  => 'updraftplus/updraftplus.php',
        'RankMath'       => 'seo-by-rank-math/rank-math.php',
        'HelloDolly'     => 'hello-dolly/hello.php',
    ];

    // Slugs for install
    $plugin_slugs = [
        'Elementor'      => 'elementor',
        'UpdraftBackup'  => 'updraftplus',
        'RankMath'       => 'seo-by-rank-math',
        'HelloDolly'     => 'hello-dolly',
    ];

    // Get installed plugins and active plugins
    include_once ABSPATH . 'wp-admin/includes/plugin.php';
    $all_plugins = get_plugins();
    $active_plugins = get_option('active_plugins', []);

    // Handle form submission
    if (isset($_POST['naro_run'])) {
        // General settings
        if (isset($_POST['site_title'])) {
            update_option('blogname', sanitize_text_field($_POST['site_title']));
        }
        if (isset($_POST['tagline'])) {
            update_option('blogdescription', sanitize_text_field($_POST['tagline']));
        }
        if (isset($_POST['site_address'])) {
            $site_address = esc_url_raw($_POST['site_address']);
            update_option('siteurl', $site_address);
            update_option('home', $site_address);
        }
        if (isset($_POST['admin_email'])) {
            update_option('admin_email', sanitize_email($_POST['admin_email']));
        }
        if (!empty($_POST['french_presets'])) {
            update_option('WPLANG', 'fr_FR');
            update_option('timezone_string', 'Europe/Paris');
            update_option('date_format', 'j F Y');
            update_option('time_format', 'G\hi');
        }

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

    // Get current general settings
    $site_title = get_option('blogname');
    $tagline = get_option('blogdescription');
    $site_address = get_option('home');
    $admin_email = get_option('admin_email');
    $site_lang = get_option('WPLANG');
    $timezone = get_option('timezone_string');
    $date_format = get_option('date_format');
    $time_format = get_option('time_format');
    ?>
    <div class="wrap">
        <h1>Naro Initial Configurator</h1>
        <form method="post">
            <h2>Settings</h2>
            <table class="form-table">
                <tr>
                    <th><label for="site_title">Site Title</label></th>
                    <td><input type="text" id="site_title" name="site_title" value="<?php echo esc_attr($site_title); ?>" class="regular-text" /></td>
                </tr>
                <tr>
                    <th><label for="tagline">Tagline</label></th>
                    <td><input type="text" id="tagline" name="tagline" value="<?php echo esc_attr($tagline); ?>" class="regular-text" /></td>
                </tr>
                <tr>
                    <th><label for="site_address">Site Address</label></th>
                    <td><input type="url" id="site_address" name="site_address" value="<?php echo esc_attr($site_address); ?>" class="regular-text" /></td>
                </tr>
                <tr>
                    <th><label for="admin_email">Administration Email</label></th>
                    <td><input type="email" id="admin_email" name="admin_email" value="<?php echo esc_attr($admin_email); ?>" class="regular-text" /></td>
                </tr>
                <tr>
                    <th><label for="french_presets">French Presets</label></th>
                    <td>
                        <input type="checkbox" id="french_presets" name="french_presets" value="1" <?php checked($site_lang === 'fr_FR' && $timezone === 'Europe/Paris' && $date_format === 'j F Y' && $time_format === 'G\hi'); ?> />
                        <span class="description">Site Language: Français, Timezone: Paris, Date Format: j F Y, Time Format: G\hi</span>
                    </td>
                </tr>
            </table>
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
