<?php

if (!defined('ABSPATH')) {
    exit;
}

class SLC_Facebook_Clocker {
    
    public function __construct() {
        // Constructor
    }
    
    public function is_facebook_crawler() {
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        
        $facebook_crawlers = array(
            'facebookexternalhit',
            'Facebot',
            'facebookcatalog',
            'FacebookBot'
        );
        
        foreach ($facebook_crawlers as $crawler) {
            if (stripos($user_agent, $crawler) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    public function handle_facebook_request($link) {
        if (!$this->is_facebook_crawler()) {
            return false;
        }
        
        // Serve different content for Facebook crawlers
        $this->serve_facebook_content($link);
        return true;
    }
    
    private function serve_facebook_content($link) {
        $clocker_page = get_option('slc_facebook_clocker_page', '');
        
        if (!empty($clocker_page)) {
            // Redirect to specified clocker page
            wp_redirect($clocker_page);
            exit;
        }
        
        // Serve default clocker page
        $this->serve_default_clocker_page($link);
    }
    
    private function serve_default_clocker_page($link) {
        $site_name = get_bloginfo('name');
        $page_title = $link->title ?: 'Link';
        $description = 'Clique para acessar o conteúdo';
        
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title><?php echo esc_html($page_title); ?></title>
            <meta name="description" content="<?php echo esc_attr($description); ?>">
            <meta property="og:title" content="<?php echo esc_attr($page_title); ?>">
            <meta property="og:description" content="<?php echo esc_attr($description); ?>">
            <meta property="og:type" content="website">
            <meta property="og:url" content="<?php echo esc_url(home_url()); ?>">
            <meta property="og:site_name" content="<?php echo esc_attr($site_name); ?>">
            <meta name="robots" content="noindex, nofollow">
        </head>
        <body>
            <h1><?php echo esc_html($page_title); ?></h1>
            <p><?php echo esc_html($description); ?></p>
        </body>
        </html>
        <?php
        exit;
    }
}