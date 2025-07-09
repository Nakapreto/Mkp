<?php

if (!defined('ABSPATH')) {
    exit;
}

class SLC_Link_Manager {
    
    public function __construct() {
        // Constructor can be used for initialization if needed
    }
    
    public function create_link($data) {
        global $wpdb;
        
        // Sanitize and validate input
        $title = sanitize_text_field($data['title']);
        $slug = sanitize_title($data['slug']);
        $target_url = sanitize_url($data['target_url']);
        $redirect_type = in_array($data['redirect_type'], array('301', '302', '307')) ? $data['redirect_type'] : '301';
        $cloaked = isset($data['cloaked']) ? 1 : 0;
        $facebook_cloaked = isset($data['facebook_cloaked']) ? 1 : 0;
        $cookie_tracking = isset($data['cookie_tracking']) ? 1 : 0;
        $smart_link = isset($data['smart_link']) ? 1 : 0;
        $keywords = sanitize_textarea_field($data['keywords']);
        $category = sanitize_text_field($data['category']);
        
        // Validate required fields
        if (empty($title) || empty($slug) || empty($target_url)) {
            return array(
                'success' => false,
                'message' => __('Título, slug e URL de destino são obrigatórios.', 'super-links-clone')
            );
        }
        
        // Check if slug already exists
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}slc_links WHERE slug = %s AND status = 'active'",
            $slug
        ));
        
        if ($existing) {
            return array(
                'success' => false,
                'message' => __('Este slug já existe. Escolha outro.', 'super-links-clone')
            );
        }
        
        // Validate URL
        if (!filter_var($target_url, FILTER_VALIDATE_URL)) {
            return array(
                'success' => false,
                'message' => __('URL de destino inválida.', 'super-links-clone')
            );
        }
        
        // Insert link into database
        $result = $wpdb->insert(
            $wpdb->prefix . 'slc_links',
            array(
                'title' => $title,
                'slug' => $slug,
                'target_url' => $target_url,
                'redirect_type' => $redirect_type,
                'cloaked' => $cloaked,
                'facebook_cloaked' => $facebook_cloaked,
                'cookie_tracking' => $cookie_tracking,
                'smart_link' => $smart_link,
                'keywords' => $keywords,
                'category' => $category,
                'created_by' => get_current_user_id(),
                'created_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%s', '%d', '%d', '%d', '%d', '%s', '%s', '%d', '%s')
        );
        
        if ($result === false) {
            return array(
                'success' => false,
                'message' => __('Erro ao criar o link. Tente novamente.', 'super-links-clone')
            );
        }
        
        $link_id = $wpdb->insert_id;
        
        // If it's a smart link, add keywords to smart links processor
        if ($smart_link && !empty($keywords)) {
            $this->add_smart_keywords($link_id, $keywords);
        }
        
        return array(
            'success' => true,
            'message' => __('Link criado com sucesso!', 'super-links-clone'),
            'link_id' => $link_id,
            'short_url' => $this->get_short_url($slug)
        );
    }
    
    public function update_link($link_id, $data) {
        global $wpdb;
        
        // Sanitize and validate input
        $title = sanitize_text_field($data['title']);
        $slug = sanitize_title($data['slug']);
        $target_url = sanitize_url($data['target_url']);
        $redirect_type = in_array($data['redirect_type'], array('301', '302', '307')) ? $data['redirect_type'] : '301';
        $cloaked = isset($data['cloaked']) ? 1 : 0;
        $facebook_cloaked = isset($data['facebook_cloaked']) ? 1 : 0;
        $cookie_tracking = isset($data['cookie_tracking']) ? 1 : 0;
        $smart_link = isset($data['smart_link']) ? 1 : 0;
        $keywords = sanitize_textarea_field($data['keywords']);
        $category = sanitize_text_field($data['category']);
        
        // Validate required fields
        if (empty($title) || empty($slug) || empty($target_url)) {
            return array(
                'success' => false,
                'message' => __('Título, slug e URL de destino são obrigatórios.', 'super-links-clone')
            );
        }
        
        // Check if slug already exists (excluding current link)
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}slc_links WHERE slug = %s AND id != %d AND status = 'active'",
            $slug,
            $link_id
        ));
        
        if ($existing) {
            return array(
                'success' => false,
                'message' => __('Este slug já existe. Escolha outro.', 'super-links-clone')
            );
        }
        
        // Update link in database
        $result = $wpdb->update(
            $wpdb->prefix . 'slc_links',
            array(
                'title' => $title,
                'slug' => $slug,
                'target_url' => $target_url,
                'redirect_type' => $redirect_type,
                'cloaked' => $cloaked,
                'facebook_cloaked' => $facebook_cloaked,
                'cookie_tracking' => $cookie_tracking,
                'smart_link' => $smart_link,
                'keywords' => $keywords,
                'category' => $category,
                'updated_at' => current_time('mysql')
            ),
            array('id' => $link_id),
            array('%s', '%s', '%s', '%s', '%d', '%d', '%d', '%d', '%s', '%s', '%s'),
            array('%d')
        );
        
        if ($result === false) {
            return array(
                'success' => false,
                'message' => __('Erro ao atualizar o link. Tente novamente.', 'super-links-clone')
            );
        }
        
        return array(
            'success' => true,
            'message' => __('Link atualizado com sucesso!', 'super-links-clone'),
            'short_url' => $this->get_short_url($slug)
        );
    }
    
    public function delete_link($link_id) {
        global $wpdb;
        
        $result = $wpdb->update(
            $wpdb->prefix . 'slc_links',
            array('status' => 'deleted'),
            array('id' => $link_id),
            array('%s'),
            array('%d')
        );
        
        if ($result === false) {
            return array(
                'success' => false,
                'message' => __('Erro ao deletar o link. Tente novamente.', 'super-links-clone')
            );
        }
        
        return array(
            'success' => true,
            'message' => __('Link deletado com sucesso!', 'super-links-clone')
        );
    }
    
    public function get_link_by_slug($slug) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}slc_links WHERE slug = %s AND status = 'active'",
            $slug
        ));
    }
    
    public function get_link_by_id($link_id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}slc_links WHERE id = %d AND status = 'active'",
            $link_id
        ));
    }
    
    public function get_all_links($category = '', $limit = 0) {
        global $wpdb;
        
        $sql = "SELECT * FROM {$wpdb->prefix}slc_links WHERE status = 'active'";
        
        if (!empty($category)) {
            $sql .= $wpdb->prepare(" AND category = %s", $category);
        }
        
        $sql .= " ORDER BY created_at DESC";
        
        if ($limit > 0) {
            $sql .= $wpdb->prepare(" LIMIT %d", $limit);
        }
        
        return $wpdb->get_results($sql);
    }
    
    public function increment_clicks($link_id, $is_unique = false) {
        global $wpdb;
        
        $update_data = array('clicks' => 'clicks + 1');
        $format = array('clicks');
        
        if ($is_unique) {
            $update_data['unique_clicks'] = 'unique_clicks + 1';
            $format[] = 'unique_clicks';
        }
        
        // We need to use a raw query for increment operations
        $sql = "UPDATE {$wpdb->prefix}slc_links SET clicks = clicks + 1";
        if ($is_unique) {
            $sql .= ", unique_clicks = unique_clicks + 1";
        }
        $sql .= " WHERE id = %d";
        
        return $wpdb->query($wpdb->prepare($sql, $link_id));
    }
    
    public function get_short_url($slug) {
        $link_prefix = get_option('slc_link_prefix', 'go');
        return home_url('/' . $link_prefix . '/' . $slug);
    }
    
    public function import_links($source) {
        global $wpdb;
        
        $imported = 0;
        $errors = array();
        
        switch ($source) {
            case 'prettylinks':
                $imported = $this->import_from_prettylinks();
                break;
            case 'thirstyaffiliates':
                $imported = $this->import_from_thirstyaffiliates();
                break;
            case 'csv':
                $imported = $this->import_from_csv();
                break;
            default:
                return array(
                    'success' => false,
                    'message' => __('Fonte de importação inválida.', 'super-links-clone')
                );
        }
        
        if ($imported > 0) {
            return array(
                'success' => true,
                'message' => sprintf(__('%d links importados com sucesso!', 'super-links-clone'), $imported)
            );
        } else {
            return array(
                'success' => false,
                'message' => __('Nenhum link foi importado. Verifique se existem links na fonte selecionada.', 'super-links-clone')
            );
        }
    }
    
    private function import_from_prettylinks() {
        global $wpdb;
        
        // Check if Pretty Links table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}prli_links'");
        if (!$table_exists) {
            return 0;
        }
        
        $imported = 0;
        $prettylinks = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}prli_links WHERE link_status = 'enabled'");
        
        foreach ($prettylinks as $link) {
            // Check if link already exists
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}slc_links WHERE slug = %s AND status = 'active'",
                $link->slug
            ));
            
            if (!$existing) {
                $result = $wpdb->insert(
                    $wpdb->prefix . 'slc_links',
                    array(
                        'title' => $link->name ?: 'Imported from Pretty Links',
                        'slug' => $link->slug,
                        'target_url' => $link->url,
                        'redirect_type' => '301',
                        'clicks' => $link->clicks ?: 0,
                        'created_by' => get_current_user_id(),
                        'created_at' => current_time('mysql')
                    ),
                    array('%s', '%s', '%s', '%s', '%d', '%d', '%s')
                );
                
                if ($result) {
                    $imported++;
                }
            }
        }
        
        return $imported;
    }
    
    private function import_from_thirstyaffiliates() {
        global $wpdb;
        
        $imported = 0;
        $thirsty_links = get_posts(array(
            'post_type' => 'thirstylink',
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ));
        
        foreach ($thirsty_links as $post) {
            $slug = $post->post_name;
            $target_url = get_post_meta($post->ID, '_ta_destination_url', true);
            $redirect_type = get_post_meta($post->ID, '_ta_redirect_type', true) ?: '301';
            
            if (!empty($target_url)) {
                // Check if link already exists
                $existing = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM {$wpdb->prefix}slc_links WHERE slug = %s AND status = 'active'",
                    $slug
                ));
                
                if (!$existing) {
                    $result = $wpdb->insert(
                        $wpdb->prefix . 'slc_links',
                        array(
                            'title' => $post->post_title,
                            'slug' => $slug,
                            'target_url' => $target_url,
                            'redirect_type' => $redirect_type,
                            'created_by' => get_current_user_id(),
                            'created_at' => current_time('mysql')
                        ),
                        array('%s', '%s', '%s', '%s', '%d', '%s')
                    );
                    
                    if ($result) {
                        $imported++;
                    }
                }
            }
        }
        
        return $imported;
    }
    
    private function import_from_csv() {
        // This would handle CSV file upload and import
        // For now, return 0 as it requires file handling
        return 0;
    }
    
    private function add_smart_keywords($link_id, $keywords) {
        // This would integrate with the smart links system
        // to automatically add links for specified keywords
        $smart_links = new SLC_Smart_Links();
        $smart_links->add_keywords_for_link($link_id, $keywords);
    }
    
    public function get_categories() {
        global $wpdb;
        
        return $wpdb->get_col("
            SELECT DISTINCT category 
            FROM {$wpdb->prefix}slc_links 
            WHERE status = 'active' AND category != '' 
            ORDER BY category ASC
        ");
    }
    
    public function bulk_action($action, $link_ids) {
        global $wpdb;
        
        if (!is_array($link_ids) || empty($link_ids)) {
            return array(
                'success' => false,
                'message' => __('Nenhum link selecionado.', 'super-links-clone')
            );
        }
        
        $affected = 0;
        
        switch ($action) {
            case 'delete':
                $placeholders = implode(',', array_fill(0, count($link_ids), '%d'));
                $affected = $wpdb->query($wpdb->prepare(
                    "UPDATE {$wpdb->prefix}slc_links SET status = 'deleted' WHERE id IN ($placeholders)",
                    $link_ids
                ));
                break;
            
            case 'activate':
                $placeholders = implode(',', array_fill(0, count($link_ids), '%d'));
                $affected = $wpdb->query($wpdb->prepare(
                    "UPDATE {$wpdb->prefix}slc_links SET status = 'active' WHERE id IN ($placeholders)",
                    $link_ids
                ));
                break;
            
            case 'deactivate':
                $placeholders = implode(',', array_fill(0, count($link_ids), '%d'));
                $affected = $wpdb->query($wpdb->prepare(
                    "UPDATE {$wpdb->prefix}slc_links SET status = 'inactive' WHERE id IN ($placeholders)",
                    $link_ids
                ));
                break;
        }
        
        if ($affected > 0) {
            return array(
                'success' => true,
                'message' => sprintf(__('%d links foram processados.', 'super-links-clone'), $affected)
            );
        } else {
            return array(
                'success' => false,
                'message' => __('Nenhum link foi processado.', 'super-links-clone')
            );
        }
    }
}