<?php

if (!defined('ABSPATH')) {
    exit;
}

class Super_Links_Clone {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function init() {
        add_action('init', array($this, 'init_hooks'));
        add_action('wp_loaded', array($this, 'init_classes'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // Add rewrite rules for link redirection
        add_action('init', array($this, 'add_rewrite_rules'));
        add_filter('query_vars', array($this, 'add_query_vars'));
        add_action('template_redirect', array($this, 'handle_link_redirect'));
        
        // AJAX handlers
        add_action('wp_ajax_slc_clone_page', array($this, 'ajax_clone_page'));
        add_action('wp_ajax_slc_create_link', array($this, 'ajax_create_link'));
        add_action('wp_ajax_slc_get_analytics', array($this, 'ajax_get_analytics'));
        add_action('wp_ajax_slc_import_links', array($this, 'ajax_import_links'));
        add_action('wp_ajax_slc_get_link', array($this, 'ajax_get_link'));
        add_action('wp_ajax_slc_update_link', array($this, 'ajax_update_link'));
        add_action('wp_ajax_slc_reset_settings', array($this, 'ajax_reset_settings'));
        add_action('wp_ajax_slc_clear_analytics', array($this, 'ajax_clear_analytics'));
        add_action('wp_ajax_slc_export_analytics', array($this, 'ajax_export_analytics'));
        add_action('wp_ajax_slc_preview_import', array($this, 'ajax_preview_import'));
        add_action('wp_ajax_slc_download_csv_template', array($this, 'ajax_download_csv_template'));
    }
    
    public function init_hooks() {
        // Load text domain
        load_plugin_textdomain('super-links-clone', false, dirname(SLC_PLUGIN_BASENAME) . '/languages');
        
        // Initialize admin interface
        if (is_admin()) {
            new SLC_Admin();
        }
    }
    
    public function init_classes() {
        // Initialize core classes
        new SLC_Link_Manager();
        new SLC_Analytics();
        new SLC_Cookie_Tracker();
        new SLC_Redirect_Handler();
        new SLC_Smart_Links();
        new SLC_Facebook_Clocker();
        new SLC_Popup_Manager();
    }
    
    public function enqueue_frontend_scripts() {
        wp_enqueue_script(
            'slc-frontend',
            SLC_PLUGIN_URL . 'assets/js/frontend.js',
            array('jquery'),
            SLC_VERSION,
            true
        );
        
        wp_enqueue_style(
            'slc-frontend',
            SLC_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            SLC_VERSION
        );
        
        // Localize script for AJAX
        wp_localize_script('slc-frontend', 'slc_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('slc_nonce'),
            'cookie_tracking' => get_option('slc_enable_cookie_tracking', 1),
            'exit_redirect' => get_option('slc_enable_exit_redirect', 0),
            'smart_links' => get_option('slc_enable_smart_links', 0)
        ));
    }
    
    public function enqueue_admin_scripts($hook) {
        // Only load on plugin pages
        if (strpos($hook, 'super-links') === false) {
            return;
        }
        
        wp_enqueue_script(
            'slc-admin',
            SLC_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'jquery-ui-sortable'),
            SLC_VERSION,
            true
        );
        
        wp_enqueue_style(
            'slc-admin',
            SLC_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            SLC_VERSION
        );
        
        wp_localize_script('slc-admin', 'slc_admin_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('slc_admin_nonce')
        ));
    }
    
    public function add_rewrite_rules() {
        // Add rewrite rule for short links
        $link_prefix = get_option('slc_link_prefix', 'go');
        add_rewrite_rule('^' . $link_prefix . '/([^/]+)/?', 'index.php?slc_link=$matches[1]', 'top');
    }
    
    public function add_query_vars($vars) {
        $vars[] = 'slc_link';
        return $vars;
    }
    
    public function handle_link_redirect() {
        $link_slug = get_query_var('slc_link');
        
        if (!empty($link_slug)) {
            $redirect_handler = new SLC_Redirect_Handler();
            $redirect_handler->process_redirect($link_slug);
        }
    }
    
    // AJAX Handlers
    public function ajax_clone_page() {
        check_ajax_referer('slc_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'super-links-clone'));
        }
        
        $url = sanitize_url($_POST['url']);
        $page_cloner = new SLC_Page_Cloner();
        $result = $page_cloner->clone_page($url);
        
        wp_send_json($result);
    }
    
    public function ajax_create_link() {
        check_ajax_referer('slc_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'super-links-clone'));
        }
        
        $data = $_POST;
        $link_manager = new SLC_Link_Manager();
        $result = $link_manager->create_link($data);
        
        wp_send_json($result);
    }
    
    public function ajax_get_analytics() {
        check_ajax_referer('slc_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'super-links-clone'));
        }
        
        $link_id = intval($_POST['link_id']);
        $analytics = new SLC_Analytics();
        $result = $analytics->get_link_analytics($link_id);
        
        wp_send_json($result);
    }
    
    public function ajax_import_links() {
        check_ajax_referer('slc_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'super-links-clone'));
        }
        
        $source = sanitize_text_field($_POST['source']);
        $link_manager = new SLC_Link_Manager();
        $result = $link_manager->import_links($source);
        
        wp_send_json($result);
    }
    
    public function ajax_get_link() {
        check_ajax_referer('slc_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'super-links-clone'));
        }
        
        global $wpdb;
        $link_id = intval($_POST['link_id']);
        
        $link = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}slc_links WHERE id = %d
        ", $link_id));
        
        if ($link) {
            wp_send_json_success($link);
        } else {
            wp_send_json_error(array('message' => __('Link não encontrado', 'super-links-clone')));
        }
    }
    
    public function ajax_update_link() {
        check_ajax_referer('slc_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'super-links-clone'));
        }
        
        $link_manager = new SLC_Link_Manager();
        $result = $link_manager->update_link($_POST);
        
        wp_send_json($result);
    }
    
    public function ajax_reset_settings() {
        check_ajax_referer('slc_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'super-links-clone'));
        }
        
        // Reset to default options
        $defaults = array(
            'slc_link_prefix' => 'go',
            'slc_enable_cookie_tracking' => 1,
            'slc_enable_smart_links' => 0,
            'slc_enable_exit_redirect' => 0,
            'slc_enable_facebook_clocker' => 0,
            'slc_default_redirect_type' => '301',
            'slc_enable_analytics' => 1,
            'slc_popup_enabled' => 0,
            'slc_popup_delay' => 5000,
            'slc_popup_content' => '',
            'slc_exit_redirect_url' => '',
            'slc_smart_keywords' => ''
        );
        
        foreach ($defaults as $option => $value) {
            update_option($option, $value);
        }
        
        wp_send_json_success(array('message' => __('Configurações restauradas com sucesso!', 'super-links-clone')));
    }
    
    public function ajax_clear_analytics() {
        check_ajax_referer('slc_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'super-links-clone'));
        }
        
        global $wpdb;
        
        // Clear analytics table
        $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}slc_analytics");
        
        // Reset click counts
        $wpdb->update(
            $wpdb->prefix . 'slc_links',
            array('clicks' => 0, 'unique_clicks' => 0),
            array(),
            array('%d', '%d')
        );
        
        wp_send_json_success(array('message' => __('Analytics limpos com sucesso!', 'super-links-clone')));
    }
    
    public function ajax_export_analytics() {
        check_ajax_referer('slc_export_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'super-links-clone'));
        }
        
        $analytics = new SLC_Analytics();
        $link_id = isset($_GET['link_id']) ? intval($_GET['link_id']) : 0;
        $format = isset($_GET['format']) ? sanitize_text_field($_GET['format']) : 'csv';
        
        $analytics->export_data($link_id, $format);
    }
    
    public function ajax_preview_import() {
        check_ajax_referer('slc_import_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'super-links-clone'));
        }
        
        $source = sanitize_text_field($_POST['source']);
        $link_manager = new SLC_Link_Manager();
        $result = $link_manager->preview_import($source);
        
        wp_send_json($result);
    }
    
    public function ajax_download_csv_template() {
        check_ajax_referer('slc_csv_template', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'super-links-clone'));
        }
        
        $csv_content = "title,slug,target_url,redirect_type,category\n";
        $csv_content .= "Meu Link de Exemplo,exemplo,https://example.com,301,categoria1\n";
        $csv_content .= "Outro Link,outro,https://google.com,302,categoria2\n";
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="super-links-template.csv"');
        header('Content-Length: ' . strlen($csv_content));
        
        echo $csv_content;
        exit;
    }
    
    // Static methods for activation/deactivation
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Links table
        $table_links = $wpdb->prefix . 'slc_links';
        $sql_links = "CREATE TABLE $table_links (
            id int(11) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            slug varchar(100) NOT NULL UNIQUE,
            target_url text NOT NULL,
            redirect_type varchar(10) DEFAULT '301',
            cloaked tinyint(1) DEFAULT 1,
            facebook_cloaked tinyint(1) DEFAULT 0,
            cookie_tracking tinyint(1) DEFAULT 1,
            smart_link tinyint(1) DEFAULT 0,
            keywords text,
            category varchar(100),
            status varchar(20) DEFAULT 'active',
            clicks int(11) DEFAULT 0,
            unique_clicks int(11) DEFAULT 0,
            created_by int(11),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY slug (slug),
            KEY status (status),
            KEY category (category)
        ) $charset_collate;";
        
        // Analytics table
        $table_analytics = $wpdb->prefix . 'slc_analytics';
        $sql_analytics = "CREATE TABLE $table_analytics (
            id int(11) NOT NULL AUTO_INCREMENT,
            link_id int(11) NOT NULL,
            ip_address varchar(45),
            user_agent text,
            referer text,
            country varchar(2),
            device varchar(50),
            browser varchar(50),
            os varchar(50),
            is_unique tinyint(1) DEFAULT 1,
            clicked_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY link_id (link_id),
            KEY clicked_at (clicked_at),
            KEY is_unique (is_unique)
        ) $charset_collate;";
        
        // Cloned pages table
        $table_pages = $wpdb->prefix . 'slc_cloned_pages';
        $sql_pages = "CREATE TABLE $table_pages (
            id int(11) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            original_url text NOT NULL,
            post_id int(11),
            content longtext,
            status varchar(20) DEFAULT 'active',
            created_by int(11),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY post_id (post_id),
            KEY status (status)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_links);
        dbDelta($sql_analytics);
        dbDelta($sql_pages);
    }
    
    public static function set_default_options() {
        $defaults = array(
            'slc_link_prefix' => 'go',
            'slc_enable_cookie_tracking' => 1,
            'slc_enable_smart_links' => 0,
            'slc_enable_exit_redirect' => 0,
            'slc_enable_facebook_clocker' => 0,
            'slc_default_redirect_type' => '301',
            'slc_enable_analytics' => 1,
            'slc_remove_data_on_uninstall' => 0,
            'slc_popup_enabled' => 0,
            'slc_popup_delay' => 5000,
            'slc_popup_content' => '',
        );
        
        foreach ($defaults as $option => $value) {
            add_option($option, $value);
        }
    }
    
    public static function remove_plugin_data() {
        global $wpdb;
        
        // Remove tables
        $tables = array(
            $wpdb->prefix . 'slc_links',
            $wpdb->prefix . 'slc_analytics',
            $wpdb->prefix . 'slc_cloned_pages'
        );
        
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }
        
        // Remove options
        $options = array(
            'slc_link_prefix',
            'slc_enable_cookie_tracking',
            'slc_enable_smart_links',
            'slc_enable_exit_redirect',
            'slc_enable_facebook_clocker',
            'slc_default_redirect_type',
            'slc_enable_analytics',
            'slc_remove_data_on_uninstall',
            'slc_popup_enabled',
            'slc_popup_delay',
            'slc_popup_content'
        );
        
        foreach ($options as $option) {
            delete_option($option);
        }
    }
}