<?php

if (!defined('ABSPATH')) {
    exit;
}

class SLC_Admin {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_notices', array($this, 'admin_notices'));
    }
    
    public function add_admin_menu() {
        add_menu_page(
            __('Super Links Clone', 'super-links-clone'),
            __('Super Links', 'super-links-clone'),
            'manage_options',
            'super-links-clone',
            array($this, 'dashboard_page'),
            'dashicons-admin-links',
            30
        );
        
        add_submenu_page(
            'super-links-clone',
            __('Dashboard', 'super-links-clone'),
            __('Dashboard', 'super-links-clone'),
            'manage_options',
            'super-links-clone',
            array($this, 'dashboard_page')
        );
        
        add_submenu_page(
            'super-links-clone',
            __('Gerenciar Links', 'super-links-clone'),
            __('Gerenciar Links', 'super-links-clone'),
            'manage_options',
            'super-links-manage',
            array($this, 'manage_links_page')
        );
        
        add_submenu_page(
            'super-links-clone',
            __('Criar Link', 'super-links-clone'),
            __('Criar Link', 'super-links-clone'),
            'manage_options',
            'super-links-create',
            array($this, 'create_link_page')
        );
        
        add_submenu_page(
            'super-links-clone',
            __('Clonar Página', 'super-links-clone'),
            __('Clonar Página', 'super-links-clone'),
            'manage_options',
            'super-links-clone-page',
            array($this, 'clone_page_page')
        );
        
        add_submenu_page(
            'super-links-clone',
            __('Analytics', 'super-links-clone'),
            __('Analytics', 'super-links-clone'),
            'manage_options',
            'super-links-analytics',
            array($this, 'analytics_page')
        );
        
        add_submenu_page(
            'super-links-clone',
            __('Links Inteligentes', 'super-links-clone'),
            __('Links Inteligentes', 'super-links-clone'),
            'manage_options',
            'super-links-smart',
            array($this, 'smart_links_page')
        );
        
        add_submenu_page(
            'super-links-clone',
            __('Importar Links', 'super-links-clone'),
            __('Importar Links', 'super-links-clone'),
            'manage_options',
            'super-links-import',
            array($this, 'import_links_page')
        );
        
        add_submenu_page(
            'super-links-clone',
            __('Configurações', 'super-links-clone'),
            __('Configurações', 'super-links-clone'),
            'manage_options',
            'super-links-settings',
            array($this, 'settings_page')
        );
    }
    
    public function register_settings() {
        register_setting('slc_settings', 'slc_link_prefix');
        register_setting('slc_settings', 'slc_enable_cookie_tracking');
        register_setting('slc_settings', 'slc_enable_smart_links');
        register_setting('slc_settings', 'slc_enable_exit_redirect');
        register_setting('slc_settings', 'slc_enable_facebook_clocker');
        register_setting('slc_settings', 'slc_default_redirect_type');
        register_setting('slc_settings', 'slc_enable_analytics');
        register_setting('slc_settings', 'slc_popup_enabled');
        register_setting('slc_settings', 'slc_popup_delay');
        register_setting('slc_settings', 'slc_popup_content');
        register_setting('slc_settings', 'slc_exit_redirect_url');
        register_setting('slc_settings', 'slc_smart_keywords');
    }
    
    public function admin_notices() {
        if (isset($_GET['slc_message'])) {
            $message_type = sanitize_text_field($_GET['slc_message']);
            $messages = array(
                'link_created' => array('success', __('Link criado com sucesso!', 'super-links-clone')),
                'link_updated' => array('success', __('Link atualizado com sucesso!', 'super-links-clone')),
                'link_deleted' => array('success', __('Link deletado com sucesso!', 'super-links-clone')),
                'page_cloned' => array('success', __('Página clonada com sucesso!', 'super-links-clone')),
                'settings_saved' => array('success', __('Configurações salvas com sucesso!', 'super-links-clone')),
                'import_completed' => array('success', __('Importação concluída com sucesso!', 'super-links-clone')),
                'error' => array('error', __('Ocorreu um erro. Tente novamente.', 'super-links-clone'))
            );
            
            if (isset($messages[$message_type])) {
                list($type, $message) = $messages[$message_type];
                echo '<div class="notice notice-' . $type . ' is-dismissible"><p>' . $message . '</p></div>';
            }
        }
    }
    
    public function dashboard_page() {
        global $wpdb;
        
        // Get statistics
        $total_links = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}slc_links WHERE status = 'active'");
        $total_clicks = $wpdb->get_var("SELECT SUM(clicks) FROM {$wpdb->prefix}slc_links WHERE status = 'active'");
        $total_unique_clicks = $wpdb->get_var("SELECT SUM(unique_clicks) FROM {$wpdb->prefix}slc_links WHERE status = 'active'");
        $total_cloned_pages = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}slc_cloned_pages WHERE status = 'active'");
        
        // Get recent links
        $recent_links = $wpdb->get_results("
            SELECT * FROM {$wpdb->prefix}slc_links 
            WHERE status = 'active' 
            ORDER BY created_at DESC 
            LIMIT 10
        ");
        
        include SLC_PLUGIN_PATH . 'templates/admin-dashboard.php';
    }
    
    public function manage_links_page() {
        global $wpdb;
        
        // Handle actions
        if (isset($_POST['action']) && isset($_POST['link_id'])) {
            $action = sanitize_text_field($_POST['action']);
            $link_id = intval($_POST['link_id']);
            
            if ($action === 'delete' && wp_verify_nonce($_POST['nonce'], 'slc_delete_link_' . $link_id)) {
                $wpdb->update(
                    $wpdb->prefix . 'slc_links',
                    array('status' => 'deleted'),
                    array('id' => $link_id),
                    array('%s'),
                    array('%d')
                );
                wp_redirect(add_query_arg('slc_message', 'link_deleted', admin_url('admin.php?page=super-links-manage')));
                exit;
            }
        }
        
        // Get all links
        $links = $wpdb->get_results("
            SELECT * FROM {$wpdb->prefix}slc_links 
            WHERE status = 'active' 
            ORDER BY created_at DESC
        ");
        
        include SLC_PLUGIN_PATH . 'templates/admin-manage-links.php';
    }
    
    public function create_link_page() {
        if (isset($_POST['submit_link'])) {
            $link_manager = new SLC_Link_Manager();
            $result = $link_manager->create_link($_POST);
            
            if ($result['success']) {
                wp_redirect(add_query_arg('slc_message', 'link_created', admin_url('admin.php?page=super-links-manage')));
                exit;
            } else {
                echo '<div class="notice notice-error"><p>' . $result['message'] . '</p></div>';
            }
        }
        
        include SLC_PLUGIN_PATH . 'templates/admin-create-link.php';
    }
    
    public function clone_page_page() {
        if (isset($_POST['submit_clone'])) {
            $url = sanitize_url($_POST['clone_url']);
            $page_cloner = new SLC_Page_Cloner();
            $result = $page_cloner->clone_page($url);
            
            if ($result['success']) {
                wp_redirect(add_query_arg('slc_message', 'page_cloned', admin_url('admin.php?page=super-links-clone-page')));
                exit;
            } else {
                echo '<div class="notice notice-error"><p>' . $result['message'] . '</p></div>';
            }
        }
        
        include SLC_PLUGIN_PATH . 'templates/admin-clone-page.php';
    }
    
    public function analytics_page() {
        global $wpdb;
        
        $link_id = isset($_GET['link_id']) ? intval($_GET['link_id']) : 0;
        
        if ($link_id) {
            // Get specific link analytics
            $link = $wpdb->get_row($wpdb->prepare("
                SELECT * FROM {$wpdb->prefix}slc_links WHERE id = %d
            ", $link_id));
            
            $analytics = $wpdb->get_results($wpdb->prepare("
                SELECT * FROM {$wpdb->prefix}slc_analytics 
                WHERE link_id = %d 
                ORDER BY clicked_at DESC 
                LIMIT 100
            ", $link_id));
        } else {
            // Get general analytics
            $analytics = $wpdb->get_results("
                SELECT l.title, l.slug, l.clicks, l.unique_clicks, 
                       COUNT(a.id) as total_analytics
                FROM {$wpdb->prefix}slc_links l
                LEFT JOIN {$wpdb->prefix}slc_analytics a ON l.id = a.link_id
                WHERE l.status = 'active'
                GROUP BY l.id
                ORDER BY l.clicks DESC
            ");
        }
        
        include SLC_PLUGIN_PATH . 'templates/admin-analytics.php';
    }
    
    public function smart_links_page() {
        if (isset($_POST['submit_smart_links'])) {
            update_option('slc_enable_smart_links', isset($_POST['enable_smart_links']) ? 1 : 0);
            update_option('slc_smart_keywords', sanitize_textarea_field($_POST['smart_keywords']));
            
            wp_redirect(add_query_arg('slc_message', 'settings_saved', admin_url('admin.php?page=super-links-smart')));
            exit;
        }
        
        include SLC_PLUGIN_PATH . 'templates/admin-smart-links.php';
    }
    
    public function import_links_page() {
        if (isset($_POST['submit_import'])) {
            $source = sanitize_text_field($_POST['import_source']);
            $link_manager = new SLC_Link_Manager();
            $result = $link_manager->import_links($source);
            
            if ($result['success']) {
                wp_redirect(add_query_arg('slc_message', 'import_completed', admin_url('admin.php?page=super-links-import')));
                exit;
            } else {
                echo '<div class="notice notice-error"><p>' . $result['message'] . '</p></div>';
            }
        }
        
        include SLC_PLUGIN_PATH . 'templates/admin-import-links.php';
    }
    
    public function settings_page() {
        if (isset($_POST['submit_settings'])) {
            update_option('slc_link_prefix', sanitize_text_field($_POST['link_prefix']));
            update_option('slc_enable_cookie_tracking', isset($_POST['enable_cookie_tracking']) ? 1 : 0);
            update_option('slc_enable_smart_links', isset($_POST['enable_smart_links']) ? 1 : 0);
            update_option('slc_enable_exit_redirect', isset($_POST['enable_exit_redirect']) ? 1 : 0);
            update_option('slc_enable_facebook_clocker', isset($_POST['enable_facebook_clocker']) ? 1 : 0);
            update_option('slc_default_redirect_type', sanitize_text_field($_POST['default_redirect_type']));
            update_option('slc_enable_analytics', isset($_POST['enable_analytics']) ? 1 : 0);
            update_option('slc_popup_enabled', isset($_POST['popup_enabled']) ? 1 : 0);
            update_option('slc_popup_delay', intval($_POST['popup_delay']));
            update_option('slc_popup_content', wp_kses_post($_POST['popup_content']));
            update_option('slc_exit_redirect_url', sanitize_url($_POST['exit_redirect_url']));
            
            // Flush rewrite rules after changing link prefix
            flush_rewrite_rules();
            
            wp_redirect(add_query_arg('slc_message', 'settings_saved', admin_url('admin.php?page=super-links-settings')));
            exit;
        }
        
        include SLC_PLUGIN_PATH . 'templates/admin-settings.php';
    }
}