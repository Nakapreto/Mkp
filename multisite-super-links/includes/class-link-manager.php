<?php
/**
 * Classe para gerenciar links camuflados
 */

if (!defined('ABSPATH')) {
    exit;
}

class MSL_Link_Manager {
    
    private $table_name;
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'msl_link_stats';
    }
    
    /**
     * Criar um novo link camuflado
     */
    public function create_link($data) {
        if (empty($data['link_title']) || empty($data['affiliate_url'])) {
            return array(
                'success' => false,
                'message' => __('Título e URL do afiliado são obrigatórios.', 'multisite-super-links')
            );
        }
        
        // Validar URL
        if (!filter_var($data['affiliate_url'], FILTER_VALIDATE_URL)) {
            return array(
                'success' => false,
                'message' => __('URL inválida.', 'multisite-super-links')
            );
        }
        
        // Criar o post
        $post_data = array(
            'post_title' => sanitize_text_field($data['link_title']),
            'post_type' => 'msl_link',
            'post_status' => 'publish',
            'meta_input' => array(
                '_msl_affiliate_url' => esc_url_raw($data['affiliate_url']),
                '_msl_description' => sanitize_textarea_field($data['description'] ?? ''),
                '_msl_category' => sanitize_text_field($data['category'] ?? ''),
                '_msl_slug' => $this->generate_unique_slug($data['custom_slug'] ?? ''),
                '_msl_enable_cloaking' => isset($data['enable_cloaking']) ? 1 : 0,
                '_msl_enable_pixel' => isset($data['enable_pixel']) ? 1 : 0,
                '_msl_pixel_code' => wp_kses_post($data['pixel_code'] ?? ''),
                '_msl_redirect_type' => sanitize_text_field($data['redirect_type'] ?? '302'),
                '_msl_created_by' => get_current_user_id(),
                '_msl_site_id' => get_current_blog_id(),
            )
        );
        
        $post_id = wp_insert_post($post_data);
        
        if (is_wp_error($post_id)) {
            return array(
                'success' => false,
                'message' => __('Erro ao criar o link.', 'multisite-super-links')
            );
        }
        
        // Criar entrada nas estatísticas
        $this->create_stats_entry($post_id);
        
        $cloaked_url = msl_get_cloaked_url(get_post_meta($post_id, '_msl_slug', true));
        
        return array(
            'success' => true,
            'message' => __('Link criado com sucesso!', 'multisite-super-links'),
            'link_id' => $post_id,
            'cloaked_url' => $cloaked_url
        );
    }
    
    /**
     * Atualizar um link existente
     */
    public function update_link($link_id, $data) {
        $post = get_post($link_id);
        
        if (!$post || $post->post_type !== 'msl_link') {
            return array(
                'success' => false,
                'message' => __('Link não encontrado.', 'multisite-super-links')
            );
        }
        
        // Verificar permissões
        if (!current_user_can('edit_post', $link_id)) {
            return array(
                'success' => false,
                'message' => __('Permissão negada.', 'multisite-super-links')
            );
        }
        
        $post_data = array(
            'ID' => $link_id,
            'post_title' => sanitize_text_field($data['link_title']),
        );
        
        wp_update_post($post_data);
        
        // Atualizar meta fields
        $meta_fields = array(
            '_msl_affiliate_url' => esc_url_raw($data['affiliate_url']),
            '_msl_description' => sanitize_textarea_field($data['description'] ?? ''),
            '_msl_category' => sanitize_text_field($data['category'] ?? ''),
            '_msl_enable_cloaking' => isset($data['enable_cloaking']) ? 1 : 0,
            '_msl_enable_pixel' => isset($data['enable_pixel']) ? 1 : 0,
            '_msl_pixel_code' => wp_kses_post($data['pixel_code'] ?? ''),
            '_msl_redirect_type' => sanitize_text_field($data['redirect_type'] ?? '302'),
        );
        
        foreach ($meta_fields as $key => $value) {
            update_post_meta($link_id, $key, $value);
        }
        
        return array(
            'success' => true,
            'message' => __('Link atualizado com sucesso!', 'multisite-super-links')
        );
    }
    
    /**
     * Lidar com redirecionamento
     */
    public function handle_redirect($slug) {
        // Buscar o link pelo slug
        $posts = get_posts(array(
            'post_type' => 'msl_link',
            'meta_query' => array(
                array(
                    'key' => '_msl_slug',
                    'value' => $slug,
                    'compare' => '='
                )
            ),
            'posts_per_page' => 1
        ));
        
        if (empty($posts)) {
            wp_redirect(home_url(), 404);
            exit;
        }
        
        $post = $posts[0];
        $affiliate_url = get_post_meta($post->ID, '_msl_affiliate_url', true);
        $enable_cloaking = get_post_meta($post->ID, '_msl_enable_cloaking', true);
        $enable_pixel = get_post_meta($post->ID, '_msl_enable_pixel', true);
        $pixel_code = get_post_meta($post->ID, '_msl_pixel_code', true);
        $redirect_type = get_post_meta($post->ID, '_msl_redirect_type', true) ?: '302';
        
        if (!$affiliate_url) {
            wp_redirect(home_url(), 404);
            exit;
        }
        
        // Registrar clique
        $this->record_click($post->ID);
        
        // Configurar cookie tracking
        $this->set_affiliate_cookie($post->ID, $affiliate_url);
        
        // Se pixel está habilitado, mostrar página intermediária
        if ($enable_pixel && !empty($pixel_code)) {
            $this->show_pixel_bridge($affiliate_url, $pixel_code);
            return;
        }
        
        // Se camuflagem está desabilitada, redirecionar direto
        if (!$enable_cloaking) {
            wp_redirect($affiliate_url, intval($redirect_type));
            exit;
        }
        
        // Mostrar página camuflada
        $this->show_cloaked_page($affiliate_url, $post);
    }
    
    /**
     * Mostrar página com pixel bridge
     */
    private function show_pixel_bridge($affiliate_url, $pixel_code) {
        ?>
        <!DOCTYPE html>
        <html <?php language_attributes(); ?>>
        <head>
            <meta charset="<?php bloginfo('charset'); ?>">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title><?php _e('Redirecionando...', 'multisite-super-links'); ?></title>
            <?php echo $pixel_code; ?>
            <script>
                setTimeout(function() {
                    window.location.href = '<?php echo esc_js($affiliate_url); ?>';
                }, 1000);
            </script>
        </head>
        <body style="margin: 0; padding: 20px; font-family: Arial, sans-serif; text-align: center; background: #f5f5f5;">
            <div style="max-width: 400px; margin: 100px auto; padding: 30px; background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                <h2><?php _e('Redirecionando...', 'multisite-super-links'); ?></h2>
                <p><?php _e('Você será redirecionado em alguns segundos.', 'multisite-super-links'); ?></p>
                <div style="margin: 20px 0;">
                    <div style="width: 100%; height: 4px; background: #eee; border-radius: 2px; overflow: hidden;">
                        <div style="width: 0%; height: 100%; background: #007cba; border-radius: 2px; animation: loading 1s ease-in-out forwards;"></div>
                    </div>
                </div>
                <a href="<?php echo esc_url($affiliate_url); ?>" style="color: #007cba; text-decoration: none;">
                    <?php _e('Clique aqui se não for redirecionado automaticamente', 'multisite-super-links'); ?>
                </a>
            </div>
            <style>
                @keyframes loading {
                    to { width: 100%; }
                }
            </style>
        </body>
        </html>
        <?php
        exit;
    }
    
    /**
     * Mostrar página camuflada
     */
    private function show_cloaked_page($affiliate_url, $post) {
        // Verificar se deve mostrar a página camuflada baseado no user agent
        if ($this->is_bot_or_crawler()) {
            // Para bots, mostrar página alternativa ou homepage
            wp_redirect(home_url());
            exit;
        }
        
        ?>
        <!DOCTYPE html>
        <html <?php language_attributes(); ?>>
        <head>
            <meta charset="<?php bloginfo('charset'); ?>">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title><?php echo esc_html($post->post_title); ?></title>
            <meta name="robots" content="noindex, nofollow">
            <style>
                body { margin: 0; padding: 0; overflow: hidden; }
                #msl-frame { width: 100vw; height: 100vh; border: none; }
                #msl-loading { 
                    position: fixed; top: 0; left: 0; width: 100%; height: 100%; 
                    background: #f5f5f5; display: flex; align-items: center; 
                    justify-content: center; z-index: 9999; 
                }
            </style>
        </head>
        <body>
            <div id="msl-loading">
                <div style="text-align: center;">
                    <h3><?php _e('Carregando...', 'multisite-super-links'); ?></h3>
                    <div style="width: 50px; height: 50px; border: 3px solid #f3f3f3; border-top: 3px solid #007cba; border-radius: 50%; animation: spin 1s linear infinite;"></div>
                </div>
            </div>
            <iframe id="msl-frame" src="<?php echo esc_url($affiliate_url); ?>" onload="document.getElementById('msl-loading').style.display='none';"></iframe>
            <style>
                @keyframes spin {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }
            </style>
            <script>
                // Detectar mudanças de URL no iframe e atualizar a URL principal
                window.addEventListener('message', function(event) {
                    if (event.data && event.data.url) {
                        window.history.replaceState({}, '', event.data.url);
                    }
                });
                
                // Fallback para casos onde o iframe não carrega
                setTimeout(function() {
                    if (document.getElementById('msl-loading').style.display !== 'none') {
                        window.location.href = '<?php echo esc_js($affiliate_url); ?>';
                    }
                }, 10000);
            </script>
        </body>
        </html>
        <?php
        exit;
    }
    
    /**
     * Verificar se é bot ou crawler
     */
    private function is_bot_or_crawler() {
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        $bots = array(
            'googlebot', 'bingbot', 'slurp', 'duckduckbot', 'baiduspider',
            'yandexbot', 'facebookexternalhit', 'twitterbot', 'linkedinbot',
            'whatsapp', 'telegram', 'skype', 'pinterest', 'instagram'
        );
        
        foreach ($bots as $bot) {
            if (stripos($user_agent, $bot) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Configurar cookie de afiliado
     */
    private function set_affiliate_cookie($link_id, $affiliate_url) {
        $cookie_duration = get_option('msl_cookie_duration', 30) * 24 * 60 * 60; // dias para segundos
        $user_hash = $this->generate_user_hash();
        
        // Cookie principal
        setcookie(
            'msl_affiliate_' . $link_id,
            $affiliate_url,
            time() + $cookie_duration,
            '/',
            '',
            is_ssl(),
            true
        );
        
        // Cookie duplo (backup)
        if (get_option('msl_enable_double_cookie', true)) {
            setcookie(
                'msl_backup_' . md5($link_id . $user_hash),
                base64_encode(json_encode(array(
                    'link_id' => $link_id,
                    'affiliate_url' => $affiliate_url,
                    'timestamp' => time()
                ))),
                time() + $cookie_duration,
                '/',
                '',
                is_ssl(),
                true
            );
        }
        
        // Salvar no banco de dados
        global $wpdb;
        $table_cookies = $wpdb->prefix . 'msl_cookie_tracking';
        
        $wpdb->replace(
            $table_cookies,
            array(
                'user_hash' => $user_hash,
                'link_id' => $link_id,
                'site_id' => get_current_blog_id(),
                'affiliate_url' => $affiliate_url,
                'cookie_data' => json_encode($_COOKIE),
                'ip_address' => $this->get_user_ip(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
            ),
            array('%s', '%d', '%d', '%s', '%s', '%s', '%s')
        );
    }
    
    /**
     * Gerar hash único do usuário
     */
    private function generate_user_hash() {
        $ip = $this->get_user_ip();
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        return md5($ip . $user_agent . date('Y-m-d'));
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
     * Registrar clique
     */
    private function record_click($link_id) {
        global $wpdb;
        
        $wpdb->query($wpdb->prepare("
            INSERT INTO {$this->table_name} (link_id, site_id, clicks) 
            VALUES (%d, %d, 1)
            ON DUPLICATE KEY UPDATE 
            clicks = clicks + 1,
            date_updated = CURRENT_TIMESTAMP
        ", $link_id, get_current_blog_id()));
        
        // Também atualizar meta do post
        $current_clicks = get_post_meta($link_id, '_msl_total_clicks', true) ?: 0;
        update_post_meta($link_id, '_msl_total_clicks', $current_clicks + 1);
        update_post_meta($link_id, '_msl_last_click', current_time('mysql'));
    }
    
    /**
     * Criar entrada nas estatísticas
     */
    private function create_stats_entry($link_id) {
        global $wpdb;
        
        $wpdb->insert(
            $this->table_name,
            array(
                'link_id' => $link_id,
                'site_id' => get_current_blog_id(),
                'clicks' => 0,
                'conversions' => 0
            ),
            array('%d', '%d', '%d', '%d')
        );
    }
    
    /**
     * Gerar slug único
     */
    private function generate_unique_slug($custom_slug = '') {
        if (!empty($custom_slug)) {
            $slug = sanitize_title($custom_slug);
        } else {
            $slug = wp_generate_password(8, false);
        }
        
        // Verificar se já existe
        $existing = get_posts(array(
            'post_type' => 'msl_link',
            'meta_query' => array(
                array(
                    'key' => '_msl_slug',
                    'value' => $slug,
                    'compare' => '='
                )
            )
        ));
        
        if (!empty($existing)) {
            $slug = $slug . '-' . wp_generate_password(4, false);
        }
        
        return $slug;
    }
    
    /**
     * Obter todos os links
     */
    public function get_links($args = array()) {
        $defaults = array(
            'posts_per_page' => 20,
            'post_type' => 'msl_link',
            'post_status' => 'publish',
            'orderby' => 'date',
            'order' => 'DESC'
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $links = get_posts($args);
        $result = array();
        
        foreach ($links as $link) {
            $result[] = array(
                'id' => $link->ID,
                'title' => $link->post_title,
                'slug' => get_post_meta($link->ID, '_msl_slug', true),
                'affiliate_url' => get_post_meta($link->ID, '_msl_affiliate_url', true),
                'cloaked_url' => msl_get_cloaked_url(get_post_meta($link->ID, '_msl_slug', true)),
                'clicks' => get_post_meta($link->ID, '_msl_total_clicks', true) ?: 0,
                'category' => get_post_meta($link->ID, '_msl_category', true),
                'description' => get_post_meta($link->ID, '_msl_description', true),
                'created' => $link->post_date,
                'last_click' => get_post_meta($link->ID, '_msl_last_click', true)
            );
        }
        
        return $result;
    }
    
    /**
     * Deletar link
     */
    public function delete_link($link_id) {
        $post = get_post($link_id);
        
        if (!$post || $post->post_type !== 'msl_link') {
            return array(
                'success' => false,
                'message' => __('Link não encontrado.', 'multisite-super-links')
            );
        }
        
        if (!current_user_can('delete_post', $link_id)) {
            return array(
                'success' => false,
                'message' => __('Permissão negada.', 'multisite-super-links')
            );
        }
        
        // Deletar estatísticas
        global $wpdb;
        $wpdb->delete(
            $this->table_name,
            array('link_id' => $link_id),
            array('%d')
        );
        
        // Deletar o post
        wp_delete_post($link_id, true);
        
        return array(
            'success' => true,
            'message' => __('Link deletado com sucesso!', 'multisite-super-links')
        );
    }
    
    /**
     * Exibir links (para shortcode)
     */
    public function display_links($atts) {
        $links = $this->get_links(array(
            'posts_per_page' => $atts['limit'],
            'meta_query' => !empty($atts['category']) ? array(
                array(
                    'key' => '_msl_category',
                    'value' => $atts['category'],
                    'compare' => '='
                )
            ) : array()
        ));
        
        if (empty($links)) {
            return '<p>' . __('Nenhum link encontrado.', 'multisite-super-links') . '</p>';
        }
        
        $columns = max(1, min(6, intval($atts['columns'])));
        $show_stats = filter_var($atts['show_stats'], FILTER_VALIDATE_BOOLEAN);
        
        ob_start();
        ?>
        <div class="msl-links-grid msl-columns-<?php echo $columns; ?>">
            <?php foreach ($links as $link): ?>
                <div class="msl-link-item">
                    <h3><a href="<?php echo esc_url($link['cloaked_url']); ?>" target="_blank"><?php echo esc_html($link['title']); ?></a></h3>
                    <?php if (!empty($link['description'])): ?>
                        <p class="msl-description"><?php echo esc_html($link['description']); ?></p>
                    <?php endif; ?>
                    <?php if ($show_stats): ?>
                        <div class="msl-stats">
                            <small><?php printf(__('Cliques: %d', 'multisite-super-links'), $link['clicks']); ?></small>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}
?>