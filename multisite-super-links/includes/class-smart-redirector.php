<?php
/**
 * Classe para redirecionamento inteligente
 */

if (!defined('ABSPATH')) {
    exit;
}

class MSL_Smart_Redirector {
    
    public function __construct() {
        // Hooks para redirecionamento
        add_action('template_redirect', array($this, 'handle_exit_redirect'), 5);
        add_action('wp_footer', array($this, 'output_exit_redirect_script'));
        add_action('wp_ajax_msl_log_exit_intent', array($this, 'log_exit_intent'));
        add_action('wp_ajax_nopriv_msl_log_exit_intent', array($this, 'log_exit_intent'));
    }
    
    /**
     * Lidar com redirecionamento de saída
     */
    public function handle_exit_redirect() {
        // Verificar se deve mostrar redirect de saída
        if (is_admin() || is_feed() || is_robots() || is_trackback()) {
            return;
        }
        
        // Verificar se há configuração de redirect para esta página/site
        $redirect_config = $this->get_redirect_config();
        
        if (!$redirect_config || !$redirect_config['enabled']) {
            return;
        }
        
        // Verificar se é uma visita de retorno (já viu o redirect)
        if (isset($_COOKIE['msl_redirect_shown'])) {
            return;
        }
        
        // Verificar condições de exibição
        if (!$this->should_show_redirect($redirect_config)) {
            return;
        }
        
        // Armazenar configuração para uso no JavaScript
        wp_localize_script('msl-frontend-js', 'msl_redirect_config', $redirect_config);
    }
    
    /**
     * Output do script de redirecionamento de saída
     */
    public function output_exit_redirect_script() {
        if (is_admin()) {
            return;
        }
        
        $redirect_config = $this->get_redirect_config();
        
        if (!$redirect_config || !$redirect_config['enabled']) {
            return;
        }
        
        // Verificar se já mostrou o redirect
        if (isset($_COOKIE['msl_redirect_shown'])) {
            return;
        }
        
        ?>
        <script type="text/javascript">
        (function() {
            var exitRedirect = {
                config: <?php echo json_encode($redirect_config); ?>,
                hasTriggered: false,
                mouseOutCount: 0,
                
                init: function() {
                    this.setupExitIntent();
                    this.setupBackButton();
                    this.setupTimeDelay();
                    this.setupScrollTrigger();
                },
                
                setupExitIntent: function() {
                    var self = this;
                    
                    document.addEventListener('mouseleave', function(e) {
                        if (e.clientY <= 0) {
                            self.mouseOutCount++;
                            if (self.mouseOutCount >= 2 && !self.hasTriggered) {
                                self.showExitRedirect('mouse_exit');
                            }
                        }
                    });
                },
                
                setupBackButton: function() {
                    var self = this;
                    
                    // Detectar tentativa de voltar
                    history.pushState(null, null, location.href);
                    window.addEventListener('popstate', function() {
                        if (!self.hasTriggered) {
                            self.showExitRedirect('back_button');
                        }
                        history.pushState(null, null, location.href);
                    });
                },
                
                setupTimeDelay: function() {
                    var self = this;
                    
                    if (this.config.time_trigger && this.config.time_trigger > 0) {
                        setTimeout(function() {
                            if (!self.hasTriggered) {
                                self.showExitRedirect('time_delay');
                            }
                        }, this.config.time_trigger * 1000);
                    }
                },
                
                setupScrollTrigger: function() {
                    var self = this;
                    
                    if (this.config.scroll_trigger && this.config.scroll_trigger > 0) {
                        window.addEventListener('scroll', function() {
                            var scrollPercent = (window.scrollY / (document.body.scrollHeight - window.innerHeight)) * 100;
                            
                            if (scrollPercent >= self.config.scroll_trigger && !self.hasTriggered) {
                                self.showExitRedirect('scroll_trigger');
                            }
                        });
                    }
                },
                
                showExitRedirect: function(trigger) {
                    if (this.hasTriggered) {
                        return;
                    }
                    
                    this.hasTriggered = true;
                    
                    // Log do evento
                    this.logExitIntent(trigger);
                    
                    // Definir cookie para não mostrar novamente
                    this.setCookie('msl_redirect_shown', '1', this.config.cookie_duration || 7);
                    
                    // Mostrar modal ou redirecionar direto
                    if (this.config.show_modal) {
                        this.showModal();
                    } else {
                        this.redirectNow();
                    }
                },
                
                showModal: function() {
                    var self = this;
                    
                    // Criar overlay
                    var overlay = document.createElement('div');
                    overlay.id = 'msl-exit-overlay';
                    overlay.style.cssText = `
                        position: fixed;
                        top: 0;
                        left: 0;
                        width: 100%;
                        height: 100%;
                        background: rgba(0,0,0,0.8);
                        z-index: 999999;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                    `;
                    
                    // Criar modal
                    var modal = document.createElement('div');
                    modal.style.cssText = `
                        background: white;
                        padding: 30px;
                        border-radius: 10px;
                        max-width: 500px;
                        text-align: center;
                        position: relative;
                        animation: mslFadeIn 0.3s ease-in-out;
                    `;
                    
                    modal.innerHTML = `
                        <button id="msl-close-modal" style="position: absolute; top: 10px; right: 15px; background: none; border: none; font-size: 24px; cursor: pointer;">&times;</button>
                        <h2 style="margin-top: 0; color: #333;">` + (this.config.modal_title || 'Espere!') + `</h2>
                        <p style="color: #666; margin: 20px 0;">` + (this.config.modal_message || 'Antes de sair, que tal conhecer esta oferta especial?') + `</p>
                        <div style="margin-top: 25px;">
                            <button id="msl-redirect-yes" style="background: #007cba; color: white; border: none; padding: 12px 25px; margin: 0 10px; border-radius: 5px; cursor: pointer; font-size: 16px;">` + (this.config.yes_button || 'Sim, quero ver!') + `</button>
                            <button id="msl-redirect-no" style="background: #ccc; color: #333; border: none; padding: 12px 25px; margin: 0 10px; border-radius: 5px; cursor: pointer; font-size: 16px;">` + (this.config.no_button || 'Não, obrigado') + `</button>
                        </div>
                    `;
                    
                    overlay.appendChild(modal);
                    document.body.appendChild(overlay);
                    
                    // Adicionar CSS de animação
                    if (!document.getElementById('msl-modal-css')) {
                        var css = document.createElement('style');
                        css.id = 'msl-modal-css';
                        css.textContent = `
                            @keyframes mslFadeIn {
                                from { opacity: 0; transform: scale(0.8); }
                                to { opacity: 1; transform: scale(1); }
                            }
                        `;
                        document.head.appendChild(css);
                    }
                    
                    // Event listeners
                    document.getElementById('msl-redirect-yes').addEventListener('click', function() {
                        self.redirectNow();
                    });
                    
                    document.getElementById('msl-redirect-no').addEventListener('click', function() {
                        self.closeModal();
                    });
                    
                    document.getElementById('msl-close-modal').addEventListener('click', function() {
                        self.closeModal();
                    });
                    
                    // Fechar com ESC
                    document.addEventListener('keydown', function(e) {
                        if (e.key === 'Escape') {
                            self.closeModal();
                        }
                    });
                    
                    // Auto-close após tempo limite
                    if (this.config.auto_close_time && this.config.auto_close_time > 0) {
                        setTimeout(function() {
                            if (document.getElementById('msl-exit-overlay')) {
                                self.redirectNow();
                            }
                        }, this.config.auto_close_time * 1000);
                    }
                },
                
                closeModal: function() {
                    var overlay = document.getElementById('msl-exit-overlay');
                    if (overlay) {
                        overlay.remove();
                    }
                },
                
                redirectNow: function() {
                    var url = this.config.redirect_url;
                    
                    // Substituir tokens na URL
                    url = url.replace('{current_url}', encodeURIComponent(window.location.href));
                    url = url.replace('{referrer}', encodeURIComponent(document.referrer));
                    url = url.replace('{timestamp}', Date.now());
                    
                    // Fechar modal se estiver aberto
                    this.closeModal();
                    
                    // Redirecionar
                    if (this.config.redirect_type === 'new_tab') {
                        window.open(url, '_blank');
                    } else {
                        window.location.href = url;
                    }
                },
                
                logExitIntent: function(trigger) {
                    if (typeof MSLTracker !== 'undefined') {
                        MSLTracker.sendData('exit_intent', {
                            trigger: trigger,
                            url: window.location.href,
                            timestamp: Date.now()
                        });
                    }
                },
                
                setCookie: function(name, value, days) {
                    var expires = "";
                    if (days) {
                        var date = new Date();
                        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
                        expires = "; expires=" + date.toUTCString();
                    }
                    document.cookie = name + "=" + value + expires + "; path=/";
                }
            };
            
            // Inicializar quando o DOM estiver pronto
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', function() {
                    exitRedirect.init();
                });
            } else {
                exitRedirect.init();
            }
        })();
        </script>
        <?php
    }
    
    /**
     * Obter configuração de redirecionamento
     */
    private function get_redirect_config() {
        // Verificar configuração específica da página
        $page_config = get_post_meta(get_the_ID(), '_msl_exit_redirect', true);
        
        if ($page_config && $page_config['enabled']) {
            return $page_config;
        }
        
        // Configuração global do site
        $global_config = get_option('msl_exit_redirect_config', array());
        
        if (!empty($global_config) && isset($global_config['enabled']) && $global_config['enabled']) {
            return $global_config;
        }
        
        return false;
    }
    
    /**
     * Verificar se deve mostrar o redirect
     */
    private function should_show_redirect($config) {
        // Verificar tipos de página permitidos
        if (isset($config['page_types']) && is_array($config['page_types'])) {
            $current_type = $this->get_current_page_type();
            if (!in_array($current_type, $config['page_types'])) {
                return false;
            }
        }
        
        // Verificar se é mobile e está configurado para desktop only
        if (isset($config['desktop_only']) && $config['desktop_only'] && wp_is_mobile()) {
            return false;
        }
        
        // Verificar se é primeira visita apenas
        if (isset($config['first_visit_only']) && $config['first_visit_only']) {
            if (isset($_COOKIE['msl_returning_visitor'])) {
                return false;
            } else {
                setcookie('msl_returning_visitor', '1', time() + (30 * 24 * 60 * 60), '/');
            }
        }
        
        // Verificar referrer
        if (isset($config['referrer_filter']) && !empty($config['referrer_filter'])) {
            $referrer = $_SERVER['HTTP_REFERER'] ?? '';
            $allowed_referrers = explode(',', $config['referrer_filter']);
            $match_found = false;
            
            foreach ($allowed_referrers as $allowed) {
                if (stripos($referrer, trim($allowed)) !== false) {
                    $match_found = true;
                    break;
                }
            }
            
            if (!$match_found && !empty($referrer)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Obter tipo da página atual
     */
    private function get_current_page_type() {
        if (is_home() || is_front_page()) {
            return 'home';
        } elseif (is_single()) {
            return 'post';
        } elseif (is_page()) {
            return 'page';
        } elseif (is_category()) {
            return 'category';
        } elseif (is_archive()) {
            return 'archive';
        } else {
            return 'other';
        }
    }
    
    /**
     * Log de intenção de saída via AJAX
     */
    public function log_exit_intent() {
        if (!wp_verify_nonce($_POST['security'] ?? '', 'msl_frontend_nonce')) {
            wp_die('Nonce inválido');
        }
        
        $trigger = sanitize_text_field($_POST['trigger'] ?? '');
        $url = esc_url_raw($_POST['url'] ?? '');
        
        // Registrar evento
        global $wpdb;
        $table_tracking = $wpdb->prefix . 'msl_tracking_events';
        
        $wpdb->insert(
            $table_tracking,
            array(
                'user_hash' => $this->generate_user_hash(),
                'site_id' => get_current_blog_id(),
                'event_type' => 'exit_intent',
                'event_data' => json_encode(array(
                    'trigger' => $trigger,
                    'url' => $url,
                    'timestamp' => time()
                )),
                'ip_address' => $this->get_user_ip(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'date_created' => current_time('mysql')
            ),
            array('%s', '%d', '%s', '%s', '%s', '%s', '%s')
        );
        
        wp_send_json_success('Intenção de saída registrada');
    }
    
    /**
     * Gerar hash do usuário
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
     * Configurar redirecionamento para página específica
     */
    public function set_page_redirect($post_id, $config) {
        return update_post_meta($post_id, '_msl_exit_redirect', $config);
    }
    
    /**
     * Obter estatísticas de redirecionamento
     */
    public function get_redirect_stats($date_range = 30) {
        global $wpdb;
        
        $site_id = get_current_blog_id();
        $date_from = date('Y-m-d H:i:s', strtotime("-{$date_range} days"));
        $table_tracking = $wpdb->prefix . 'msl_tracking_events';
        
        // Total de intenções de saída
        $total_exits = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) 
            FROM {$table_tracking} 
            WHERE event_type = 'exit_intent' 
            AND site_id = %d 
            AND date_created >= %s
        ", $site_id, $date_from));
        
        // Por trigger
        $by_trigger = $wpdb->get_results($wpdb->prepare("
            SELECT 
                JSON_EXTRACT(event_data, '$.trigger') as trigger,
                COUNT(*) as count
            FROM {$table_tracking} 
            WHERE event_type = 'exit_intent' 
            AND site_id = %d 
            AND date_created >= %s
            GROUP BY trigger
            ORDER BY count DESC
        ", $site_id, $date_from));
        
        // Por dia
        $by_day = $wpdb->get_results($wpdb->prepare("
            SELECT 
                DATE(date_created) as date,
                COUNT(*) as exits
            FROM {$table_tracking} 
            WHERE event_type = 'exit_intent' 
            AND site_id = %d 
            AND date_created >= %s
            GROUP BY DATE(date_created)
            ORDER BY date ASC
        ", $site_id, $date_from));
        
        // Top páginas com mais saídas
        $top_pages = $wpdb->get_results($wpdb->prepare("
            SELECT 
                JSON_EXTRACT(event_data, '$.url') as url,
                COUNT(*) as exits
            FROM {$table_tracking} 
            WHERE event_type = 'exit_intent' 
            AND site_id = %d 
            AND date_created >= %s
            GROUP BY url
            ORDER BY exits DESC
            LIMIT 10
        ", $site_id, $date_from));
        
        return array(
            'total_exits' => $total_exits,
            'by_trigger' => $by_trigger,
            'by_day' => $by_day,
            'top_pages' => $top_pages
        );
    }
    
    /**
     * Teste A/B para redirecionamentos
     */
    public function setup_ab_test($test_config) {
        $test_id = wp_generate_password(8, false);
        
        update_option('msl_redirect_ab_test_' . $test_id, array(
            'config' => $test_config,
            'start_date' => current_time('mysql'),
            'status' => 'active',
            'results' => array(
                'variant_a' => array('views' => 0, 'conversions' => 0),
                'variant_b' => array('views' => 0, 'conversions' => 0)
            )
        ));
        
        return $test_id;
    }
    
    /**
     * Obter configuração para teste A/B
     */
    public function get_ab_test_config() {
        $active_tests = get_option('msl_active_redirect_tests', array());
        
        if (empty($active_tests)) {
            return false;
        }
        
        $test_id = $active_tests[array_rand($active_tests)];
        $test_data = get_option('msl_redirect_ab_test_' . $test_id);
        
        if (!$test_data || $test_data['status'] !== 'active') {
            return false;
        }
        
        // Determinar variante (50/50)
        $variant = (rand(1, 100) <= 50) ? 'a' : 'b';
        
        // Incrementar visualizações
        $test_data['results']['variant_' . $variant]['views']++;
        update_option('msl_redirect_ab_test_' . $test_id, $test_data);
        
        // Retornar configuração da variante
        return array(
            'test_id' => $test_id,
            'variant' => $variant,
            'config' => $test_data['config']['variant_' . $variant]
        );
    }
}
?>