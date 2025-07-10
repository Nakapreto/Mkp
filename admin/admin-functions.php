<?php
/**
 * Admin Functions for MKP Super Links
 * 
 * This file contains admin-specific functions for the MKP Super Links plugin.
 * 
 * @package MKPSuperLinks
 * @subpackage Admin
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit('Direct access forbidden.');
}

/**
 * MKP Super Links Admin Functions Class
 */
class MKP_Super_Links_Admin_Functions {
    
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
        add_action('admin_init', array($this, 'admin_init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('network_admin_menu', array($this, 'add_network_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_mkp_super_links_action', array($this, 'handle_ajax_requests'));
        add_action('admin_notices', array($this, 'admin_notices'));
        add_action('network_admin_notices', array($this, 'network_admin_notices'));
        
        // Add settings link to plugins page
        add_filter('plugin_action_links_' . MKP_SUPER_LINKS_PLUGIN_BASENAME, array($this, 'add_settings_link'));
        add_filter('network_admin_plugin_action_links_' . MKP_SUPER_LINKS_PLUGIN_BASENAME, array($this, 'add_network_settings_link'));
    }
    
    /**
     * Admin initialization
     */
    public function admin_init() {
        // Register settings
        $this->register_settings();
        
        // Check for plugin updates
        $this->check_plugin_updates();
    }
    
    /**
     * Register plugin settings
     */
    private function register_settings() {
        register_setting('mkp_super_links_settings', 'mkp_super_links_options', array(
            'sanitize_callback' => array($this, 'sanitize_options')
        ));
        
        // Add settings sections
        add_settings_section(
            'mkp_super_links_general',
            __('General Settings', 'mkp-super-links'),
            array($this, 'general_settings_section_callback'),
            'mkp_super_links_settings'
        );
        
        add_settings_section(
            'mkp_super_links_toolbar',
            __('Toolbar Settings', 'mkp-super-links'),
            array($this, 'toolbar_settings_section_callback'),
            'mkp_super_links_settings'
        );
        
        add_settings_section(
            'mkp_super_links_advanced',
            __('Advanced Settings', 'mkp-super-links'),
            array($this, 'advanced_settings_section_callback'),
            'mkp_super_links_settings'
        );
        
        // Add settings fields
        $this->add_settings_fields();
    }
    
    /**
     * Add settings fields
     */
    private function add_settings_fields() {
        // General settings
        add_settings_field(
            'enabled',
            __('Enable MKP Super Links', 'mkp-super-links'),
            array($this, 'enabled_field_callback'),
            'mkp_super_links_settings',
            'mkp_super_links_general'
        );
        
        add_settings_field(
            'load_on_frontend',
            __('Load on Frontend', 'mkp-super-links'),
            array($this, 'load_on_frontend_field_callback'),
            'mkp_super_links_settings',
            'mkp_super_links_general'
        );
        
        // Toolbar settings
        add_settings_field(
            'show_toolbar_links',
            __('Show Toolbar Links', 'mkp-super-links'),
            array($this, 'show_toolbar_links_field_callback'),
            'mkp_super_links_settings',
            'mkp_super_links_toolbar'
        );
        
        add_settings_field(
            'show_site_links',
            __('Show Site Links', 'mkp-super-links'),
            array($this, 'show_site_links_field_callback'),
            'mkp_super_links_settings',
            'mkp_super_links_toolbar'
        );
        
        add_settings_field(
            'show_network_links',
            __('Show Network Links', 'mkp-super-links'),
            array($this, 'show_network_links_field_callback'),
            'mkp_super_links_settings',
            'mkp_super_links_toolbar'
        );
        
        add_settings_field(
            'show_admin_links',
            __('Show Admin Links', 'mkp-super-links'),
            array($this, 'show_admin_links_field_callback'),
            'mkp_super_links_settings',
            'mkp_super_links_toolbar'
        );
        
        // Advanced settings
        add_settings_field(
            'cache_enabled',
            __('Enable Caching', 'mkp-super-links'),
            array($this, 'cache_enabled_field_callback'),
            'mkp_super_links_settings',
            'mkp_super_links_advanced'
        );
        
        add_settings_field(
            'cache_duration',
            __('Cache Duration (seconds)', 'mkp-super-links'),
            array($this, 'cache_duration_field_callback'),
            'mkp_super_links_settings',
            'mkp_super_links_advanced'
        );
        
        add_settings_field(
            'custom_css',
            __('Custom CSS', 'mkp-super-links'),
            array($this, 'custom_css_field_callback'),
            'mkp_super_links_settings',
            'mkp_super_links_advanced'
        );
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        // Only add for users with appropriate capabilities
        if (!current_user_can('manage_options')) {
            return;
        }
        
        add_menu_page(
            __('MKP Super Links', 'mkp-super-links'),
            __('Super Links', 'mkp-super-links'),
            'manage_options',
            'mkp-super-links',
            array($this, 'admin_page'),
            'dashicons-admin-links',
            30
        );
        
        add_submenu_page(
            'mkp-super-links',
            __('Settings', 'mkp-super-links'),
            __('Settings', 'mkp-super-links'),
            'manage_options',
            'mkp-super-links-settings',
            array($this, 'settings_page')
        );
        
        add_submenu_page(
            'mkp-super-links',
            __('Tools', 'mkp-super-links'),
            __('Tools', 'mkp-super-links'),
            'manage_options',
            'mkp-super-links-tools',
            array($this, 'tools_page')
        );
    }
    
    /**
     * Add network admin menu
     */
    public function add_network_admin_menu() {
        // Only add for super admins
        if (!is_super_admin()) {
            return;
        }
        
        add_menu_page(
            __('MKP Super Links', 'mkp-super-links'),
            __('Super Links', 'mkp-super-links'),
            'manage_network',
            'mkp-super-links',
            array($this, 'network_admin_page'),
            'dashicons-admin-links',
            30
        );
        
        add_submenu_page(
            'mkp-super-links',
            __('Network Settings', 'mkp-super-links'),
            __('Settings', 'mkp-super-links'),
            'manage_network',
            'mkp-super-links-network-settings',
            array($this, 'network_settings_page')
        );
        
        add_submenu_page(
            'mkp-super-links',
            __('Sites Overview', 'mkp-super-links'),
            __('Sites Overview', 'mkp-super-links'),
            'manage_network',
            'mkp-super-links-sites',
            array($this, 'sites_overview_page')
        );
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        // Only load on plugin pages
        if (strpos($hook, 'mkp-super-links') === false) {
            return;
        }
        
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
        wp_enqueue_script('jquery-ui-sortable');
        wp_enqueue_script('jquery-ui-tabs');
    }
    
    /**
     * Main admin page
     */
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('MKP Super Links', 'mkp-super-links'); ?></h1>
            
            <div class="mkp-super-links-dashboard">
                <div class="mkp-dashboard-widgets">
                    
                    <div class="mkp-widget">
                        <h2><?php esc_html_e('Quick Actions', 'mkp-super-links'); ?></h2>
                        <div class="mkp-widget-content">
                            <p><a href="<?php echo admin_url('admin.php?page=mkp-super-links-settings'); ?>" class="button button-primary"><?php esc_html_e('Configure Settings', 'mkp-super-links'); ?></a></p>
                            <p><a href="<?php echo admin_url('admin.php?page=mkp-super-links-tools'); ?>" class="button"><?php esc_html_e('Access Tools', 'mkp-super-links'); ?></a></p>
                            <?php if (is_multisite() && is_super_admin()): ?>
                            <p><a href="<?php echo network_admin_url('admin.php?page=mkp-super-links'); ?>" class="button"><?php esc_html_e('Network Settings', 'mkp-super-links'); ?></a></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="mkp-widget">
                        <h2><?php esc_html_e('Plugin Status', 'mkp-super-links'); ?></h2>
                        <div class="mkp-widget-content">
                            <?php $options = mkp_super_links()->get_options(); ?>
                            <p><strong><?php esc_html_e('Status:', 'mkp-super-links'); ?></strong> 
                            <?php if (!empty($options['enabled'])): ?>
                                <span class="mkp-status-enabled"><?php esc_html_e('Enabled', 'mkp-super-links'); ?></span>
                            <?php else: ?>
                                <span class="mkp-status-disabled"><?php esc_html_e('Disabled', 'mkp-super-links'); ?></span>
                            <?php endif; ?>
                            </p>
                            <p><strong><?php esc_html_e('Version:', 'mkp-super-links'); ?></strong> <?php echo esc_html(MKP_SUPER_LINKS_VERSION); ?></p>
                            <p><strong><?php esc_html_e('Multisite:', 'mkp-super-links'); ?></strong> <?php echo is_multisite() ? esc_html__('Yes', 'mkp-super-links') : esc_html__('No', 'mkp-super-links'); ?></p>
                        </div>
                    </div>
                    
                    <div class="mkp-widget">
                        <h2><?php esc_html_e('Features', 'mkp-super-links'); ?></h2>
                        <div class="mkp-widget-content">
                            <ul>
                                <li><?php esc_html_e('Enhanced Toolbar Navigation', 'mkp-super-links'); ?></li>
                                <li><?php esc_html_e('Quick Site Switching', 'mkp-super-links'); ?></li>
                                <li><?php esc_html_e('Network Admin Tools', 'mkp-super-links'); ?></li>
                                <li><?php esc_html_e('Custom Link Management', 'mkp-super-links'); ?></li>
                                <li><?php esc_html_e('Performance Optimization', 'mkp-super-links'); ?></li>
                            </ul>
                        </div>
                    </div>
                    
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Network admin page
     */
    public function network_admin_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('MKP Super Links - Network Admin', 'mkp-super-links'); ?></h1>
            
            <div class="mkp-super-links-network-dashboard">
                <div class="mkp-dashboard-widgets">
                    
                    <div class="mkp-widget">
                        <h2><?php esc_html_e('Network Overview', 'mkp-super-links'); ?></h2>
                        <div class="mkp-widget-content">
                            <?php
                            $sites_count = get_blog_count();
                            $users_count = get_user_count();
                            ?>
                            <p><strong><?php esc_html_e('Total Sites:', 'mkp-super-links'); ?></strong> <?php echo esc_html($sites_count); ?></p>
                            <p><strong><?php esc_html_e('Total Users:', 'mkp-super-links'); ?></strong> <?php echo esc_html($users_count); ?></p>
                            <p><a href="<?php echo network_admin_url('admin.php?page=mkp-super-links-sites'); ?>" class="button"><?php esc_html_e('View Sites Overview', 'mkp-super-links'); ?></a></p>
                        </div>
                    </div>
                    
                    <div class="mkp-widget">
                        <h2><?php esc_html_e('Quick Actions', 'mkp-super-links'); ?></h2>
                        <div class="mkp-widget-content">
                            <p><a href="<?php echo network_admin_url('site-new.php'); ?>" class="button button-primary"><?php esc_html_e('Add New Site', 'mkp-super-links'); ?></a></p>
                            <p><a href="<?php echo network_admin_url('sites.php'); ?>" class="button"><?php esc_html_e('Manage Sites', 'mkp-super-links'); ?></a></p>
                            <p><a href="<?php echo network_admin_url('users.php'); ?>" class="button"><?php esc_html_e('Manage Users', 'mkp-super-links'); ?></a></p>
                        </div>
                    </div>
                    
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Settings page
     */
    public function settings_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('MKP Super Links Settings', 'mkp-super-links'); ?></h1>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('mkp_super_links_settings');
                do_settings_sections('mkp_super_links_settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Network settings page
     */
    public function network_settings_page() {
        if (isset($_POST['submit'])) {
            check_admin_referer('mkp_super_links_network_settings');
            
            $options = array();
            $options['enabled'] = isset($_POST['enabled']) ? 1 : 0;
            $options['show_toolbar_links'] = isset($_POST['show_toolbar_links']) ? 1 : 0;
            $options['show_site_links'] = isset($_POST['show_site_links']) ? 1 : 0;
            $options['show_network_links'] = isset($_POST['show_network_links']) ? 1 : 0;
            $options['show_admin_links'] = isset($_POST['show_admin_links']) ? 1 : 0;
            $options['load_on_frontend'] = isset($_POST['load_on_frontend']) ? 1 : 0;
            $options['cache_enabled'] = isset($_POST['cache_enabled']) ? 1 : 0;
            $options['cache_duration'] = absint($_POST['cache_duration']);
            $options['custom_css'] = sanitize_textarea_field($_POST['custom_css']);
            
            mkp_super_links()->update_options($options);
            
            echo '<div class="notice notice-success"><p>' . esc_html__('Settings saved.', 'mkp-super-links') . '</p></div>';
        }
        
        $options = mkp_super_links()->get_options();
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('MKP Super Links Network Settings', 'mkp-super-links'); ?></h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('mkp_super_links_network_settings'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php esc_html_e('Enable MKP Super Links', 'mkp-super-links'); ?></th>
                        <td>
                            <input type="checkbox" name="enabled" value="1" <?php checked(!empty($options['enabled'])); ?> />
                            <p class="description"><?php esc_html_e('Enable or disable the plugin functionality network-wide.', 'mkp-super-links'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Show Toolbar Links', 'mkp-super-links'); ?></th>
                        <td>
                            <input type="checkbox" name="show_toolbar_links" value="1" <?php checked(!empty($options['show_toolbar_links'])); ?> />
                            <p class="description"><?php esc_html_e('Display enhanced links in the WordPress toolbar.', 'mkp-super-links'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Tools page
     */
    public function tools_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('MKP Super Links Tools', 'mkp-super-links'); ?></h1>
            
            <div class="mkp-tools-section">
                <h2><?php esc_html_e('Cache Management', 'mkp-super-links'); ?></h2>
                <p><?php esc_html_e('Clear the plugin cache to force refresh of all cached data.', 'mkp-super-links'); ?></p>
                <p><button type="button" class="button" id="mkp-clear-cache"><?php esc_html_e('Clear Cache', 'mkp-super-links'); ?></button></p>
            </div>
            
            <div class="mkp-tools-section">
                <h2><?php esc_html_e('Export/Import Settings', 'mkp-super-links'); ?></h2>
                <p><?php esc_html_e('Export or import plugin settings for backup or migration purposes.', 'mkp-super-links'); ?></p>
                <p>
                    <button type="button" class="button" id="mkp-export-settings"><?php esc_html_e('Export Settings', 'mkp-super-links'); ?></button>
                    <button type="button" class="button" id="mkp-import-settings"><?php esc_html_e('Import Settings', 'mkp-super-links'); ?></button>
                </p>
            </div>
        </div>
        <?php
    }
    
    /**
     * Sites overview page
     */
    public function sites_overview_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Sites Overview', 'mkp-super-links'); ?></h1>
            
            <div class="mkp-sites-overview">
                <?php
                $sites = get_sites(array('number' => 0));
                
                if (!empty($sites)) {
                    echo '<table class="wp-list-table widefat fixed striped">';
                    echo '<thead><tr>';
                    echo '<th>' . esc_html__('Site ID', 'mkp-super-links') . '</th>';
                    echo '<th>' . esc_html__('URL', 'mkp-super-links') . '</th>';
                    echo '<th>' . esc_html__('Name', 'mkp-super-links') . '</th>';
                    echo '<th>' . esc_html__('Status', 'mkp-super-links') . '</th>';
                    echo '<th>' . esc_html__('Actions', 'mkp-super-links') . '</th>';
                    echo '</tr></thead>';
                    echo '<tbody>';
                    
                    foreach ($sites as $site) {
                        $site_url = get_site_url($site->blog_id);
                        $site_name = get_blog_option($site->blog_id, 'blogname');
                        $site_status = $site->public ? __('Public', 'mkp-super-links') : __('Private', 'mkp-super-links');
                        
                        echo '<tr>';
                        echo '<td>' . esc_html($site->blog_id) . '</td>';
                        echo '<td><a href="' . esc_url($site_url) . '" target="_blank">' . esc_html($site_url) . '</a></td>';
                        echo '<td>' . esc_html($site_name) . '</td>';
                        echo '<td>' . esc_html($site_status) . '</td>';
                        echo '<td>';
                        echo '<a href="' . esc_url(get_admin_url($site->blog_id)) . '" class="button button-small">' . esc_html__('Admin', 'mkp-super-links') . '</a> ';
                        echo '<a href="' . esc_url($site_url) . '" class="button button-small" target="_blank">' . esc_html__('Visit', 'mkp-super-links') . '</a>';
                        echo '</td>';
                        echo '</tr>';
                    }
                    
                    echo '</tbody>';
                    echo '</table>';
                } else {
                    echo '<p>' . esc_html__('No sites found.', 'mkp-super-links') . '</p>';
                }
                ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Settings section callbacks
     */
    public function general_settings_section_callback() {
        echo '<p>' . esc_html__('General plugin settings.', 'mkp-super-links') . '</p>';
    }
    
    public function toolbar_settings_section_callback() {
        echo '<p>' . esc_html__('Configure toolbar and navigation settings.', 'mkp-super-links') . '</p>';
    }
    
    public function advanced_settings_section_callback() {
        echo '<p>' . esc_html__('Advanced configuration options.', 'mkp-super-links') . '</p>';
    }
    
    /**
     * Settings field callbacks
     */
    public function enabled_field_callback() {
        $options = mkp_super_links()->get_options();
        $enabled = !empty($options['enabled']);
        echo '<input type="checkbox" name="mkp_super_links_options[enabled]" value="1" ' . checked($enabled, true, false) . ' />';
        echo '<p class="description">' . esc_html__('Enable or disable the plugin functionality.', 'mkp-super-links') . '</p>';
    }
    
    public function load_on_frontend_field_callback() {
        $options = mkp_super_links()->get_options();
        $load_on_frontend = !empty($options['load_on_frontend']);
        echo '<input type="checkbox" name="mkp_super_links_options[load_on_frontend]" value="1" ' . checked($load_on_frontend, true, false) . ' />';
        echo '<p class="description">' . esc_html__('Load plugin assets on the frontend for logged-in users.', 'mkp-super-links') . '</p>';
    }
    
    public function show_toolbar_links_field_callback() {
        $options = mkp_super_links()->get_options();
        $show_toolbar_links = !empty($options['show_toolbar_links']);
        echo '<input type="checkbox" name="mkp_super_links_options[show_toolbar_links]" value="1" ' . checked($show_toolbar_links, true, false) . ' />';
        echo '<p class="description">' . esc_html__('Display enhanced links in the WordPress toolbar.', 'mkp-super-links') . '</p>';
    }
    
    public function show_site_links_field_callback() {
        $options = mkp_super_links()->get_options();
        $show_site_links = !empty($options['show_site_links']);
        echo '<input type="checkbox" name="mkp_super_links_options[show_site_links]" value="1" ' . checked($show_site_links, true, false) . ' />';
        echo '<p class="description">' . esc_html__('Show quick links to site-specific admin pages.', 'mkp-super-links') . '</p>';
    }
    
    public function show_network_links_field_callback() {
        $options = mkp_super_links()->get_options();
        $show_network_links = !empty($options['show_network_links']);
        echo '<input type="checkbox" name="mkp_super_links_options[show_network_links]" value="1" ' . checked($show_network_links, true, false) . ' />';
        echo '<p class="description">' . esc_html__('Show network admin links for super administrators.', 'mkp-super-links') . '</p>';
    }
    
    public function show_admin_links_field_callback() {
        $options = mkp_super_links()->get_options();
        $show_admin_links = !empty($options['show_admin_links']);
        echo '<input type="checkbox" name="mkp_super_links_options[show_admin_links]" value="1" ' . checked($show_admin_links, true, false) . ' />';
        echo '<p class="description">' . esc_html__('Show additional admin utility links.', 'mkp-super-links') . '</p>';
    }
    
    public function cache_enabled_field_callback() {
        $options = mkp_super_links()->get_options();
        $cache_enabled = !empty($options['cache_enabled']);
        echo '<input type="checkbox" name="mkp_super_links_options[cache_enabled]" value="1" ' . checked($cache_enabled, true, false) . ' />';
        echo '<p class="description">' . esc_html__('Enable caching to improve performance.', 'mkp-super-links') . '</p>';
    }
    
    public function cache_duration_field_callback() {
        $options = mkp_super_links()->get_options();
        $cache_duration = isset($options['cache_duration']) ? $options['cache_duration'] : 3600;
        echo '<input type="number" name="mkp_super_links_options[cache_duration]" value="' . esc_attr($cache_duration) . '" min="300" max="86400" />';
        echo '<p class="description">' . esc_html__('Cache duration in seconds (300-86400).', 'mkp-super-links') . '</p>';
    }
    
    public function custom_css_field_callback() {
        $options = mkp_super_links()->get_options();
        $custom_css = isset($options['custom_css']) ? $options['custom_css'] : '';
        echo '<textarea name="mkp_super_links_options[custom_css]" rows="10" cols="50" class="large-text">' . esc_textarea($custom_css) . '</textarea>';
        echo '<p class="description">' . esc_html__('Add custom CSS to style the plugin elements.', 'mkp-super-links') . '</p>';
    }
    
    /**
     * Sanitize options
     */
    public function sanitize_options($options) {
        $sanitized = array();
        
        if (isset($options['enabled'])) {
            $sanitized['enabled'] = (bool) $options['enabled'];
        }
        
        if (isset($options['load_on_frontend'])) {
            $sanitized['load_on_frontend'] = (bool) $options['load_on_frontend'];
        }
        
        if (isset($options['show_toolbar_links'])) {
            $sanitized['show_toolbar_links'] = (bool) $options['show_toolbar_links'];
        }
        
        if (isset($options['show_site_links'])) {
            $sanitized['show_site_links'] = (bool) $options['show_site_links'];
        }
        
        if (isset($options['show_network_links'])) {
            $sanitized['show_network_links'] = (bool) $options['show_network_links'];
        }
        
        if (isset($options['show_admin_links'])) {
            $sanitized['show_admin_links'] = (bool) $options['show_admin_links'];
        }
        
        if (isset($options['cache_enabled'])) {
            $sanitized['cache_enabled'] = (bool) $options['cache_enabled'];
        }
        
        if (isset($options['cache_duration'])) {
            $sanitized['cache_duration'] = absint($options['cache_duration']);
            if ($sanitized['cache_duration'] < 300) {
                $sanitized['cache_duration'] = 300;
            }
            if ($sanitized['cache_duration'] > 86400) {
                $sanitized['cache_duration'] = 86400;
            }
        }
        
        if (isset($options['custom_css'])) {
            $sanitized['custom_css'] = sanitize_textarea_field($options['custom_css']);
        }
        
        return $sanitized;
    }
    
    /**
     * Handle AJAX requests
     */
    public function handle_ajax_requests() {
        check_ajax_referer('mkp_super_links_nonce', 'nonce');
        
        $action = sanitize_text_field($_POST['mkp_action']);
        
        switch ($action) {
            case 'clear_cache':
                $this->clear_cache();
                wp_send_json_success(__('Cache cleared successfully.', 'mkp-super-links'));
                break;
                
            case 'export_settings':
                $this->export_settings();
                break;
                
            default:
                wp_send_json_error(__('Invalid action.', 'mkp-super-links'));
        }
    }
    
    /**
     * Clear cache
     */
    private function clear_cache() {
        // Clear plugin-specific cache
        delete_transient('mkp_super_links_sites_cache');
        delete_transient('mkp_super_links_users_cache');
        
        // Clear site transients if multisite
        if (is_multisite()) {
            delete_site_transient('mkp_super_links_network_cache');
        }
        
        do_action('mkp_super_links_cache_cleared');
    }
    
    /**
     * Export settings
     */
    private function export_settings() {
        $options = mkp_super_links()->get_options();
        $export_data = array(
            'version' => MKP_SUPER_LINKS_VERSION,
            'timestamp' => current_time('timestamp'),
            'options' => $options
        );
        
        wp_send_json_success(array(
            'data' => base64_encode(json_encode($export_data)),
            'filename' => 'mkp-super-links-settings-' . date('Y-m-d-H-i-s') . '.json'
        ));
    }
    
    /**
     * Check plugin updates
     */
    private function check_plugin_updates() {
        $current_version = mkp_super_links()->get_option('version', '0.0.0');
        
        if (version_compare($current_version, MKP_SUPER_LINKS_VERSION, '<')) {
            // Run update routines
            $this->run_updates($current_version);
            
            // Update version
            mkp_super_links()->update_option('version', MKP_SUPER_LINKS_VERSION);
        }
    }
    
    /**
     * Run plugin updates
     */
    private function run_updates($from_version) {
        // Add update routines here as needed
        do_action('mkp_super_links_updated', $from_version, MKP_SUPER_LINKS_VERSION);
    }
    
    /**
     * Add settings link to plugins page
     */
    public function add_settings_link($links) {
        $settings_link = '<a href="' . admin_url('admin.php?page=mkp-super-links-settings') . '">' . __('Settings', 'mkp-super-links') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }
    
    /**
     * Add network settings link to plugins page
     */
    public function add_network_settings_link($links) {
        $settings_link = '<a href="' . network_admin_url('admin.php?page=mkp-super-links-network-settings') . '">' . __('Network Settings', 'mkp-super-links') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }
    
    /**
     * Admin notices
     */
    public function admin_notices() {
        // Show first install notice
        if (mkp_super_links()->get_option('first_install', false)) {
            echo '<div class="notice notice-success is-dismissible">';
            echo '<p>' . sprintf(
                __('Thank you for installing MKP Super Links! <a href="%s">Configure your settings</a> to get started.', 'mkp-super-links'),
                admin_url('admin.php?page=mkp-super-links-settings')
            ) . '</p>';
            echo '</div>';
            
            mkp_super_links()->update_option('first_install', false);
        }
    }
    
    /**
     * Network admin notices
     */
    public function network_admin_notices() {
        // Show network admin specific notices
        if (is_super_admin() && mkp_super_links()->get_option('first_install', false)) {
            echo '<div class="notice notice-success is-dismissible">';
            echo '<p>' . sprintf(
                __('MKP Super Links is now active network-wide! <a href="%s">Configure network settings</a> to customize the experience.', 'mkp-super-links'),
                network_admin_url('admin.php?page=mkp-super-links-network-settings')
            ) . '</p>';
            echo '</div>';
        }
    }
}

// Initialize admin functions
if (is_admin()) {
    new MKP_Super_Links_Admin_Functions();
}

// End of file