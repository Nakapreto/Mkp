<?php
/**
 * Toolbar Management Class for MKP Super Links
 * 
 * @package MKPSuperLinks
 * @subpackage Includes
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit('Direct access forbidden.');
}

/**
 * MKP Super Links Toolbar Class
 */
class MKP_Super_Links_Toolbar {
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('admin_bar_menu', array($this, 'add_toolbar_items'), 100);
        add_action('wp_enqueue_scripts', array($this, 'enqueue_toolbar_styles'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_toolbar_styles'));
    }
    
    /**
     * Add toolbar items
     *
     * @param WP_Admin_Bar $wp_admin_bar
     */
    public function add_toolbar_items($wp_admin_bar) {
        // Check if user can access super links
        if (!mkp_can_access_super_links()) {
            return;
        }
        
        // Check if toolbar links are enabled
        if (!mkp_get_option('show_toolbar_links', true)) {
            return;
        }
        
        // Add main super links menu
        $this->add_main_menu($wp_admin_bar);
        
        // Add network admin items for super admins
        if (mkp_is_super_admin() && mkp_get_option('show_network_links', true)) {
            $this->add_network_items($wp_admin_bar);
        }
        
        // Add site-specific items
        if (mkp_get_option('show_site_links', true)) {
            $this->add_site_items($wp_admin_bar);
        }
        
        // Add admin utility items
        if (mkp_get_option('show_admin_links', true)) {
            $this->add_admin_items($wp_admin_bar);
        }
        
        // Enhance existing My Sites menu
        if (is_multisite()) {
            $this->enhance_my_sites_menu($wp_admin_bar);
        }
    }
    
    /**
     * Add main super links menu
     *
     * @param WP_Admin_Bar $wp_admin_bar
     */
    private function add_main_menu($wp_admin_bar) {
        $wp_admin_bar->add_menu(array(
            'id' => 'mkp-super-links',
            'title' => '<span class="ab-icon dashicons-admin-links"></span><span class="ab-label">' . __('Super Links', 'mkp-super-links') . '</span>',
            'href' => admin_url('admin.php?page=mkp-super-links'),
            'meta' => array(
                'title' => __('MKP Super Links Dashboard', 'mkp-super-links')
            )
        ));
        
        // Add quick actions submenu
        $quick_links = mkp_get_quick_action_links();
        foreach ($quick_links as $id => $link) {
            $wp_admin_bar->add_menu(array(
                'parent' => 'mkp-super-links',
                'id' => 'mkp-quick-' . $id,
                'title' => '<span class="dashicons ' . $link['icon'] . '"></span> ' . $link['title'],
                'href' => $link['url'],
                'meta' => array(
                    'title' => $link['title']
                )
            ));
        }
        
        // Add separator
        $wp_admin_bar->add_menu(array(
            'parent' => 'mkp-super-links',
            'id' => 'mkp-separator-1',
            'title' => '<hr style="margin: 5px 0; border: none; border-top: 1px solid #464646;">'
        ));
        
        // Add settings link
        $wp_admin_bar->add_menu(array(
            'parent' => 'mkp-super-links',
            'id' => 'mkp-settings',
            'title' => '<span class="dashicons dashicons-admin-settings"></span> ' . __('Settings', 'mkp-super-links'),
            'href' => admin_url('admin.php?page=mkp-super-links-settings'),
            'meta' => array(
                'title' => __('Plugin Settings', 'mkp-super-links')
            )
        ));
    }
    
    /**
     * Add network admin items
     *
     * @param WP_Admin_Bar $wp_admin_bar
     */
    private function add_network_items($wp_admin_bar) {
        if (!is_multisite()) {
            return;
        }
        
        // Add network menu
        $wp_admin_bar->add_menu(array(
            'id' => 'mkp-network',
            'title' => '<span class="ab-icon dashicons-networking"></span><span class="ab-label">' . __('Network', 'mkp-super-links') . '</span>',
            'href' => network_admin_url(),
            'meta' => array(
                'title' => __('Network Administration', 'mkp-super-links')
            )
        ));
        
        // Network admin links
        $network_links = array(
            'dashboard' => array(
                'title' => __('Network Dashboard', 'mkp-super-links'),
                'url' => network_admin_url(),
                'icon' => 'dashicons-dashboard'
            ),
            'sites' => array(
                'title' => __('Sites', 'mkp-super-links'),
                'url' => network_admin_url('sites.php'),
                'icon' => 'dashicons-admin-multisite'
            ),
            'users' => array(
                'title' => __('Users', 'mkp-super-links'),
                'url' => network_admin_url('users.php'),
                'icon' => 'dashicons-admin-users'
            ),
            'themes' => array(
                'title' => __('Themes', 'mkp-super-links'),
                'url' => network_admin_url('themes.php'),
                'icon' => 'dashicons-admin-appearance'
            ),
            'plugins' => array(
                'title' => __('Plugins', 'mkp-super-links'),
                'url' => network_admin_url('plugins.php'),
                'icon' => 'dashicons-admin-plugins'
            ),
            'settings' => array(
                'title' => __('Network Settings', 'mkp-super-links'),
                'url' => network_admin_url('settings.php'),
                'icon' => 'dashicons-admin-settings'
            )
        );
        
        foreach ($network_links as $id => $link) {
            $wp_admin_bar->add_menu(array(
                'parent' => 'mkp-network',
                'id' => 'mkp-network-' . $id,
                'title' => '<span class="dashicons ' . $link['icon'] . '"></span> ' . $link['title'],
                'href' => $link['url'],
                'meta' => array(
                    'title' => $link['title']
                )
            ));
        }
        
        // Add separator
        $wp_admin_bar->add_menu(array(
            'parent' => 'mkp-network',
            'id' => 'mkp-network-separator',
            'title' => '<hr style="margin: 5px 0; border: none; border-top: 1px solid #464646;">'
        ));
        
        // Add new site link
        $wp_admin_bar->add_menu(array(
            'parent' => 'mkp-network',
            'id' => 'mkp-network-new-site',
            'title' => '<span class="dashicons dashicons-plus-alt"></span> ' . __('Add New Site', 'mkp-super-links'),
            'href' => network_admin_url('site-new.php'),
            'meta' => array(
                'title' => __('Add New Site', 'mkp-super-links')
            )
        ));
    }
    
    /**
     * Add site-specific items
     *
     * @param WP_Admin_Bar $wp_admin_bar
     */
    private function add_site_items($wp_admin_bar) {
        // Current site info
        $site_info = mkp_get_site_info(get_current_blog_id());
        
        // Add site menu
        $wp_admin_bar->add_menu(array(
            'id' => 'mkp-site',
            'title' => '<span class="ab-icon dashicons-admin-home"></span><span class="ab-label">' . __('Site Tools', 'mkp-super-links') . '</span>',
            'href' => admin_url(),
            'meta' => array(
                'title' => sprintf(__('Tools for %s', 'mkp-super-links'), $site_info['name'])
            )
        ));
        
        // Site links
        $site_links = array(
            'dashboard' => array(
                'title' => __('Dashboard', 'mkp-super-links'),
                'url' => admin_url(),
                'icon' => 'dashicons-dashboard'
            ),
            'posts' => array(
                'title' => __('Posts', 'mkp-super-links'),
                'url' => admin_url('edit.php'),
                'icon' => 'dashicons-admin-post'
            ),
            'pages' => array(
                'title' => __('Pages', 'mkp-super-links'),
                'url' => admin_url('edit.php?post_type=page'),
                'icon' => 'dashicons-admin-page'
            ),
            'media' => array(
                'title' => __('Media', 'mkp-super-links'),
                'url' => admin_url('upload.php'),
                'icon' => 'dashicons-admin-media'
            ),
            'comments' => array(
                'title' => __('Comments', 'mkp-super-links'),
                'url' => admin_url('edit-comments.php'),
                'icon' => 'dashicons-admin-comments'
            )
        );
        
        // Add capability checks
        if (current_user_can('edit_posts')) {
            foreach ($site_links as $id => $link) {
                // Skip comments if user can't moderate
                if ($id === 'comments' && !current_user_can('moderate_comments')) {
                    continue;
                }
                
                $wp_admin_bar->add_menu(array(
                    'parent' => 'mkp-site',
                    'id' => 'mkp-site-' . $id,
                    'title' => '<span class="dashicons ' . $link['icon'] . '"></span> ' . $link['title'],
                    'href' => $link['url'],
                    'meta' => array(
                        'title' => $link['title']
                    )
                ));
            }
        }
        
        // Add admin links for administrators
        if (current_user_can('manage_options')) {
            $wp_admin_bar->add_menu(array(
                'parent' => 'mkp-site',
                'id' => 'mkp-site-separator',
                'title' => '<hr style="margin: 5px 0; border: none; border-top: 1px solid #464646;">'
            ));
            
            $admin_links = array(
                'appearance' => array(
                    'title' => __('Appearance', 'mkp-super-links'),
                    'url' => admin_url('themes.php'),
                    'icon' => 'dashicons-admin-appearance'
                ),
                'plugins' => array(
                    'title' => __('Plugins', 'mkp-super-links'),
                    'url' => admin_url('plugins.php'),
                    'icon' => 'dashicons-admin-plugins'
                ),
                'users' => array(
                    'title' => __('Users', 'mkp-super-links'),
                    'url' => admin_url('users.php'),
                    'icon' => 'dashicons-admin-users'
                ),
                'settings' => array(
                    'title' => __('Settings', 'mkp-super-links'),
                    'url' => admin_url('options-general.php'),
                    'icon' => 'dashicons-admin-settings'
                )
            );
            
            foreach ($admin_links as $id => $link) {
                $wp_admin_bar->add_menu(array(
                    'parent' => 'mkp-site',
                    'id' => 'mkp-site-admin-' . $id,
                    'title' => '<span class="dashicons ' . $link['icon'] . '"></span> ' . $link['title'],
                    'href' => $link['url'],
                    'meta' => array(
                        'title' => $link['title']
                    )
                ));
            }
        }
    }
    
    /**
     * Add admin utility items
     *
     * @param WP_Admin_Bar $wp_admin_bar
     */
    private function add_admin_items($wp_admin_bar) {
        // Add utilities menu
        $wp_admin_bar->add_menu(array(
            'id' => 'mkp-utilities',
            'title' => '<span class="ab-icon dashicons-admin-tools"></span><span class="ab-label">' . __('Utilities', 'mkp-super-links') . '</span>',
            'href' => '#',
            'meta' => array(
                'title' => __('Useful Utilities', 'mkp-super-links')
            )
        ));
        
        // Utility links
        $utility_links = array();
        
        // Add cache clear option
        if (mkp_get_option('cache_enabled', true)) {
            $utility_links['clear_cache'] = array(
                'title' => __('Clear Cache', 'mkp-super-links'),
                'url' => '#',
                'icon' => 'dashicons-update',
                'onclick' => 'mkpClearCache(); return false;'
            );
        }
        
        // Add view site link
        $utility_links['view_site'] = array(
            'title' => __('View Site', 'mkp-super-links'),
            'url' => home_url(),
            'icon' => 'dashicons-visibility',
            'target' => '_blank'
        );
        
        // Add customize link
        if (current_user_can('customize')) {
            $utility_links['customize'] = array(
                'title' => __('Customize', 'mkp-super-links'),
                'url' => admin_url('customize.php'),
                'icon' => 'dashicons-admin-customizer'
            );
        }
        
        foreach ($utility_links as $id => $link) {
            $menu_args = array(
                'parent' => 'mkp-utilities',
                'id' => 'mkp-utility-' . $id,
                'title' => '<span class="dashicons ' . $link['icon'] . '"></span> ' . $link['title'],
                'href' => $link['url'],
                'meta' => array(
                    'title' => $link['title']
                )
            );
            
            if (isset($link['onclick'])) {
                $menu_args['meta']['onclick'] = $link['onclick'];
            }
            
            if (isset($link['target'])) {
                $menu_args['meta']['target'] = $link['target'];
            }
            
            $wp_admin_bar->add_menu($menu_args);
        }
    }
    
    /**
     * Enhance existing My Sites menu
     *
     * @param WP_Admin_Bar $wp_admin_bar
     */
    private function enhance_my_sites_menu($wp_admin_bar) {
        if (!mkp_is_super_admin()) {
            return;
        }
        
        // Get the existing my-sites menu
        $my_sites = $wp_admin_bar->get_node('my-sites');
        if (!$my_sites) {
            return;
        }
        
        // Add sites overview link for super admins
        $wp_admin_bar->add_menu(array(
            'parent' => 'my-sites',
            'id' => 'mkp-sites-overview',
            'title' => '<span class="dashicons dashicons-admin-multisite"></span> ' . __('Sites Overview', 'mkp-super-links'),
            'href' => network_admin_url('admin.php?page=mkp-super-links-sites'),
            'meta' => array(
                'title' => __('View all sites in a detailed overview', 'mkp-super-links')
            )
        ));
        
        // Add search functionality (JavaScript-based)
        if (mkp_get_option('enable_site_search', true)) {
            $wp_admin_bar->add_menu(array(
                'parent' => 'my-sites',
                'id' => 'mkp-site-search',
                'title' => '<div id="mkp-site-search-box" style="padding: 5px;"><input type="text" placeholder="' . __('Search sites...', 'mkp-super-links') . '" style="width: 200px;" /></div>',
                'href' => '#'
            ));
        }
    }
    
    /**
     * Enqueue toolbar styles
     */
    public function enqueue_toolbar_styles() {
        if (!is_admin_bar_showing() || !mkp_can_access_super_links()) {
            return;
        }
        
        wp_enqueue_style(
            'mkp-super-links-toolbar',
            MKP_SUPER_LINKS_ASSETS_URL . 'css/toolbar.css',
            array(),
            MKP_SUPER_LINKS_VERSION
        );
        
        // Add custom CSS if set
        $custom_css = mkp_get_option('custom_css', '');
        if (!empty($custom_css)) {
            wp_add_inline_style('mkp-super-links-toolbar', $custom_css);
        }
        
        // Enqueue toolbar JavaScript
        wp_enqueue_script(
            'mkp-super-links-toolbar',
            MKP_SUPER_LINKS_ASSETS_URL . 'js/toolbar.js',
            array('jquery'),
            MKP_SUPER_LINKS_VERSION,
            true
        );
        
        // Localize script
        wp_localize_script('mkp-super-links-toolbar', 'mkpToolbar', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mkp_super_links_nonce'),
            'strings' => array(
                'clearing_cache' => __('Clearing cache...', 'mkp-super-links'),
                'cache_cleared' => __('Cache cleared successfully!', 'mkp-super-links'),
                'error' => __('An error occurred', 'mkp-super-links')
            )
        ));
    }
    
    /**
     * Get sites for super admin with search and pagination
     *
     * @param string $search Search term
     * @param int $page Page number
     * @param int $per_page Items per page
     * @return array
     */
    public function get_sites_for_toolbar($search = '', $page = 1, $per_page = 20) {
        if (!mkp_is_super_admin()) {
            return array();
        }
        
        $args = array(
            'number' => $per_page,
            'offset' => ($page - 1) * $per_page,
            'orderby' => 'domain',
            'order' => 'ASC'
        );
        
        if (!empty($search)) {
            $args['search'] = $search;
        }
        
        $sites = mkp_get_sites($args);
        $formatted_sites = array();
        
        foreach ($sites as $site) {
            $site_info = mkp_get_site_info($site->blog_id);
            $formatted_sites[] = array(
                'id' => $site_info['id'],
                'name' => $site_info['name'],
                'url' => $site_info['url'],
                'admin_url' => $site_info['admin_url'],
                'domain' => mkp_format_site_url($site_info['url'])
            );
        }
        
        return $formatted_sites;
    }
}

// Initialize toolbar
if (mkp_get_option('enabled', true)) {
    new MKP_Super_Links_Toolbar();
}

// End of file