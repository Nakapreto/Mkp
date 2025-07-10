<?php
/**
 * Plugin Name: MultiSite Super Links
 * Plugin URI: https://example.com/multisite-super-links
 * Description: Plugin avançado para gerenciamento de links de afiliados em WordPress MultiSite com subdomínios. Inclui camuflagem de links, clonagem de páginas, ativação dupla de cookies, links inteligentes e muito mais.
 * Version: 1.0.0
 * Author: AI Assistant
 * License: GPL v2 or later
 * Network: true
 * Text Domain: multisite-super-links
 * Domain Path: /languages
 */

// Prevenir acesso direto
if (!defined('ABSPATH')) {
    exit;
}

// Definir constantes do plugin
define('MSL_PLUGIN_URL', plugin_dir_url(__FILE__));
define('MSL_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('MSL_VERSION', '1.0.0');

// Classe principal do plugin
class MultisiteSuperLinks {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('network_admin_menu', array($this, 'network_admin_menu'));
        
        // Hooks para ativação e desativação
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Scripts e estilos
        add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'frontend_scripts'));
        
        // Ajax handlers
        add_action('wp_ajax_msl_create_link', array($this, 'create_link'));
        add_action('wp_ajax_msl_clone_page', array($this, 'clone_page'));
        add_action('wp_ajax_msl_update_stats', array($this, 'update_stats'));
        
        // Rewrite rules para links camuflados
        add_action('init', array($this, 'add_rewrite_rules'));
        add_action('template_redirect', array($this, 'handle_redirect'));
        
        // Cookie tracking
        add_action('wp_footer', array($this, 'cookie_tracking'));
    }
    
    public function init() {
        // Carregar textdomain
        load_plugin_textdomain('multisite-super-links', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        // Registrar custom post type para links
        $this->register_post_types();
        
        // Incluir arquivos necessários
        $this->include_files();
    }
    
    private function include_files() {
        require_once MSL_PLUGIN_PATH . 'includes/class-link-manager.php';
        require_once MSL_PLUGIN_PATH . 'includes/class-page-cloner.php';
        require_once MSL_PLUGIN_PATH . 'includes/class-cookie-tracker.php';
        require_once MSL_PLUGIN_PATH . 'includes/class-stats-manager.php';
        require_once MSL_PLUGIN_PATH . 'includes/class-smart-redirector.php';
        require_once MSL_PLUGIN_PATH . 'includes/class-intelligent-links.php';
        require_once MSL_PLUGIN_PATH . 'admin/admin-functions.php';
    }
    
    public function register_post_types() {
        // Custom Post Type para Links Camuflados
        register_post_type('msl_link', array(
            'labels' => array(
                'name' => __('Super Links', 'multisite-super-links'),
                'singular_name' => __('Super Link', 'multisite-super-links'),
                'add_new' => __('Adicionar Novo Link', 'multisite-super-links'),
                'add_new_item' => __('Adicionar Novo Super Link', 'multisite-super-links'),
                'edit_item' => __('Editar Super Link', 'multisite-super-links'),
                'new_item' => __('Novo Super Link', 'multisite-super-links'),
                'view_item' => __('Ver Super Link', 'multisite-super-links'),
                'search_items' => __('Buscar Super Links', 'multisite-super-links'),
                'not_found' => __('Nenhum Super Link encontrado', 'multisite-super-links'),
                'not_found_in_trash' => __('Nenhum Super Link na lixeira', 'multisite-super-links'),
            ),
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => false,
            'capability_type' => 'post',
            'supports' => array('title', 'custom-fields'),
            'has_archive' => false,
        ));
        
        // Custom Post Type para Páginas Clonadas
        register_post_type('msl_cloned_page', array(
            'labels' => array(
                'name' => __('Páginas Clonadas', 'multisite-super-links'),
                'singular_name' => __('Página Clonada', 'multisite-super-links'),
            ),
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => false,
            'capability_type' => 'post',
            'supports' => array('title', 'editor', 'custom-fields'),
        ));
    }
    
    public function admin_menu() {
        add_menu_page(
            __('MultiSite Super Links', 'multisite-super-links'),
            __('Super Links', 'multisite-super-links'),
            'manage_options',
            'multisite-super-links',
            array($this, 'admin_page'),
            'dashicons-admin-links',
            30
        );
        
        add_submenu_page(
            'multisite-super-links',
            __('Gerenciar Links', 'multisite-super-links'),
            __('Gerenciar Links', 'multisite-super-links'),
            'manage_options',
            'multisite-super-links',
            array($this, 'admin_page')
        );
        
        add_submenu_page(
            'multisite-super-links',
            __('Clonar Páginas', 'multisite-super-links'),
            __('Clonar Páginas', 'multisite-super-links'),
            'manage_options',
            'msl-clone-pages',
            array($this, 'clone_pages_admin')
        );
        
        add_submenu_page(
            'multisite-super-links',
            __('Links Inteligentes', 'multisite-super-links'),
            __('Links Inteligentes', 'multisite-super-links'),
            'manage_options',
            'msl-intelligent-links',
            array($this, 'intelligent_links_admin')
        );
        
        add_submenu_page(
            'multisite-super-links',
            __('Estatísticas', 'multisite-super-links'),
            __('Estatísticas', 'multisite-super-links'),
            'manage_options',
            'msl-stats',
            array($this, 'stats_admin')
        );
        
        add_submenu_page(
            'multisite-super-links',
            __('Configurações', 'multisite-super-links'),
            __('Configurações', 'multisite-super-links'),
            'manage_options',
            'msl-settings',
            array($this, 'settings_admin')
        );
    }
    
    public function network_admin_menu() {
        add_menu_page(
            __('MultiSite Super Links - Rede', 'multisite-super-links'),
            __('Super Links Rede', 'multisite-super-links'),
            'manage_network_options',
            'multisite-super-links-network',
            array($this, 'network_admin_page'),
            'dashicons-admin-links',
            30
        );
    }
    
    public function admin_scripts($hook) {
        if (strpos($hook, 'multisite-super-links') !== false || strpos($hook, 'msl-') !== false) {
            wp_enqueue_script('msl-admin-js', MSL_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), MSL_VERSION, true);
            wp_enqueue_style('msl-admin-css', MSL_PLUGIN_URL . 'assets/css/admin.css', array(), MSL_VERSION);
            
            wp_localize_script('msl-admin-js', 'msl_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('msl_nonce'),
                'messages' => array(
                    'success' => __('Operação realizada com sucesso!', 'multisite-super-links'),
                    'error' => __('Erro ao realizar operação.', 'multisite-super-links'),
                    'confirm_delete' => __('Tem certeza que deseja excluir este item?', 'multisite-super-links'),
                )
            ));
        }
    }
    
    public function frontend_scripts() {
        wp_enqueue_script('msl-frontend-js', MSL_PLUGIN_URL . 'assets/js/frontend.js', array('jquery'), MSL_VERSION, true);
        wp_enqueue_style('msl-frontend-css', MSL_PLUGIN_URL . 'assets/css/frontend.css', array(), MSL_VERSION);
        
        wp_localize_script('msl-frontend-js', 'msl_frontend', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('msl_frontend_nonce'),
            'site_url' => get_site_url(),
        ));
    }
    
    public function add_rewrite_rules() {
        add_rewrite_rule('^msl/([^/]+)/?$', 'index.php?msl_redirect=$matches[1]', 'top');
        add_rewrite_tag('%msl_redirect%', '([^&]+)');
    }
    
    public function handle_redirect() {
        $redirect_slug = get_query_var('msl_redirect');
        
        if (!empty($redirect_slug)) {
            $link_manager = new MSL_Link_Manager();
            $link_manager->handle_redirect($redirect_slug);
        }
    }
    
    public function cookie_tracking() {
        if (is_admin()) return;
        
        $cookie_tracker = new MSL_Cookie_Tracker();
        $cookie_tracker->output_tracking_script();
    }
    
    // AJAX Handlers
    public function create_link() {
        check_ajax_referer('msl_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Permissão negada.', 'multisite-super-links'));
        }
        
        $link_manager = new MSL_Link_Manager();
        $result = $link_manager->create_link($_POST);
        
        wp_send_json($result);
    }
    
    public function clone_page() {
        check_ajax_referer('msl_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Permissão negada.', 'multisite-super-links'));
        }
        
        $page_cloner = new MSL_Page_Cloner();
        $result = $page_cloner->clone_page($_POST['url']);
        
        wp_send_json($result);
    }
    
    public function update_stats() {
        check_ajax_referer('msl_frontend_nonce', 'nonce');
        
        $stats_manager = new MSL_Stats_Manager();
        $result = $stats_manager->update_click_stats($_POST);
        
        wp_send_json($result);
    }
    
    // Páginas de administração
    public function admin_page() {
        include MSL_PLUGIN_PATH . 'admin/pages/main-admin.php';
    }
    
    public function network_admin_page() {
        include MSL_PLUGIN_PATH . 'admin/pages/network-admin.php';
    }
    
    public function clone_pages_admin() {
        include MSL_PLUGIN_PATH . 'admin/pages/clone-pages.php';
    }
    
    public function intelligent_links_admin() {
        include MSL_PLUGIN_PATH . 'admin/pages/intelligent-links.php';
    }
    
    public function stats_admin() {
        include MSL_PLUGIN_PATH . 'admin/pages/stats.php';
    }
    
    public function settings_admin() {
        include MSL_PLUGIN_PATH . 'admin/pages/settings.php';
    }
    
    public function activate() {
        // Criar tabelas personalizadas
        $this->create_tables();
        
        // Flush rewrite rules
        $this->add_rewrite_rules();
        flush_rewrite_rules();
        
        // Configurações padrão
        $this->set_default_options();
    }
    
    public function deactivate() {
        flush_rewrite_rules();
    }
    
    private function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Tabela para estatísticas de links
        $table_stats = $wpdb->prefix . 'msl_link_stats';
        $sql_stats = "CREATE TABLE $table_stats (
            id int(11) NOT NULL AUTO_INCREMENT,
            link_id int(11) NOT NULL,
            site_id int(11) NOT NULL,
            clicks int(11) DEFAULT 0,
            conversions int(11) DEFAULT 0,
            date_created datetime DEFAULT CURRENT_TIMESTAMP,
            date_updated datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY link_id (link_id),
            KEY site_id (site_id)
        ) $charset_collate;";
        
        // Tabela para tracking de cookies
        $table_cookies = $wpdb->prefix . 'msl_cookie_tracking';
        $sql_cookies = "CREATE TABLE $table_cookies (
            id int(11) NOT NULL AUTO_INCREMENT,
            user_hash varchar(255) NOT NULL,
            link_id int(11) NOT NULL,
            site_id int(11) NOT NULL,
            affiliate_url text NOT NULL,
            cookie_data text,
            ip_address varchar(45),
            user_agent text,
            date_created datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_hash_link (user_hash, link_id),
            KEY site_id (site_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_stats);
        dbDelta($sql_cookies);
    }
    
    private function set_default_options() {
        $default_options = array(
            'msl_cookie_duration' => 30, // dias
            'msl_enable_double_cookie' => true,
            'msl_enable_intelligent_redirect' => true,
            'msl_facebook_pixel_id' => '',
            'msl_google_analytics_id' => '',
            'msl_enable_stats_tracking' => true,
            'msl_link_prefix' => 'msl',
            'msl_enable_cloaking' => true,
        );
        
        foreach ($default_options as $option => $value) {
            if (get_option($option) === false) {
                add_option($option, $value);
            }
        }
    }
}

// Inicializar o plugin
function multisite_super_links_init() {
    return MultisiteSuperLinks::get_instance();
}

// Hook para inicializar após WordPress carregar
add_action('plugins_loaded', 'multisite_super_links_init');

// Função para verificar se é multisite
function msl_is_multisite_subdomain() {
    return is_multisite() && (defined('SUBDOMAIN_INSTALL') && SUBDOMAIN_INSTALL);
}

// Função helper para obter URL do link camuflado
function msl_get_cloaked_url($link_id, $site_id = null) {
    if ($site_id === null) {
        $site_id = get_current_blog_id();
    }
    
    $site_url = get_site_url($site_id);
    $prefix = get_option('msl_link_prefix', 'msl');
    
    return trailingslashit($site_url) . $prefix . '/' . $link_id;
}

// Shortcode para exibir links
function msl_links_shortcode($atts) {
    $atts = shortcode_atts(array(
        'category' => '',
        'limit' => 10,
        'columns' => 3,
        'show_stats' => false,
    ), $atts);
    
    $link_manager = new MSL_Link_Manager();
    return $link_manager->display_links($atts);
}
add_shortcode('msl_links', 'msl_links_shortcode');

?>