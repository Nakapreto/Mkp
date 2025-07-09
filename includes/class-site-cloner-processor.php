<?php
/**
 * Site Cloner Processor Class
 *
 * @package Site_Cloner
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Site_Cloner_Processor {
    
    /**
     * Job ID being processed
     *
     * @var int
     */
    private $job_id;
    
    /**
     * Job data
     *
     * @var object
     */
    private $job;
    
    /**
     * Log array
     *
     * @var array
     */
    private $log;
    
    /**
     * Assets handler
     *
     * @var Site_Cloner_Assets
     */
    private $assets;
    
    /**
     * Media handler
     *
     * @var Site_Cloner_Media
     */
    private $media;
    
    /**
     * Elementor handler
     *
     * @var Site_Cloner_Elementor
     */
    private $elementor;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->assets = new Site_Cloner_Assets();
        $this->media = new Site_Cloner_Media();
        $this->elementor = new Site_Cloner_Elementor();
    }
    
    /**
     * Process a cloning job
     */
    public function process_job($job_id) {
        $this->job_id = $job_id;
        
        // Get job from database
        global $wpdb;
        $table_name = $wpdb->prefix . 'site_cloner_jobs';
        
        $this->job = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $job_id
        ));
        
        if (!$this->job) {
            return false;
        }
        
        $this->log = json_decode($this->job->log, true);
        
        try {
            $this->update_status('processing', 5);
            $this->log_message('Iniciando processo de clonagem');
            
            // Set memory and execution limits
            $this->set_limits();
            
            // Resolve final URL (handle redirects)
            $final_url = $this->resolve_url($this->job->url);
            $this->log_message("URL final resolvida: $final_url");
            $this->update_progress(10);
            
            // Fetch page content
            $content = $this->fetch_page_content($final_url);
            if (!$content) {
                throw new Exception('Não foi possível obter o conteúdo da página');
            }
            $this->log_message('Conteúdo da página obtido');
            $this->update_progress(20);
            
            // Parse HTML
            $dom = $this->parse_html($content);
            $this->log_message('HTML analisado');
            $this->update_progress(25);
            
            // Extract page title
            $page_title = $this->extract_title($dom);
            if (empty($this->log['page_title'])) {
                $this->log['page_title'] = $page_title;
            }
            $this->log_message("Título extraído: $page_title");
            $this->update_progress(30);
            
            // Check if it's an Elementor site
            $is_elementor = $this->elementor->detect_elementor($content);
            $this->log_message($is_elementor ? 'Site Elementor detectado' : 'Site padrão detectado');
            $this->update_progress(35);
            
            // Process and download assets
            $processed_content = $this->process_assets($dom, $final_url);
            $this->log_message('Assets processados');
            $this->update_progress(70);
            
            // Convert internal links
            $processed_content = $this->convert_internal_links($processed_content, $final_url);
            $this->log_message('Links internos convertidos');
            $this->update_progress(80);
            
            // Create WordPress page
            $page_id = $this->create_wordpress_page($processed_content, $is_elementor);
            $this->log_message("Página WordPress criada (ID: $page_id)");
            $this->update_progress(90);
            
            // Generate export ZIP
            $zip_path = $this->generate_export_zip($page_id, $processed_content);
            $this->log_message("ZIP de exportação gerado: $zip_path");
            $this->update_progress(95);
            
            $this->update_status('completed', 100);
            $this->log_message('Clonagem concluída com sucesso');
            
            return $page_id;
            
        } catch (Exception $e) {
            $this->log_message('Erro: ' . $e->getMessage(), 'error');
            $this->update_status('failed', $this->job->progress);
            return false;
        }
    }
    
    /**
     * Import from ZIP file
     */
    public function import_from_zip($extract_dir, $page_title, $page_status) {
        try {
            // Look for content.html file
            $content_file = $extract_dir . 'content.html';
            if (!file_exists($content_file)) {
                throw new Exception('Arquivo content.html não encontrado no ZIP');
            }
            
            $content = file_get_contents($content_file);
            if (!$content) {
                throw new Exception('Não foi possível ler o conteúdo do arquivo');
            }
            
            // Look for assets directory
            $assets_dir = $extract_dir . 'assets/';
            if (is_dir($assets_dir)) {
                // Import assets to media library
                $this->import_assets_from_zip($assets_dir);
            }
            
            // Check for Elementor data
            $elementor_file = $extract_dir . 'elementor.json';
            $is_elementor = file_exists($elementor_file);
            
            // Create WordPress page
            $page_id = wp_insert_post(array(
                'post_title' => $page_title,
                'post_content' => $content,
                'post_status' => $page_status,
                'post_type' => 'page',
                'meta_input' => array(
                    '_site_cloner_imported' => true,
                    '_site_cloner_import_date' => current_time('mysql')
                )
            ));
            
            if (is_wp_error($page_id)) {
                throw new Exception('Erro ao criar página: ' . $page_id->get_error_message());
            }
            
            // Configure Elementor if needed
            if ($is_elementor && $this->elementor->is_active()) {
                $elementor_data = json_decode(file_get_contents($elementor_file), true);
                $this->elementor->configure_page($page_id, $elementor_data);
            }
            
            return $page_id;
            
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    /**
     * Set memory and execution limits
     */
    private function set_limits() {
        $settings = get_option('site_cloner_settings', array());
        
        if (isset($settings['max_execution_time']) && $settings['max_execution_time'] > 0) {
            set_time_limit($settings['max_execution_time']);
        }
        
        if (isset($settings['memory_limit']) && !empty($settings['memory_limit'])) {
            ini_set('memory_limit', $settings['memory_limit']);
        }
    }
    
    /**
     * Resolve final URL handling redirects
     */
    private function resolve_url($url) {
        $max_redirects = 10;
        $redirect_count = 0;
        $current_url = $url;
        
        while ($redirect_count < $max_redirects) {
            $headers = get_headers($current_url, 1);
            
            if (!$headers) {
                break;
            }
            
            $status_code = intval(substr($headers[0], 9, 3));
            
            if ($status_code >= 300 && $status_code < 400) {
                $location = isset($headers['Location']) ? $headers['Location'] : null;
                
                if (is_array($location)) {
                    $location = end($location);
                }
                
                if ($location) {
                    if (!parse_url($location, PHP_URL_HOST)) {
                        // Relative URL
                        $parsed_url = parse_url($current_url);
                        $location = $parsed_url['scheme'] . '://' . $parsed_url['host'] . $location;
                    }
                    $current_url = $location;
                    $redirect_count++;
                } else {
                    break;
                }
            } else {
                break;
            }
        }
        
        return $current_url;
    }
    
    /**
     * Fetch page content
     */
    private function fetch_page_content($url) {
        $args = array(
            'timeout' => 60,
            'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'headers' => array(
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.5',
                'Accept-Encoding' => 'gzip, deflate',
                'Connection' => 'keep-alive',
            )
        );
        
        $response = wp_remote_get($url, $args);
        
        if (is_wp_error($response)) {
            throw new Exception('Erro ao buscar conteúdo: ' . $response->get_error_message());
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code !== 200) {
            throw new Exception("Erro HTTP: $status_code");
        }
        
        return wp_remote_retrieve_body($response);
    }
    
    /**
     * Parse HTML content
     */
    private function parse_html($content) {
        $dom = new DOMDocument();
        
        // Suppress warnings for malformed HTML
        libxml_use_internal_errors(true);
        
        // Set encoding to UTF-8
        $content = mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8');
        
        $dom->loadHTML($content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        
        libxml_clear_errors();
        
        return $dom;
    }
    
    /**
     * Extract page title
     */
    private function extract_title($dom) {
        $title_elements = $dom->getElementsByTagName('title');
        if ($title_elements->length > 0) {
            return trim($title_elements->item(0)->textContent);
        }
        
        // Fallback to h1
        $h1_elements = $dom->getElementsByTagName('h1');
        if ($h1_elements->length > 0) {
            return trim($h1_elements->item(0)->textContent);
        }
        
        return 'Página Clonada';
    }
    
    /**
     * Process and download assets
     */
    private function process_assets($dom, $base_url) {
        $options = $this->log['options'];
        
        // Process images
        if ($options['clone_images']) {
            $this->assets->process_images($dom, $base_url, $this->job_id);
            $this->update_progress($this->job->progress + 10);
        }
        
        // Process videos
        if ($options['clone_videos']) {
            $this->assets->process_videos($dom, $base_url, $this->job_id);
            $this->update_progress($this->job->progress + 10);
        }
        
        // Process CSS
        if ($options['clone_css']) {
            $this->assets->process_css($dom, $base_url, $this->job_id);
            $this->update_progress($this->job->progress + 10);
        }
        
        // Process fonts
        if ($options['clone_fonts']) {
            $this->assets->process_fonts($dom, $base_url, $this->job_id);
            $this->update_progress($this->job->progress + 5);
        }
        
        // Process JavaScript (optional)
        if ($options['clone_js']) {
            $this->assets->process_javascript($dom, $base_url, $this->job_id);
            $this->update_progress($this->job->progress + 5);
        }
        
        return $dom->saveHTML();
    }
    
    /**
     * Convert internal links
     */
    private function convert_internal_links($content, $base_url) {
        $parsed_base = parse_url($base_url);
        $base_domain = $parsed_base['host'];
        
        // Convert relative URLs to absolute
        $content = preg_replace_callback(
            '/(?:href|src)=["\'](\/[^"\']*)["\']/',
            function($matches) use ($parsed_base) {
                $relative_url = $matches[1];
                $absolute_url = $parsed_base['scheme'] . '://' . $parsed_base['host'] . $relative_url;
                return str_replace($matches[1], $absolute_url, $matches[0]);
            },
            $content
        );
        
        // Replace internal links with WordPress structure
        $site_url = get_site_url();
        $content = preg_replace_callback(
            '/(?:href)=["\'](https?:\/\/' . preg_quote($base_domain, '/') . '[^"\']*)["\']/',
            function($matches) use ($site_url) {
                // This is a simplified conversion - in real implementation,
                // you'd want to map specific pages to WordPress pages/posts
                return 'href="' . $site_url . '"';
            },
            $content
        );
        
        return $content;
    }
    
    /**
     * Create WordPress page
     */
    private function create_wordpress_page($content, $is_elementor = false) {
        $page_title = $this->log['page_title'];
        $page_status = $this->log['page_status'];
        
        $page_data = array(
            'post_title' => $page_title,
            'post_content' => $content,
            'post_status' => $page_status,
            'post_type' => 'page',
            'meta_input' => array(
                '_site_cloner_source_url' => $this->job->url,
                '_site_cloner_clone_date' => current_time('mysql'),
                '_site_cloner_job_id' => $this->job_id
            )
        );
        
        $page_id = wp_insert_post($page_data);
        
        if (is_wp_error($page_id)) {
            throw new Exception('Erro ao criar página: ' . $page_id->get_error_message());
        }
        
        // Configure Elementor if detected and enabled
        if ($is_elementor && $this->log['options']['elementor_support'] && $this->elementor->is_active()) {
            $this->elementor->configure_page($page_id, $content);
        }
        
        return $page_id;
    }
    
    /**
     * Generate export ZIP
     */
    private function generate_export_zip($page_id, $content) {
        $upload_dir = wp_upload_dir();
        $exports_dir = $upload_dir['basedir'] . '/site-cloner/exports/';
        
        if (!is_dir($exports_dir)) {
            wp_mkdir_p($exports_dir);
        }
        
        $zip_filename = 'site-clone-' . $page_id . '-' . date('Y-m-d-H-i-s') . '.zip';
        $zip_path = $exports_dir . $zip_filename;
        
        $zip = new ZipArchive();
        if ($zip->open($zip_path, ZipArchive::CREATE) !== TRUE) {
            throw new Exception('Não foi possível criar arquivo ZIP');
        }
        
        // Add main content
        $zip->addFromString('content.html', $content);
        
        // Add metadata
        $metadata = array(
            'source_url' => $this->job->url,
            'clone_date' => current_time('mysql'),
            'page_id' => $page_id,
            'page_title' => $this->log['page_title'],
            'options' => $this->log['options']
        );
        $zip->addFromString('metadata.json', json_encode($metadata, JSON_PRETTY_PRINT));
        
        // Add Elementor data if applicable
        if ($this->elementor->is_active()) {
            $elementor_data = get_post_meta($page_id, '_elementor_data', true);
            if ($elementor_data) {
                $zip->addFromString('elementor.json', $elementor_data);
            }
        }
        
        // Add assets (this would be implemented in the assets class)
        // $this->assets->add_to_zip($zip, $page_id);
        
        $zip->close();
        
        return $zip_path;
    }
    
    /**
     * Import assets from ZIP
     */
    private function import_assets_from_zip($assets_dir) {
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($assets_dir));
        
        foreach ($files as $file) {
            if ($file->isFile()) {
                $relative_path = str_replace($assets_dir, '', $file->getPathname());
                $this->media->import_file($file->getPathname(), $relative_path);
            }
        }
    }
    
    /**
     * Update job status
     */
    private function update_status($status, $progress = null) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'site_cloner_jobs';
        
        $data = array('status' => $status);
        $format = array('%s');
        
        if ($progress !== null) {
            $data['progress'] = $progress;
            $format[] = '%d';
        }
        
        $wpdb->update(
            $table_name,
            $data,
            array('id' => $this->job_id),
            $format,
            array('%d')
        );
        
        // Update object
        $this->job->status = $status;
        if ($progress !== null) {
            $this->job->progress = $progress;
        }
    }
    
    /**
     * Update progress
     */
    private function update_progress($progress) {
        $this->update_status($this->job->status, min(100, max(0, $progress)));
    }
    
    /**
     * Log message
     */
    private function log_message($message, $level = 'info') {
        $this->log['messages'][] = array(
            'time' => current_time('mysql'),
            'level' => $level,
            'message' => $message
        );
        
        // Update log in database
        global $wpdb;
        $table_name = $wpdb->prefix . 'site_cloner_jobs';
        
        $wpdb->update(
            $table_name,
            array('log' => json_encode($this->log)),
            array('id' => $this->job_id),
            array('%s'),
            array('%d')
        );
        
        // Also log to WordPress debug log
        if (WP_DEBUG && WP_DEBUG_LOG) {
            error_log(sprintf('[Site Cloner Job %d] [%s] %s', $this->job_id, strtoupper($level), $message));
        }
    }
}