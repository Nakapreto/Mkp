/**
 * Super Links Clone - Frontend JavaScript
 */

(function($) {
    'use strict';

    // Main SLC object
    window.SLC = window.SLC || {};

    // Configuration from WordPress
    var config = slc_ajax || {};

    /**
     * Cookie Helper Functions
     */
    SLC.Cookie = {
        set: function(name, value, days) {
            var expires = "";
            if (days) {
                var date = new Date();
                date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
                expires = "; expires=" + date.toUTCString();
            }
            document.cookie = name + "=" + (value || "") + expires + "; path=/";
        },

        get: function(name) {
            var nameEQ = name + "=";
            var ca = document.cookie.split(';');
            for (var i = 0; i < ca.length; i++) {
                var c = ca[i];
                while (c.charAt(0) == ' ') c = c.substring(1, c.length);
                if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length, c.length);
            }
            return null;
        },

        remove: function(name) {
            document.cookie = name + "=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
        }
    };

    /**
     * Analytics Tracker
     */
    SLC.Analytics = {
        track: function(action, data) {
            if (!config.ajax_url || !config.nonce) return;

            $.post(config.ajax_url, {
                action: 'slc_track_event',
                event_action: action,
                event_data: data,
                nonce: config.nonce
            });
        },

        trackLinkClick: function(linkElement) {
            var href = linkElement.href;
            var text = linkElement.textContent || linkElement.innerText;
            
            this.track('link_click', {
                url: href,
                text: text,
                page_url: window.location.href,
                referrer: document.referrer
            });
        },

        trackPageView: function() {
            this.track('page_view', {
                page_url: window.location.href,
                referrer: document.referrer,
                timestamp: Date.now()
            });
        }
    };

    /**
     * Smart Links Handler
     */
    SLC.SmartLinks = {
        init: function() {
            if (!config.smart_links) return;

            // Track smart link clicks
            $(document).on('click', '.slc-smart-link', function(e) {
                SLC.Analytics.trackLinkClick(this);
            });
        }
    };

    /**
     * Exit Intent Handler
     */
    SLC.ExitIntent = {
        init: function() {
            if (!config.exit_redirect) return;

            var hasTriggered = false;
            var sensitivity = 20;

            $(document).on('mouseleave', function(e) {
                if (e.clientY <= sensitivity && !hasTriggered) {
                    hasTriggered = true;
                    SLC.ExitIntent.trigger();
                }
            });

            // Backup trigger on beforeunload
            $(window).on('beforeunload', function(e) {
                if (!hasTriggered && Math.random() < 0.3) { // 30% chance
                    hasTriggered = true;
                    return 'Tem certeza que deseja sair? Temos conteúdo exclusivo esperando por você!';
                }
            });
        },

        trigger: function() {
            // Check if already shown today
            if (SLC.Cookie.get('slc_exit_shown')) return;

            // Set cookie to prevent showing again today
            SLC.Cookie.set('slc_exit_shown', '1', 1);

            // Track exit intent
            SLC.Analytics.track('exit_intent', {
                page_url: window.location.href,
                timestamp: Date.now()
            });

            // Show exit popup or redirect
            this.showExitPopup();
        },

        showExitPopup: function() {
            var popup = $('.slc-exit-popup');
            if (popup.length) {
                popup.show();
                
                // Close handlers
                $('.slc-exit-popup-close, .slc-exit-popup').on('click', function(e) {
                    if (e.target === this) {
                        popup.hide();
                    }
                });

                // ESC key handler
                $(document).on('keydown.slc-exit', function(e) {
                    if (e.keyCode === 27) {
                        popup.hide();
                        $(document).off('keydown.slc-exit');
                    }
                });
            }
        }
    };

    /**
     * Link Previewer
     */
    SLC.LinkPreviewer = {
        init: function() {
            $(document).on('mouseenter', 'a[href^="http"]', function() {
                SLC.LinkPreviewer.showPreview(this);
            }).on('mouseleave', 'a[href^="http"]', function() {
                SLC.LinkPreviewer.hidePreview();
            });
        },

        showPreview: function(link) {
            // Simple preview implementation
            var href = link.href;
            var text = link.textContent || link.innerText;
            
            if ($('.slc-link-preview').length) return;

            var preview = $('<div class="slc-link-preview">' +
                '<div class="slc-link-preview-title">' + text + '</div>' +
                '<div class="slc-link-preview-url">' + href + '</div>' +
                '</div>');

            $('body').append(preview);

            // Position preview
            var offset = $(link).offset();
            preview.css({
                position: 'absolute',
                top: offset.top + $(link).outerHeight() + 5,
                left: offset.left,
                zIndex: 10000
            });
        },

        hidePreview: function() {
            $('.slc-link-preview').remove();
        }
    };

    /**
     * Cookie Tracking
     */
    SLC.CookieTracking = {
        init: function() {
            if (!config.cookie_tracking) return;

            // Set affiliate cookies when clicking SLC links
            $(document).on('click', 'a[href*="/go/"]', function() {
                var href = this.href;
                var slug = href.split('/').pop();
                
                // Set cookie for this specific link
                SLC.Cookie.set('slc_clicked_' + slug, '1', 30);
                
                // Update last affiliate cookie
                SLC.Cookie.set('slc_last_affiliate', slug, 30);
                
                // Track the click
                SLC.Analytics.trackLinkClick(this);
            });

            // Track page views with affiliate attribution
            this.trackPageViewWithAttribution();
        },

        trackPageViewWithAttribution: function() {
            var lastAffiliate = SLC.Cookie.get('slc_last_affiliate');
            if (lastAffiliate) {
                SLC.Analytics.track('attributed_page_view', {
                    affiliate_slug: lastAffiliate,
                    page_url: window.location.href,
                    referrer: document.referrer
                });
            }
        }
    };

    /**
     * Utility Functions
     */
    SLC.Utils = {
        showNotification: function(message, type) {
            type = type || 'info';
            var notification = $('<div class="slc-notification slc-notification-' + type + '">' + message + '</div>');
            $('body').prepend(notification);

            setTimeout(function() {
                notification.fadeOut(function() {
                    notification.remove();
                });
            }, 5000);
        },

        showLoading: function(element) {
            $(element).addClass('slc-loading');
        },

        hideLoading: function(element) {
            $(element).removeClass('slc-loading');
        },

        copyToClipboard: function(text) {
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text).then(function() {
                    SLC.Utils.showNotification('Link copiado para a área de transferência!', 'success');
                });
            } else {
                // Fallback for older browsers
                var textArea = document.createElement('textarea');
                textArea.value = text;
                document.body.appendChild(textArea);
                textArea.select();
                try {
                    document.execCommand('copy');
                    SLC.Utils.showNotification('Link copiado para a área de transferência!', 'success');
                } catch (err) {
                    SLC.Utils.showNotification('Erro ao copiar link.', 'error');
                }
                document.body.removeChild(textArea);
            }
        }
    };

    /**
     * Initialize Everything
     */
    SLC.init = function() {
        SLC.SmartLinks.init();
        SLC.ExitIntent.init();
        SLC.CookieTracking.init();
        
        // Track initial page view
        SLC.Analytics.trackPageView();

        // Global click tracking for all external links
        $(document).on('click', 'a[href^="http"]', function() {
            if (this.hostname !== window.location.hostname) {
                SLC.Analytics.trackLinkClick(this);
            }
        });

        // Copy link functionality
        $(document).on('click', '[data-slc-copy]', function(e) {
            e.preventDefault();
            var text = $(this).data('slc-copy');
            SLC.Utils.copyToClipboard(text);
        });

        console.log('Super Links Clone frontend initialized');
    };

    // Initialize when DOM is ready
    $(document).ready(function() {
        SLC.init();
    });

    // Also initialize if DOM is already ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', SLC.init);
    } else {
        SLC.init();
    }

})(jQuery);