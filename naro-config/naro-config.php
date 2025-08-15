<?php
/*
Plugin Name: Naro Configurator
Description: Initial WordPress configuration assistant (general settings, permalinks, plugins).
Version: 0.5.20250816.004406
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

    // List of plugins to manage (default/free plugins)
    $plugins = [
        'Elementor'      => 'elementor/elementor.php',
        'UpdraftBackup'  => 'updraftplus/updraftplus.php',
        'RankMath'       => 'seo-by-rank-math/rank-math.php',
        'HelloDolly'     => 'hello-dolly/hello.php',
    ];

    // Slugs for install (for WP repo plugins)
    $plugin_slugs = [
        'Elementor'      => 'elementor',
        'UpdraftBackup'  => 'updraftplus',
        'RankMath'       => 'seo-by-rank-math',
        'HelloDolly'     => 'hello-dolly',
    ];

    // Add pro plugins from naro-config/plugins folder (ZIP support)
    $pro_plugins_dir = __DIR__ . '/plugins';
    if (is_dir($pro_plugins_dir)) {
        $pro_plugin_zips = glob($pro_plugins_dir . '/*.zip');
        foreach ($pro_plugin_zips as $zip_file) {
            $zip = new ZipArchive();
            if ($zip->open($zip_file) === TRUE) {
                // Try to find the main plugin file in the ZIP
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $entry = $zip->getNameIndex($i);
                    if (preg_match('#^[^/]+/[^/]+\.php$#', $entry)) {
                        // Extract to temp and read plugin data
                        $tmp_dir = sys_get_temp_dir() . '/naro_plugin_' . uniqid();
                        mkdir($tmp_dir);
                        $zip->extractTo($tmp_dir, $entry);
                        $plugin_file_path = $tmp_dir . '/' . $entry;
                        if (file_exists($plugin_file_path)) {
                            $plugin_data = get_plugin_data($plugin_file_path, false, false);
                            if (!empty($plugin_data['Name'])) {
                                $plugin_folder = explode('/', $entry)[0];
                                $plugin_main_file = $plugin_folder . '/' . basename($entry);
                                $plugins[$plugin_data['Name']] = $plugin_main_file;
                                // No slug for pro plugins
                                break;
                            }
                        }
                        // Clean up temp
                        if (file_exists($plugin_file_path)) unlink($plugin_file_path);
                        rmdir($tmp_dir . '/' . $plugin_folder);
                        rmdir($tmp_dir);
                    }
                }
                $zip->close();
            }
        }
    }

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
        if (!empty($_POST['custom_presets'])) {
            update_option('WPLANG', 'fr_FR');
            update_option('timezone_string', 'Europe/Paris');
            update_option('date_format', 'j F Y');
            update_option('time_format', 'G\hi');
            update_option('permalink_structure', '/%postname%/'); // Set permalink structure
        }

        include_once ABSPATH . 'wp-admin/includes/plugin-install.php';
        include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

        foreach ($plugins as $name => $main_file) {
            $action = $_POST['plugin_action'][$name] ?? 'uninstall';
            $slug = $plugin_slugs[$name] ?? null;

            $is_installed = isset($all_plugins[$main_file]);
            $is_active = in_array($main_file, $active_plugins);

            // Check if this is a pro plugin (no slug)
            $is_pro = !isset($plugin_slugs[$name]);

            if ($action === 'install' || $action === 'install_activate') {
                if (!$is_installed && $is_pro) {
                    // Find the ZIP file for this pro plugin
                    $zip_file = null;
                    foreach (glob($pro_plugins_dir . '/*.zip') as $zf) {
                        $zip = new ZipArchive();
                        if ($zip->open($zf) === TRUE) {
                            for ($i = 0; $i < $zip->numFiles; $i++) {
                                $entry = $zip->getNameIndex($i);
                                if (strpos($entry, $main_file) !== false) {
                                    $zip_file = $zf;
                                    break 2;
                                }
                            }
                            $zip->close();
                        }
                    }
                    if ($zip_file) {
                        // Unpack ZIP to wp-content/plugins
                        $zip = new ZipArchive();
                        if ($zip->open($zip_file) === TRUE) {
                            $zip->extractTo(WP_PLUGIN_DIR);
                            $zip->close();
                        }
                        // Refresh plugin list after install
                        $all_plugins = get_plugins();
                    }
                }
                if (($action === 'install' || $action === 'install_activate') && !$is_pro && !$is_installed) {
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

        if (isset($_POST['theme_action']) && is_array($_POST['theme_action'])) {
            // --- Theme Management Actions ---
            include_once ABSPATH . 'wp-admin/includes/theme.php';
            include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
            $installed_themes = wp_get_themes();
            $current_theme = wp_get_theme();
            $hello_slug = 'hello-elementor';

            foreach ($_POST['theme_action'] as $slug => $action) {
                $is_installed = isset($installed_themes[$slug]);
                $is_active = ($current_theme->get_stylesheet() === $slug);

                // Only act if the requested action does not match the current state
                if ($action === 'install' && !$is_installed) {
                    // Install theme from WP repo
                    $upgrader = new Theme_Upgrader();
                    $upgrader->install("https://downloads.wordpress.org/theme/{$slug}.latest.zip");
                } elseif ($action === 'install_activate') {
                    if (!$is_installed) {
                        $upgrader = new Theme_Upgrader();
                        $upgrader->install("https://downloads.wordpress.org/theme/{$slug}.latest.zip");
                        // Refresh theme list after install
                        $installed_themes = wp_get_themes();
                    }
                    if (!$is_active && isset($installed_themes[$slug])) {
                        switch_theme($slug);
                    }
                } elseif ($action === 'uninstall' && $is_installed && !$is_active) {
                    // Only uninstall if not active
                    delete_theme($slug);
                } elseif ($action === 'uninstall_deactivate' && $is_installed) {
                    if ($is_active) {
                        // Switch to default theme before deleting
                        switch_theme(WP_DEFAULT_THEME);
                        $current_theme = wp_get_theme();
                    }
                    // Now delete
                    delete_theme($slug);
                }
            }
        }

        echo '<div class="updated"><p>Configuration applied!</p></div>';
        // Refresh plugin and theme state after changes
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
    $permalink_structure = get_option('permalink_structure');
    ?>
    <div class="wrap">
        <h1>Naro Configurator</h1>
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
                    <th><label for="custom_presets">French Presets</label></th>
                    <td>
                        <input type="checkbox" id="custom_presets" name="custom_presets" value="1" <?php checked($site_lang === 'fr_FR' && $timezone === 'Europe/Paris' && $date_format === 'j F Y' && $time_format === 'G\hi' && $permalink_structure === '/%postname%/'); ?> />
                        <span class="description">Site Language: Français, Timezone: Paris, Date Format: j F Y, Time Format: G\hi, Permalink Structure: /%postname%/</span>
                    </td>
                </tr>
            </table>
            <h2>Plugin Management</h2>
            <table class="form-table">
                <tr>
                    <th>Plugin</th>
                    <th>Action</th>
                </tr>
                <?php ksort($plugins); foreach ($plugins as $name => $main_file): 
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
            <?php
            // Theme Management Section
            include_once ABSPATH . 'wp-admin/includes/theme.php';
            $installed_themes = wp_get_themes();
            $current_theme = wp_get_theme();
            $themes = [];

            // Add installed themes
            foreach ($installed_themes as $theme_slug => $theme_obj) {
                $themes[$theme_slug] = [
                    'Name' => $theme_obj->get('Name'),
                    'Slug' => $theme_slug,
                    'Installed' => true,
                    'Active' => ($current_theme->get_stylesheet() === $theme_slug),
                ];
            }

            // Always add Elementor Hello theme (show even if not installed)
            $hello_slug = 'hello-elementor';
            if (!isset($themes[$hello_slug])) {
                $themes[$hello_slug] = [
                    'Name' => 'Hello Elementor',
                    'Slug' => $hello_slug,
                    'Installed' => false,
                    'Active' => false,
                ];
            }
            ?>

            <h2>Theme Management</h2>
            <table class="form-table">
                <tr>
                    <th>Theme</th>
                    <th>Action</th>
                </tr>
                <?php foreach ($themes as $slug => $theme): 
                    // Determine preselected option
                    $selected = 'uninstall';
                    if ($theme['Installed'] && $theme['Active']) {
                        $selected = 'install_activate';
                    } elseif ($theme['Installed'] && !$theme['Active']) {
                        $selected = 'uninstall_deactivate';
                    }
                ?>
                <tr>
                    <td><?php echo esc_html($theme['Name']); ?></td>
                    <td>
                        <select name="theme_action[<?php echo esc_attr($slug); ?>]">
                            <option value="install" <?php selected($selected, 'install'); ?>>Install</option>
                            <option value="install_activate" <?php selected($selected, 'install_activate'); ?>>Install & Activate</option>
                            <option value="uninstall" <?php selected($selected, 'uninstall'); ?>>Uninstall</option>
                            <option value="uninstall_deactivate" <?php selected($selected, 'uninstall_deactivate'); ?>>Uninstall & Deactivate</option>
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
