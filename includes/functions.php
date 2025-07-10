<?php
/**
 * Helper Functions for MKP Super Links
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
 * Get plugin option with fallback
 *
 * @param string $key Option key
 * @param mixed $default Default value
 * @return mixed Option value
 */
function mkp_get_option($key, $default = null) {
    return mkp_super_links()->get_option($key, $default);
}

/**
 * Update plugin option
 *
 * @param string $key Option key
 * @param mixed $value Option value
 * @return bool Success status
 */
function mkp_update_option($key, $value) {
    return mkp_super_links()->update_option($key, $value);
}

/**
 * Check if current user can access super links
 *
 * @return bool
 */
function mkp_can_access_super_links() {
    if (!is_user_logged_in()) {
        return false;
    }
    
    // Super admins always have access
    if (is_super_admin()) {
        return true;
    }
    
    // Regular admins have access to site-specific features
    if (current_user_can('manage_options')) {
        return true;
    }
    
    // Editors have limited access
    if (current_user_can('edit_posts')) {
        return mkp_get_option('allow_editors', false);
    }
    
    return false;
}

/**
 * Get sites with caching
 *
 * @param array $args Query arguments
 * @return array Sites array
 */
function mkp_get_sites($args = array()) {
    $cache_key = 'mkp_super_links_sites_' . md5(serialize($args));
    $cache_duration = mkp_get_option('cache_duration', 3600);
    
    if (mkp_get_option('cache_enabled', true)) {
        $sites = get_transient($cache_key);
        if ($sites !== false) {
            return $sites;
        }
    }
    
    $default_args = array(
        'number' => 0,
        'orderby' => 'domain',
        'order' => 'ASC'
    );
    
    $args = wp_parse_args($args, $default_args);
    $sites = get_sites($args);
    
    if (mkp_get_option('cache_enabled', true)) {
        set_transient($cache_key, $sites, $cache_duration);
    }
    
    return $sites;
}

/**
 * Get site info with enhanced data
 *
 * @param int $site_id Site ID
 * @return array|false Site info or false
 */
function mkp_get_site_info($site_id) {
    $site = get_site($site_id);
    if (!$site) {
        return false;
    }
    
    $site_info = array(
        'id' => $site->blog_id,
        'domain' => $site->domain,
        'path' => $site->path,
        'url' => get_site_url($site_id),
        'admin_url' => get_admin_url($site_id),
        'name' => get_blog_option($site_id, 'blogname'),
        'description' => get_blog_option($site_id, 'blogdescription'),
        'admin_email' => get_blog_option($site_id, 'admin_email'),
        'public' => (bool) $site->public,
        'archived' => (bool) $site->archived,
        'mature' => (bool) $site->mature,
        'spam' => (bool) $site->spam,
        'deleted' => (bool) $site->deleted,
        'registered' => $site->registered,
        'last_updated' => $site->last_updated
    );
    
    return apply_filters('mkp_site_info', $site_info, $site_id);
}

/**
 * Get current site ID
 *
 * @return int
 */
function mkp_get_current_site_id() {
    return get_current_blog_id();
}

/**
 * Check if current user is super admin
 *
 * @return bool
 */
function mkp_is_super_admin() {
    return is_super_admin();
}

/**
 * Get network admin URL
 *
 * @param string $path Path
 * @param string $scheme Scheme
 * @return string
 */
function mkp_network_admin_url($path = '', $scheme = 'admin') {
    if (!is_multisite()) {
        return admin_url($path, $scheme);
    }
    
    return network_admin_url($path, $scheme);
}

/**
 * Format site URL for display
 *
 * @param string $url Site URL
 * @return string Formatted URL
 */
function mkp_format_site_url($url) {
    $url = untrailingslashit($url);
    $url = str_replace(array('http://', 'https://'), '', $url);
    
    return $url;
}

/**
 * Get user sites with enhanced data
 *
 * @param int $user_id User ID
 * @return array User sites
 */
function mkp_get_user_sites($user_id = null) {
    if (is_null($user_id)) {
        $user_id = get_current_user_id();
    }
    
    // Super admins get all sites
    if (is_super_admin($user_id)) {
        return mkp_get_sites();
    }
    
    $user_sites = get_blogs_of_user($user_id);
    $sites = array();
    
    foreach ($user_sites as $site_id => $site_data) {
        $sites[] = mkp_get_site_info($site_id);
    }
    
    return array_filter($sites);
}

/**
 * Get quick action links for toolbar
 *
 * @return array
 */
function mkp_get_quick_action_links() {
    $links = array();
    
    if (mkp_is_super_admin()) {
        $links['network'] = array(
            'title' => __('Network Admin', 'mkp-super-links'),
            'url' => network_admin_url(),
            'icon' => 'dashicons-networking'
        );
        
        $links['new_site'] = array(
            'title' => __('Add New Site', 'mkp-super-links'),
            'url' => network_admin_url('site-new.php'),
            'icon' => 'dashicons-plus-alt'
        );
    }
    
    if (current_user_can('manage_options')) {
        $links['dashboard'] = array(
            'title' => __('Dashboard', 'mkp-super-links'),
            'url' => admin_url(),
            'icon' => 'dashicons-dashboard'
        );
        
        $links['settings'] = array(
            'title' => __('Settings', 'mkp-super-links'),
            'url' => admin_url('options-general.php'),
            'icon' => 'dashicons-admin-settings'
        );
    }
    
    if (current_user_can('edit_posts')) {
        $links['new_post'] = array(
            'title' => __('New Post', 'mkp-super-links'),
            'url' => admin_url('post-new.php'),
            'icon' => 'dashicons-plus'
        );
        
        $links['posts'] = array(
            'title' => __('Posts', 'mkp-super-links'),
            'url' => admin_url('edit.php'),
            'icon' => 'dashicons-admin-post'
        );
    }
    
    if (current_user_can('edit_pages')) {
        $links['new_page'] = array(
            'title' => __('New Page', 'mkp-super-links'),
            'url' => admin_url('post-new.php?post_type=page'),
            'icon' => 'dashicons-plus-alt2'
        );
        
        $links['pages'] = array(
            'title' => __('Pages', 'mkp-super-links'),
            'url' => admin_url('edit.php?post_type=page'),
            'icon' => 'dashicons-admin-page'
        );
    }
    
    return apply_filters('mkp_quick_action_links', $links);
}

/**
 * Get site statistics
 *
 * @param int $site_id Site ID
 * @return array
 */
function mkp_get_site_stats($site_id = null) {
    if (is_null($site_id)) {
        $site_id = get_current_blog_id();
    }
    
    $original_blog_id = get_current_blog_id();
    
    if ($site_id !== $original_blog_id) {
        switch_to_blog($site_id);
    }
    
    $stats = array(
        'posts' => wp_count_posts('post')->publish,
        'pages' => wp_count_posts('page')->publish,
        'comments' => wp_count_comments()->approved,
        'users' => count_users()['total_users']
    );
    
    if ($site_id !== $original_blog_id) {
        restore_current_blog();
    }
    
    return apply_filters('mkp_site_stats', $stats, $site_id);
}

/**
 * Check if feature is enabled
 *
 * @param string $feature Feature name
 * @return bool
 */
function mkp_is_feature_enabled($feature) {
    $enabled_features = mkp_get_option('enabled_features', array());
    
    if (empty($enabled_features)) {
        // Default enabled features
        $enabled_features = array(
            'toolbar_links',
            'site_links',
            'network_links',
            'admin_links',
            'quick_actions'
        );
    }
    
    return in_array($feature, $enabled_features);
}

/**
 * Log plugin activity
 *
 * @param string $message Log message
 * @param string $level Log level
 */
function mkp_log($message, $level = 'info') {
    if (!mkp_get_option('debug_logging', false)) {
        return;
    }
    
    $log_entry = sprintf(
        '[%s] [%s] %s',
        current_time('Y-m-d H:i:s'),
        strtoupper($level),
        $message
    );
    
    error_log('[MKP Super Links] ' . $log_entry);
}

/**
 * Get plugin assets URL
 *
 * @param string $file Asset file
 * @return string Asset URL
 */
function mkp_get_asset_url($file) {
    return MKP_SUPER_LINKS_ASSETS_URL . ltrim($file, '/');
}

/**
 * Enqueue plugin styles
 *
 * @param string $handle Style handle
 * @param string $file CSS file
 * @param array $deps Dependencies
 */
function mkp_enqueue_style($handle, $file, $deps = array()) {
    wp_enqueue_style(
        'mkp-super-links-' . $handle,
        mkp_get_asset_url('css/' . $file),
        $deps,
        MKP_SUPER_LINKS_VERSION
    );
}

/**
 * Enqueue plugin scripts
 *
 * @param string $handle Script handle
 * @param string $file JS file
 * @param array $deps Dependencies
 * @param bool $in_footer Load in footer
 */
function mkp_enqueue_script($handle, $file, $deps = array('jquery'), $in_footer = true) {
    wp_enqueue_script(
        'mkp-super-links-' . $handle,
        mkp_get_asset_url('js/' . $file),
        $deps,
        MKP_SUPER_LINKS_VERSION,
        $in_footer
    );
}

/**
 * Generate nonce for AJAX requests
 *
 * @param string $action Action name
 * @return string Nonce
 */
function mkp_create_nonce($action = 'mkp_super_links_nonce') {
    return wp_create_nonce($action);
}

/**
 * Verify nonce for security
 *
 * @param string $nonce Nonce value
 * @param string $action Action name
 * @return bool
 */
function mkp_verify_nonce($nonce, $action = 'mkp_super_links_nonce') {
    return wp_verify_nonce($nonce, $action);
}

/**
 * Get localized strings for JavaScript
 *
 * @return array
 */
function mkp_get_localized_strings() {
    return array(
        'loading' => __('Loading...', 'mkp-super-links'),
        'error' => __('An error occurred', 'mkp-super-links'),
        'success' => __('Operation successful', 'mkp-super-links'),
        'confirm' => __('Are you sure?', 'mkp-super-links'),
        'cancel' => __('Cancel', 'mkp-super-links'),
        'ok' => __('OK', 'mkp-super-links'),
        'save' => __('Save', 'mkp-super-links'),
        'delete' => __('Delete', 'mkp-super-links'),
        'edit' => __('Edit', 'mkp-super-links'),
        'view' => __('View', 'mkp-super-links'),
        'admin' => __('Admin', 'mkp-super-links'),
        'dashboard' => __('Dashboard', 'mkp-super-links'),
        'settings' => __('Settings', 'mkp-super-links'),
        'tools' => __('Tools', 'mkp-super-links'),
        'cache_cleared' => __('Cache cleared successfully', 'mkp-super-links'),
        'settings_saved' => __('Settings saved successfully', 'mkp-super-links'),
        'settings_exported' => __('Settings exported successfully', 'mkp-super-links'),
        'settings_imported' => __('Settings imported successfully', 'mkp-super-links')
    );
}

/**
 * Check if plugin is network activated
 *
 * @return bool
 */
function mkp_is_network_activated() {
    if (!is_multisite()) {
        return false;
    }
    
    $plugins = get_site_option('active_sitewide_plugins');
    return isset($plugins[MKP_SUPER_LINKS_PLUGIN_BASENAME]);
}

/**
 * Get plugin info
 *
 * @return array
 */
function mkp_get_plugin_info() {
    return array(
        'name' => 'MKP Super Links',
        'version' => MKP_SUPER_LINKS_VERSION,
        'url' => MKP_SUPER_LINKS_PLUGIN_URL,
        'path' => MKP_SUPER_LINKS_PLUGIN_DIR,
        'file' => MKP_SUPER_LINKS_PLUGIN_FILE,
        'basename' => MKP_SUPER_LINKS_PLUGIN_BASENAME,
        'network_activated' => mkp_is_network_activated(),
        'multisite' => is_multisite()
    );
}

/**
 * Sanitize and validate plugin options
 *
 * @param array $options Raw options
 * @return array Sanitized options
 */
function mkp_sanitize_options($options) {
    $sanitized = array();
    
    // Boolean options
    $boolean_options = array(
        'enabled',
        'show_toolbar_links',
        'show_site_links',
        'show_network_links',
        'show_admin_links',
        'load_on_frontend',
        'cache_enabled',
        'debug_logging',
        'allow_editors'
    );
    
    foreach ($boolean_options as $option) {
        if (isset($options[$option])) {
            $sanitized[$option] = (bool) $options[$option];
        }
    }
    
    // Integer options
    if (isset($options['cache_duration'])) {
        $sanitized['cache_duration'] = max(300, min(86400, absint($options['cache_duration'])));
    }
    
    // String options
    if (isset($options['custom_css'])) {
        $sanitized['custom_css'] = sanitize_textarea_field($options['custom_css']);
    }
    
    // Array options
    if (isset($options['enabled_features']) && is_array($options['enabled_features'])) {
        $sanitized['enabled_features'] = array_map('sanitize_text_field', $options['enabled_features']);
    }
    
    return apply_filters('mkp_sanitize_options', $sanitized, $options);
}

// End of file