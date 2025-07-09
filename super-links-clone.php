<?php
/**
 * Plugin Name: Super Links Clone
 * Plugin URI: https://exemplo.com/super-links-clone
 * Description: Plugin completo para clonagem de páginas, camuflagem de links de afiliados, ativação dupla de cookies, links inteligentes e muito mais. Clone do plugin Super Links.
 * Version: 1.0.0
 * Author: Desenvolvedor
 * License: GPL v2 or later
 * Text Domain: super-links-clone
 * Domain Path: /languages
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('SLC_VERSION', '1.0.0');
define('SLC_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SLC_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('SLC_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Include required files
require_once SLC_PLUGIN_PATH . 'includes/class-super-links-clone.php';
require_once SLC_PLUGIN_PATH . 'includes/class-slc-admin.php';
require_once SLC_PLUGIN_PATH . 'includes/class-slc-link-manager.php';
require_once SLC_PLUGIN_PATH . 'includes/class-slc-page-cloner.php';
require_once SLC_PLUGIN_PATH . 'includes/class-slc-analytics.php';
require_once SLC_PLUGIN_PATH . 'includes/class-slc-cookie-tracker.php';
require_once SLC_PLUGIN_PATH . 'includes/class-slc-redirect-handler.php';
require_once SLC_PLUGIN_PATH . 'includes/class-slc-smart-links.php';
require_once SLC_PLUGIN_PATH . 'includes/class-slc-facebook-clocker.php';
require_once SLC_PLUGIN_PATH . 'includes/class-slc-popup-manager.php';

// Initialize the plugin
function super_links_clone_init() {
    $super_links_clone = new Super_Links_Clone();
    $super_links_clone->init();
}
add_action('plugins_loaded', 'super_links_clone_init');

// Activation hook
register_activation_hook(__FILE__, 'super_links_clone_activate');

function super_links_clone_activate() {
    // Create necessary database tables
    Super_Links_Clone::create_tables();
    
    // Set default options
    Super_Links_Clone::set_default_options();
    
    // Flush rewrite rules
    flush_rewrite_rules();
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'super_links_clone_deactivate');

function super_links_clone_deactivate() {
    // Flush rewrite rules
    flush_rewrite_rules();
}

// Uninstall hook
register_uninstall_hook(__FILE__, 'super_links_clone_uninstall');

function super_links_clone_uninstall() {
    // Remove plugin data if option is set
    if (get_option('slc_remove_data_on_uninstall')) {
        Super_Links_Clone::remove_plugin_data();
    }
}