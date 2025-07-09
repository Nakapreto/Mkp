<?php

if (!defined('ABSPATH')) {
    exit;
}

class SLC_Redirect_Handler {
    
    public function __construct() {
        // Constructor
    }
    
    public function process_redirect($slug) {
        $link_manager = new SLC_Link_Manager();
        $link = $link_manager->get_link_by_slug($slug);
        
        if (!$link) {
            // Link not found, redirect to 404
            global $wp_query;
            $wp_query->set_404();
            status_header(404);
            return;
        }
        
        // Check if analytics is enabled
        if (get_option('slc_enable_analytics', 1)) {
            $analytics = new SLC_Analytics();
            $analytics->track_click($link->id);
        }
        
        // Check if cookie tracking is enabled for this link
        if ($link->cookie_tracking) {
            $this->set_affiliate_cookie($link);
        }
        
        // Handle Facebook cloaking if enabled
        if ($link->facebook_cloaked && $this->is_facebook_bot()) {
            $this->handle_facebook_clocker($link);
            return;
        }
        
        // Check if link should be cloaked
        if ($link->cloaked && !$this->should_bypass_cloaking()) {
            $this->serve_cloaked_content($link);
        } else {
            $this->redirect_to_target($link);
        }
    }
    
    private function set_affiliate_cookie($link) {
        $cookie_name = 'slc_affiliate_' . $link->id;
        $cookie_value = array(
            'link_id' => $link->id,
            'timestamp' => time(),
            'target_url' => $link->target_url
        );
        
        // Set cookie for 30 days
        $cookie_duration = 30 * 24 * 60 * 60; // 30 days in seconds
        setcookie(
            $cookie_name,
            json_encode($cookie_value),
            time() + $cookie_duration,
            '/',
            $this->get_cookie_domain(),
            is_ssl(),
            true // HttpOnly
        );
        
        // Also set a general affiliate cookie
        setcookie(
            'slc_last_affiliate',
            $link->id,
            time() + $cookie_duration,
            '/',
            $this->get_cookie_domain(),
            is_ssl(),
            true
        );
    }
    
    private function get_cookie_domain() {
        $domain = parse_url(home_url(), PHP_URL_HOST);
        
        // For subdomains, we might want to set cookie for the main domain
        if (strpos($domain, '.') !== false) {
            $parts = explode('.', $domain);
            if (count($parts) > 2) {
                // Return main domain for subdomains (e.g., .example.com for sub.example.com)
                return '.' . implode('.', array_slice($parts, -2));
            }
        }
        
        return $domain;
    }
    
    private function is_facebook_bot() {
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
    
    private function should_bypass_cloaking() {
        // Check if this is a bot/crawler that should see the actual content
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        
        $bots = array(
            'Googlebot',
            'Bingbot',
            'Slurp',
            'DuckDuckBot',
            'Baiduspider',
            'YandexBot',
            'facebookexternalhit',
            'twitterbot',
            'linkedinbot',
            'whatsapp'
        );
        
        foreach ($bots as $bot) {
            if (stripos($user_agent, $bot) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    private function handle_facebook_clocker($link) {
        // For Facebook bots, serve a different page or redirect
        $clocker_url = get_option('slc_facebook_clocker_url', home_url());
        
        if (empty($clocker_url) || $clocker_url === home_url()) {
            // Serve a simple HTML page for Facebook
            $this->serve_facebook_clocker_page($link);
        } else {
            // Redirect to the specified clocker URL
            $this->redirect_to_url($clocker_url, 301);
        }
    }
    
    private function serve_facebook_clocker_page($link) {
        $page_title = $link->title ?: 'Link Redirection';
        $site_name = get_bloginfo('name');
        
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title><?php echo esc_html($page_title); ?></title>
            <meta name="description" content="<?php echo esc_attr($page_title); ?>">
            <meta property="og:title" content="<?php echo esc_attr($page_title); ?>">
            <meta property="og:description" content="<?php echo esc_attr($page_title); ?>">
            <meta property="og:type" content="website">
            <meta property="og:url" content="<?php echo esc_url(home_url()); ?>">
            <meta property="og:site_name" content="<?php echo esc_attr($site_name); ?>">
            <meta name="robots" content="noindex, nofollow">
        </head>
        <body>
            <h1><?php echo esc_html($page_title); ?></h1>
            <p>Redirecionando...</p>
            <script>
                setTimeout(function() {
                    window.location.href = '<?php echo esc_js($link->target_url); ?>';
                }, 1000);
            </script>
        </body>
        </html>
        <?php
        exit;
    }
    
    private function serve_cloaked_content($link) {
        // Get the target page content
        $content = $this->fetch_target_content($link->target_url);
        
        if (!$content) {
            // If we can't fetch content, fallback to redirect
            $this->redirect_to_target($link);
            return;
        }
        
        // Process the content to fix URLs and add tracking
        $processed_content = $this->process_cloaked_content($content, $link);
        
        // Serve the processed content
        echo $processed_content;
        exit;
    }
    
    private function fetch_target_content($url) {
        // Use the same method as the page cloner
        $page_cloner = new SLC_Page_Cloner();
        
        // We need to make the fetch method public or create a separate method
        if (function_exists('curl_init')) {
            return $this->fetch_with_curl($url);
        }
        
        return $this->fetch_with_file_get_contents($url);
    }
    
    private function fetch_with_curl($url) {
        $ch = curl_init();
        
        curl_setopt_array($ch, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HTTPHEADER => array(
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language: pt-BR,pt;q=0.8,en;q=0.6',
            ),
        ));
        
        $content = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        if ($error || $http_code >= 400) {
            return false;
        }
        
        return $content;
    }
    
    private function fetch_with_file_get_contents($url) {
        $context = stream_context_create(array(
            'http' => array(
                'method' => 'GET',
                'header' => 'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'timeout' => 15,
                'follow_location' => true,
                'max_redirects' => 5,
            ),
        ));
        
        return file_get_contents($url, false, $context);
    }
    
    private function process_cloaked_content($content, $link) {
        // Basic processing to ensure the content works when served from our domain
        
        // Replace relative URLs with absolute ones
        $base_url = parse_url($link->target_url, PHP_URL_SCHEME) . '://' . parse_url($link->target_url, PHP_URL_HOST);
        
        // Fix relative links
        $content = preg_replace('/href="\/([^"]*)"/', 'href="' . $base_url . '/$1"', $content);
        $content = preg_replace('/src="\/([^"]*)"/', 'src="' . $base_url . '/$1"', $content);
        
        // Add our tracking scripts if needed
        if (get_option('slc_enable_analytics', 1)) {
            $tracking_script = $this->get_tracking_script($link);
            $content = str_replace('</body>', $tracking_script . '</body>', $content);
        }
        
        return $content;
    }
    
    private function get_tracking_script($link) {
        $nonce = wp_create_nonce('slc_track_click');
        $ajax_url = admin_url('admin-ajax.php');
        
        return "
        <script>
        // Super Links Clone tracking
        if (typeof jQuery !== 'undefined') {
            jQuery.post('{$ajax_url}', {
                action: 'slc_track_additional_event',
                link_id: {$link->id},
                event_type: 'page_view',
                nonce: '{$nonce}'
            });
        }
        </script>
        ";
    }
    
    private function redirect_to_target($link) {
        $redirect_type = $link->redirect_type ?: '301';
        $this->redirect_to_url($link->target_url, intval($redirect_type));
    }
    
    private function redirect_to_url($url, $status_code = 301) {
        // Ensure we have a valid URL
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            wp_die(__('URL de destino inválida.', 'super-links-clone'));
        }
        
        // Set the status code
        status_header($status_code);
        
        // Set the location header
        header('Location: ' . $url, true, $status_code);
        
        // Additional headers for better caching/SEO
        if ($status_code === 301) {
            header('Cache-Control: public, max-age=31536000'); // 1 year for permanent redirects
        } else {
            header('Cache-Control: no-cache, must-revalidate'); // No cache for temporary redirects
        }
        
        exit;
    }
    
    public function handle_exit_redirect() {
        // This function would be called when someone tries to leave the site
        $exit_redirect_url = get_option('slc_exit_redirect_url', '');
        
        if (!empty($exit_redirect_url) && get_option('slc_enable_exit_redirect', 0)) {
            $this->redirect_to_url($exit_redirect_url, 302);
        }
    }
    
    public function add_exit_redirect_script() {
        if (!get_option('slc_enable_exit_redirect', 0)) {
            return;
        }
        
        $exit_url = get_option('slc_exit_redirect_url', '');
        if (empty($exit_url)) {
            return;
        }
        
        ?>
        <script>
        (function() {
            var exitRedirectUrl = '<?php echo esc_js($exit_url); ?>';
            var isExiting = false;
            
            document.addEventListener('mouseleave', function(e) {
                if (e.clientY <= 0 && !isExiting) {
                    isExiting = true;
                    if (confirm('Aguarde! Temos uma oferta especial para você. Deseja ver?')) {
                        window.location.href = exitRedirectUrl;
                    }
                }
            });
            
            window.addEventListener('beforeunload', function(e) {
                if (!isExiting && Math.random() < 0.3) { // 30% chance
                    var confirmationMessage = 'Tem certeza que deseja sair? Temos conteúdo exclusivo esperando por você!';
                    e.returnValue = confirmationMessage;
                    return confirmationMessage;
                }
            });
        })();
        </script>
        <?php
    }
    
    public function check_affiliate_cookies() {
        // Check if user has affiliate cookies and take appropriate action
        if (isset($_COOKIE['slc_last_affiliate'])) {
            $affiliate_link_id = intval($_COOKIE['slc_last_affiliate']);
            
            // You could trigger additional actions here
            // Like showing specific content or tracking conversions
            
            do_action('slc_affiliate_cookie_detected', $affiliate_link_id);
        }
    }
    
    public function get_cloaked_content_for_ajax() {
        check_ajax_referer('slc_nonce', 'nonce');
        
        $link_id = intval($_POST['link_id']);
        $link_manager = new SLC_Link_Manager();
        $link = $link_manager->get_link_by_id($link_id);
        
        if (!$link || !$link->cloaked) {
            wp_die(__('Link não encontrado ou não configurado para camuflagem.', 'super-links-clone'));
        }
        
        $content = $this->fetch_target_content($link->target_url);
        
        if ($content) {
            $processed_content = $this->process_cloaked_content($content, $link);
            wp_send_json_success(array('content' => $processed_content));
        } else {
            wp_send_json_error(array('message' => __('Não foi possível carregar o conteúdo.', 'super-links-clone')));
        }
    }
}