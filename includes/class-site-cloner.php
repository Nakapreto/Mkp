<?php
/**
 * Main Site Cloner Class
 *
 * @package Site_Cloner
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Site_Cloner {
    
    /**
     * Plugin version
     *
     * @var string
     */
    public $version = '1.0.0';
    
    /**
     * The single instance of the class
     *
     * @var Site_Cloner
     */
    protected static $_instance = null;
    
    /**
     * Admin instance
     *
     * @var Site_Cloner_Admin
     */
    public $admin;
    
    /**
     * Processor instance
     *
     * @var Site_Cloner_Processor
     */
    public $processor;
    
    /**
     * Assets instance
     *
     * @var Site_Cloner_Assets
     */
    public $assets;
    
    /**
     * Elementor instance
     *
     * @var Site_Cloner_Elementor
     */
    public $elementor;
    
    /**
     * Media instance
     *
     * @var Site_Cloner_Media
     */
    public $media;
    
    /**
     * AJAX instance
     *
     * @var Site_Cloner_Ajax
     */
    public $ajax;
    
    /**
     * Main Site_Cloner Instance
     *
     * @static
     * @return Site_Cloner - Main instance
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
        $this->define_constants();
        $this->includes();
        $this->init_hooks();
    }
    
    /**
     * Define plugin constants
     */
    private function define_constants() {
        if (!defined('SITE_CLONER_ABSPATH')) {
            define('SITE_CLONER_ABSPATH', dirname(SITE_CLONER_PLUGIN_FILE) . '/');
        }
    }
    
    /**
     * Include required core files
     */
    public function includes() {
        // Core includes are already loaded in main plugin file
    }
    
    /**
     * Hook into actions and filters
     */
    private function init_hooks() {
        add_action('init', array($this, 'init'), 0);
        add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
    }
    
    /**
     * Init Site_Cloner when WordPress Initialises
     */
    public function init() {
        // Load plugin text domain
        $this->load_plugin_textdomain();
        
        // Initialize classes
        $this->admin = new Site_Cloner_Admin();
        $this->processor = new Site_Cloner_Processor();
        $this->assets = new Site_Cloner_Assets();
        $this->elementor = new Site_Cloner_Elementor();
        $this->media = new Site_Cloner_Media();
        $this->ajax = new Site_Cloner_Ajax();
        
        // Hook for plugin loaded
        do_action('site_cloner_loaded');
    }
    
    /**
     * Load plugin text domain
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain('site-cloner', false, dirname(plugin_basename(SITE_CLONER_PLUGIN_FILE)) . '/languages/');
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function admin_scripts($hook) {
        // Only load on our admin pages
        if (strpos($hook, 'site-cloner') === false) {
            return;
        }
        
        wp_enqueue_script(
            'site-cloner-admin',
            SITE_CLONER_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'wp-util'),
            SITE_CLONER_VERSION,
            true
        );
        
        wp_enqueue_style(
            'site-cloner-admin',
            SITE_CLONER_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            SITE_CLONER_VERSION
        );
        
        // Localize script
        wp_localize_script('site-cloner-admin', 'siteCloner', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('site_cloner_nonce'),
            'strings' => array(
                'processing' => __('Processando...', 'site-cloner'),
                'success' => __('Sucesso!', 'site-cloner'),
                'error' => __('Erro!', 'site-cloner'),
                'confirm_clone' => __('Tem certeza que deseja clonar este site?', 'site-cloner'),
                'clone_started' => __('Clone iniciado. Acompanhe o progresso na aba de Status.', 'site-cloner'),
            )
        ));
    }
    
    /**
     * Get settings
     */
    public function get_settings() {
        return get_option('site_cloner_settings', array());
    }
    
    /**
     * Update settings
     */
    public function update_settings($settings) {
        update_option('site_cloner_settings', $settings);
    }
    
    /**
     * Log message
     */
    public function log($message, $level = 'info') {
        if (WP_DEBUG && WP_DEBUG_LOG) {
            error_log(sprintf('[Site Cloner] [%s] %s', strtoupper($level), $message));
        }
    }
    
    /**
     * Get plugin version
     */
    public function get_version() {
        return $this->version;
    }
}