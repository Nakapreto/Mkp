<?php

if (!defined('ABSPATH')) {
    exit;
}

class SLC_Smart_Links {
    
    public function __construct() {
        add_filter('the_content', array($this, 'process_smart_links'));
        add_filter('widget_text', array($this, 'process_smart_links'));
    }
    
    public function process_smart_links($content) {
        if (!get_option('slc_enable_smart_links', 0)) {
            return $content;
        }
        
        // Get smart keywords from database
        $smart_keywords = $this->get_smart_keywords();
        
        if (empty($smart_keywords)) {
            return $content;
        }
        
        // Process each keyword
        foreach ($smart_keywords as $keyword_data) {
            $content = $this->replace_keywords_with_links($content, $keyword_data);
        }
        
        return $content;
    }
    
    private function get_smart_keywords() {
        global $wpdb;
        
        $keywords = $wpdb->get_results("
            SELECT l.id, l.slug, l.keywords, l.title
            FROM {$wpdb->prefix}slc_links l
            WHERE l.status = 'active' 
            AND l.smart_link = 1 
            AND l.keywords != ''
        ");
        
        $processed_keywords = array();
        
        foreach ($keywords as $link) {
            if (!empty($link->keywords)) {
                $keywords_array = array_map('trim', explode(',', $link->keywords));
                foreach ($keywords_array as $keyword) {
                    if (!empty($keyword)) {
                        $processed_keywords[] = array(
                            'keyword' => $keyword,
                            'link_id' => $link->id,
                            'slug' => $link->slug,
                            'title' => $link->title
                        );
                    }
                }
            }
        }
        
        // Sort by keyword length (longer keywords first)
        usort($processed_keywords, function($a, $b) {
            return strlen($b['keyword']) - strlen($a['keyword']);
        });
        
        return $processed_keywords;
    }
    
    private function replace_keywords_with_links($content, $keyword_data) {
        $keyword = $keyword_data['keyword'];
        $slug = $keyword_data['slug'];
        $title = $keyword_data['title'];
        
        // Create the link URL
        $link_prefix = get_option('slc_link_prefix', 'go');
        $link_url = home_url('/' . $link_prefix . '/' . $slug);
        
        // Create the replacement link
        $replacement = '<a href="' . esc_url($link_url) . '" title="' . esc_attr($title) . '" class="slc-smart-link">' . $keyword . '</a>';
        
        // Use word boundaries to avoid partial matches
        $pattern = '/\b' . preg_quote($keyword, '/') . '\b/i';
        
        // Only replace if the keyword is not already inside a link
        $content = preg_replace_callback($pattern, function($matches) use ($replacement, $keyword) {
            return $replacement;
        }, $content);
        
        return $content;
    }
    
    public function add_keywords_for_link($link_id, $keywords) {
        // This method is called when adding smart keywords for a link
        // The keywords are already stored in the link record
        // This could be extended to store keywords in a separate table for more complex relationships
    }
    
    public function get_smart_links_stats() {
        global $wpdb;
        
        $stats = $wpdb->get_results("
            SELECT l.id, l.title, l.slug, l.keywords, l.clicks, l.unique_clicks
            FROM {$wpdb->prefix}slc_links l
            WHERE l.status = 'active' 
            AND l.smart_link = 1
            ORDER BY l.clicks DESC
        ");
        
        return $stats;
    }
}