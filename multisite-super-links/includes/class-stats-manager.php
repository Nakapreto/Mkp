<?php
/**
 * Classe para gerenciar estatísticas dos links
 */

if (!defined('ABSPATH')) {
    exit;
}

class MSL_Stats_Manager {
    
    private $table_stats;
    private $table_tracking;
    
    public function __construct() {
        global $wpdb;
        $this->table_stats = $wpdb->prefix . 'msl_link_stats';
        $this->table_tracking = $wpdb->prefix . 'msl_tracking_events';
    }
    
    /**
     * Atualizar estatísticas de clique
     */
    public function update_click_stats($data) {
        if (!isset($data['link_id']) || !is_numeric($data['link_id'])) {
            return array('success' => false, 'message' => 'ID do link inválido');
        }
        
        $link_id = intval($data['link_id']);
        
        // Verificar se o link existe
        $post = get_post($link_id);
        if (!$post || $post->post_type !== 'msl_link') {
            return array('success' => false, 'message' => 'Link não encontrado');
        }
        
        global $wpdb;
        
        // Atualizar contador de cliques
        $wpdb->query($wpdb->prepare("
            INSERT INTO {$this->table_stats} (link_id, site_id, clicks) 
            VALUES (%d, %d, 1)
            ON DUPLICATE KEY UPDATE 
            clicks = clicks + 1,
            date_updated = CURRENT_TIMESTAMP
        ", $link_id, get_current_blog_id()));
        
        return array('success' => true, 'message' => 'Estatísticas atualizadas');
    }
    
    /**
     * Obter estatísticas de um link específico
     */
    public function get_link_stats($link_id, $date_range = 30) {
        global $wpdb;
        
        $site_id = get_current_blog_id();
        $date_from = date('Y-m-d H:i:s', strtotime("-{$date_range} days"));
        
        // Estatísticas básicas
        $basic_stats = $wpdb->get_row($wpdb->prepare("
            SELECT clicks, conversions, date_created, date_updated
            FROM {$this->table_stats}
            WHERE link_id = %d AND site_id = %d
        ", $link_id, $site_id));
        
        if (!$basic_stats) {
            $basic_stats = (object) array(
                'clicks' => 0,
                'conversions' => 0,
                'date_created' => null,
                'date_updated' => null
            );
        }
        
        // Cliques por dia
        $daily_clicks = $wpdb->get_results($wpdb->prepare("
            SELECT DATE(date_created) as date, COUNT(*) as clicks
            FROM {$this->table_tracking}
            WHERE event_type = 'link_click'
            AND JSON_EXTRACT(event_data, '$.link_id') = %d
            AND site_id = %d
            AND date_created >= %s
            GROUP BY DATE(date_created)
            ORDER BY date ASC
        ", $link_id, $site_id, $date_from));
        
        // Conversões por dia
        $daily_conversions = $wpdb->get_results($wpdb->prepare("
            SELECT DATE(tc.date_created) as date, COUNT(*) as conversions
            FROM {$this->table_tracking} tc
            INNER JOIN {$wpdb->prefix}msl_cookie_tracking ct ON tc.user_hash = ct.user_hash
            WHERE tc.event_type = 'conversion'
            AND ct.link_id = %d
            AND tc.site_id = %d
            AND tc.date_created >= %s
            GROUP BY DATE(tc.date_created)
            ORDER BY date ASC
        ", $link_id, $site_id, $date_from));
        
        // Estatísticas por país (baseado em IP)
        $country_stats = $this->get_country_stats($link_id, $date_range);
        
        // Estatísticas por dispositivo
        $device_stats = $this->get_device_stats($link_id, $date_range);
        
        // Estatísticas por referrer
        $referrer_stats = $this->get_referrer_stats($link_id, $date_range);
        
        // Taxa de conversão
        $conversion_rate = $basic_stats->clicks > 0 ? ($basic_stats->conversions / $basic_stats->clicks) * 100 : 0;
        
        return array(
            'basic' => $basic_stats,
            'conversion_rate' => number_format($conversion_rate, 2),
            'daily_clicks' => $daily_clicks,
            'daily_conversions' => $daily_conversions,
            'country_stats' => $country_stats,
            'device_stats' => $device_stats,
            'referrer_stats' => $referrer_stats
        );
    }
    
    /**
     * Obter estatísticas por país
     */
    private function get_country_stats($link_id, $date_range) {
        global $wpdb;
        
        $site_id = get_current_blog_id();
        $date_from = date('Y-m-d H:i:s', strtotime("-{$date_range} days"));
        
        $results = $wpdb->get_results($wpdb->prepare("
            SELECT 
                CASE 
                    WHEN ip_address LIKE '177.%' THEN 'Brasil'
                    WHEN ip_address LIKE '200.%' THEN 'Brasil'
                    WHEN ip_address LIKE '189.%' THEN 'Brasil'
                    WHEN ip_address LIKE '179.%' THEN 'Brasil'
                    WHEN ip_address LIKE '186.%' THEN 'Brasil'
                    WHEN ip_address LIKE '191.%' THEN 'Brasil'
                    WHEN ip_address LIKE '201.%' THEN 'Brasil'
                    ELSE 'Outros'
                END as country,
                COUNT(*) as clicks
            FROM {$this->table_tracking}
            WHERE event_type = 'link_click'
            AND JSON_EXTRACT(event_data, '$.link_id') = %d
            AND site_id = %d
            AND date_created >= %s
            GROUP BY country
            ORDER BY clicks DESC
        ", $link_id, $site_id, $date_from));
        
        return $results;
    }
    
    /**
     * Obter estatísticas por dispositivo
     */
    private function get_device_stats($link_id, $date_range) {
        global $wpdb;
        
        $site_id = get_current_blog_id();
        $date_from = date('Y-m-d H:i:s', strtotime("-{$date_range} days"));
        
        $results = $wpdb->get_results($wpdb->prepare("
            SELECT 
                CASE 
                    WHEN user_agent LIKE '%%Mobile%%' OR user_agent LIKE '%%Android%%' OR user_agent LIKE '%%iPhone%%' THEN 'Mobile'
                    WHEN user_agent LIKE '%%Tablet%%' OR user_agent LIKE '%%iPad%%' THEN 'Tablet'
                    ELSE 'Desktop'
                END as device_type,
                COUNT(*) as clicks
            FROM {$this->table_tracking}
            WHERE event_type = 'link_click'
            AND JSON_EXTRACT(event_data, '$.link_id') = %d
            AND site_id = %d
            AND date_created >= %s
            GROUP BY device_type
            ORDER BY clicks DESC
        ", $link_id, $site_id, $date_from));
        
        return $results;
    }
    
    /**
     * Obter estatísticas por referrer
     */
    private function get_referrer_stats($link_id, $date_range) {
        global $wpdb;
        
        $site_id = get_current_blog_id();
        $date_from = date('Y-m-d H:i:s', strtotime("-{$date_range} days"));
        
        $results = $wpdb->get_results($wpdb->prepare("
            SELECT 
                CASE 
                    WHEN JSON_EXTRACT(event_data, '$.referrer') LIKE '%%facebook.com%%' THEN 'Facebook'
                    WHEN JSON_EXTRACT(event_data, '$.referrer') LIKE '%%google.com%%' THEN 'Google'
                    WHEN JSON_EXTRACT(event_data, '$.referrer') LIKE '%%youtube.com%%' THEN 'YouTube'
                    WHEN JSON_EXTRACT(event_data, '$.referrer') LIKE '%%instagram.com%%' THEN 'Instagram'
                    WHEN JSON_EXTRACT(event_data, '$.referrer') LIKE '%%whatsapp.com%%' OR JSON_EXTRACT(event_data, '$.referrer') LIKE '%%wa.me%%' THEN 'WhatsApp'
                    WHEN JSON_EXTRACT(event_data, '$.referrer') = '' OR JSON_EXTRACT(event_data, '$.referrer') IS NULL THEN 'Direto'
                    ELSE 'Outros'
                END as referrer_source,
                COUNT(*) as clicks
            FROM {$this->table_tracking}
            WHERE event_type = 'link_click'
            AND JSON_EXTRACT(event_data, '$.link_id') = %d
            AND site_id = %d
            AND date_created >= %s
            GROUP BY referrer_source
            ORDER BY clicks DESC
        ", $link_id, $site_id, $date_from));
        
        return $results;
    }
    
    /**
     * Obter estatísticas gerais do site
     */
    public function get_site_stats($date_range = 30) {
        global $wpdb;
        
        $site_id = get_current_blog_id();
        $date_from = date('Y-m-d H:i:s', strtotime("-{$date_range} days"));
        
        // Total de links
        $total_links = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) 
            FROM {$wpdb->posts} 
            WHERE post_type = 'msl_link' 
            AND post_status = 'publish'
            AND ID IN (
                SELECT DISTINCT post_id 
                FROM {$wpdb->postmeta} 
                WHERE meta_key = '_msl_site_id' 
                AND meta_value = %d
            )
        ", $site_id));
        
        // Total de cliques
        $total_clicks = $wpdb->get_var($wpdb->prepare("
            SELECT SUM(clicks) 
            FROM {$this->table_stats} 
            WHERE site_id = %d
        ", $site_id));
        
        // Total de conversões
        $total_conversions = $wpdb->get_var($wpdb->prepare("
            SELECT SUM(conversions) 
            FROM {$this->table_stats} 
            WHERE site_id = %d
        ", $site_id));
        
        // Cliques no período
        $period_clicks = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) 
            FROM {$this->table_tracking} 
            WHERE event_type = 'link_click' 
            AND site_id = %d 
            AND date_created >= %s
        ", $site_id, $date_from));
        
        // Conversões no período
        $period_conversions = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) 
            FROM {$this->table_tracking} tc
            INNER JOIN {$wpdb->prefix}msl_cookie_tracking ct ON tc.user_hash = ct.user_hash
            WHERE tc.event_type = 'conversion' 
            AND tc.site_id = %d 
            AND tc.date_created >= %s
        ", $site_id, $date_from));
        
        // Top 5 links mais clicados
        $top_links = $wpdb->get_results($wpdb->prepare("
            SELECT 
                p.ID,
                p.post_title,
                s.clicks,
                s.conversions,
                CASE WHEN s.clicks > 0 THEN (s.conversions / s.clicks) * 100 ELSE 0 END as conversion_rate
            FROM {$this->table_stats} s
            INNER JOIN {$wpdb->posts} p ON s.link_id = p.ID
            WHERE s.site_id = %d
            ORDER BY s.clicks DESC
            LIMIT 5
        ", $site_id));
        
        // Cliques por dia nos últimos 30 dias
        $daily_clicks = $wpdb->get_results($wpdb->prepare("
            SELECT 
                DATE(date_created) as date, 
                COUNT(*) as clicks
            FROM {$this->table_tracking}
            WHERE event_type = 'link_click'
            AND site_id = %d
            AND date_created >= %s
            GROUP BY DATE(date_created)
            ORDER BY date ASC
        ", $site_id, $date_from));
        
        $conversion_rate = $total_clicks > 0 ? ($total_conversions / $total_clicks) * 100 : 0;
        
        return array(
            'totals' => array(
                'links' => intval($total_links),
                'clicks' => intval($total_clicks),
                'conversions' => intval($total_conversions),
                'conversion_rate' => number_format($conversion_rate, 2)
            ),
            'period' => array(
                'clicks' => intval($period_clicks),
                'conversions' => intval($period_conversions),
                'conversion_rate' => $period_clicks > 0 ? number_format(($period_conversions / $period_clicks) * 100, 2) : '0.00'
            ),
            'top_links' => $top_links,
            'daily_clicks' => $daily_clicks
        );
    }
    
    /**
     * Obter estatísticas da rede (para network admin)
     */
    public function get_network_stats($date_range = 30) {
        if (!is_multisite()) {
            return $this->get_site_stats($date_range);
        }
        
        global $wpdb;
        
        $date_from = date('Y-m-d H:i:s', strtotime("-{$date_range} days"));
        
        // Total de sites
        $total_sites = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->blogs} WHERE deleted = 0");
        
        // Total de links na rede
        $total_links = $wpdb->get_var("
            SELECT COUNT(*) 
            FROM {$wpdb->posts} 
            WHERE post_type = 'msl_link' 
            AND post_status = 'publish'
        ");
        
        // Total de cliques na rede
        $total_clicks = $wpdb->get_var("SELECT SUM(clicks) FROM {$this->table_stats}");
        
        // Total de conversões na rede
        $total_conversions = $wpdb->get_var("SELECT SUM(conversions) FROM {$this->table_stats}");
        
        // Estatísticas por site
        $sites_stats = $wpdb->get_results("
            SELECT 
                s.site_id,
                b.domain,
                b.path,
                SUM(s.clicks) as total_clicks,
                SUM(s.conversions) as total_conversions,
                COUNT(DISTINCT s.link_id) as total_links
            FROM {$this->table_stats} s
            INNER JOIN {$wpdb->blogs} b ON s.site_id = b.blog_id
            WHERE b.deleted = 0
            GROUP BY s.site_id, b.domain, b.path
            ORDER BY total_clicks DESC
            LIMIT 10
        ");
        
        // Cliques por dia na rede
        $daily_clicks = $wpdb->get_results($wpdb->prepare("
            SELECT 
                DATE(date_created) as date, 
                COUNT(*) as clicks
            FROM {$this->table_tracking}
            WHERE event_type = 'link_click'
            AND date_created >= %s
            GROUP BY DATE(date_created)
            ORDER BY date ASC
        ", $date_from));
        
        $conversion_rate = $total_clicks > 0 ? ($total_conversions / $total_clicks) * 100 : 0;
        
        return array(
            'network_totals' => array(
                'sites' => intval($total_sites),
                'links' => intval($total_links),
                'clicks' => intval($total_clicks),
                'conversions' => intval($total_conversions),
                'conversion_rate' => number_format($conversion_rate, 2)
            ),
            'sites_stats' => $sites_stats,
            'daily_clicks' => $daily_clicks
        );
    }
    
    /**
     * Exportar estatísticas para CSV
     */
    public function export_stats_csv($link_id = null, $date_range = 30) {
        global $wpdb;
        
        $site_id = get_current_blog_id();
        $date_from = date('Y-m-d H:i:s', strtotime("-{$date_range} days"));
        
        $filename = 'msl-stats-' . date('Y-m-d') . '.csv';
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);
        
        $output = fopen('php://output', 'w');
        
        if ($link_id) {
            // Exportar estatísticas de um link específico
            fputcsv($output, array('Data', 'Evento', 'URL', 'Referrer', 'IP', 'User Agent'));
            
            $events = $wpdb->get_results($wpdb->prepare("
                SELECT 
                    date_created,
                    event_type,
                    event_data,
                    ip_address,
                    user_agent
                FROM {$this->table_tracking}
                WHERE JSON_EXTRACT(event_data, '$.link_id') = %d
                AND site_id = %d
                AND date_created >= %s
                ORDER BY date_created DESC
            ", $link_id, $site_id, $date_from));
            
            foreach ($events as $event) {
                $data = json_decode($event->event_data, true);
                fputcsv($output, array(
                    $event->date_created,
                    $event->event_type,
                    $data['url'] ?? '',
                    $data['referrer'] ?? '',
                    $event->ip_address,
                    $event->user_agent
                ));
            }
        } else {
            // Exportar estatísticas gerais
            fputcsv($output, array('Link ID', 'Título', 'Cliques', 'Conversões', 'Taxa de Conversão', 'Última Atualização'));
            
            $links_stats = $wpdb->get_results($wpdb->prepare("
                SELECT 
                    p.ID,
                    p.post_title,
                    s.clicks,
                    s.conversions,
                    s.date_updated,
                    CASE WHEN s.clicks > 0 THEN (s.conversions / s.clicks) * 100 ELSE 0 END as conversion_rate
                FROM {$this->table_stats} s
                INNER JOIN {$wpdb->posts} p ON s.link_id = p.ID
                WHERE s.site_id = %d
                ORDER BY s.clicks DESC
            ", $site_id));
            
            foreach ($links_stats as $link) {
                fputcsv($output, array(
                    $link->ID,
                    $link->post_title,
                    $link->clicks,
                    $link->conversions,
                    number_format($link->conversion_rate, 2) . '%',
                    $link->date_updated
                ));
            }
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Limpar estatísticas antigas
     */
    public function cleanup_old_stats($days = 365) {
        global $wpdb;
        
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        // Limpar eventos de tracking antigos
        $deleted_tracking = $wpdb->query($wpdb->prepare("
            DELETE FROM {$this->table_tracking} 
            WHERE date_created < %s
        ", $cutoff_date));
        
        // Limpar cookies antigos
        $deleted_cookies = $wpdb->query($wpdb->prepare("
            DELETE FROM {$wpdb->prefix}msl_cookie_tracking 
            WHERE date_created < %s
        ", $cutoff_date));
        
        return array(
            'tracking_events_deleted' => $deleted_tracking,
            'cookies_deleted' => $deleted_cookies
        );
    }
    
    /**
     * Obter relatório de performance
     */
    public function get_performance_report($date_range = 30) {
        global $wpdb;
        
        $site_id = get_current_blog_id();
        $date_from = date('Y-m-d H:i:s', strtotime("-{$date_range} days"));
        
        // Links com melhor performance
        $best_performers = $wpdb->get_results($wpdb->prepare("
            SELECT 
                p.ID,
                p.post_title,
                s.clicks,
                s.conversions,
                CASE WHEN s.clicks > 0 THEN (s.conversions / s.clicks) * 100 ELSE 0 END as conversion_rate
            FROM {$this->table_stats} s
            INNER JOIN {$wpdb->posts} p ON s.link_id = p.ID
            WHERE s.site_id = %d
            AND s.clicks > 10
            ORDER BY conversion_rate DESC, s.clicks DESC
            LIMIT 5
        ", $site_id));
        
        // Links com pior performance
        $worst_performers = $wpdb->get_results($wpdb->prepare("
            SELECT 
                p.ID,
                p.post_title,
                s.clicks,
                s.conversions,
                CASE WHEN s.clicks > 0 THEN (s.conversions / s.clicks) * 100 ELSE 0 END as conversion_rate
            FROM {$this->table_stats} s
            INNER JOIN {$wpdb->posts} p ON s.link_id = p.ID
            WHERE s.site_id = %d
            AND s.clicks > 10
            ORDER BY conversion_rate ASC, s.clicks DESC
            LIMIT 5
        ", $site_id));
        
        // Links sem cliques
        $no_clicks = $wpdb->get_results($wpdb->prepare("
            SELECT 
                p.ID,
                p.post_title,
                p.post_date
            FROM {$wpdb->posts} p
            LEFT JOIN {$this->table_stats} s ON p.ID = s.link_id AND s.site_id = %d
            WHERE p.post_type = 'msl_link'
            AND p.post_status = 'publish'
            AND (s.clicks IS NULL OR s.clicks = 0)
            AND p.ID IN (
                SELECT post_id 
                FROM {$wpdb->postmeta} 
                WHERE meta_key = '_msl_site_id' 
                AND meta_value = %d
            )
            ORDER BY p.post_date DESC
            LIMIT 10
        ", $site_id, $site_id));
        
        return array(
            'best_performers' => $best_performers,
            'worst_performers' => $worst_performers,
            'no_clicks' => $no_clicks
        );
    }
}
?>