<?php
/**
 * Admin Functions for Multisite Super Links
 * 
 * This file contains admin-specific functions for the Multisite Super Links plugin.
 * Created to resolve the missing file error.
 * 
 * @package MultisiteSuperLinks
 * @version 1.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit('Direct access forbidden.');
}

/**
 * Initialize admin functionality
 */
function msl_admin_init() {
    // Register admin styles and scripts
    add_action('admin_enqueue_scripts', 'msl_admin_enqueue_scripts');
    
    // Add settings
    register_setting('msl_settings', 'msl_options');
}

/**
 * Enqueue admin scripts and styles
 */
function msl_admin_enqueue_scripts($hook) {
    // Only load on plugin pages
    if (strpos($hook, 'multisite-super-links') === false) {
        return;
    }
    
    // Enqueue admin styles (if needed)
    // wp_enqueue_style('msl-admin-style', plugin_dir_url(__FILE__) . '../assets/admin.css');
    
    // Enqueue admin scripts (if needed)
    // wp_enqueue_script('msl-admin-script', plugin_dir_url(__FILE__) . '../assets/admin.js', array('jquery'));
}

/**
 * Add admin menu items
 */
function msl_add_admin_menu() {
    // Add main menu item for network admin
    if (is_multisite() && is_network_admin()) {
        add_menu_page(
            __('Multisite Super Links', 'multisite-super-links'),
            __('Super Links', 'multisite-super-links'),
            'manage_network',
            'multisite-super-links',
            'msl_admin_page',
            'dashicons-admin-links',
            30
        );
        
        // Add submenu items
        add_submenu_page(
            'multisite-super-links',
            __('Settings', 'multisite-super-links'),
            __('Settings', 'multisite-super-links'),
            'manage_network',
            'multisite-super-links-settings',
            'msl_settings_page'
        );
    }
}

/**
 * Main admin page callback
 */
function msl_admin_page() {
    ?>
    <div class="wrap">
        <h1><?php echo esc_html__('Multisite Super Links', 'multisite-super-links'); ?></h1>
        <p><?php echo esc_html__('Manage your multisite super links from this dashboard.', 'multisite-super-links'); ?></p>
        
        <div class="notice notice-info">
            <p><?php echo esc_html__('This is a basic admin interface. Please reinstall the plugin for full functionality.', 'multisite-super-links'); ?></p>
        </div>
        
        <!-- Basic admin interface -->
        <div class="msl-admin-content">
            <h2><?php echo esc_html__('Quick Actions', 'multisite-super-links'); ?></h2>
            <p><?php echo esc_html__('Basic functionality is available. For full features, please reinstall the plugin.', 'multisite-super-links'); ?></p>
        </div>
    </div>
    <?php
}

/**
 * Settings page callback
 */
function msl_settings_page() {
    ?>
    <div class="wrap">
        <h1><?php echo esc_html__('Super Links Settings', 'multisite-super-links'); ?></h1>
        
        <form method="post" action="options.php">
            <?php
            settings_fields('msl_settings');
            do_settings_sections('msl_settings');
            ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="msl_enable"><?php echo esc_html__('Enable Super Links', 'multisite-super-links'); ?></label>
                    </th>
                    <td>
                        <input type="checkbox" id="msl_enable" name="msl_options[enable]" value="1" />
                        <p class="description"><?php echo esc_html__('Enable or disable the super links functionality.', 'multisite-super-links'); ?></p>
                    </td>
                </tr>
            </table>
            
            <?php submit_button(); ?>
        </form>
        
        <div class="notice notice-warning">
            <p><?php echo esc_html__('This is a basic settings interface. Please reinstall the plugin for complete functionality.', 'multisite-super-links'); ?></p>
        </div>
    </div>
    <?php
}

/**
 * Handle plugin activation
 */
function msl_admin_activation() {
    // Set default options
    $default_options = array(
        'enable' => true,
        'version' => '1.0'
    );
    
    add_site_option('msl_options', $default_options);
}

/**
 * Handle plugin deactivation
 */
function msl_admin_deactivation() {
    // Clean up if needed
    // Note: Don't delete options on deactivation, only on uninstall
}

/**
 * Add admin notices
 */
function msl_admin_notices() {
    if (current_user_can('manage_options')) {
        ?>
        <div class="notice notice-warning is-dismissible">
            <p>
                <strong><?php echo esc_html__('Multisite Super Links:', 'multisite-super-links'); ?></strong>
                <?php echo esc_html__('You are using a basic version of this plugin. Please reinstall from the WordPress repository for full functionality.', 'multisite-super-links'); ?>
            </p>
        </div>
        <?php
    }
}

/**
 * Register admin hooks
 */
add_action('admin_init', 'msl_admin_init');
add_action('network_admin_menu', 'msl_add_admin_menu');
add_action('admin_menu', 'msl_add_admin_menu');
add_action('admin_notices', 'msl_admin_notices');
add_action('network_admin_notices', 'msl_admin_notices');

/**
 * Plugin activation/deactivation hooks
 */
register_activation_hook(__FILE__, 'msl_admin_activation');
register_deactivation_hook(__FILE__, 'msl_admin_deactivation');

// End of file