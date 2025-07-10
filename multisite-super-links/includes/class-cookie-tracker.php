<?php
/**
 * Classe para rastreamento duplo de cookies
 */

if (!defined('ABSPATH')) {
    exit;
}

class MSL_Cookie_Tracker {
    
    private $table_name;
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'msl_cookie_tracking';
        
        // Hooks para capturar conversões
        add_action('wp_footer', array($this, 'output_tracking_script'));
        add_action('wp_ajax_msl_track_conversion', array($this, 'track_conversion'));
        add_action('wp_ajax_nopriv_msl_track_conversion', array($this, 'track_conversion'));
    }
    
    /**
     * Output do script de tracking
     */
    public function output_tracking_script() {
        if (is_admin()) {
            return;
        }
        
        $user_hash = $this->generate_user_hash();
        $site_id = get_current_blog_id();
        
        ?>
        <script type="text/javascript">
        (function() {
            var mslTracker = {
                userHash: '<?php echo esc_js($user_hash); ?>',
                siteId: <?php echo intval($site_id); ?>,
                ajaxUrl: '<?php echo esc_js(admin_url('admin-ajax.php')); ?>',
                nonce: '<?php echo wp_create_nonce('msl_tracking_nonce'); ?>',
                
                init: function() {
                    this.setupEventListeners();
                    this.trackPageView();
                    this.checkForConversions();
                },
                
                setupEventListeners: function() {
                    var self = this;
                    
                    // Rastrear cliques em links
                    document.addEventListener('click', function(e) {
                        var link = e.target.closest('a');
                        if (link && link.href) {
                            self.trackLinkClick(link.href);
                        }
                    });
                    
                    // Rastrear saída da página
                    window.addEventListener('beforeunload', function() {
                        self.trackPageExit();
                    });
                    
                    // Rastrear tempo na página
                    this.startTimeTracking();
                },
                
                trackPageView: function() {
                    this.sendData('page_view', {
                        url: window.location.href,
                        referrer: document.referrer,
                        timestamp: Date.now()
                    });
                },
                
                trackLinkClick: function(url) {
                    this.sendData('link_click', {
                        url: url,
                        page_url: window.location.href,
                        timestamp: Date.now()
                    });
                },
                
                trackPageExit: function() {
                    var timeOnPage = Date.now() - this.pageStartTime;
                    this.sendData('page_exit', {
                        url: window.location.href,
                        time_on_page: timeOnPage,
                        timestamp: Date.now()
                    });
                },
                
                startTimeTracking: function() {
                    this.pageStartTime = Date.now();
                    
                    // Enviar update de tempo a cada 30 segundos
                    setInterval(function() {
                        this.sendData('time_update', {
                            url: window.location.href,
                            time_elapsed: Date.now() - this.pageStartTime,
                            timestamp: Date.now()
                        });
                    }.bind(this), 30000);
                },
                
                checkForConversions: function() {
                    // Verificar se há parâmetros de conversão na URL
                    var urlParams = new URLSearchParams(window.location.search);
                    
                    if (urlParams.get('msl_conversion') || urlParams.get('success') || urlParams.get('confirmed')) {
                        this.trackConversion();
                    }
                    
                    // Verificar localStorage para conversões offline
                    var pendingConversions = localStorage.getItem('msl_pending_conversions');
                    if (pendingConversions) {
                        try {
                            var conversions = JSON.parse(pendingConversions);
                            for (var i = 0; i < conversions.length; i++) {
                                this.trackConversion(conversions[i]);
                            }
                            localStorage.removeItem('msl_pending_conversions');
                        } catch (e) {
                            console.error('MSL: Erro ao processar conversões pendentes', e);
                        }
                    }
                },
                
                trackConversion: function(data) {
                    data = data || {};
                    
                    this.sendData('conversion', {
                        url: window.location.href,
                        referrer: document.referrer,
                        value: data.value || 0,
                        currency: data.currency || 'BRL',
                        transaction_id: data.transaction_id || '',
                        timestamp: Date.now()
                    });
                },
                
                sendData: function(action, data) {
                    var self = this;
                    
                    // Preparar dados
                    var postData = {
                        action: 'msl_track_conversion',
                        security: this.nonce,
                        user_hash: this.userHash,
                        site_id: this.siteId,
                        track_action: action,
                        track_data: JSON.stringify(data)
                    };
                    
                    // Tentar enviar via fetch primeiro
                    if (typeof fetch !== 'undefined') {
                        fetch(this.ajaxUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: this.serializeData(postData)
                        }).catch(function(error) {
                            // Fallback para XMLHttpRequest
                            self.sendDataXHR(postData);
                        });
                    } else {
                        this.sendDataXHR(postData);
                    }
                },
                
                sendDataXHR: function(postData) {
                    var xhr = new XMLHttpRequest();
                    xhr.open('POST', this.ajaxUrl, true);
                    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                    xhr.send(this.serializeData(postData));
                },
                
                serializeData: function(data) {
                    var pairs = [];
                    for (var key in data) {
                        if (data.hasOwnProperty(key)) {
                            pairs.push(encodeURIComponent(key) + '=' + encodeURIComponent(data[key]));
                        }
                    }
                    return pairs.join('&');
                }
            };
            
            // Inicializar quando o DOM estiver pronto
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', function() {
                    mslTracker.init();
                });
            } else {
                mslTracker.init();
            }
            
            // Expor globalmente para uso manual
            window.MSLTracker = mslTracker;
        })();
        </script>
        <?php
    }
    
    /**
     * Processar dados de tracking via AJAX
     */
    public function track_conversion() {
        // Verificar nonce
        if (!wp_verify_nonce($_POST['security'] ?? '', 'msl_tracking_nonce')) {
            wp_die('Nonce inválido');
        }
        
        $user_hash = sanitize_text_field($_POST['user_hash'] ?? '');
        $site_id = intval($_POST['site_id'] ?? 0);
        $track_action = sanitize_text_field($_POST['track_action'] ?? '');
        $track_data = json_decode(stripslashes($_POST['track_data'] ?? '{}'), true);
        
        if (empty($user_hash) || empty($track_action)) {
            wp_send_json_error('Dados inválidos');
        }
        
        // Processar diferentes tipos de ação
        switch ($track_action) {
            case 'page_view':
                $this->record_page_view($user_hash, $site_id, $track_data);
                break;
                
            case 'link_click':
                $this->record_link_click($user_hash, $site_id, $track_data);
                break;
                
            case 'conversion':
                $this->record_conversion($user_hash, $site_id, $track_data);
                break;
                
            case 'page_exit':
                $this->record_page_exit($user_hash, $site_id, $track_data);
                break;
                
            case 'time_update':
                $this->update_time_on_page($user_hash, $site_id, $track_data);
                break;
        }
        
        wp_send_json_success('Dados registrados');
    }
    
    /**
     * Registrar visualização de página
     */
    private function record_page_view($user_hash, $site_id, $data) {
        global $wpdb;
        
        $table_tracking = $wpdb->prefix . 'msl_tracking_events';
        
        $wpdb->insert(
            $table_tracking,
            array(
                'user_hash' => $user_hash,
                'site_id' => $site_id,
                'event_type' => 'page_view',
                'event_data' => json_encode($data),
                'ip_address' => $this->get_user_ip(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'date_created' => current_time('mysql')
            ),
            array('%s', '%d', '%s', '%s', '%s', '%s', '%s')
        );
        
        // Verificar se há cookies de afiliado para este usuário
        $this->check_affiliate_cookies($user_hash, $site_id, $data);
    }
    
    /**
     * Registrar clique em link
     */
    private function record_link_click($user_hash, $site_id, $data) {
        global $wpdb;
        
        $table_tracking = $wpdb->prefix . 'msl_tracking_events';
        
        $wpdb->insert(
            $table_tracking,
            array(
                'user_hash' => $user_hash,
                'site_id' => $site_id,
                'event_type' => 'link_click',
                'event_data' => json_encode($data),
                'ip_address' => $this->get_user_ip(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'date_created' => current_time('mysql')
            ),
            array('%s', '%d', '%s', '%s', '%s', '%s', '%s')
        );
    }
    
    /**
     * Registrar conversão
     */
    private function record_conversion($user_hash, $site_id, $data) {
        global $wpdb;
        
        // Verificar se há links de afiliado associados a este usuário
        $affiliate_cookies = $wpdb->get_results($wpdb->prepare("
            SELECT * FROM {$this->table_name} 
            WHERE user_hash = %s AND site_id = %d
            ORDER BY date_created DESC
        ", $user_hash, $site_id));
        
        if (!empty($affiliate_cookies)) {
            foreach ($affiliate_cookies as $cookie) {
                // Atualizar estatísticas de conversão
                $this->update_conversion_stats($cookie->link_id, $data);
            }
        }
        
        // Registrar o evento de conversão
        $table_tracking = $wpdb->prefix . 'msl_tracking_events';
        
        $wpdb->insert(
            $table_tracking,
            array(
                'user_hash' => $user_hash,
                'site_id' => $site_id,
                'event_type' => 'conversion',
                'event_data' => json_encode($data),
                'ip_address' => $this->get_user_ip(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'date_created' => current_time('mysql')
            ),
            array('%s', '%d', '%s', '%s', '%s', '%s', '%s')
        );
    }
    
    /**
     * Atualizar estatísticas de conversão
     */
    private function update_conversion_stats($link_id, $conversion_data) {
        global $wpdb;
        
        $table_stats = $wpdb->prefix . 'msl_link_stats';
        
        // Atualizar contador de conversões
        $wpdb->query($wpdb->prepare("
            UPDATE {$table_stats} 
            SET conversions = conversions + 1,
                date_updated = CURRENT_TIMESTAMP
            WHERE link_id = %d
        ", $link_id));
        
        // Atualizar meta do post
        $current_conversions = get_post_meta($link_id, '_msl_total_conversions', true) ?: 0;
        update_post_meta($link_id, '_msl_total_conversions', $current_conversions + 1);
        update_post_meta($link_id, '_msl_last_conversion', current_time('mysql'));
        
        // Se há valor da conversão, salvar também
        if (isset($conversion_data['value']) && $conversion_data['value'] > 0) {
            $current_revenue = get_post_meta($link_id, '_msl_total_revenue', true) ?: 0;
            update_post_meta($link_id, '_msl_total_revenue', $current_revenue + floatval($conversion_data['value']));
        }
        
        // Disparar hook para integrações
        do_action('msl_conversion_tracked', $link_id, $conversion_data);
    }
    
    /**
     * Verificar cookies de afiliado
     */
    private function check_affiliate_cookies($user_hash, $site_id, $data) {
        // Verificar se há cookies MSL no navegador
        foreach ($_COOKIE as $key => $value) {
            if (strpos($key, 'msl_affiliate_') === 0) {
                $link_id = str_replace('msl_affiliate_', '', $key);
                $this->associate_user_with_link($user_hash, $site_id, $link_id, $value);
            }
            
            if (strpos($key, 'msl_backup_') === 0) {
                $backup_data = json_decode(base64_decode($value), true);
                if (is_array($backup_data) && isset($backup_data['link_id'])) {
                    $this->associate_user_with_link($user_hash, $site_id, $backup_data['link_id'], $backup_data['affiliate_url']);
                }
            }
        }
    }
    
    /**
     * Associar usuário com link de afiliado
     */
    private function associate_user_with_link($user_hash, $site_id, $link_id, $affiliate_url) {
        global $wpdb;
        
        $wpdb->replace(
            $this->table_name,
            array(
                'user_hash' => $user_hash,
                'link_id' => intval($link_id),
                'site_id' => $site_id,
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
        $session_id = session_id() ?: '';
        
        return md5($ip . $user_agent . $session_id . date('Y-m-d'));
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
     * Obter estatísticas de cookie para um usuário
     */
    public function get_user_affiliate_data($user_hash, $site_id = null) {
        global $wpdb;
        
        $where_site = $site_id ? $wpdb->prepare(' AND site_id = %d', $site_id) : '';
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT * FROM {$this->table_name} 
            WHERE user_hash = %s {$where_site}
            ORDER BY date_created DESC
        ", $user_hash));
    }
    
    /**
     * Limpar cookies expirados
     */
    public function cleanup_expired_cookies() {
        global $wpdb;
        
        $cookie_duration = get_option('msl_cookie_duration', 30);
        $expiry_date = date('Y-m-d H:i:s', strtotime("-{$cookie_duration} days"));
        
        $wpdb->query($wpdb->prepare("
            DELETE FROM {$this->table_name} 
            WHERE date_created < %s
        ", $expiry_date));
        
        // Também limpar eventos de tracking antigos (manter apenas 90 dias)
        $table_tracking = $wpdb->prefix . 'msl_tracking_events';
        $tracking_expiry = date('Y-m-d H:i:s', strtotime('-90 days'));
        
        $wpdb->query($wpdb->prepare("
            DELETE FROM {$table_tracking} 
            WHERE date_created < %s
        ", $tracking_expiry));
    }
    
    /**
     * Registrar saída da página
     */
    private function record_page_exit($user_hash, $site_id, $data) {
        global $wpdb;
        
        $table_tracking = $wpdb->prefix . 'msl_tracking_events';
        
        $wpdb->insert(
            $table_tracking,
            array(
                'user_hash' => $user_hash,
                'site_id' => $site_id,
                'event_type' => 'page_exit',
                'event_data' => json_encode($data),
                'ip_address' => $this->get_user_ip(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'date_created' => current_time('mysql')
            ),
            array('%s', '%d', '%s', '%s', '%s', '%s', '%s')
        );
    }
    
    /**
     * Atualizar tempo na página
     */
    private function update_time_on_page($user_hash, $site_id, $data) {
        global $wpdb;
        
        $table_tracking = $wpdb->prefix . 'msl_tracking_events';
        
        // Atualizar ou inserir registro de tempo na página
        $existing = $wpdb->get_row($wpdb->prepare("
            SELECT id FROM {$table_tracking} 
            WHERE user_hash = %s AND site_id = %d AND event_type = 'time_tracking'
            AND JSON_EXTRACT(event_data, '$.url') = %s
            ORDER BY date_created DESC LIMIT 1
        ", $user_hash, $site_id, $data['url']));
        
        if ($existing) {
            $wpdb->update(
                $table_tracking,
                array(
                    'event_data' => json_encode($data),
                    'date_updated' => current_time('mysql')
                ),
                array('id' => $existing->id),
                array('%s', '%s'),
                array('%d')
            );
        } else {
            $wpdb->insert(
                $table_tracking,
                array(
                    'user_hash' => $user_hash,
                    'site_id' => $site_id,
                    'event_type' => 'time_tracking',
                    'event_data' => json_encode($data),
                    'ip_address' => $this->get_user_ip(),
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                    'date_created' => current_time('mysql')
                ),
                array('%s', '%d', '%s', '%s', '%s', '%s', '%s')
            );
        }
    }
}
?>