/**
 * Admin JavaScript for MKP Super Links
 * 
 * @package MKPSuperLinks
 * @version 1.0.0
 */

(function($) {
    'use strict';
    
    // Main admin object
    window.MKPSuperLinksAdmin = {
        
        // Initialize
        init: function() {
            this.bindEvents();
            this.initTabs();
            this.initColorPickers();
            this.initSortables();
            this.checkDependencies();
        },
        
        // Bind events
        bindEvents: function() {
            var self = this;
            
            // Clear cache button
            $(document).on('click', '#mkp-clear-cache', function(e) {
                e.preventDefault();
                self.clearCache();
            });
            
            // Export settings button
            $(document).on('click', '#mkp-export-settings', function(e) {
                e.preventDefault();
                self.exportSettings();
            });
            
            // Import settings button
            $(document).on('click', '#mkp-import-settings', function(e) {
                e.preventDefault();
                self.importSettings();
            });
            
            // Form submission with validation
            $(document).on('submit', '.mkp-settings-form', function(e) {
                if (!self.validateForm(this)) {
                    e.preventDefault();
                }
            });
            
            // Feature toggles
            $(document).on('change', '.mkp-feature-toggle input[type="checkbox"]', function() {
                self.toggleFeature($(this));
            });
            
            // Auto-save settings
            $(document).on('change', '.mkp-auto-save', function() {
                self.autoSaveSettings();
            });
            
            // Dismiss notices
            $(document).on('click', '.mkp-notice .notice-dismiss', function() {
                $(this).closest('.mkp-notice').fadeOut();
            });
        },
        
        // Initialize tabs
        initTabs: function() {
            if ($('.mkp-tabs').length) {
                $('.mkp-tabs').tabs({
                    activate: function(event, ui) {
                        // Store active tab in localStorage
                        localStorage.setItem('mkp_active_tab', ui.newTab.index());
                    }
                });
                
                // Restore active tab
                var activeTab = localStorage.getItem('mkp_active_tab');
                if (activeTab !== null) {
                    $('.mkp-tabs').tabs('option', 'active', parseInt(activeTab));
                }
            }
        },
        
        // Initialize color pickers
        initColorPickers: function() {
            if ($('.mkp-color-picker').length) {
                $('.mkp-color-picker').wpColorPicker({
                    change: function(event, ui) {
                        // Trigger auto-save if enabled
                        if ($(this).hasClass('mkp-auto-save')) {
                            MKPSuperLinksAdmin.autoSaveSettings();
                        }
                    }
                });
            }
        },
        
        // Initialize sortables
        initSortables: function() {
            if ($('.mkp-sortable').length) {
                $('.mkp-sortable').sortable({
                    placeholder: 'mkp-sortable-placeholder',
                    update: function(event, ui) {
                        // Auto-save on sort
                        MKPSuperLinksAdmin.autoSaveSettings();
                    }
                });
            }
        },
        
        // Check dependencies
        checkDependencies: function() {
            var self = this;
            
            // Check if multisite is required for certain features
            $('.mkp-requires-multisite').each(function() {
                if (!mkpSuperLinks.isMultisite) {
                    $(this).addClass('mkp-disabled').find('input, select, textarea').prop('disabled', true);
                    $(this).append('<div class="mkp-notice mkp-notice-warning"><p>' + mkpSuperLinks.strings.requires_multisite + '</p></div>');
                }
            });
            
            // Check if super admin is required
            $('.mkp-requires-super-admin').each(function() {
                if (!mkpSuperLinks.isSuperAdmin) {
                    $(this).addClass('mkp-disabled').find('input, select, textarea').prop('disabled', true);
                    $(this).append('<div class="mkp-notice mkp-notice-warning"><p>' + mkpSuperLinks.strings.requires_super_admin + '</p></div>');
                }
            });
        },
        
        // Clear cache
        clearCache: function() {
            var $button = $('#mkp-clear-cache');
            var originalText = $button.text();
            
            $button.text(mkpSuperLinks.strings.loading).prop('disabled', true);
            
            $.ajax({
                url: mkpSuperLinks.ajaxurl,
                type: 'POST',
                data: {
                    action: 'mkp_super_links_action',
                    mkp_action: 'clear_cache',
                    nonce: mkpSuperLinks.nonce
                },
                success: function(response) {
                    if (response.success) {
                        MKPSuperLinksAdmin.showNotice(mkpSuperLinks.strings.cache_cleared, 'success');
                    } else {
                        MKPSuperLinksAdmin.showNotice(response.data || mkpSuperLinks.strings.error, 'error');
                    }
                },
                error: function() {
                    MKPSuperLinksAdmin.showNotice(mkpSuperLinks.strings.error, 'error');
                },
                complete: function() {
                    $button.text(originalText).prop('disabled', false);
                }
            });
        },
        
        // Export settings
        exportSettings: function() {
            var $button = $('#mkp-export-settings');
            var originalText = $button.text();
            
            $button.text(mkpSuperLinks.strings.loading).prop('disabled', true);
            
            $.ajax({
                url: mkpSuperLinks.ajaxurl,
                type: 'POST',
                data: {
                    action: 'mkp_super_links_action',
                    mkp_action: 'export_settings',
                    nonce: mkpSuperLinks.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Create download link
                        var blob = new Blob([atob(response.data.data)], {type: 'application/json'});
                        var url = window.URL.createObjectURL(blob);
                        var a = document.createElement('a');
                        a.href = url;
                        a.download = response.data.filename;
                        document.body.appendChild(a);
                        a.click();
                        document.body.removeChild(a);
                        window.URL.revokeObjectURL(url);
                        
                        MKPSuperLinksAdmin.showNotice(mkpSuperLinks.strings.settings_exported, 'success');
                    } else {
                        MKPSuperLinksAdmin.showNotice(response.data || mkpSuperLinks.strings.error, 'error');
                    }
                },
                error: function() {
                    MKPSuperLinksAdmin.showNotice(mkpSuperLinks.strings.error, 'error');
                },
                complete: function() {
                    $button.text(originalText).prop('disabled', false);
                }
            });
        },
        
        // Import settings
        importSettings: function() {
            var input = document.createElement('input');
            input.type = 'file';
            input.accept = '.json';
            
            input.onchange = function(e) {
                var file = e.target.files[0];
                if (!file) return;
                
                var reader = new FileReader();
                reader.onload = function(e) {
                    try {
                        var settings = JSON.parse(e.target.result);
                        MKPSuperLinksAdmin.processImportedSettings(settings);
                    } catch (error) {
                        MKPSuperLinksAdmin.showNotice('Invalid settings file', 'error');
                    }
                };
                reader.readAsText(file);
            };
            
            input.click();
        },
        
        // Process imported settings
        processImportedSettings: function(settings) {
            if (confirm('This will overwrite your current settings. Are you sure?')) {
                $.ajax({
                    url: mkpSuperLinks.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'mkp_super_links_action',
                        mkp_action: 'import_settings',
                        settings: JSON.stringify(settings),
                        nonce: mkpSuperLinks.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            MKPSuperLinksAdmin.showNotice(mkpSuperLinks.strings.settings_imported, 'success');
                            setTimeout(function() {
                                location.reload();
                            }, 1500);
                        } else {
                            MKPSuperLinksAdmin.showNotice(response.data || mkpSuperLinks.strings.error, 'error');
                        }
                    },
                    error: function() {
                        MKPSuperLinksAdmin.showNotice(mkpSuperLinks.strings.error, 'error');
                    }
                });
            }
        },
        
        // Validate form
        validateForm: function(form) {
            var isValid = true;
            var $form = $(form);
            
            // Clear previous errors
            $form.find('.mkp-error').removeClass('mkp-error');
            $form.find('.mkp-error-message').remove();
            
            // Validate required fields
            $form.find('[required]').each(function() {
                var $field = $(this);
                var value = $field.val();
                
                if (!value || value.trim() === '') {
                    isValid = false;
                    $field.addClass('mkp-error');
                    $field.after('<div class="mkp-error-message">This field is required</div>');
                }
            });
            
            // Validate email fields
            $form.find('input[type="email"]').each(function() {
                var $field = $(this);
                var value = $field.val();
                
                if (value && !MKPSuperLinksAdmin.isValidEmail(value)) {
                    isValid = false;
                    $field.addClass('mkp-error');
                    $field.after('<div class="mkp-error-message">Please enter a valid email address</div>');
                }
            });
            
            // Validate number fields
            $form.find('input[type="number"]').each(function() {
                var $field = $(this);
                var value = parseInt($field.val());
                var min = parseInt($field.attr('min'));
                var max = parseInt($field.attr('max'));
                
                if (!isNaN(min) && value < min) {
                    isValid = false;
                    $field.addClass('mkp-error');
                    $field.after('<div class="mkp-error-message">Value must be at least ' + min + '</div>');
                }
                
                if (!isNaN(max) && value > max) {
                    isValid = false;
                    $field.addClass('mkp-error');
                    $field.after('<div class="mkp-error-message">Value must be no more than ' + max + '</div>');
                }
            });
            
            return isValid;
        },
        
        // Toggle feature
        toggleFeature: function($checkbox) {
            var feature = $checkbox.data('feature');
            var enabled = $checkbox.is(':checked');
            var $container = $checkbox.closest('.mkp-feature-container');
            
            // Toggle dependent elements
            $container.find('.mkp-feature-dependent').toggle(enabled);
            
            // Add/remove classes
            if (enabled) {
                $container.addClass('mkp-feature-enabled').removeClass('mkp-feature-disabled');
            } else {
                $container.addClass('mkp-feature-disabled').removeClass('mkp-feature-enabled');
            }
        },
        
        // Auto-save settings
        autoSaveSettings: function() {
            clearTimeout(this.autoSaveTimer);
            this.autoSaveTimer = setTimeout(function() {
                $('.mkp-settings-form').submit();
            }, 1000);
        },
        
        // Show notice
        showNotice: function(message, type) {
            type = type || 'info';
            
            var $notice = $('<div class="mkp-notice mkp-notice-' + type + ' mkp-fade-in">' +
                '<p>' + message + '</p>' +
                '<button type="button" class="notice-dismiss">' +
                '<span class="screen-reader-text">Dismiss this notice.</span>' +
                '</button>' +
                '</div>');
            
            $('.wrap h1').after($notice);
            
            // Auto-dismiss after 5 seconds
            setTimeout(function() {
                $notice.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        },
        
        // Validate email
        isValidEmail: function(email) {
            var regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return regex.test(email);
        },
        
        // Update progress bar
        updateProgress: function(selector, percentage) {
            var $progressBar = $(selector).find('.mkp-progress-bar');
            $progressBar.css('width', percentage + '%').text(percentage + '%');
        },
        
        // Show loading state
        showLoading: function(selector) {
            $(selector).addClass('mkp-loading');
        },
        
        // Hide loading state
        hideLoading: function(selector) {
            $(selector).removeClass('mkp-loading');
        },
        
        // AJAX helper
        ajax: function(action, data, callback) {
            var ajaxData = $.extend({
                action: 'mkp_super_links_action',
                mkp_action: action,
                nonce: mkpSuperLinks.nonce
            }, data);
            
            return $.ajax({
                url: mkpSuperLinks.ajaxurl,
                type: 'POST',
                data: ajaxData,
                success: callback || function() {},
                error: function() {
                    MKPSuperLinksAdmin.showNotice(mkpSuperLinks.strings.error, 'error');
                }
            });
        }
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        MKPSuperLinksAdmin.init();
    });
    
    // Global functions for backwards compatibility
    window.mkpClearCache = function() {
        MKPSuperLinksAdmin.clearCache();
    };
    
    window.mkpExportSettings = function() {
        MKPSuperLinksAdmin.exportSettings();
    };
    
    window.mkpImportSettings = function() {
        MKPSuperLinksAdmin.importSettings();
    };
    
})(jQuery);