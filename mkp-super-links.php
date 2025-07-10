<?php
/**
 * Plugin Name: MKP Super Links
 * Plugin URI: https://github.com/mkpdigital/mkp-super-links
 * Description: Advanced multisite super links management for WordPress networks. Adds enhanced toolbar links, site navigation, and admin tools for super administrators.
 * Version: 1.0.0
 * Author: MKP Digital
 * Author URI: https://mkpdigital.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: mkp-super-links
 * Domain Path: /languages
 * Network: true
 * Requires at least: 5.0
 * Tested up to: 6.8
 * Requires PHP: 7.4
 *
 * @package MKPSuperLinks
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit('Direct access forbidden.');
}

// Define plugin constants
define('MKP_SUPER_LINKS_VERSION', '1.0.0');
define('MKP_SUPER_LINKS_PLUGIN_FILE', __FILE__);
define('MKP_SUPER_LINKS_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('MKP_SUPER_LINKS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MKP_SUPER_LINKS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('MKP_SUPER_LINKS_ASSETS_URL', MKP_SUPER_LINKS_PLUGIN_URL . 'assets/');
define('MKP_SUPER_LINKS_INCLUDES_DIR', MKP_SUPER_LINKS_PLUGIN_DIR . 'includes/');
define('MKP_SUPER_LINKS_ADMIN_DIR', MKP_SUPER_LINKS_PLUGIN_DIR . 'admin/');

/**
 * Main MKP Super Links Class
 */
class MKPSuperLinks {
    
    /**
     * Single instance of the class
     *
     * @var MKPSuperLinks
     */
    private static $_instance = null;
    
    /**
     * Plugin version
     *
     * @var string
     */
    public $version = MKP_SUPER_LINKS_VERSION;
    
    /**
     * Main MKPSuperLinks Instance
     *
     * @static
     * @return MKPSuperLinks - Main instance
     */
    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Hook into actions and filters
     */
    private function init_hooks() {
        add_action('init', array($this, 'init'), 0);
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        
        // Plugin activation/deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activation'));
        register_deactivation_hook(__FILE__, array($this, 'deactivation'));
        
        // Only proceed if multisite is enabled or user is admin
        if (is_multisite() || current_user_can('manage_options')) {
            add_action('init', array($this, 'include_files'));
            add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
            add_action('wp_enqueue_scripts', array($this, 'frontend_enqueue_scripts'));
        }
    }
    
    /**
     * Init MKP Super Links when WordPress Initializes
     */
    public function init() {
        // Before init action
        do_action('before_mkp_super_links_init');
        
        // Init action
        do_action('mkp_super_links_init');
    }
    
    /**
     * Load plugin textdomain
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'mkp-super-links',
            false,
            dirname(MKP_SUPER_LINKS_PLUGIN_BASENAME) . '/languages/'
        );
    }
    
    /**
     * Include required files
     */
    public function include_files() {
        // Core files
        $this->include_core_files();
        
        // Admin files
        if (is_admin()) {
            $this->include_admin_files();
        }
        
        // Frontend files
        if (!is_admin()) {
            $this->include_frontend_files();
        }
    }
    
    /**
     * Include core files
     */
    private function include_core_files() {
        $core_files = array(
            'functions.php',
            'class-toolbar.php',
            'class-sites-manager.php',
            'class-permissions.php'
        );
        
        foreach ($core_files as $file) {
            $file_path = MKP_SUPER_LINKS_INCLUDES_DIR . $file;
            if (file_exists($file_path)) {
                require_once $file_path;
            }
        }
    }
    
    /**
     * Include admin files
     */
    private function include_admin_files() {
        // Always include admin-functions.php
        $admin_functions_file = MKP_SUPER_LINKS_ADMIN_DIR . 'admin-functions.php';
        if (file_exists($admin_functions_file)) {
            require_once $admin_functions_file;
        }
        
        $admin_files = array(
            'class-admin.php',
            'class-settings.php',
            'class-network-admin.php'
        );
        
        foreach ($admin_files as $file) {
            $file_path = MKP_SUPER_LINKS_ADMIN_DIR . $file;
            if (file_exists($file_path)) {
                require_once $file_path;
            }
        }
    }
    
    /**
     * Include frontend files
     */
    private function include_frontend_files() {
        $frontend_files = array(
            'class-frontend.php'
        );
        
        foreach ($frontend_files as $file) {
            $file_path = MKP_SUPER_LINKS_INCLUDES_DIR . $file;
            if (file_exists($file_path)) {
                require_once $file_path;
            }
        }
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function admin_enqueue_scripts($hook) {
        // Only load on relevant admin pages
        if (!$this->should_load_admin_assets($hook)) {
            return;
        }
        
        // Admin CSS
        wp_enqueue_style(
            'mkp-super-links-admin',
            MKP_SUPER_LINKS_ASSETS_URL . 'css/admin.css',
            array(),
            $this->version
        );
        
        // Admin JS
        wp_enqueue_script(
            'mkp-super-links-admin',
            MKP_SUPER_LINKS_ASSETS_URL . 'js/admin.js',
            array('jquery'),
            $this->version,
            true
        );
        
        // Localize script
        wp_localize_script('mkp-super-links-admin', 'mkpSuperLinks', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mkp_super_links_nonce'),
            'version' => $this->version,
            'isMultisite' => is_multisite(),
            'isNetworkAdmin' => is_network_admin(),
            'isSuperAdmin' => is_super_admin(),
            'strings' => array(
                'loading' => __('Loading...', 'mkp-super-links'),
                'error' => __('An error occurred', 'mkp-super-links'),
                'success' => __('Operation successful', 'mkp-super-links')
            )
        ));
    }
    
    /**
     * Enqueue frontend scripts and styles
     */
    public function frontend_enqueue_scripts() {
        // Only load for logged in users with appropriate capabilities
        if (!is_user_logged_in() || !current_user_can('edit_posts')) {
            return;
        }
        
        // Frontend CSS
        wp_enqueue_style(
            'mkp-super-links-frontend',
            MKP_SUPER_LINKS_ASSETS_URL . 'css/frontend.css',
            array(),
            $this->version
        );
        
        // Frontend JS
        wp_enqueue_script(
            'mkp-super-links-frontend',
            MKP_SUPER_LINKS_ASSETS_URL . 'js/frontend.js',
            array('jquery'),
            $this->version,
            true
        );
    }
    
    /**
     * Check if admin assets should be loaded
     */
    private function should_load_admin_assets($hook) {
        // Always load on network admin pages
        if (is_network_admin()) {
            return true;
        }
        
        // Load on specific admin pages
        $load_on_pages = array(
            'toplevel_page_mkp-super-links',
            'admin_page_mkp-super-links-settings',
            'sites.php',
            'users.php',
            'themes.php',
            'plugins.php'
        );
        
        if (in_array($hook, $load_on_pages)) {
            return true;
        }
        
        // Load on multisite admin pages
        if (strpos($hook, 'mkp-super-links') !== false) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Plugin activation
     */
    public function activation() {
        // Check WordPress version
        if (version_compare(get_bloginfo('version'), '5.0', '<')) {
            deactivate_plugins(MKP_SUPER_LINKS_PLUGIN_BASENAME);
            wp_die(__('MKP Super Links requires WordPress 5.0 or higher.', 'mkp-super-links'));
        }
        
        // Check PHP version
        if (version_compare(PHP_VERSION, '7.4', '<')) {
            deactivate_plugins(MKP_SUPER_LINKS_PLUGIN_BASENAME);
            wp_die(__('MKP Super Links requires PHP 7.4 or higher.', 'mkp-super-links'));
        }
        
        // Set default options
        $this->set_default_options();
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        do_action('mkp_super_links_activated');
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivation() {
        // Clean up if needed
        flush_rewrite_rules();
        
        do_action('mkp_super_links_deactivated');
    }
    
    /**
     * Set default options
     */
    private function set_default_options() {
        $default_options = array(
            'version' => $this->version,
            'enabled' => true,
            'show_toolbar_links' => true,
            'show_site_links' => true,
            'show_network_links' => true,
            'show_admin_links' => true,
            'load_on_frontend' => true,
            'cache_enabled' => true,
            'cache_duration' => 3600,
            'custom_css' => '',
            'first_install' => true
        );
        
        if (is_multisite()) {
            add_site_option('mkp_super_links_options', $default_options);
        } else {
            add_option('mkp_super_links_options', $default_options);
        }
    }
    
    /**
     * Get plugin options
     */
    public function get_options() {
        if (is_multisite()) {
            return get_site_option('mkp_super_links_options', array());
        } else {
            return get_option('mkp_super_links_options', array());
        }
    }
    
    /**
     * Update plugin options
     */
    public function update_options($options) {
        if (is_multisite()) {
            return update_site_option('mkp_super_links_options', $options);
        } else {
            return update_option('mkp_super_links_options', $options);
        }
    }
    
    /**
     * Get plugin option
     */
    public function get_option($key, $default = null) {
        $options = $this->get_options();
        return isset($options[$key]) ? $options[$key] : $default;
    }
    
    /**
     * Update plugin option
     */
    public function update_option($key, $value) {
        $options = $this->get_options();
        $options[$key] = $value;
        return $this->update_options($options);
    }
}

/**
 * Main instance of MKPSuperLinks
 *
 * @return MKPSuperLinks
 */
function mkp_super_links() {
    return MKPSuperLinks::instance();
}

// Global for backwards compatibility
$GLOBALS['mkp_super_links'] = mkp_super_links();

// Initialize the plugin
mkp_super_links();

// End of file