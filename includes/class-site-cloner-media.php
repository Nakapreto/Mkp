<?php
/**
 * Site Cloner Media Class
 *
 * @package Site_Cloner
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Site_Cloner_Media {
    
    /**
     * Save file to media library
     */
    public function save_to_media_library($file_data, $filename, $type) {
        // Validate file data
        if (empty($file_data)) {
            return false;
        }
        
        // Get upload directory
        $upload_dir = wp_upload_dir();
        
        // Create site-cloner subdirectory
        $cloner_subdir = '/site-cloner/' . date('Y/m');
        $target_dir = $upload_dir['basedir'] . $cloner_subdir;
        
        if (!wp_mkdir_p($target_dir)) {
            error_log('Site Cloner: Could not create directory: ' . $target_dir);
            return false;
        }
        
        // Generate unique filename
        $filename = $this->generate_unique_filename($target_dir, $filename);
        $file_path = $target_dir . '/' . $filename;
        
        // Write file
        if (file_put_contents($file_path, $file_data) === false) {
            error_log('Site Cloner: Could not write file: ' . $file_path);
            return false;
        }
        
        // Determine mime type
        $mime_type = $this->get_mime_type($file_path, $type);
        
        // Prepare attachment data
        $attachment_data = array(
            'post_mime_type' => $mime_type,
            'post_title' => sanitize_file_name(pathinfo($filename, PATHINFO_FILENAME)),
            'post_content' => '',
            'post_status' => 'inherit',
            'meta_input' => array(
                '_site_cloner_asset' => true,
                '_site_cloner_asset_type' => $type,
                '_site_cloner_import_date' => current_time('mysql')
            )
        );
        
        // Insert attachment
        $attachment_id = wp_insert_attachment($attachment_data, $file_path);
        
        if (is_wp_error($attachment_id)) {
            error_log('Site Cloner: Error creating attachment: ' . $attachment_id->get_error_message());
            unlink($file_path);
            return false;
        }
        
        // Generate attachment metadata
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attachment_metadata = wp_generate_attachment_metadata($attachment_id, $file_path);
        wp_update_attachment_metadata($attachment_id, $attachment_metadata);
        
        return $attachment_id;
    }
    
    /**
     * Import file from ZIP extraction
     */
    public function import_file($source_path, $relative_path) {
        if (!file_exists($source_path)) {
            return false;
        }
        
        $file_data = file_get_contents($source_path);
        if ($file_data === false) {
            return false;
        }
        
        $filename = basename($relative_path);
        $type = $this->determine_type_from_extension($filename);
        
        return $this->save_to_media_library($file_data, $filename, $type);
    }
    
    /**
     * Generate unique filename
     */
    private function generate_unique_filename($directory, $filename) {
        $original_filename = $filename;
        $counter = 1;
        
        while (file_exists($directory . '/' . $filename)) {
            $pathinfo = pathinfo($original_filename);
            $filename = $pathinfo['filename'] . '-' . $counter . '.' . $pathinfo['extension'];
            $counter++;
        }
        
        return $filename;
    }
    
    /**
     * Get MIME type for file
     */
    private function get_mime_type($file_path, $type) {
        // First try to detect from file content
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file_path);
        finfo_close($finfo);
        
        if ($mime_type && $mime_type !== 'application/octet-stream') {
            return $mime_type;
        }
        
        // Fallback based on extension and type
        $extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
        
        $mime_types = array(
            // Images
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            'bmp' => 'image/bmp',
            'ico' => 'image/x-icon',
            
            // Videos
            'mp4' => 'video/mp4',
            'webm' => 'video/webm',
            'ogv' => 'video/ogg',
            'avi' => 'video/avi',
            'mov' => 'video/quicktime',
            'wmv' => 'video/x-ms-wmv',
            
            // Audio
            'mp3' => 'audio/mpeg',
            'wav' => 'audio/wav',
            'ogg' => 'audio/ogg',
            
            // Documents
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            
            // Fonts
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf' => 'font/ttf',
            'otf' => 'font/otf',
            'eot' => 'application/vnd.ms-fontobject',
            
            // Stylesheets and scripts
            'css' => 'text/css',
            'js' => 'application/javascript',
            
            // Archives
            'zip' => 'application/zip',
            'rar' => 'application/x-rar-compressed',
        );
        
        if (isset($mime_types[$extension])) {
            return $mime_types[$extension];
        }
        
        // Type-based fallbacks
        switch ($type) {
            case 'image':
                return 'image/jpeg';
            case 'video':
                return 'video/mp4';
            case 'css':
                return 'text/css';
            case 'js':
                return 'application/javascript';
            case 'font':
                return 'font/woff';
            default:
                return 'application/octet-stream';
        }
    }
    
    /**
     * Determine asset type from file extension
     */
    private function determine_type_from_extension($filename) {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        $type_map = array(
            // Images
            'jpg' => 'image',
            'jpeg' => 'image',
            'png' => 'image',
            'gif' => 'image',
            'webp' => 'image',
            'svg' => 'image',
            'bmp' => 'image',
            'ico' => 'image',
            
            // Videos
            'mp4' => 'video',
            'webm' => 'video',
            'ogv' => 'video',
            'avi' => 'video',
            'mov' => 'video',
            'wmv' => 'video',
            
            // Fonts
            'woff' => 'font',
            'woff2' => 'font',
            'ttf' => 'font',
            'otf' => 'font',
            'eot' => 'font',
            
            // Stylesheets and scripts
            'css' => 'css',
            'js' => 'js'
        );
        
        return isset($type_map[$extension]) ? $type_map[$extension] : 'other';
    }
    
    /**
     * Get assets for a specific job
     */
    public function get_job_assets($job_id) {
        global $wpdb;
        
        $assets = $wpdb->get_results($wpdb->prepare(
            "SELECT p.* FROM {$wpdb->posts} p 
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id 
             WHERE pm.meta_key = '_site_cloner_job_id' 
             AND pm.meta_value = %d 
             AND p.post_type = 'attachment'",
            $job_id
        ));
        
        return $assets;
    }
    
    /**
     * Delete assets for a specific job
     */
    public function delete_job_assets($job_id) {
        $assets = $this->get_job_assets($job_id);
        
        foreach ($assets as $asset) {
            wp_delete_attachment($asset->ID, true);
        }
        
        return true;
    }
    
    /**
     * Get asset statistics
     */
    public function get_asset_stats($job_id = null) {
        global $wpdb;
        
        $where_clause = '';
        $params = array();
        
        if ($job_id) {
            $where_clause = 'AND pm.meta_value = %d';
            $params[] = $job_id;
        }
        
        $stats = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                COUNT(*) as total_assets,
                SUM(CASE WHEN pm2.meta_value = 'image' THEN 1 ELSE 0 END) as images,
                SUM(CASE WHEN pm2.meta_value = 'video' THEN 1 ELSE 0 END) as videos,
                SUM(CASE WHEN pm2.meta_value = 'css' THEN 1 ELSE 0 END) as css_files,
                SUM(CASE WHEN pm2.meta_value = 'js' THEN 1 ELSE 0 END) as js_files,
                SUM(CASE WHEN pm2.meta_value = 'font' THEN 1 ELSE 0 END) as fonts,
                SUM(CASE WHEN pm2.meta_value = 'other' THEN 1 ELSE 0 END) as other
             FROM {$wpdb->posts} p 
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id 
             INNER JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id 
             WHERE pm.meta_key = '_site_cloner_job_id' 
             AND pm2.meta_key = '_site_cloner_asset_type'
             AND p.post_type = 'attachment' 
             $where_clause",
            $params
        ));
        
        return $stats;
    }
    
    /**
     * Clean up old assets
     */
    public function cleanup_old_assets($days = 30) {
        global $wpdb;
        
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-$days days"));
        
        $old_assets = $wpdb->get_col($wpdb->prepare(
            "SELECT p.ID FROM {$wpdb->posts} p 
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id 
             WHERE pm.meta_key = '_site_cloner_import_date' 
             AND pm.meta_value < %s 
             AND p.post_type = 'attachment'",
            $cutoff_date
        ));
        
        $deleted_count = 0;
        foreach ($old_assets as $asset_id) {
            if (wp_delete_attachment($asset_id, true)) {
                $deleted_count++;
            }
        }
        
        return $deleted_count;
    }
    
    /**
     * Validate file before processing
     */
    public function validate_file($file_data, $filename, $type) {
        // Check file size
        $settings = get_option('site_cloner_settings', array());
        $max_size = isset($settings['max_file_size']) ? $settings['max_file_size'] * 1024 * 1024 : 50 * 1024 * 1024;
        
        if (strlen($file_data) > $max_size) {
            return new WP_Error('file_too_large', 'File is too large');
        }
        
        // Check file extension
        $allowed_extensions = $this->get_allowed_extensions();
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (!in_array($extension, $allowed_extensions)) {
            return new WP_Error('invalid_extension', 'File extension not allowed');
        }
        
        // Basic file content validation
        if (!$this->validate_file_content($file_data, $extension)) {
            return new WP_Error('invalid_content', 'File content appears to be invalid');
        }
        
        return true;
    }
    
    /**
     * Get allowed file extensions
     */
    private function get_allowed_extensions() {
        return array(
            // Images
            'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp', 'ico',
            // Videos
            'mp4', 'webm', 'ogv', 'avi', 'mov', 'wmv',
            // Audio
            'mp3', 'wav', 'ogg',
            // Documents
            'pdf', 'doc', 'docx',
            // Fonts
            'woff', 'woff2', 'ttf', 'otf', 'eot',
            // Web assets
            'css', 'js'
        );
    }
    
    /**
     * Validate file content
     */
    private function validate_file_content($file_data, $extension) {
        // Basic validation based on file signatures
        $signatures = array(
            'jpg' => array('FFD8FF'),
            'png' => array('89504E47'),
            'gif' => array('474946'),
            'pdf' => array('25504446'),
            'zip' => array('504B0304', '504B0506')
        );
        
        if (isset($signatures[$extension])) {
            $file_header = strtoupper(bin2hex(substr($file_data, 0, 8)));
            
            foreach ($signatures[$extension] as $signature) {
                if (strpos($file_header, $signature) === 0) {
                    return true;
                }
            }
            
            return false;
        }
        
        // For file types without specific signatures, do basic checks
        switch ($extension) {
            case 'css':
                // Check if it looks like CSS
                return preg_match('/[{};:]/', $file_data);
                
            case 'js':
                // Check if it looks like JavaScript
                return preg_match('/[();{}]/', $file_data);
                
            default:
                // For other types, assume valid if we got this far
                return true;
        }
    }
    
    /**
     * Get media library URL for asset
     */
    public function get_media_url($attachment_id) {
        return wp_get_attachment_url($attachment_id);
    }
    
    /**
     * Update asset metadata
     */
    public function update_asset_metadata($attachment_id, $metadata) {
        foreach ($metadata as $key => $value) {
            update_post_meta($attachment_id, $key, $value);
        }
    }
}