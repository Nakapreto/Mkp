<?php

if (!defined('ABSPATH')) {
    exit;
}

class SLC_Page_Cloner {
    
    private $max_execution_time = 60;
    private $user_agent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36';
    
    public function __construct() {
        // Set max execution time for cloning operations
        if (!ini_get('safe_mode')) {
            set_time_limit($this->max_execution_time);
        }
    }
    
    public function clone_page($url) {
        // Validate URL
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return array(
                'success' => false,
                'message' => __('URL inválida fornecida.', 'super-links-clone')
            );
        }
        
        // Follow redirects to get final URL
        $final_url = $this->follow_redirects($url);
        if (!$final_url) {
            return array(
                'success' => false,
                'message' => __('Não foi possível acessar a URL fornecida.', 'super-links-clone')
            );
        }
        
        // Get page content
        $content = $this->fetch_page_content($final_url);
        if (!$content) {
            return array(
                'success' => false,
                'message' => __('Não foi possível baixar o conteúdo da página.', 'super-links-clone')
            );
        }
        
        // Parse and process content
        $processed_content = $this->process_content($content, $final_url);
        
        // Extract title
        $title = $this->extract_title($content);
        if (empty($title)) {
            $title = 'Página Clonada - ' . date('Y-m-d H:i:s');
        }
        
        // Create WordPress post
        $post_id = $this->create_wordpress_post($title, $processed_content);
        
        if (!$post_id) {
            return array(
                'success' => false,
                'message' => __('Erro ao criar a página no WordPress.', 'super-links-clone')
            );
        }
        
        // Save cloned page record
        $this->save_cloned_page_record($title, $url, $post_id, $processed_content);
        
        return array(
            'success' => true,
            'message' => __('Página clonada com sucesso!', 'super-links-clone'),
            'post_id' => $post_id,
            'post_url' => get_permalink($post_id),
            'title' => $title
        );
    }
    
    private function follow_redirects($url, $max_redirects = 5) {
        $redirect_count = 0;
        
        while ($redirect_count < $max_redirects) {
            $headers = get_headers($url, 1);
            
            if (!$headers) {
                return false;
            }
            
            $status_code = $this->extract_status_code($headers[0]);
            
            // If it's a redirect
            if (in_array($status_code, array(301, 302, 303, 307, 308))) {
                if (isset($headers['Location'])) {
                    $location = is_array($headers['Location']) ? end($headers['Location']) : $headers['Location'];
                    
                    // Handle relative URLs
                    if (strpos($location, 'http') !== 0) {
                        $parsed_url = parse_url($url);
                        $base_url = $parsed_url['scheme'] . '://' . $parsed_url['host'];
                        $location = $base_url . '/' . ltrim($location, '/');
                    }
                    
                    $url = $location;
                    $redirect_count++;
                } else {
                    break;
                }
            } else {
                break;
            }
        }
        
        return $url;
    }
    
    private function extract_status_code($status_line) {
        preg_match('/\d{3}/', $status_line, $matches);
        return isset($matches[0]) ? intval($matches[0]) : 200;
    }
    
    private function fetch_page_content($url) {
        // Try cURL first
        if (function_exists('curl_init')) {
            return $this->fetch_with_curl($url);
        }
        
        // Fallback to file_get_contents with context
        return $this->fetch_with_file_get_contents($url);
    }
    
    private function fetch_with_curl($url) {
        $ch = curl_init();
        
        curl_setopt_array($ch, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_USERAGENT => $this->user_agent,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HTTPHEADER => array(
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Language: pt-BR,pt;q=0.8,en;q=0.6',
                'Accept-Encoding: gzip, deflate',
                'Connection: keep-alive',
                'Upgrade-Insecure-Requests: 1',
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
                'header' => array(
                    'User-Agent: ' . $this->user_agent,
                    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'Accept-Language: pt-BR,pt;q=0.8,en;q=0.6',
                ),
                'timeout' => 30,
                'follow_location' => true,
                'max_redirects' => 5,
            ),
        ));
        
        return file_get_contents($url, false, $context);
    }
    
    private function process_content($content, $base_url) {
        // Parse HTML
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">' . $content);
        libxml_clear_errors();
        
        // Process images
        $this->process_images($dom, $base_url);
        
        // Process CSS files
        $this->process_css_files($dom, $base_url);
        
        // Process JavaScript files
        $this->process_js_files($dom, $base_url);
        
        // Process inline styles
        $this->process_inline_styles($dom, $base_url);
        
        // Fix relative URLs
        $this->fix_relative_urls($dom, $base_url);
        
        // Remove scripts that might interfere
        $this->remove_problematic_scripts($dom);
        
        // Get body content only
        $body = $dom->getElementsByTagName('body')->item(0);
        if ($body) {
            $innerHTML = '';
            foreach ($body->childNodes as $child) {
                $innerHTML .= $dom->saveHTML($child);
            }
            return $innerHTML;
        }
        
        return $dom->saveHTML();
    }
    
    private function process_images($dom, $base_url) {
        $images = $dom->getElementsByTagName('img');
        
        foreach ($images as $img) {
            $src = $img->getAttribute('src');
            
            if (empty($src) || strpos($src, 'data:') === 0) {
                continue;
            }
            
            // Convert relative URLs to absolute
            if (strpos($src, 'http') !== 0) {
                $src = $this->make_absolute_url($src, $base_url);
            }
            
            // Download and save image
            $new_url = $this->download_and_save_image($src);
            if ($new_url) {
                $img->setAttribute('src', $new_url);
            }
        }
    }
    
    private function process_css_files($dom, $base_url) {
        $links = $dom->getElementsByTagName('link');
        
        foreach ($links as $link) {
            if ($link->getAttribute('rel') === 'stylesheet') {
                $href = $link->getAttribute('href');
                
                if (!empty($href) && strpos($href, 'http') !== 0) {
                    $href = $this->make_absolute_url($href, $base_url);
                    $link->setAttribute('href', $href);
                }
            }
        }
    }
    
    private function process_js_files($dom, $base_url) {
        $scripts = $dom->getElementsByTagName('script');
        
        foreach ($scripts as $script) {
            $src = $script->getAttribute('src');
            
            if (!empty($src) && strpos($src, 'http') !== 0) {
                $src = $this->make_absolute_url($src, $base_url);
                $script->setAttribute('src', $src);
            }
        }
    }
    
    private function process_inline_styles($dom, $base_url) {
        $xpath = new DOMXPath($dom);
        $elements = $xpath->query('//*[@style]');
        
        foreach ($elements as $element) {
            $style = $element->getAttribute('style');
            $style = $this->fix_css_urls($style, $base_url);
            $element->setAttribute('style', $style);
        }
    }
    
    private function fix_relative_urls($dom, $base_url) {
        $xpath = new DOMXPath($dom);
        
        // Fix href attributes
        $links = $xpath->query('//a[@href]');
        foreach ($links as $link) {
            $href = $link->getAttribute('href');
            if (!empty($href) && strpos($href, 'http') !== 0 && strpos($href, '#') !== 0 && strpos($href, 'mailto:') !== 0) {
                $href = $this->make_absolute_url($href, $base_url);
                $link->setAttribute('href', $href);
            }
        }
        
        // Fix form actions
        $forms = $xpath->query('//form[@action]');
        foreach ($forms as $form) {
            $action = $form->getAttribute('action');
            if (!empty($action) && strpos($action, 'http') !== 0) {
                $action = $this->make_absolute_url($action, $base_url);
                $form->setAttribute('action', $action);
            }
        }
    }
    
    private function remove_problematic_scripts($dom) {
        $scripts = $dom->getElementsByTagName('script');
        $scripts_to_remove = array();
        
        foreach ($scripts as $script) {
            $src = $script->getAttribute('src');
            $content = $script->nodeValue;
            
            // Remove analytics, tracking, and other problematic scripts
            if (
                strpos($src, 'google-analytics') !== false ||
                strpos($src, 'googletagmanager') !== false ||
                strpos($src, 'facebook.net') !== false ||
                strpos($src, 'hotjar') !== false ||
                strpos($content, 'gtag') !== false ||
                strpos($content, 'analytics') !== false
            ) {
                $scripts_to_remove[] = $script;
            }
        }
        
        foreach ($scripts_to_remove as $script) {
            $script->parentNode->removeChild($script);
        }
    }
    
    private function make_absolute_url($relative_url, $base_url) {
        if (strpos($relative_url, '//') === 0) {
            $parsed_base = parse_url($base_url);
            return $parsed_base['scheme'] . ':' . $relative_url;
        }
        
        if (strpos($relative_url, '/') === 0) {
            $parsed_base = parse_url($base_url);
            return $parsed_base['scheme'] . '://' . $parsed_base['host'] . $relative_url;
        }
        
        return rtrim($base_url, '/') . '/' . ltrim($relative_url, '/');
    }
    
    private function fix_css_urls($css, $base_url) {
        return preg_replace_callback('/url\(["\']?([^"\']+)["\']?\)/', function($matches) use ($base_url) {
            $url = $matches[1];
            if (strpos($url, 'http') !== 0 && strpos($url, 'data:') !== 0) {
                $url = $this->make_absolute_url($url, $base_url);
            }
            return 'url("' . $url . '")';
        }, $css);
    }
    
    private function download_and_save_image($image_url) {
        // For this demo, we'll just return the original URL
        // In a production version, you'd want to download and save to WordPress media library
        return $image_url;
        
        /*
        // Production code would look like this:
        $image_data = wp_remote_get($image_url);
        if (is_wp_error($image_data) || wp_remote_retrieve_response_code($image_data) !== 200) {
            return false;
        }
        
        $image_content = wp_remote_retrieve_body($image_data);
        $filename = basename(parse_url($image_url, PHP_URL_PATH));
        
        $upload = wp_upload_bits($filename, null, $image_content);
        if (!$upload['error']) {
            return $upload['url'];
        }
        
        return false;
        */
    }
    
    private function extract_title($content) {
        if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $content, $matches)) {
            return html_entity_decode(strip_tags($matches[1]), ENT_QUOTES, 'UTF-8');
        }
        
        return '';
    }
    
    private function create_wordpress_post($title, $content) {
        $post_data = array(
            'post_title' => $title,
            'post_content' => $content,
            'post_status' => 'draft',
            'post_type' => 'page',
            'post_author' => get_current_user_id(),
            'meta_input' => array(
                '_slc_cloned_page' => true,
                '_slc_cloned_at' => current_time('mysql')
            )
        );
        
        return wp_insert_post($post_data);
    }
    
    private function save_cloned_page_record($title, $original_url, $post_id, $content) {
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'slc_cloned_pages',
            array(
                'title' => $title,
                'original_url' => $original_url,
                'post_id' => $post_id,
                'content' => $content,
                'created_by' => get_current_user_id(),
                'created_at' => current_time('mysql')
            ),
            array('%s', '%s', '%d', '%s', '%d', '%s')
        );
    }
    
    public function get_cloned_pages() {
        global $wpdb;
        
        return $wpdb->get_results("
            SELECT * FROM {$wpdb->prefix}slc_cloned_pages 
            WHERE status = 'active' 
            ORDER BY created_at DESC
        ");
    }
    
    public function delete_cloned_page($page_id) {
        global $wpdb;
        
        // Get the cloned page record
        $cloned_page = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}slc_cloned_pages WHERE id = %d",
            $page_id
        ));
        
        if (!$cloned_page) {
            return array(
                'success' => false,
                'message' => __('Página clonada não encontrada.', 'super-links-clone')
            );
        }
        
        // Delete the WordPress post if it exists
        if ($cloned_page->post_id) {
            wp_delete_post($cloned_page->post_id, true);
        }
        
        // Update the record status
        $result = $wpdb->update(
            $wpdb->prefix . 'slc_cloned_pages',
            array('status' => 'deleted'),
            array('id' => $page_id),
            array('%s'),
            array('%d')
        );
        
        if ($result === false) {
            return array(
                'success' => false,
                'message' => __('Erro ao deletar a página clonada.', 'super-links-clone')
            );
        }
        
        return array(
            'success' => true,
            'message' => __('Página clonada deletada com sucesso!', 'super-links-clone')
        );
    }
    
    public function clone_page_ajax() {
        check_ajax_referer('slc_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'super-links-clone'));
        }
        
        $url = sanitize_url($_POST['url']);
        $result = $this->clone_page($url);
        
        wp_send_json($result);
    }
}