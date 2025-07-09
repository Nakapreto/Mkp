<?php

if (!defined('ABSPATH')) {
    exit;
}

class SLC_Popup_Manager {
    
    public function __construct() {
        add_action('wp_footer', array($this, 'add_popup_script'));
    }
    
    public function add_popup_script() {
        if (!get_option('slc_popup_enabled', 0)) {
            return;
        }
        
        $popup_delay = get_option('slc_popup_delay', 5000);
        $popup_content = get_option('slc_popup_content', '');
        
        if (empty($popup_content)) {
            return;
        }
        
        ?>
        <div id="slc-popup-overlay" style="display: none;">
            <div id="slc-popup-container">
                <div id="slc-popup-close">&times;</div>
                <div id="slc-popup-content">
                    <?php echo wp_kses_post($popup_content); ?>
                </div>
            </div>
        </div>
        
        <style>
        #slc-popup-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 10000;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        #slc-popup-container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            max-width: 500px;
            width: 90%;
            position: relative;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        }
        
        #slc-popup-close {
            position: absolute;
            top: 10px;
            right: 15px;
            font-size: 24px;
            cursor: pointer;
            color: #999;
        }
        
        #slc-popup-close:hover {
            color: #333;
        }
        
        #slc-popup-content {
            margin-top: 20px;
        }
        
        #slc-popup-content h1,
        #slc-popup-content h2,
        #slc-popup-content h3 {
            margin-top: 0;
        }
        </style>
        
        <script>
        (function() {
            var popupShown = false;
            var popupDelay = <?php echo intval($popup_delay); ?>;
            
            function showPopup() {
                if (popupShown) return;
                
                var overlay = document.getElementById('slc-popup-overlay');
                if (overlay) {
                    overlay.style.display = 'flex';
                    popupShown = true;
                    
                    // Set cookie to prevent showing again
                    document.cookie = 'slc_popup_shown=1; expires=' + 
                        new Date(Date.now() + 24*60*60*1000).toUTCString() + '; path=/';
                }
            }
            
            function hidePopup() {
                var overlay = document.getElementById('slc-popup-overlay');
                if (overlay) {
                    overlay.style.display = 'none';
                }
            }
            
            // Check if popup was already shown today
            if (document.cookie.indexOf('slc_popup_shown=1') === -1) {
                // Show popup after delay
                setTimeout(showPopup, popupDelay);
            }
            
            // Close popup handlers
            document.addEventListener('DOMContentLoaded', function() {
                var closeBtn = document.getElementById('slc-popup-close');
                var overlay = document.getElementById('slc-popup-overlay');
                
                if (closeBtn) {
                    closeBtn.addEventListener('click', hidePopup);
                }
                
                if (overlay) {
                    overlay.addEventListener('click', function(e) {
                        if (e.target === overlay) {
                            hidePopup();
                        }
                    });
                }
                
                // Close with ESC key
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape' && popupShown) {
                        hidePopup();
                    }
                });
            });
        })();
        </script>
        <?php
    }
}