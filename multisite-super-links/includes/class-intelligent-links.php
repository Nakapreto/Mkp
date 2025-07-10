<?php
/**
 * Classe para links inteligentes automáticos
 */

if (!defined('ABSPATH')) {
    exit;
}

class MSL_Intelligent_Links {
    
    private $rules = array();
    private $processed_content = array();
    
    public function __construct() {
        // Hooks para processamento de conteúdo
        add_filter('the_content', array($this, 'process_content'), 99);
        add_filter('the_excerpt', array($this, 'process_excerpt'), 99);
        add_action('wp_ajax_msl_test_intelligent_rule', array($this, 'test_intelligent_rule'));
        add_action('wp_ajax_msl_save_intelligent_rule', array($this, 'save_intelligent_rule'));
        
        // Carregar regras salvas
        $this->load_rules();
    }
    
    /**
     * Processar conteúdo principal
     */
    public function process_content($content) {
        if (is_admin() || is_feed() || !is_main_query()) {
            return $content;
        }
        
        // Evitar processamento duplo
        $content_hash = md5($content);
        if (isset($this->processed_content[$content_hash])) {
            return $this->processed_content[$content_hash];
        }
        
        $processed = $this->apply_intelligent_rules($content);
        $this->processed_content[$content_hash] = $processed;
        
        return $processed;
    }
    
    /**
     * Processar excerpt
     */
    public function process_excerpt($excerpt) {
        if (is_admin()) {
            return $excerpt;
        }
        
        return $this->apply_intelligent_rules($excerpt, true);
    }
    
    /**
     * Aplicar regras inteligentes ao conteúdo
     */
    public function apply_intelligent_rules($content, $is_excerpt = false) {
        if (empty($this->rules) || empty($content)) {
            return $content;
        }
        
        // Verificar se links inteligentes estão habilitados para este site
        if (!get_option('msl_enable_intelligent_links', true)) {
            return $content;
        }
        
        $site_id = get_current_blog_id();
        $post_id = get_the_ID();
        
        // Verificar exclusões por post
        $excluded_posts = get_option('msl_intelligent_links_excluded_posts', array());
        if (in_array($post_id, $excluded_posts)) {
            return $content;
        }
        
        // Verificar exclusões por categoria
        if (is_single()) {
            $categories = wp_get_post_categories($post_id);
            $excluded_categories = get_option('msl_intelligent_links_excluded_categories', array());
            if (array_intersect($categories, $excluded_categories)) {
                return $content;
            }
        }
        
        // Aplicar regras por prioridade
        $rules = $this->get_sorted_rules($site_id);
        $modified_content = $content;
        $applied_rules = array();
        
        foreach ($rules as $rule) {
            // Verificar se a regra está ativa
            if (!$rule['enabled']) {
                continue;
            }
            
            // Verificar limite de aplicações
            if (isset($rule['max_applications']) && $rule['max_applications'] > 0) {
                $current_applications = $this->count_rule_applications($rule['id'], $site_id);
                if ($current_applications >= $rule['max_applications']) {
                    continue;
                }
            }
            
            // Verificar condições da regra
            if (!$this->check_rule_conditions($rule, $is_excerpt)) {
                continue;
            }
            
            // Aplicar a regra
            $result = $this->apply_single_rule($modified_content, $rule);
            
            if ($result['applied']) {
                $modified_content = $result['content'];
                $applied_rules[] = array(
                    'rule_id' => $rule['id'],
                    'replacements' => $result['replacements']
                );
                
                // Registrar aplicação da regra
                $this->log_rule_application($rule['id'], $post_id, $site_id, $result['replacements']);
                
                // Verificar limite de substituições por conteúdo
                if (count($applied_rules) >= get_option('msl_max_intelligent_links_per_content', 5)) {
                    break;
                }
            }
        }
        
        return $modified_content;
    }
    
    /**
     * Aplicar uma regra específica
     */
    private function apply_single_rule($content, $rule) {
        $replacements = 0;
        $modified_content = $content;
        
        // Evitar substituições dentro de tags HTML
        $dom = new DOMDocument();
        @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $modified_content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        
        if (!$dom) {
            // Fallback para regex simples se DOMDocument falhar
            return $this->apply_rule_regex($content, $rule);
        }
        
        $xpath = new DOMXPath($dom);
        $textNodes = $xpath->query('//text()[not(ancestor::script) and not(ancestor::style) and not(ancestor::a)]');
        
        foreach ($textNodes as $textNode) {
            $text = $textNode->textContent;
            $modified_text = $this->process_text_with_rule($text, $rule);
            
            if ($modified_text !== $text) {
                $replacements++;
                
                // Criar fragmento HTML
                $fragment = $dom->createDocumentFragment();
                $fragment->appendXML($modified_text);
                $textNode->parentNode->replaceChild($fragment, $textNode);
                
                // Verificar limite de substituições por regra
                if ($replacements >= ($rule['max_replacements'] ?? 3)) {
                    break;
                }
            }
        }
        
        $result_content = $dom->saveHTML();
        
        // Limpar encoding artifacts
        $result_content = str_replace('<?xml encoding="utf-8" ?>', '', $result_content);
        
        return array(
            'applied' => $replacements > 0,
            'content' => $result_content,
            'replacements' => $replacements
        );
    }
    
    /**
     * Processar texto com uma regra específica
     */
    private function process_text_with_rule($text, $rule) {
        $keywords = $rule['keywords'];
        $link_url = $rule['link_url'];
        $link_title = $rule['link_title'] ?? '';
        $link_class = $rule['link_class'] ?? 'msl-intelligent-link';
        $open_new_tab = $rule['open_new_tab'] ?? false;
        $case_sensitive = $rule['case_sensitive'] ?? false;
        $whole_words_only = $rule['whole_words_only'] ?? true;
        
        $target_attr = $open_new_tab ? ' target="_blank" rel="noopener"' : '';
        $title_attr = !empty($link_title) ? ' title="' . esc_attr($link_title) . '"' : '';
        $class_attr = !empty($link_class) ? ' class="' . esc_attr($link_class) . '"' : '';
        
        $link_template = '<a href="' . esc_url($link_url) . '"' . $target_attr . $title_attr . $class_attr . '>$1</a>';
        
        foreach ($keywords as $keyword) {
            $keyword = trim($keyword);
            if (empty($keyword)) {
                continue;
            }
            
            // Verificar se a palavra já está dentro de um link
            if (stripos($text, '<a') !== false && stripos($text, $keyword) !== false) {
                if (preg_match('/<a[^>]*>.*?' . preg_quote($keyword, '/') . '.*?<\/a>/i', $text)) {
                    continue; // Pular se já está linkado
                }
            }
            
            $flags = $case_sensitive ? '' : 'i';
            
            if ($whole_words_only) {
                $pattern = '/\b(' . preg_quote($keyword, '/') . ')\b/' . $flags;
            } else {
                $pattern = '/(' . preg_quote($keyword, '/') . ')/' . $flags;
            }
            
            $text = preg_replace($pattern, $link_template, $text, 1); // Apenas primeira ocorrência
        }
        
        return $text;
    }
    
    /**
     * Fallback para aplicação via regex
     */
    private function apply_rule_regex($content, $rule) {
        $replacements = 0;
        $modified_content = $content;
        
        $keywords = $rule['keywords'];
        $link_url = $rule['link_url'];
        $link_title = $rule['link_title'] ?? '';
        $link_class = $rule['link_class'] ?? 'msl-intelligent-link';
        $open_new_tab = $rule['open_new_tab'] ?? false;
        $case_sensitive = $rule['case_sensitive'] ?? false;
        $whole_words_only = $rule['whole_words_only'] ?? true;
        
        $target_attr = $open_new_tab ? ' target="_blank" rel="noopener"' : '';
        $title_attr = !empty($link_title) ? ' title="' . esc_attr($link_title) . '"' : '';
        $class_attr = !empty($link_class) ? ' class="' . esc_attr($link_class) . '"' : '';
        
        $link_template = '<a href="' . esc_url($link_url) . '"' . $target_attr . $title_attr . $class_attr . '>$1</a>';
        
        foreach ($keywords as $keyword) {
            $keyword = trim($keyword);
            if (empty($keyword)) {
                continue;
            }
            
            $flags = $case_sensitive ? '' : 'i';
            
            if ($whole_words_only) {
                $pattern = '/\b(' . preg_quote($keyword, '/') . ')\b/' . $flags;
            } else {
                $pattern = '/(' . preg_quote($keyword, '/') . ')/' . $flags;
            }
            
            // Evitar substituições dentro de tags HTML
            $pattern_with_exclusions = '/(?![^<]*>)(?![^<]*<\/a>)' . substr($pattern, 1);
            
            $new_content = preg_replace($pattern_with_exclusions, $link_template, $modified_content, 1, $count);
            
            if ($count > 0) {
                $modified_content = $new_content;
                $replacements += $count;
            }
            
            if ($replacements >= ($rule['max_replacements'] ?? 3)) {
                break;
            }
        }
        
        return array(
            'applied' => $replacements > 0,
            'content' => $modified_content,
            'replacements' => $replacements
        );
    }
    
    /**
     * Verificar condições da regra
     */
    private function check_rule_conditions($rule, $is_excerpt = false) {
        // Verificar se deve aplicar em excerpts
        if ($is_excerpt && !($rule['apply_to_excerpt'] ?? false)) {
            return false;
        }
        
        // Verificar tipos de post permitidos
        if (isset($rule['post_types']) && !empty($rule['post_types'])) {
            $current_post_type = get_post_type();
            if (!in_array($current_post_type, $rule['post_types'])) {
                return false;
            }
        }
        
        // Verificar categorias específicas
        if (isset($rule['categories']) && !empty($rule['categories'])) {
            $post_categories = wp_get_post_categories(get_the_ID());
            if (!array_intersect($post_categories, $rule['categories'])) {
                return false;
            }
        }
        
        // Verificar tags específicas
        if (isset($rule['tags']) && !empty($rule['tags'])) {
            $post_tags = wp_get_post_tags(get_the_ID(), array('fields' => 'ids'));
            if (!array_intersect($post_tags, $rule['tags'])) {
                return false;
            }
        }
        
        // Verificar data de validade
        if (isset($rule['expiry_date']) && !empty($rule['expiry_date'])) {
            if (current_time('timestamp') > strtotime($rule['expiry_date'])) {
                return false;
            }
        }
        
        // Verificar horário de ativação
        if (isset($rule['active_hours']) && !empty($rule['active_hours'])) {
            $current_hour = intval(current_time('H'));
            if (!in_array($current_hour, $rule['active_hours'])) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Obter regras ordenadas por prioridade
     */
    private function get_sorted_rules($site_id) {
        $rules = array_filter($this->rules, function($rule) use ($site_id) {
            return !isset($rule['site_id']) || $rule['site_id'] == $site_id || $rule['site_id'] == 0; // 0 = global
        });
        
        usort($rules, function($a, $b) {
            $priority_a = $a['priority'] ?? 10;
            $priority_b = $b['priority'] ?? 10;
            return $priority_a - $priority_b;
        });
        
        return $rules;
    }
    
    /**
     * Carregar regras salvas
     */
    private function load_rules() {
        $this->rules = get_option('msl_intelligent_link_rules', array());
    }
    
    /**
     * Salvar regras
     */
    private function save_rules() {
        update_option('msl_intelligent_link_rules', $this->rules);
    }
    
    /**
     * Adicionar nova regra
     */
    public function add_rule($rule_data) {
        $rule_id = wp_generate_password(8, false);
        
        $rule = array_merge(array(
            'id' => $rule_id,
            'name' => '',
            'keywords' => array(),
            'link_url' => '',
            'link_title' => '',
            'link_class' => 'msl-intelligent-link',
            'enabled' => true,
            'priority' => 10,
            'max_replacements' => 3,
            'max_applications' => 0, // 0 = unlimited
            'case_sensitive' => false,
            'whole_words_only' => true,
            'open_new_tab' => false,
            'apply_to_excerpt' => false,
            'post_types' => array('post', 'page'),
            'categories' => array(),
            'tags' => array(),
            'site_id' => get_current_blog_id(),
            'created_date' => current_time('mysql'),
            'created_by' => get_current_user_id()
        ), $rule_data);
        
        $this->rules[] = $rule;
        $this->save_rules();
        
        return $rule_id;
    }
    
    /**
     * Atualizar regra existente
     */
    public function update_rule($rule_id, $rule_data) {
        foreach ($this->rules as &$rule) {
            if ($rule['id'] === $rule_id) {
                $rule = array_merge($rule, $rule_data);
                $this->save_rules();
                return true;
            }
        }
        return false;
    }
    
    /**
     * Deletar regra
     */
    public function delete_rule($rule_id) {
        $this->rules = array_filter($this->rules, function($rule) use ($rule_id) {
            return $rule['id'] !== $rule_id;
        });
        $this->save_rules();
        
        // Limpar logs da regra
        global $wpdb;
        $table = $wpdb->prefix . 'msl_intelligent_link_logs';
        $wpdb->delete($table, array('rule_id' => $rule_id), array('%s'));
    }
    
    /**
     * Obter regra por ID
     */
    public function get_rule($rule_id) {
        foreach ($this->rules as $rule) {
            if ($rule['id'] === $rule_id) {
                return $rule;
            }
        }
        return null;
    }
    
    /**
     * Obter todas as regras
     */
    public function get_all_rules($site_id = null) {
        if ($site_id === null) {
            return $this->rules;
        }
        
        return array_filter($this->rules, function($rule) use ($site_id) {
            return !isset($rule['site_id']) || $rule['site_id'] == $site_id || $rule['site_id'] == 0;
        });
    }
    
    /**
     * Testar regra via AJAX
     */
    public function test_intelligent_rule() {
        check_ajax_referer('msl_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permissão negada');
        }
        
        $rule_data = json_decode(stripslashes($_POST['rule_data']), true);
        $test_content = stripslashes($_POST['test_content']);
        
        if (!$rule_data || !$test_content) {
            wp_send_json_error('Dados inválidos');
        }
        
        // Aplicar a regra ao conteúdo de teste
        $result = $this->apply_single_rule($test_content, $rule_data);
        
        wp_send_json_success(array(
            'original_content' => $test_content,
            'processed_content' => $result['content'],
            'replacements' => $result['replacements'],
            'applied' => $result['applied']
        ));
    }
    
    /**
     * Salvar regra via AJAX
     */
    public function save_intelligent_rule() {
        check_ajax_referer('msl_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permissão negada');
        }
        
        $rule_data = json_decode(stripslashes($_POST['rule_data']), true);
        $rule_id = sanitize_text_field($_POST['rule_id'] ?? '');
        
        if (!$rule_data) {
            wp_send_json_error('Dados da regra inválidos');
        }
        
        // Validar dados obrigatórios
        if (empty($rule_data['name']) || empty($rule_data['keywords']) || empty($rule_data['link_url'])) {
            wp_send_json_error('Nome, palavras-chave e URL são obrigatórios');
        }
        
        if (empty($rule_id)) {
            // Criar nova regra
            $rule_id = $this->add_rule($rule_data);
            wp_send_json_success(array(
                'message' => 'Regra criada com sucesso',
                'rule_id' => $rule_id
            ));
        } else {
            // Atualizar regra existente
            $updated = $this->update_rule($rule_id, $rule_data);
            if ($updated) {
                wp_send_json_success(array(
                    'message' => 'Regra atualizada com sucesso',
                    'rule_id' => $rule_id
                ));
            } else {
                wp_send_json_error('Erro ao atualizar regra');
            }
        }
    }
    
    /**
     * Registrar aplicação de regra
     */
    private function log_rule_application($rule_id, $post_id, $site_id, $replacements) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'msl_intelligent_link_logs';
        
        $wpdb->insert(
            $table,
            array(
                'rule_id' => $rule_id,
                'post_id' => $post_id,
                'site_id' => $site_id,
                'replacements' => $replacements,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'ip_address' => $this->get_user_ip(),
                'date_created' => current_time('mysql')
            ),
            array('%s', '%d', '%d', '%d', '%s', '%s', '%s')
        );
    }
    
    /**
     * Contar aplicações de uma regra
     */
    private function count_rule_applications($rule_id, $site_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'msl_intelligent_link_logs';
        
        return $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) 
            FROM {$table} 
            WHERE rule_id = %s AND site_id = %d
        ", $rule_id, $site_id));
    }
    
    /**
     * Obter estatísticas de regras
     */
    public function get_rule_stats($rule_id = null, $date_range = 30) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'msl_intelligent_link_logs';
        $site_id = get_current_blog_id();
        $date_from = date('Y-m-d H:i:s', strtotime("-{$date_range} days"));
        
        if ($rule_id) {
            // Estatísticas de uma regra específica
            $stats = $wpdb->get_row($wpdb->prepare("
                SELECT 
                    COUNT(*) as total_applications,
                    SUM(replacements) as total_replacements,
                    COUNT(DISTINCT post_id) as posts_affected
                FROM {$table} 
                WHERE rule_id = %s AND site_id = %d AND date_created >= %s
            ", $rule_id, $site_id, $date_from));
            
            $by_day = $wpdb->get_results($wpdb->prepare("
                SELECT 
                    DATE(date_created) as date,
                    COUNT(*) as applications,
                    SUM(replacements) as replacements
                FROM {$table} 
                WHERE rule_id = %s AND site_id = %d AND date_created >= %s
                GROUP BY DATE(date_created)
                ORDER BY date ASC
            ", $rule_id, $site_id, $date_from));
            
            return array(
                'total_applications' => $stats->total_applications ?? 0,
                'total_replacements' => $stats->total_replacements ?? 0,
                'posts_affected' => $stats->posts_affected ?? 0,
                'by_day' => $by_day
            );
        } else {
            // Estatísticas gerais
            $rules_stats = $wpdb->get_results($wpdb->prepare("
                SELECT 
                    rule_id,
                    COUNT(*) as applications,
                    SUM(replacements) as replacements
                FROM {$table} 
                WHERE site_id = %d AND date_created >= %s
                GROUP BY rule_id
                ORDER BY applications DESC
            ", $site_id, $date_from));
            
            return $rules_stats;
        }
    }
    
    /**
     * Obter IP do usuário
     */
    private function get_user_ip() {
        $ip_keys = array(
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        );
        
        foreach ($ip_keys as $key) {
            if (isset($_SERVER[$key]) && !empty($_SERVER[$key])) {
                $ips = explode(',', $_SERVER[$key]);
                return trim($ips[0]);
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    /**
     * Importar regras de arquivo
     */
    public function import_rules($file_content) {
        $rules = json_decode($file_content, true);
        
        if (!is_array($rules)) {
            return array('success' => false, 'message' => 'Formato de arquivo inválido');
        }
        
        $imported = 0;
        $errors = 0;
        
        foreach ($rules as $rule_data) {
            if (!isset($rule_data['name']) || !isset($rule_data['keywords']) || !isset($rule_data['link_url'])) {
                $errors++;
                continue;
            }
            
            $this->add_rule($rule_data);
            $imported++;
        }
        
        return array(
            'success' => true,
            'imported' => $imported,
            'errors' => $errors,
            'message' => sprintf('Importadas %d regras com %d erros', $imported, $errors)
        );
    }
    
    /**
     * Exportar regras para arquivo
     */
    public function export_rules($rule_ids = null) {
        $rules_to_export = array();
        
        if ($rule_ids) {
            foreach ($rule_ids as $rule_id) {
                $rule = $this->get_rule($rule_id);
                if ($rule) {
                    unset($rule['id'], $rule['created_date'], $rule['created_by']);
                    $rules_to_export[] = $rule;
                }
            }
        } else {
            $site_id = get_current_blog_id();
            $all_rules = $this->get_all_rules($site_id);
            
            foreach ($all_rules as $rule) {
                unset($rule['id'], $rule['created_date'], $rule['created_by']);
                $rules_to_export[] = $rule;
            }
        }
        
        return json_encode($rules_to_export, JSON_PRETTY_PRINT);
    }
}
?>