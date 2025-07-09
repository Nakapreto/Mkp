<?php
/**
 * Site Cloner Elementor Class
 *
 * @package Site_Cloner
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Site_Cloner_Elementor {
    
    /**
     * Check if Elementor is active
     */
    public function is_active() {
        return class_exists('\\Elementor\\Plugin');
    }
    
    /**
     * Check if Elementor Pro is active
     */
    public function is_pro_active() {
        return class_exists('\\ElementorPro\\Plugin');
    }
    
    /**
     * Detect if the cloned site uses Elementor
     */
    public function detect_elementor($content) {
        // Check for Elementor-specific classes and attributes
        $elementor_indicators = array(
            'elementor-element',
            'elementor-section',
            'elementor-container',
            'elementor-widget',
            'data-elementor-type',
            'data-elementor-id',
            'elementor-column',
            'elementor-row',
            'elementor-inner',
            'e-con',
            'e-container'
        );
        
        foreach ($elementor_indicators as $indicator) {
            if (strpos($content, $indicator) !== false) {
                return true;
            }
        }
        
        // Check for Elementor CSS files
        if (preg_match('/elementor.*\.css/', $content)) {
            return true;
        }
        
        // Check for Elementor JS files
        if (preg_match('/elementor.*\.js/', $content)) {
            return true;
        }
        
        // Check for Elementor inline styles
        if (preg_match('/elementor-[0-9]+-[0-9]+/', $content)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Configure WordPress page for Elementor editing
     */
    public function configure_page($page_id, $content_or_data) {
        if (!$this->is_active()) {
            return false;
        }
        
        try {
            // Enable Elementor for this page
            update_post_meta($page_id, '_elementor_edit_mode', 'builder');
            update_post_meta($page_id, '_elementor_template_type', 'wp-page');
            update_post_meta($page_id, '_elementor_version', ELEMENTOR_VERSION);
            
            // If we have Elementor data, use it
            if (is_array($content_or_data)) {
                $elementor_data = $content_or_data;
            } else {
                // Extract Elementor data from HTML content
                $elementor_data = $this->extract_elementor_data($content_or_data);
            }
            
            if ($elementor_data) {
                update_post_meta($page_id, '_elementor_data', $elementor_data);
                
                // Generate CSS for the page
                $this->generate_elementor_css($page_id);
                
                // Update page status
                update_post_meta($page_id, '_elementor_page_settings', array());
                
                return true;
            } else {
                // Convert HTML content to Elementor structure
                return $this->convert_html_to_elementor($page_id, $content_or_data);
            }
            
        } catch (Exception $e) {
            error_log('Site Cloner Elementor: Error configuring page: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Extract Elementor data from HTML content
     */
    private function extract_elementor_data($content) {
        // Try to find Elementor data in script tags
        if (preg_match('/elementorFrontendConfig.*?"data":\s*({.+?})/s', $content, $matches)) {
            $json_data = $matches[1];
            $elementor_data = json_decode($json_data, true);
            
            if ($elementor_data && is_array($elementor_data)) {
                return $this->format_elementor_data($elementor_data);
            }
        }
        
        // Try to extract from data attributes
        if (preg_match_all('/data-elementor-settings="([^"]+)"/i', $content, $matches)) {
            $settings = array();
            foreach ($matches[1] as $match) {
                $decoded = json_decode(html_entity_decode($match), true);
                if ($decoded) {
                    $settings = array_merge($settings, $decoded);
                }
            }
            
            if (!empty($settings)) {
                return $this->build_elementor_structure($content, $settings);
            }
        }
        
        return false;
    }
    
    /**
     * Convert HTML content to Elementor structure
     */
    private function convert_html_to_elementor($page_id, $content) {
        // Create a basic Elementor structure
        $elementor_data = array();
        
        // Parse the HTML
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        
        // Find main content area
        $body = $dom->getElementsByTagName('body')->item(0);
        if (!$body) {
            // No body tag, use the entire content
            $elementor_data[] = $this->create_html_widget($content);
        } else {
            // Extract content from body and convert to Elementor widgets
            $elementor_data = $this->convert_dom_to_elementor($body);
        }
        
        if (!empty($elementor_data)) {
            update_post_meta($page_id, '_elementor_data', wp_json_encode($elementor_data));
            update_post_meta($page_id, '_elementor_edit_mode', 'builder');
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Convert DOM elements to Elementor structure
     */
    private function convert_dom_to_elementor($dom_element) {
        $elementor_data = array();
        
        // Create a section
        $section = array(
            'id' => $this->generate_elementor_id(),
            'elType' => 'section',
            'settings' => array(),
            'elements' => array()
        );
        
        // Create a column
        $column = array(
            'id' => $this->generate_elementor_id(),
            'elType' => 'column',
            'settings' => array('_column_size' => 100),
            'elements' => array()
        );
        
        // Process child elements
        foreach ($dom_element->childNodes as $child) {
            if ($child->nodeType === XML_ELEMENT_NODE) {
                $widget = $this->convert_element_to_widget($child);
                if ($widget) {
                    $column['elements'][] = $widget;
                }
            }
        }
        
        $section['elements'][] = $column;
        $elementor_data[] = $section;
        
        return $elementor_data;
    }
    
    /**
     * Convert individual DOM element to Elementor widget
     */
    private function convert_element_to_widget($element) {
        $tag_name = strtolower($element->tagName);
        
        switch ($tag_name) {
            case 'h1':
            case 'h2':
            case 'h3':
            case 'h4':
            case 'h5':
            case 'h6':
                return $this->create_heading_widget($element);
                
            case 'p':
                return $this->create_text_widget($element);
                
            case 'img':
                return $this->create_image_widget($element);
                
            case 'video':
                return $this->create_video_widget($element);
                
            case 'div':
            case 'section':
                // For generic containers, create an HTML widget
                return $this->create_html_widget($element->ownerDocument->saveHTML($element));
                
            default:
                // For other elements, create an HTML widget
                return $this->create_html_widget($element->ownerDocument->saveHTML($element));
        }
    }
    
    /**
     * Create Elementor heading widget
     */
    private function create_heading_widget($element) {
        $tag = $element->tagName;
        $title = trim($element->textContent);
        
        return array(
            'id' => $this->generate_elementor_id(),
            'elType' => 'widget',
            'widgetType' => 'heading',
            'settings' => array(
                'title' => $title,
                'size' => strtolower($tag),
                'header_size' => array(
                    'unit' => 'px',
                    'size' => '',
                    'sizes' => array()
                )
            )
        );
    }
    
    /**
     * Create Elementor text widget
     */
    private function create_text_widget($element) {
        $text = $element->ownerDocument->saveHTML($element);
        
        return array(
            'id' => $this->generate_elementor_id(),
            'elType' => 'widget',
            'widgetType' => 'text-editor',
            'settings' => array(
                'editor' => $text
            )
        );
    }
    
    /**
     * Create Elementor image widget
     */
    private function create_image_widget($element) {
        $src = $element->getAttribute('src');
        $alt = $element->getAttribute('alt');
        
        return array(
            'id' => $this->generate_elementor_id(),
            'elType' => 'widget',
            'widgetType' => 'image',
            'settings' => array(
                'image' => array(
                    'url' => $src,
                    'alt' => $alt
                )
            )
        );
    }
    
    /**
     * Create Elementor video widget
     */
    private function create_video_widget($element) {
        $src = $element->getAttribute('src');
        
        return array(
            'id' => $this->generate_elementor_id(),
            'elType' => 'widget',
            'widgetType' => 'video',
            'settings' => array(
                'video_type' => 'hosted',
                'hosted_url' => array(
                    'url' => $src
                )
            )
        );
    }
    
    /**
     * Create Elementor HTML widget
     */
    private function create_html_widget($html) {
        return array(
            'id' => $this->generate_elementor_id(),
            'elType' => 'widget',
            'widgetType' => 'html',
            'settings' => array(
                'html' => $html
            )
        );
    }
    
    /**
     * Generate Elementor-style ID
     */
    private function generate_elementor_id() {
        return substr(md5(uniqid(mt_rand(), true)), 0, 7);
    }
    
    /**
     * Format Elementor data
     */
    private function format_elementor_data($data) {
        // Ensure data is properly formatted for Elementor
        if (is_string($data)) {
            return json_decode($data, true);
        }
        
        return $data;
    }
    
    /**
     * Build Elementor structure from content and settings
     */
    private function build_elementor_structure($content, $settings) {
        // This is a simplified implementation
        // In a full implementation, you would parse the Elementor structure more thoroughly
        
        $elementor_data = array(
            array(
                'id' => $this->generate_elementor_id(),
                'elType' => 'section',
                'settings' => $settings,
                'elements' => array(
                    array(
                        'id' => $this->generate_elementor_id(),
                        'elType' => 'column',
                        'settings' => array('_column_size' => 100),
                        'elements' => array(
                            array(
                                'id' => $this->generate_elementor_id(),
                                'elType' => 'widget',
                                'widgetType' => 'html',
                                'settings' => array(
                                    'html' => $content
                                )
                            )
                        )
                    )
                )
            )
        );
        
        return wp_json_encode($elementor_data);
    }
    
    /**
     * Generate Elementor CSS for the page
     */
    private function generate_elementor_css($page_id) {
        if (!$this->is_active()) {
            return false;
        }
        
        try {
            // Use Elementor's CSS generation if available
            if (class_exists('\\Elementor\\Core\\Files\\CSS\\Page')) {
                $css_file = new \Elementor\Core\Files\CSS\Page($page_id);
                $css_file->update();
                return true;
            }
        } catch (Exception $e) {
            error_log('Site Cloner Elementor: Error generating CSS: ' . $e->getMessage());
        }
        
        return false;
    }
    
    /**
     * Import Elementor templates
     */
    public function import_template($template_data, $page_id) {
        if (!$this->is_active()) {
            return false;
        }
        
        try {
            // Process template data
            $processed_data = $this->process_template_data($template_data);
            
            // Update page meta
            update_post_meta($page_id, '_elementor_data', wp_json_encode($processed_data));
            update_post_meta($page_id, '_elementor_edit_mode', 'builder');
            update_post_meta($page_id, '_elementor_template_type', 'wp-page');
            
            // Generate CSS
            $this->generate_elementor_css($page_id);
            
            return true;
            
        } catch (Exception $e) {
            error_log('Site Cloner Elementor: Error importing template: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Process template data
     */
    private function process_template_data($data) {
        if (is_string($data)) {
            $data = json_decode($data, true);
        }
        
        if (!is_array($data)) {
            return array();
        }
        
        // Process each element to update IDs and ensure compatibility
        return $this->process_elements($data);
    }
    
    /**
     * Process Elementor elements recursively
     */
    private function process_elements($elements) {
        $processed = array();
        
        foreach ($elements as $element) {
            // Generate new ID
            $element['id'] = $this->generate_elementor_id();
            
            // Process child elements
            if (isset($element['elements']) && is_array($element['elements'])) {
                $element['elements'] = $this->process_elements($element['elements']);
            }
            
            // Update asset URLs in settings
            if (isset($element['settings'])) {
                $element['settings'] = $this->update_asset_urls($element['settings']);
            }
            
            $processed[] = $element;
        }
        
        return $processed;
    }
    
    /**
     * Update asset URLs in Elementor settings
     */
    private function update_asset_urls($settings) {
        foreach ($settings as $key => $value) {
            if (is_array($value)) {
                $settings[$key] = $this->update_asset_urls($value);
            } elseif (is_string($value) && filter_var($value, FILTER_VALIDATE_URL)) {
                // This would need to be integrated with the asset replacement system
                // For now, we'll leave URLs as-is
                $settings[$key] = $value;
            }
        }
        
        return $settings;
    }
    
    /**
     * Get Elementor requirements
     */
    public function get_requirements() {
        $requirements = array(
            'elementor_active' => $this->is_active(),
            'elementor_pro_active' => $this->is_pro_active(),
            'elementor_version' => $this->is_active() ? ELEMENTOR_VERSION : null,
            'required_plugins' => array('elementor/elementor.php')
        );
        
        if ($this->is_pro_active()) {
            $requirements['required_plugins'][] = 'elementor-pro/elementor-pro.php';
        }
        
        return $requirements;
    }
    
    /**
     * Check if system meets Elementor requirements
     */
    public function check_requirements() {
        $requirements = $this->get_requirements();
        
        return array(
            'meets_requirements' => $requirements['elementor_active'],
            'missing' => $requirements['elementor_active'] ? array() : array('Elementor plugin'),
            'recommendations' => $requirements['elementor_pro_active'] ? array() : array('Elementor Pro for full compatibility')
        );
    }
}