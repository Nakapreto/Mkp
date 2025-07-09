<?php
/**
 * Site Cloner Assets Class
 *
 * @package Site_Cloner
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Site_Cloner_Assets {
    
    /**
     * Media handler
     *
     * @var Site_Cloner_Media
     */
    private $media;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->media = new Site_Cloner_Media();
    }
    
    /**
     * Process images
     */
    public function process_images($dom, $base_url, $job_id) {
        $img_elements = $dom->getElementsByTagName('img');
        
        foreach ($img_elements as $img) {
            $src = $img->getAttribute('src');
            if (!empty($src)) {
                $new_url = $this->download_and_replace_asset($src, $base_url, 'image', $job_id);
                if ($new_url) {
                    $img->setAttribute('src', $new_url);
                }
            }
            
            // Handle srcset attribute
            $srcset = $img->getAttribute('srcset');
            if (!empty($srcset)) {
                $new_srcset = $this->process_srcset($srcset, $base_url, $job_id);
                if ($new_srcset) {
                    $img->setAttribute('srcset', $new_srcset);
                }
            }
        }
        
        // Process background images in style attributes
        $this->process_inline_background_images($dom, $base_url, $job_id);
    }
    
    /**
     * Process videos
     */
    public function process_videos($dom, $base_url, $job_id) {
        // Process video elements
        $video_elements = $dom->getElementsByTagName('video');
        
        foreach ($video_elements as $video) {
            $src = $video->getAttribute('src');
            if (!empty($src)) {
                if (!$this->is_external_video_platform($src)) {
                    $new_url = $this->download_and_replace_asset($src, $base_url, 'video', $job_id);
                    if ($new_url) {
                        $video->setAttribute('src', $new_url);
                    }
                }
            }
            
            // Process source elements within video
            $source_elements = $video->getElementsByTagName('source');
            foreach ($source_elements as $source) {
                $src = $source->getAttribute('src');
                if (!empty($src) && !$this->is_external_video_platform($src)) {
                    $new_url = $this->download_and_replace_asset($src, $base_url, 'video', $job_id);
                    if ($new_url) {
                        $source->setAttribute('src', $new_url);
                    }
                }
            }
        }
        
        // Process iframe embeds (YouTube, Vimeo, etc.)
        $this->process_video_embeds($dom);
    }
    
    /**
     * Process CSS files
     */
    public function process_css($dom, $base_url, $job_id) {
        $link_elements = $dom->getElementsByTagName('link');
        
        foreach ($link_elements as $link) {
            $rel = $link->getAttribute('rel');
            $href = $link->getAttribute('href');
            
            if ($rel === 'stylesheet' && !empty($href)) {
                $new_url = $this->download_and_replace_asset($href, $base_url, 'css', $job_id);
                if ($new_url) {
                    $link->setAttribute('href', $new_url);
                }
            }
        }
        
        // Process inline CSS
        $style_elements = $dom->getElementsByTagName('style');
        foreach ($style_elements as $style) {
            $css_content = $style->textContent;
            $processed_css = $this->process_css_content($css_content, $base_url, $job_id);
            $style->textContent = $processed_css;
        }
        
        // Process style attributes
        $this->process_inline_styles($dom, $base_url, $job_id);
    }
    
    /**
     * Process fonts
     */
    public function process_fonts($dom, $base_url, $job_id) {
        // Process @font-face rules in CSS
        $link_elements = $dom->getElementsByTagName('link');
        
        foreach ($link_elements as $link) {
            $href = $link->getAttribute('href');
            
            // Check if it's a font file or Google Fonts
            if ($this->is_font_url($href)) {
                if (strpos($href, 'fonts.googleapis.com') !== false || strpos($href, 'fonts.gstatic.com') !== false) {
                    // Keep Google Fonts as external links
                    continue;
                } else {
                    // Download other font files
                    $new_url = $this->download_and_replace_asset($href, $base_url, 'font', $job_id);
                    if ($new_url) {
                        $link->setAttribute('href', $new_url);
                    }
                }
            }
        }
    }
    
    /**
     * Process JavaScript files
     */
    public function process_javascript($dom, $base_url, $job_id) {
        $script_elements = $dom->getElementsByTagName('script');
        
        foreach ($script_elements as $script) {
            $src = $script->getAttribute('src');
            
            if (!empty($src)) {
                // Skip external libraries (jQuery, Google Analytics, etc.)
                if ($this->should_skip_js($src)) {
                    continue;
                }
                
                $new_url = $this->download_and_replace_asset($src, $base_url, 'js', $job_id);
                if ($new_url) {
                    $script->setAttribute('src', $new_url);
                }
            }
        }
    }
    
    /**
     * Download and replace asset
     */
    private function download_and_replace_asset($url, $base_url, $type, $job_id) {
        try {
            $absolute_url = $this->make_absolute_url($url, $base_url);
            
            if (!$this->is_valid_url($absolute_url)) {
                return false;
            }
            
            // Check file size before downloading
            if (!$this->check_file_size($absolute_url)) {
                return false;
            }
            
            // Download the file
            $file_data = $this->download_file($absolute_url);
            if (!$file_data) {
                return false;
            }
            
            // Generate filename
            $filename = $this->generate_filename($absolute_url, $type);
            
            // Save to media library
            $attachment_id = $this->media->save_to_media_library($file_data, $filename, $type);
            
            if ($attachment_id) {
                $new_url = wp_get_attachment_url($attachment_id);
                
                // Log the asset download
                $this->log_asset_download($job_id, $absolute_url, $new_url, $type);
                
                return $new_url;
            }
            
        } catch (Exception $e) {
            error_log("Site Cloner: Error downloading asset $url: " . $e->getMessage());
        }
        
        return false;
    }
    
    /**
     * Process srcset attribute
     */
    private function process_srcset($srcset, $base_url, $job_id) {
        $srcset_parts = explode(',', $srcset);
        $new_srcset_parts = array();
        
        foreach ($srcset_parts as $part) {
            $part = trim($part);
            if (preg_match('/^(.+?)\s+(.+)$/', $part, $matches)) {
                $url = trim($matches[1]);
                $descriptor = trim($matches[2]);
                
                $new_url = $this->download_and_replace_asset($url, $base_url, 'image', $job_id);
                if ($new_url) {
                    $new_srcset_parts[] = $new_url . ' ' . $descriptor;
                } else {
                    $new_srcset_parts[] = $part;
                }
            } else {
                $new_srcset_parts[] = $part;
            }
        }
        
        return implode(', ', $new_srcset_parts);
    }
    
    /**
     * Process inline background images
     */
    private function process_inline_background_images($dom, $base_url, $job_id) {
        $xpath = new DOMXPath($dom);
        $elements = $xpath->query('//*[@style]');
        
        foreach ($elements as $element) {
            $style = $element->getAttribute('style');
            $new_style = $this->process_css_content($style, $base_url, $job_id);
            $element->setAttribute('style', $new_style);
        }
    }
    
    /**
     * Process inline styles
     */
    private function process_inline_styles($dom, $base_url, $job_id) {
        $xpath = new DOMXPath($dom);
        $elements = $xpath->query('//*[@style]');
        
        foreach ($elements as $element) {
            $style = $element->getAttribute('style');
            $new_style = $this->process_css_content($style, $base_url, $job_id);
            $element->setAttribute('style', $new_style);
        }
    }
    
    /**
     * Process CSS content
     */
    private function process_css_content($css, $base_url, $job_id) {
        // Process url() references in CSS
        $css = preg_replace_callback(
            '/url\(["\']?([^"\')]+)["\']?\)/',
            function($matches) use ($base_url, $job_id) {
                $url = $matches[1];
                $new_url = $this->download_and_replace_asset($url, $base_url, 'css-asset', $job_id);
                return $new_url ? "url('$new_url')" : $matches[0];
            },
            $css
        );
        
        return $css;
    }
    
    /**
     * Process video embeds
     */
    private function process_video_embeds($dom) {
        $iframe_elements = $dom->getElementsByTagName('iframe');
        
        foreach ($iframe_elements as $iframe) {
            $src = $iframe->getAttribute('src');
            
            if ($this->is_external_video_platform($src)) {
                // Keep external video embeds as-is
                continue;
            }
        }
    }
    
    /**
     * Make URL absolute
     */
    private function make_absolute_url($url, $base_url) {
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            return $url;
        }
        
        $parsed_base = parse_url($base_url);
        
        if (strpos($url, '//') === 0) {
            return $parsed_base['scheme'] . ':' . $url;
        }
        
        if (strpos($url, '/') === 0) {
            return $parsed_base['scheme'] . '://' . $parsed_base['host'] . $url;
        }
        
        return rtrim($base_url, '/') . '/' . ltrim($url, '/');
    }
    
    /**
     * Check if URL is valid
     */
    private function is_valid_url($url) {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
    
    /**
     * Get effective settings (network settings override site settings)
     */
    private function get_effective_settings() {
        $defaults = array(
            'max_execution_time' => 300,
            'memory_limit' => '512M',
            'download_timeout' => 60,
            'ssl_verify' => 0,
            'follow_redirects' => 1,
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            'max_file_size' => 50
        );
        
        $site_settings = get_option('site_cloner_settings', array());
        
        if (is_multisite()) {
            $network_settings = get_site_option('site_cloner_network_settings', array());
            // Network settings override site settings
            $settings = wp_parse_args($network_settings, $site_settings);
        } else {
            $settings = $site_settings;
        }
        
        return wp_parse_args($settings, $defaults);
    }
    
    /**
     * Check file size before downloading
     */
    private function check_file_size($url) {
        $settings = $this->get_effective_settings();
        $max_size = $settings['max_file_size'] * 1024 * 1024; // Convert MB to bytes
        
        $response = wp_remote_head($url, array(
            'timeout' => 30,
            'user-agent' => $settings['user_agent'],
            'sslverify' => $settings['ssl_verify']
        ));
        
        if (!is_wp_error($response)) {
            $content_length = wp_remote_retrieve_header($response, 'content-length');
            if ($content_length) {
                return intval($content_length) <= $max_size;
            }
        }
        
        return true; // If we can't determine size, proceed
    }
    
    /**
     * Download file
     */
    private function download_file($url) {
        $settings = $this->get_effective_settings();
        
        $args = array(
            'timeout' => $settings['download_timeout'],
            'user-agent' => $settings['user_agent'],
            'sslverify' => $settings['ssl_verify'],
            'headers' => array(
                'Accept' => '*/*',
                'Accept-Language' => 'pt-BR,pt;q=0.9,en;q=0.8',
                'Accept-Encoding' => 'gzip, deflate, br',
                'Connection' => 'keep-alive',
                'Cache-Control' => 'max-age=0'
            ),
            'redirection' => $settings['follow_redirects'] ? 5 : 0
        );
        
        $response = wp_remote_get($url, $args);
        
        if (is_wp_error($response)) {
            throw new Exception('Download failed: ' . $response->get_error_message());
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code !== 200) {
            throw new Exception("HTTP error: $status_code");
        }
        
        return wp_remote_retrieve_body($response);
    }
    
    /**
     * Generate filename
     */
    private function generate_filename($url, $type) {
        $parsed_url = parse_url($url);
        $path = $parsed_url['path'];
        $filename = basename($path);
        
        // Remove query parameters from filename
        $filename = preg_replace('/\?.*$/', '', $filename);
        
        // If no extension, add based on type
        if (!pathinfo($filename, PATHINFO_EXTENSION)) {
            $extensions = array(
                'image' => 'jpg',
                'video' => 'mp4',
                'css' => 'css',
                'js' => 'js',
                'font' => 'woff',
                'css-asset' => 'png'
            );
            
            $ext = isset($extensions[$type]) ? $extensions[$type] : 'bin';
            $filename .= '.' . $ext;
        }
        
        // Sanitize filename
        $filename = sanitize_file_name($filename);
        
        // Add timestamp to avoid conflicts
        $info = pathinfo($filename);
        $filename = $info['filename'] . '-' . time() . '.' . $info['extension'];
        
        return $filename;
    }
    
    /**
     * Check if URL is external video platform
     */
    private function is_external_video_platform($url) {
        $platforms = array(
            'youtube.com',
            'youtu.be',
            'vimeo.com',
            'dailymotion.com',
            'wistia.com',
            'vids.io'
        );
        
        foreach ($platforms as $platform) {
            if (strpos($url, $platform) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if URL is a font
     */
    private function is_font_url($url) {
        $font_extensions = array('woff', 'woff2', 'ttf', 'otf', 'eot');
        $extension = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION);
        
        return in_array(strtolower($extension), $font_extensions) || 
               strpos($url, 'fonts.googleapis.com') !== false ||
               strpos($url, 'fonts.gstatic.com') !== false;
    }
    
    /**
     * Check if JavaScript should be skipped
     */
    private function should_skip_js($url) {
        $skip_patterns = array(
            'google-analytics.com',
            'googletagmanager.com',
            'facebook.net',
            'doubleclick.net',
            'googlesyndication.com',
            'jquery.min.js',
            'jquery.js'
        );
        
        foreach ($skip_patterns as $pattern) {
            if (strpos($url, $pattern) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Log asset download
     */
    private function log_asset_download($job_id, $original_url, $new_url, $type) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'site_cloner_assets';
        
        // Create table if it doesn't exist
        $this->create_assets_table();
        
        $wpdb->insert(
            $table_name,
            array(
                'job_id' => $job_id,
                'original_url' => $original_url,
                'new_url' => $new_url,
                'asset_type' => $type,
                'created_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s', '%s')
        );
    }
    
    /**
     * Create assets table
     */
    private function create_assets_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'site_cloner_assets';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            job_id mediumint(9) NOT NULL,
            original_url varchar(500) NOT NULL,
            new_url varchar(500) NOT NULL,
            asset_type varchar(50) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY job_id (job_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Add assets to ZIP export
     */
    public function add_to_zip($zip, $page_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'site_cloner_assets';
        
        $assets = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE job_id IN (SELECT id FROM {$wpdb->prefix}site_cloner_jobs WHERE id = (SELECT _site_cloner_job_id FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = '_site_cloner_job_id'))",
            $page_id
        ));
        
        foreach ($assets as $asset) {
            $file_path = str_replace(wp_get_upload_dir()['baseurl'], wp_get_upload_dir()['basedir'], $asset->new_url);
            
            if (file_exists($file_path)) {
                $zip_path = 'assets/' . basename($file_path);
                $zip->addFile($file_path, $zip_path);
            }
        }
    }
}