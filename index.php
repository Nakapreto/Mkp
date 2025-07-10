<?php
/**
 * Prevent direct access to the plugin directory
 * 
 * @package MKPSuperLinks
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit('Direct access forbidden.');
}

// Redirect to admin area if accessed directly
wp_redirect(admin_url());
exit;