<?php
/**
 * Plugin Name: Site Cloner
 * Plugin URI: https://yoursite.com
 * Description: Plugin para clonar sites externos com suporte completo ao Elementor e WordPress Multisite.
 * Version: 1.0.0
 * Author: Site Cloner Team
 * License: GPL v2 or later
 * Text Domain: site-cloner
 * Domain Path: /languages
 * Network: true
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('SITE_CLONER_VERSION', '1.0.0');
define('SITE_CLONER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SITE_CLONER_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SITE_CLONER_PLUGIN_FILE', __FILE__);

// Include required files
require_once SITE_CLONER_PLUGIN_DIR . 'includes/class-site-cloner.php';
require_once SITE_CLONER_PLUGIN_DIR . 'includes/class-site-cloner-admin.php';
require_once SITE_CLONER_PLUGIN_DIR . 'includes/class-site-cloner-processor.php';
require_once SITE_CLONER_PLUGIN_DIR . 'includes/class-site-cloner-assets.php';
require_once SITE_CLONER_PLUGIN_DIR . 'includes/class-site-cloner-elementor.php';
require_once SITE_CLONER_PLUGIN_DIR . 'includes/class-site-cloner-media.php';
require_once SITE_CLONER_PLUGIN_DIR . 'includes/class-site-cloner-ajax.php';

/**
 * Main plugin class initialization
 */
function site_cloner_init() {
    $site_cloner = new Site_Cloner();
    $site_cloner->init();
}
add_action('plugins_loaded', 'site_cloner_init');

/**
 * Plugin activation hook
 */
function site_cloner_activate() {
    // Create necessary database tables
    site_cloner_create_tables();
    
    // Create upload directories
    site_cloner_create_directories();
    
    // Set default options
    if (!get_option('site_cloner_settings')) {
        add_option('site_cloner_settings', array(
            'max_execution_time' => 300,
            'memory_limit' => '512M',
            'download_timeout' => 60,
            'elementor_support' => true,
            'multisite_support' => true
        ));
    }
}
register_activation_hook(__FILE__, 'site_cloner_activate');

/**
 * Plugin deactivation hook
 */
function site_cloner_deactivate() {
    // Clean up temporary files
    site_cloner_cleanup_temp_files();
}
register_deactivation_hook(__FILE__, 'site_cloner_deactivate');

/**
 * Create necessary database tables
 */
function site_cloner_create_tables() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'site_cloner_jobs';
    
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        url varchar(500) NOT NULL,
        status varchar(50) DEFAULT 'pending',
        progress int(3) DEFAULT 0,
        log text,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

/**
 * Create necessary directories
 */
function site_cloner_create_directories() {
    $upload_dir = wp_upload_dir();
    $cloner_dir = $upload_dir['basedir'] . '/site-cloner/';
    
    if (!file_exists($cloner_dir)) {
        wp_mkdir_p($cloner_dir);
    }
    
    // Create subdirectories
    $subdirs = array('temp', 'assets', 'exports');
    foreach ($subdirs as $subdir) {
        $dir = $cloner_dir . $subdir . '/';
        if (!file_exists($dir)) {
            wp_mkdir_p($dir);
        }
    }
}

/**
 * Clean up temporary files
 */
function site_cloner_cleanup_temp_files() {
    $upload_dir = wp_upload_dir();
    $temp_dir = $upload_dir['basedir'] . '/site-cloner/temp/';
    
    if (is_dir($temp_dir)) {
        $files = glob($temp_dir . '*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }
}