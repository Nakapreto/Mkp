<?php
/**
 * Site Cloner AJAX Class
 *
 * @package Site_Cloner
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Site_Cloner_Ajax {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_ajax_site_cloner_start_clone', array($this, 'start_clone'));
        add_action('wp_ajax_site_cloner_check_progress', array($this, 'check_progress'));
        add_action('wp_ajax_site_cloner_get_log', array($this, 'get_log'));
        add_action('wp_ajax_site_cloner_import_zip', array($this, 'import_zip'));
        add_action('wp_ajax_site_cloner_cancel_job', array($this, 'cancel_job'));
    }
    
    /**
     * Start cloning process
     */
    public function start_clone() {
        check_ajax_referer('site_cloner_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Acesso negado', 'site-cloner'));
        }
        
        $url = sanitize_url($_POST['url']);
        $page_title = sanitize_text_field($_POST['page_title']);
        $page_status = sanitize_text_field($_POST['page_status']);
        $options = array(
            'clone_images' => isset($_POST['clone_images']) ? 1 : 0,
            'clone_videos' => isset($_POST['clone_videos']) ? 1 : 0,
            'clone_fonts' => isset($_POST['clone_fonts']) ? 1 : 0,
            'clone_css' => isset($_POST['clone_css']) ? 1 : 0,
            'clone_js' => isset($_POST['clone_js']) ? 1 : 0,
            'elementor_support' => isset($_POST['elementor_support']) ? 1 : 0,
        );
        
        if (empty($url)) {
            wp_send_json_error(__('URL é obrigatória', 'site-cloner'));
        }
        
        // Validate URL
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            wp_send_json_error(__('URL inválida', 'site-cloner'));
        }
        
        // Create job in database
        global $wpdb;
        $table_name = $wpdb->prefix . 'site_cloner_jobs';
        
        $job_id = $wpdb->insert(
            $table_name,
            array(
                'url' => $url,
                'status' => 'pending',
                'progress' => 0,
                'log' => json_encode(array(
                    'start_time' => current_time('mysql'),
                    'options' => $options,
                    'page_title' => $page_title,
                    'page_status' => $page_status,
                    'messages' => array()
                ))
            ),
            array('%s', '%s', '%d', '%s')
        );
        
        if (!$job_id) {
            wp_send_json_error(__('Erro ao criar job', 'site-cloner'));
        }
        
        $job_id = $wpdb->insert_id;
        
        // Start background process
        wp_schedule_single_event(time(), 'site_cloner_process_job', array($job_id));
        
        wp_send_json_success(array(
            'job_id' => $job_id,
            'message' => __('Clone iniciado com sucesso', 'site-cloner')
        ));
    }
    
    /**
     * Check progress of a job
     */
    public function check_progress() {
        check_ajax_referer('site_cloner_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Acesso negado', 'site-cloner'));
        }
        
        $job_id = intval($_POST['job_id']);
        
        if (!$job_id) {
            wp_send_json_error(__('Job ID inválido', 'site-cloner'));
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'site_cloner_jobs';
        
        $job = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $job_id
        ));
        
        if (!$job) {
            wp_send_json_error(__('Job não encontrado', 'site-cloner'));
        }
        
        $log = json_decode($job->log, true);
        
        wp_send_json_success(array(
            'status' => $job->status,
            'progress' => $job->progress,
            'messages' => isset($log['messages']) ? array_slice($log['messages'], -5) : array(),
            'completed' => in_array($job->status, array('completed', 'failed'))
        ));
    }
    
    /**
     * Get full log of a job
     */
    public function get_log() {
        check_ajax_referer('site_cloner_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Acesso negado', 'site-cloner'));
        }
        
        $job_id = intval($_POST['job_id']);
        
        if (!$job_id) {
            wp_send_json_error(__('Job ID inválido', 'site-cloner'));
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'site_cloner_jobs';
        
        $job = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $job_id
        ));
        
        if (!$job) {
            wp_send_json_error(__('Job não encontrado', 'site-cloner'));
        }
        
        $log = json_decode($job->log, true);
        
        wp_send_json_success(array(
            'log' => $log,
            'status' => $job->status,
            'progress' => $job->progress
        ));
    }
    
    /**
     * Import ZIP file
     */
    public function import_zip() {
        check_ajax_referer('site_cloner_import_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Acesso negado', 'site-cloner'));
        }
        
        if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error(__('Erro no upload do arquivo', 'site-cloner'));
        }
        
        $file = $_FILES['import_file'];
        $page_title = sanitize_text_field($_POST['page_title']);
        $page_status = sanitize_text_field($_POST['page_status']);
        
        // Validate file type
        $file_type = wp_check_filetype($file['name']);
        if ($file_type['ext'] !== 'zip') {
            wp_send_json_error(__('Apenas arquivos ZIP são permitidos', 'site-cloner'));
        }
        
        // Create temporary directory
        $upload_dir = wp_upload_dir();
        $temp_dir = $upload_dir['basedir'] . '/site-cloner/temp/' . uniqid() . '/';
        wp_mkdir_p($temp_dir);
        
        // Move uploaded file
        $zip_path = $temp_dir . 'import.zip';
        if (!move_uploaded_file($file['tmp_name'], $zip_path)) {
            wp_send_json_error(__('Erro ao mover arquivo', 'site-cloner'));
        }
        
        // Extract ZIP
        $zip = new ZipArchive();
        if ($zip->open($zip_path) !== TRUE) {
            wp_send_json_error(__('Erro ao abrir arquivo ZIP', 'site-cloner'));
        }
        
        $extract_dir = $temp_dir . 'extracted/';
        if (!$zip->extractTo($extract_dir)) {
            wp_send_json_error(__('Erro ao extrair arquivo ZIP', 'site-cloner'));
        }
        $zip->close();
        
        // Process extracted files
        try {
            $importer = new Site_Cloner_Processor();
            $result = $importer->import_from_zip($extract_dir, $page_title, $page_status);
            
            // Clean up
            $this->delete_directory($temp_dir);
            
            if ($result) {
                wp_send_json_success(array(
                    'message' => __('Arquivo importado com sucesso', 'site-cloner'),
                    'page_id' => $result
                ));
            } else {
                wp_send_json_error(__('Erro ao importar arquivo', 'site-cloner'));
            }
            
        } catch (Exception $e) {
            // Clean up on error
            $this->delete_directory($temp_dir);
            wp_send_json_error($e->getMessage());
        }
    }
    
    /**
     * Cancel a running job
     */
    public function cancel_job() {
        check_ajax_referer('site_cloner_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Acesso negado', 'site-cloner'));
        }
        
        $job_id = intval($_POST['job_id']);
        
        if (!$job_id) {
            wp_send_json_error(__('Job ID inválido', 'site-cloner'));
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'site_cloner_jobs';
        
        $updated = $wpdb->update(
            $table_name,
            array('status' => 'cancelled'),
            array('id' => $job_id),
            array('%s'),
            array('%d')
        );
        
        if ($updated) {
            wp_send_json_success(__('Job cancelado', 'site-cloner'));
        } else {
            wp_send_json_error(__('Erro ao cancelar job', 'site-cloner'));
        }
    }
    
    /**
     * Delete directory recursively
     */
    private function delete_directory($dir) {
        if (!is_dir($dir)) {
            return false;
        }
        
        $files = array_diff(scandir($dir), array('.', '..'));
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                $this->delete_directory($path);
            } else {
                unlink($path);
            }
        }
        
        return rmdir($dir);
    }
}

// Hook for background processing
add_action('site_cloner_process_job', 'site_cloner_process_job_callback');

function site_cloner_process_job_callback($job_id) {
    $processor = new Site_Cloner_Processor();
    $processor->process_job($job_id);
}