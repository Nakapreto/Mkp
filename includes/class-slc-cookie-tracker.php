<?php

if (!defined('ABSPATH')) {
    exit;
}

class SLC_Cookie_Tracker {
    
    public function __construct() {
        add_action('wp_footer', array($this, 'add_cookie_tracking_script'));
        add_action('wp_ajax_slc_track_cookie', array($this, 'handle_cookie_tracking'));
        add_action('wp_ajax_nopriv_slc_track_cookie', array($this, 'handle_cookie_tracking'));
    }
    
    public function add_cookie_tracking_script() {
        if (!get_option('slc_enable_cookie_tracking', 1)) {
            return;
        }
        
        ?>
        <script>
        (function() {
            // Super Links Clone Cookie Tracker
            var slcCookieTracker = {
                init: function() {
                    this.trackPageView();
                    this.setupLinkTracking();
                },
                
                trackPageView: function() {
                    var affiliateCookie = this.getCookie('slc_last_affiliate');
                    if (affiliateCookie) {
                        this.sendTrackingData('page_view', {
                            affiliate_id: affiliateCookie,
                            page_url: window.location.href,
                            referrer: document.referrer
                        });
                    }
                },
                
                setupLinkTracking: function() {
                    var self = this;
                    var links = document.querySelectorAll('a[href*="<?php echo get_option('slc_link_prefix', 'go'); ?>"]');
                    
                    for (var i = 0; i < links.length; i++) {
                        links[i].addEventListener('click', function(e) {
                            self.trackLinkClick(this);
                        });
                    }
                },
                
                trackLinkClick: function(link) {
                    var href = link.getAttribute('href');
                    var linkSlug = href.split('/').pop();
                    
                    this.sendTrackingData('link_click', {
                        link_slug: linkSlug,
                        link_url: href,
                        page_url: window.location.href
                    });
                    
                    // Set cookie for double tracking
                    this.setCookie('slc_clicked_' + linkSlug, '1', 30);
                },
                
                sendTrackingData: function(eventType, data) {
                    if (typeof jQuery !== 'undefined') {
                        jQuery.post('<?php echo admin_url('admin-ajax.php'); ?>', {
                            action: 'slc_track_cookie',
                            event_type: eventType,
                            data: data,
                            nonce: '<?php echo wp_create_nonce('slc_cookie_track'); ?>'
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
                    document.cookie = name + "=" + (value || "") + expires + "; path=/";
                },
                
                getCookie: function(name) {
                    var nameEQ = name + "=";
                    var ca = document.cookie.split(';');
                    for (var i = 0; i < ca.length; i++) {
                        var c = ca[i];
                        while (c.charAt(0) == ' ') c = c.substring(1, c.length);
                        if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length, c.length);
                    }
                    return null;
                }
            };
            
            // Initialize when DOM is ready
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', function() {
                    slcCookieTracker.init();
                });
            } else {
                slcCookieTracker.init();
            }
        })();
        </script>
        <?php
    }
    
    public function handle_cookie_tracking() {
        check_ajax_referer('slc_cookie_track', 'nonce');
        
        $event_type = sanitize_text_field($_POST['event_type']);
        $data = $_POST['data'];
        
        switch ($event_type) {
            case 'page_view':
                $this->track_page_view($data);
                break;
            case 'link_click':
                $this->track_link_click($data);
                break;
        }
        
        wp_send_json_success();
    }
    
    private function track_page_view($data) {
        global $wpdb;
        
        $affiliate_id = intval($data['affiliate_id']);
        $page_url = sanitize_url($data['page_url']);
        $referrer = sanitize_url($data['referrer']);
        
        // Insert page view tracking
        $wpdb->insert(
            $wpdb->prefix . 'slc_analytics',
            array(
                'link_id' => $affiliate_id,
                'ip_address' => $this->get_user_ip(),
                'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '',
                'referer' => $referrer,
                'country' => '',
                'device' => 'Unknown',
                'browser' => 'Unknown',
                'os' => 'Unknown',
                'is_unique' => 0,
                'clicked_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s')
        );
    }
    
    private function track_link_click($data) {
        // Additional tracking for link clicks from cookie perspective
        // This could be used for attribution and conversion tracking
    }
    
    private function get_user_ip() {
        $ip_headers = array(
            'HTTP_CF_CONNECTING_IP',
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        );
        
        foreach ($ip_headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
    }
}