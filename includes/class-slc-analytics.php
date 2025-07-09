<?php

if (!defined('ABSPATH')) {
    exit;
}

class SLC_Analytics {
    
    public function __construct() {
        // Constructor
    }
    
    public function track_click($link_id, $user_ip = '', $user_agent = '', $referer = '') {
        global $wpdb;
        
        if (empty($user_ip)) {
            $user_ip = $this->get_user_ip();
        }
        
        if (empty($user_agent)) {
            $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        }
        
        if (empty($referer)) {
            $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
        }
        
        // Check if this is a unique click
        $is_unique = $this->is_unique_click($link_id, $user_ip);
        
        // Get additional info
        $country = $this->get_country_from_ip($user_ip);
        $device_info = $this->parse_user_agent($user_agent);
        
        // Insert analytics record
        $result = $wpdb->insert(
            $wpdb->prefix . 'slc_analytics',
            array(
                'link_id' => $link_id,
                'ip_address' => $user_ip,
                'user_agent' => $user_agent,
                'referer' => $referer,
                'country' => $country,
                'device' => $device_info['device'],
                'browser' => $device_info['browser'],
                'os' => $device_info['os'],
                'is_unique' => $is_unique ? 1 : 0,
                'clicked_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s')
        );
        
        if ($result) {
            // Update link click counts
            $link_manager = new SLC_Link_Manager();
            $link_manager->increment_clicks($link_id, $is_unique);
        }
        
        return $result !== false;
    }
    
    private function get_user_ip() {
        // Check for various proxy headers
        $ip_headers = array(
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_CLIENT_IP',            // Proxy
            'HTTP_X_FORWARDED_FOR',      // Load balancer/proxy
            'HTTP_X_FORWARDED',          // Proxy
            'HTTP_X_CLUSTER_CLIENT_IP',  // Cluster
            'HTTP_FORWARDED_FOR',        // Proxy
            'HTTP_FORWARDED',            // Proxy
            'REMOTE_ADDR'                // Standard
        );
        
        foreach ($ip_headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                
                // Handle comma-separated list (from proxy chains)
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                
                // Validate IP
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
    }
    
    private function is_unique_click($link_id, $user_ip) {
        global $wpdb;
        
        // Check if this IP has clicked this link in the last 24 hours
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}slc_analytics 
             WHERE link_id = %d AND ip_address = %s 
             AND clicked_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
             LIMIT 1",
            $link_id,
            $user_ip
        ));
        
        return !$existing;
    }
    
    private function get_country_from_ip($ip) {
        // For a production plugin, you'd want to use a GeoIP service
        // This is a simplified version
        
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            // In production, use a service like:
            // - MaxMind GeoIP2
            // - IPStack
            // - IP2Location
            // For now, return empty
            return '';
        }
        
        return '';
    }
    
    private function parse_user_agent($user_agent) {
        $device_info = array(
            'device' => 'Desktop',
            'browser' => 'Unknown',
            'os' => 'Unknown'
        );
        
        if (empty($user_agent)) {
            return $device_info;
        }
        
        // Detect device type
        if (preg_match('/Mobile|Android|iPhone|iPad|iPod|BlackBerry|Windows Phone/i', $user_agent)) {
            if (preg_match('/iPad/i', $user_agent)) {
                $device_info['device'] = 'Tablet';
            } else {
                $device_info['device'] = 'Mobile';
            }
        }
        
        // Detect browser
        if (preg_match('/Chrome\/([0-9.]+)/i', $user_agent, $matches)) {
            $device_info['browser'] = 'Chrome';
        } elseif (preg_match('/Firefox\/([0-9.]+)/i', $user_agent, $matches)) {
            $device_info['browser'] = 'Firefox';
        } elseif (preg_match('/Safari\/([0-9.]+)/i', $user_agent, $matches)) {
            if (preg_match('/Chrome/i', $user_agent)) {
                // Chrome also contains Safari in user agent
                $device_info['browser'] = 'Chrome';
            } else {
                $device_info['browser'] = 'Safari';
            }
        } elseif (preg_match('/Edge\/([0-9.]+)/i', $user_agent, $matches)) {
            $device_info['browser'] = 'Edge';
        } elseif (preg_match('/MSIE ([0-9.]+)/i', $user_agent, $matches)) {
            $device_info['browser'] = 'Internet Explorer';
        }
        
        // Detect operating system
        if (preg_match('/Windows NT ([0-9.]+)/i', $user_agent, $matches)) {
            $device_info['os'] = 'Windows';
        } elseif (preg_match('/Mac OS X ([0-9._]+)/i', $user_agent, $matches)) {
            $device_info['os'] = 'macOS';
        } elseif (preg_match('/Linux/i', $user_agent)) {
            $device_info['os'] = 'Linux';
        } elseif (preg_match('/Android ([0-9.]+)/i', $user_agent, $matches)) {
            $device_info['os'] = 'Android';
        } elseif (preg_match('/iPhone OS ([0-9._]+)/i', $user_agent, $matches)) {
            $device_info['os'] = 'iOS';
        } elseif (preg_match('/iPad.*OS ([0-9._]+)/i', $user_agent, $matches)) {
            $device_info['os'] = 'iOS';
        }
        
        return $device_info;
    }
    
    public function get_link_analytics($link_id, $limit = 100) {
        global $wpdb;
        
        $analytics = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}slc_analytics 
             WHERE link_id = %d 
             ORDER BY clicked_at DESC 
             LIMIT %d",
            $link_id,
            $limit
        ));
        
        return $analytics;
    }
    
    public function get_analytics_summary($link_id = 0, $period = '30') {
        global $wpdb;
        
        $where_clause = '';
        $params = array();
        
        if ($link_id > 0) {
            $where_clause .= ' AND link_id = %d';
            $params[] = $link_id;
        }
        
        switch ($period) {
            case '7':
                $date_clause = 'clicked_at > DATE_SUB(NOW(), INTERVAL 7 DAY)';
                break;
            case '30':
                $date_clause = 'clicked_at > DATE_SUB(NOW(), INTERVAL 30 DAY)';
                break;
            case '90':
                $date_clause = 'clicked_at > DATE_SUB(NOW(), INTERVAL 90 DAY)';
                break;
            default:
                $date_clause = '1=1';
        }
        
        // Total clicks
        $total_clicks = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}slc_analytics 
             WHERE $date_clause $where_clause",
            $params
        ));
        
        // Unique clicks
        $unique_clicks = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}slc_analytics 
             WHERE $date_clause AND is_unique = 1 $where_clause",
            $params
        ));
        
        // Top countries
        $top_countries = $wpdb->get_results($wpdb->prepare(
            "SELECT country, COUNT(*) as count 
             FROM {$wpdb->prefix}slc_analytics 
             WHERE $date_clause AND country != '' $where_clause
             GROUP BY country 
             ORDER BY count DESC 
             LIMIT 10",
            $params
        ));
        
        // Top browsers
        $top_browsers = $wpdb->get_results($wpdb->prepare(
            "SELECT browser, COUNT(*) as count 
             FROM {$wpdb->prefix}slc_analytics 
             WHERE $date_clause AND browser != '' $where_clause
             GROUP BY browser 
             ORDER BY count DESC 
             LIMIT 10",
            $params
        ));
        
        // Top devices
        $top_devices = $wpdb->get_results($wpdb->prepare(
            "SELECT device, COUNT(*) as count 
             FROM {$wpdb->prefix}slc_analytics 
             WHERE $date_clause AND device != '' $where_clause
             GROUP BY device 
             ORDER BY count DESC 
             LIMIT 10",
            $params
        ));
        
        // Top referers
        $top_referers = $wpdb->get_results($wpdb->prepare(
            "SELECT referer, COUNT(*) as count 
             FROM {$wpdb->prefix}slc_analytics 
             WHERE $date_clause AND referer != '' $where_clause
             GROUP BY referer 
             ORDER BY count DESC 
             LIMIT 10",
            $params
        ));
        
        // Clicks by day
        $clicks_by_day = $wpdb->get_results($wpdb->prepare(
            "SELECT DATE(clicked_at) as date, COUNT(*) as clicks, 
                    COUNT(CASE WHEN is_unique = 1 THEN 1 END) as unique_clicks
             FROM {$wpdb->prefix}slc_analytics 
             WHERE $date_clause $where_clause
             GROUP BY DATE(clicked_at) 
             ORDER BY date DESC 
             LIMIT 30",
            $params
        ));
        
        return array(
            'total_clicks' => $total_clicks,
            'unique_clicks' => $unique_clicks,
            'top_countries' => $top_countries,
            'top_browsers' => $top_browsers,
            'top_devices' => $top_devices,
            'top_referers' => $top_referers,
            'clicks_by_day' => $clicks_by_day
        );
    }
    
    public function get_top_links($limit = 10, $period = '30') {
        global $wpdb;
        
        switch ($period) {
            case '7':
                $date_clause = 'AND a.clicked_at > DATE_SUB(NOW(), INTERVAL 7 DAY)';
                break;
            case '30':
                $date_clause = 'AND a.clicked_at > DATE_SUB(NOW(), INTERVAL 30 DAY)';
                break;
            case '90':
                $date_clause = 'AND a.clicked_at > DATE_SUB(NOW(), INTERVAL 90 DAY)';
                break;
            default:
                $date_clause = '';
        }
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT l.id, l.title, l.slug, l.target_url,
                    COUNT(a.id) as total_clicks,
                    COUNT(CASE WHEN a.is_unique = 1 THEN 1 END) as unique_clicks
             FROM {$wpdb->prefix}slc_links l
             LEFT JOIN {$wpdb->prefix}slc_analytics a ON l.id = a.link_id
             WHERE l.status = 'active' $date_clause
             GROUP BY l.id
             ORDER BY total_clicks DESC
             LIMIT %d",
            $limit
        ));
    }
    
    public function export_analytics($link_id = 0, $format = 'csv') {
        global $wpdb;
        
        $where_clause = '';
        $params = array();
        
        if ($link_id > 0) {
            $where_clause = 'WHERE a.link_id = %d';
            $params[] = $link_id;
        }
        
        $analytics = $wpdb->get_results($wpdb->prepare(
            "SELECT l.title, l.slug, a.ip_address, a.user_agent, a.referer, 
                    a.country, a.device, a.browser, a.os, a.is_unique, a.clicked_at
             FROM {$wpdb->prefix}slc_analytics a
             LEFT JOIN {$wpdb->prefix}slc_links l ON a.link_id = l.id
             $where_clause
             ORDER BY a.clicked_at DESC",
            $params
        ));
        
        if ($format === 'csv') {
            return $this->export_to_csv($analytics);
        }
        
        return $analytics;
    }
    
    private function export_to_csv($data) {
        $filename = 'analytics_export_' . date('Y-m-d_H-i-s') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // Add CSV headers
        fputcsv($output, array(
            'Title',
            'Slug',
            'IP Address',
            'User Agent',
            'Referer',
            'Country',
            'Device',
            'Browser',
            'OS',
            'Unique',
            'Clicked At'
        ));
        
        // Add data rows
        foreach ($data as $row) {
            fputcsv($output, array(
                $row->title,
                $row->slug,
                $row->ip_address,
                $row->user_agent,
                $row->referer,
                $row->country,
                $row->device,
                $row->browser,
                $row->os,
                $row->is_unique ? 'Yes' : 'No',
                $row->clicked_at
            ));
        }
        
        fclose($output);
        exit;
    }
    
    public function delete_analytics($link_id = 0, $older_than_days = 0) {
        global $wpdb;
        
        $where_clauses = array();
        $params = array();
        
        if ($link_id > 0) {
            $where_clauses[] = 'link_id = %d';
            $params[] = $link_id;
        }
        
        if ($older_than_days > 0) {
            $where_clauses[] = 'clicked_at < DATE_SUB(NOW(), INTERVAL %d DAY)';
            $params[] = $older_than_days;
        }
        
        if (empty($where_clauses)) {
            return false;
        }
        
        $where_clause = 'WHERE ' . implode(' AND ', $where_clauses);
        
        return $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->prefix}slc_analytics $where_clause",
            $params
        ));
    }
    
    public function get_analytics_chart_data($link_id = 0, $period = '30') {
        global $wpdb;
        
        $where_clause = '';
        $params = array();
        
        if ($link_id > 0) {
            $where_clause = 'AND link_id = %d';
            $params[] = $link_id;
        }
        
        switch ($period) {
            case '7':
                $date_clause = 'clicked_at > DATE_SUB(NOW(), INTERVAL 7 DAY)';
                $group_by = 'DATE(clicked_at)';
                break;
            case '30':
                $date_clause = 'clicked_at > DATE_SUB(NOW(), INTERVAL 30 DAY)';
                $group_by = 'DATE(clicked_at)';
                break;
            case '90':
                $date_clause = 'clicked_at > DATE_SUB(NOW(), INTERVAL 90 DAY)';
                $group_by = 'DATE(clicked_at)';
                break;
            case '365':
                $date_clause = 'clicked_at > DATE_SUB(NOW(), INTERVAL 365 DAY)';
                $group_by = 'YEAR(clicked_at), MONTH(clicked_at)';
                break;
            default:
                $date_clause = '1=1';
                $group_by = 'DATE(clicked_at)';
        }
        
        $chart_data = $wpdb->get_results($wpdb->prepare(
            "SELECT $group_by as period, 
                    COUNT(*) as clicks,
                    COUNT(CASE WHEN is_unique = 1 THEN 1 END) as unique_clicks
             FROM {$wpdb->prefix}slc_analytics 
             WHERE $date_clause $where_clause
             GROUP BY $group_by
             ORDER BY period ASC",
            $params
        ));
        
        return $chart_data;
    }
}