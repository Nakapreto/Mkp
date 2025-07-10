<?php
/**
 * Classe para clonar páginas de vendas
 */

if (!defined('ABSPATH')) {
    exit;
}

class MSL_Page_Cloner {
    
    public function __construct() {
        // Hooks específicos para clonagem
        add_action('wp_ajax_msl_clone_page_preview', array($this, 'clone_page_preview'));
        add_action('wp_ajax_msl_save_cloned_page', array($this, 'save_cloned_page'));
    }
    
    /**
     * Clonar uma página
     */
    public function clone_page($url, $options = array()) {
        if (empty($url)) {
            return array(
                'success' => false,
                'message' => __('URL é obrigatória.', 'multisite-super-links')
            );
        }
        
        // Validar URL
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return array(
                'success' => false,
                'message' => __('URL inválida.', 'multisite-super-links')
            );
        }
        
        try {
            // Fazer download da página
            $page_content = $this->download_page($url);
            
            if (!$page_content) {
                return array(
                    'success' => false,
                    'message' => __('Não foi possível baixar a página.', 'multisite-super-links')
                );
            }
            
            // Processar o conteúdo
            $processed_content = $this->process_page_content($page_content, $url, $options);
            
            // Extrair informações da página
            $page_info = $this->extract_page_info($page_content);
            
            // Salvar página clonada
            $cloned_page_id = $this->save_cloned_page_to_db($url, $processed_content, $page_info, $options);
            
            if (!$cloned_page_id) {
                return array(
                    'success' => false,
                    'message' => __('Erro ao salvar a página clonada.', 'multisite-super-links')
                );
            }
            
            return array(
                'success' => true,
                'message' => __('Página clonada com sucesso!', 'multisite-super-links'),
                'page_id' => $cloned_page_id,
                'page_url' => get_permalink($cloned_page_id),
                'title' => $page_info['title'],
                'preview_url' => admin_url('admin-ajax.php?action=msl_clone_page_preview&page_id=' . $cloned_page_id)
            );
            
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => sprintf(__('Erro: %s', 'multisite-super-links'), $e->getMessage())
            );
        }
    }
    
    /**
     * Fazer download da página
     */
    private function download_page($url) {
        $args = array(
            'timeout' => 30,
            'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            'headers' => array(
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Language' => 'pt-BR,pt;q=0.9,en;q=0.8',
                'Accept-Encoding' => 'gzip, deflate, br',
                'DNT' => '1',
                'Connection' => 'keep-alive',
                'Upgrade-Insecure-Requests' => '1',
            )
        );
        
        $response = wp_remote_get($url, $args);
        
        if (is_wp_error($response)) {
            throw new Exception($response->get_error_message());
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            throw new Exception(sprintf(__('Erro HTTP: %d', 'multisite-super-links'), $response_code));
        }
        
        return wp_remote_retrieve_body($response);
    }
    
    /**
     * Processar conteúdo da página
     */
    private function process_page_content($content, $original_url, $options = array()) {
        // Criar DOMDocument
        $dom = new DOMDocument();
        $dom->loadHTML($content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        
        // Processar diferentes elementos
        $this->process_links($dom, $original_url, $options);
        $this->process_images($dom, $original_url);
        $this->process_css($dom, $original_url);
        $this->process_scripts($dom, $original_url, $options);
        $this->process_forms($dom, $options);
        $this->add_tracking_code($dom, $options);
        
        // Adicionar meta tags para SEO
        $this->add_meta_tags($dom);
        
        return $dom->saveHTML();
    }
    
    /**
     * Processar links
     */
    private function process_links($dom, $original_url, $options) {
        $links = $dom->getElementsByTagName('a');
        $base_url = parse_url($original_url, PHP_URL_SCHEME) . '://' . parse_url($original_url, PHP_URL_HOST);
        
        foreach ($links as $link) {
            $href = $link->getAttribute('href');
            
            if (empty($href) || $href === '#') {
                continue;
            }
            
            // Converter links relativos em absolutos
            if (strpos($href, 'http') !== 0) {
                if (strpos($href, '/') === 0) {
                    $href = $base_url . $href;
                } else {
                    $href = rtrim($original_url, '/') . '/' . ltrim($href, '/');
                }
            }
            
            // Se for um link de afiliado, criar link camuflado
            if (isset($options['camouflage_affiliate_links']) && $options['camouflage_affiliate_links']) {
                if ($this->is_affiliate_link($href)) {
                    $cloaked_link = $this->create_cloaked_link_for_url($href);
                    if ($cloaked_link) {
                        $link->setAttribute('href', $cloaked_link);
                    }
                }
            }
            
            // Adicionar target="_blank" se configurado
            if (isset($options['open_links_new_tab']) && $options['open_links_new_tab']) {
                $link->setAttribute('target', '_blank');
                $link->setAttribute('rel', 'noopener noreferrer');
            }
        }
    }
    
    /**
     * Processar imagens
     */
    private function process_images($dom, $original_url) {
        $images = $dom->getElementsByTagName('img');
        $base_url = parse_url($original_url, PHP_URL_SCHEME) . '://' . parse_url($original_url, PHP_URL_HOST);
        
        foreach ($images as $img) {
            $src = $img->getAttribute('src');
            
            if (empty($src)) {
                continue;
            }
            
            // Converter URLs relativos em absolutos
            if (strpos($src, 'http') !== 0) {
                if (strpos($src, '/') === 0) {
                    $src = $base_url . $src;
                } else {
                    $src = rtrim($original_url, '/') . '/' . ltrim($src, '/');
                }
                $img->setAttribute('src', $src);
            }
            
            // Adicionar lazy loading
            $img->setAttribute('loading', 'lazy');
        }
    }
    
    /**
     * Processar CSS
     */
    private function process_css($dom, $original_url) {
        $links = $dom->getElementsByTagName('link');
        $base_url = parse_url($original_url, PHP_URL_SCHEME) . '://' . parse_url($original_url, PHP_URL_HOST);
        
        foreach ($links as $link) {
            if ($link->getAttribute('rel') === 'stylesheet') {
                $href = $link->getAttribute('href');
                
                if (!empty($href) && strpos($href, 'http') !== 0) {
                    if (strpos($href, '/') === 0) {
                        $href = $base_url . $href;
                    } else {
                        $href = rtrim($original_url, '/') . '/' . ltrim($href, '/');
                    }
                    $link->setAttribute('href', $href);
                }
            }
        }
        
        // Processar CSS inline
        $styles = $dom->getElementsByTagName('style');
        foreach ($styles as $style) {
            $css_content = $style->textContent;
            $css_content = $this->process_css_urls($css_content, $original_url);
            $style->textContent = $css_content;
        }
    }
    
    /**
     * Processar URLs no CSS
     */
    private function process_css_urls($css_content, $original_url) {
        $base_url = parse_url($original_url, PHP_URL_SCHEME) . '://' . parse_url($original_url, PHP_URL_HOST);
        
        return preg_replace_callback('/url\(["\']?([^"\')\s]+)["\']?\)/i', function($matches) use ($base_url, $original_url) {
            $url = $matches[1];
            
            if (strpos($url, 'http') !== 0 && strpos($url, 'data:') !== 0) {
                if (strpos($url, '/') === 0) {
                    $url = $base_url . $url;
                } else {
                    $url = rtrim($original_url, '/') . '/' . ltrim($url, '/');
                }
            }
            
            return 'url(' . $url . ')';
        }, $css_content);
    }
    
    /**
     * Processar scripts
     */
    private function process_scripts($dom, $original_url, $options) {
        $scripts = $dom->getElementsByTagName('script');
        $base_url = parse_url($original_url, PHP_URL_SCHEME) . '://' . parse_url($original_url, PHP_URL_HOST);
        
        foreach ($scripts as $script) {
            $src = $script->getAttribute('src');
            
            if (!empty($src) && strpos($src, 'http') !== 0) {
                if (strpos($src, '/') === 0) {
                    $src = $base_url . $src;
                } else {
                    $src = rtrim($original_url, '/') . '/' . ltrim($src, '/');
                }
                $script->setAttribute('src', $src);
            }
            
            // Remover scripts problemáticos se configurado
            if (isset($options['remove_tracking_scripts']) && $options['remove_tracking_scripts']) {
                $script_content = $script->textContent;
                if ($this->is_tracking_script($script_content) || $this->is_tracking_script($src)) {
                    $script->parentNode->removeChild($script);
                }
            }
        }
    }
    
    /**
     * Verificar se é script de tracking
     */
    private function is_tracking_script($content) {
        $tracking_patterns = array(
            'google-analytics.com',
            'googletagmanager.com',
            'facebook.net',
            'hotjar.com',
            'crazyegg.com',
            'mixpanel.com',
            'segment.com'
        );
        
        foreach ($tracking_patterns as $pattern) {
            if (stripos($content, $pattern) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Processar formulários
     */
    private function process_forms($dom, $options) {
        $forms = $dom->getElementsByTagName('form');
        
        foreach ($forms as $form) {
            $action = $form->getAttribute('action');
            
            // Se configurado para redirecionar formulários
            if (isset($options['redirect_forms_to']) && !empty($options['redirect_forms_to'])) {
                $form->setAttribute('action', $options['redirect_forms_to']);
            }
            
            // Adicionar campo de origem
            if (isset($options['add_source_field']) && $options['add_source_field']) {
                $hidden_input = $dom->createElement('input');
                $hidden_input->setAttribute('type', 'hidden');
                $hidden_input->setAttribute('name', 'msl_source');
                $hidden_input->setAttribute('value', get_site_url());
                $form->appendChild($hidden_input);
            }
        }
    }
    
    /**
     * Adicionar código de tracking
     */
    private function add_tracking_code($dom, $options) {
        if (isset($options['tracking_code']) && !empty($options['tracking_code'])) {
            $head = $dom->getElementsByTagName('head')->item(0);
            if ($head) {
                $script = $dom->createElement('script');
                $script->textContent = $options['tracking_code'];
                $head->appendChild($script);
            }
        }
        
        // Adicionar pixel do Facebook se configurado
        $facebook_pixel = get_option('msl_facebook_pixel_id');
        if (!empty($facebook_pixel)) {
            $head = $dom->getElementsByTagName('head')->item(0);
            if ($head) {
                $pixel_code = $this->get_facebook_pixel_code($facebook_pixel);
                $script = $dom->createElement('script');
                $script->textContent = $pixel_code;
                $head->appendChild($script);
            }
        }
    }
    
    /**
     * Adicionar meta tags
     */
    private function add_meta_tags($dom) {
        $head = $dom->getElementsByTagName('head')->item(0);
        if (!$head) {
            return;
        }
        
        // Meta tag para indicar que é página clonada
        $meta_cloned = $dom->createElement('meta');
        $meta_cloned->setAttribute('name', 'msl-cloned-page');
        $meta_cloned->setAttribute('content', 'true');
        $head->appendChild($meta_cloned);
        
        // Meta tag noindex
        $meta_robots = $dom->createElement('meta');
        $meta_robots->setAttribute('name', 'robots');
        $meta_robots->setAttribute('content', 'noindex, nofollow');
        $head->appendChild($meta_robots);
        
        // Meta tag viewport se não existir
        $has_viewport = false;
        $metas = $dom->getElementsByTagName('meta');
        foreach ($metas as $meta) {
            if ($meta->getAttribute('name') === 'viewport') {
                $has_viewport = true;
                break;
            }
        }
        
        if (!$has_viewport) {
            $meta_viewport = $dom->createElement('meta');
            $meta_viewport->setAttribute('name', 'viewport');
            $meta_viewport->setAttribute('content', 'width=device-width, initial-scale=1');
            $head->appendChild($meta_viewport);
        }
    }
    
    /**
     * Extrair informações da página
     */
    private function extract_page_info($content) {
        $info = array(
            'title' => '',
            'description' => '',
            'keywords' => '',
            'og_title' => '',
            'og_description' => '',
            'og_image' => ''
        );
        
        // Extrair title
        if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $content, $matches)) {
            $info['title'] = trim(strip_tags($matches[1]));
        }
        
        // Extrair meta description
        if (preg_match('/<meta[^>]*name=["\']description["\'][^>]*content=["\']([^"\']*)["\'][^>]*>/i', $content, $matches)) {
            $info['description'] = trim($matches[1]);
        }
        
        // Extrair meta keywords
        if (preg_match('/<meta[^>]*name=["\']keywords["\'][^>]*content=["\']([^"\']*)["\'][^>]*>/i', $content, $matches)) {
            $info['keywords'] = trim($matches[1]);
        }
        
        // Extrair Open Graph
        if (preg_match('/<meta[^>]*property=["\']og:title["\'][^>]*content=["\']([^"\']*)["\'][^>]*>/i', $content, $matches)) {
            $info['og_title'] = trim($matches[1]);
        }
        
        if (preg_match('/<meta[^>]*property=["\']og:description["\'][^>]*content=["\']([^"\']*)["\'][^>]*>/i', $content, $matches)) {
            $info['og_description'] = trim($matches[1]);
        }
        
        if (preg_match('/<meta[^>]*property=["\']og:image["\'][^>]*content=["\']([^"\']*)["\'][^>]*>/i', $content, $matches)) {
            $info['og_image'] = trim($matches[1]);
        }
        
        return $info;
    }
    
    /**
     * Salvar página clonada no banco
     */
    private function save_cloned_page_to_db($original_url, $content, $page_info, $options) {
        $title = !empty($page_info['title']) ? $page_info['title'] : __('Página Clonada', 'multisite-super-links');
        
        $post_data = array(
            'post_title' => sanitize_text_field($title),
            'post_content' => $content,
            'post_type' => 'msl_cloned_page',
            'post_status' => 'publish',
            'meta_input' => array(
                '_msl_original_url' => esc_url_raw($original_url),
                '_msl_page_info' => $page_info,
                '_msl_clone_options' => $options,
                '_msl_cloned_by' => get_current_user_id(),
                '_msl_site_id' => get_current_blog_id(),
                '_msl_clone_date' => current_time('mysql'),
            )
        );
        
        return wp_insert_post($post_data);
    }
    
    /**
     * Verificar se é link de afiliado
     */
    private function is_affiliate_link($url) {
        $affiliate_patterns = array(
            'hotmart.com',
            'eduzz.com',
            'monetizze.com.br',
            'amazon.com',
            'amazon.com.br',
            '/go/',
            '/offer/',
            '/aff/',
            'affiliate',
            'ref=',
            'aff=',
            'affid=',
            'affiliate_id=',
            'src='
        );
        
        foreach ($affiliate_patterns as $pattern) {
            if (stripos($url, $pattern) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Criar link camuflado para URL
     */
    private function create_cloaked_link_for_url($url) {
        $link_manager = new MSL_Link_Manager();
        
        $data = array(
            'link_title' => 'Link Automático - ' . parse_url($url, PHP_URL_HOST),
            'affiliate_url' => $url,
            'description' => 'Link criado automaticamente durante clonagem de página',
            'category' => 'auto-cloned',
            'enable_cloaking' => true
        );
        
        $result = $link_manager->create_link($data);
        
        if ($result['success']) {
            return $result['cloaked_url'];
        }
        
        return false;
    }
    
    /**
     * Preview da página clonada
     */
    public function clone_page_preview() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Permissão negada.', 'multisite-super-links'));
        }
        
        $page_id = intval($_GET['page_id'] ?? 0);
        
        if (!$page_id) {
            wp_die(__('ID da página inválido.', 'multisite-super-links'));
        }
        
        $post = get_post($page_id);
        
        if (!$post || $post->post_type !== 'msl_cloned_page') {
            wp_die(__('Página clonada não encontrada.', 'multisite-super-links'));
        }
        
        echo $post->post_content;
        exit;
    }
    
    /**
     * Obter código do pixel do Facebook
     */
    private function get_facebook_pixel_code($pixel_id) {
        return "
            !function(f,b,e,v,n,t,s)
            {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
            n.callMethod.apply(n,arguments):n.queue.push(arguments)};
            if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
            n.queue=[];t=b.createElement(e);t.async=!0;
            t.src=v;s=b.getElementsByTagName(e)[0];
            s.parentNode.insertBefore(t,s)}(window, document,'script',
            'https://connect.facebook.net/en_US/fbevents.js');
            fbq('init', '{$pixel_id}');
            fbq('track', 'PageView');
        ";
    }
    
    /**
     * Obter páginas clonadas
     */
    public function get_cloned_pages($args = array()) {
        $defaults = array(
            'posts_per_page' => 20,
            'post_type' => 'msl_cloned_page',
            'post_status' => 'publish',
            'orderby' => 'date',
            'order' => 'DESC'
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $pages = get_posts($args);
        $result = array();
        
        foreach ($pages as $page) {
            $result[] = array(
                'id' => $page->ID,
                'title' => $page->post_title,
                'original_url' => get_post_meta($page->ID, '_msl_original_url', true),
                'page_url' => get_permalink($page->ID),
                'preview_url' => admin_url('admin-ajax.php?action=msl_clone_page_preview&page_id=' . $page->ID),
                'created' => $page->post_date,
                'cloned_by' => get_post_meta($page->ID, '_msl_cloned_by', true)
            );
        }
        
        return $result;
    }
    
    /**
     * Deletar página clonada
     */
    public function delete_cloned_page($page_id) {
        $post = get_post($page_id);
        
        if (!$post || $post->post_type !== 'msl_cloned_page') {
            return array(
                'success' => false,
                'message' => __('Página clonada não encontrada.', 'multisite-super-links')
            );
        }
        
        if (!current_user_can('delete_post', $page_id)) {
            return array(
                'success' => false,
                'message' => __('Permissão negada.', 'multisite-super-links')
            );
        }
        
        wp_delete_post($page_id, true);
        
        return array(
            'success' => true,
            'message' => __('Página clonada deletada com sucesso!', 'multisite-super-links')
        );
    }
}
?>