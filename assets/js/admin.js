/**
 * Super Links Clone - Admin JavaScript
 */

(function($) {
    'use strict';

    // Main admin object
    window.SLCAdmin = window.SLCAdmin || {};

    // Configuration from WordPress
    var config = slc_admin_ajax || {};

    /**
     * Dashboard functionality
     */
    SLCAdmin.Dashboard = {
        init: function() {
            // Copy link functionality
            $('.slc-copy-link').on('click', function(e) {
                e.preventDefault();
                var link = $(this).data('link');
                SLCAdmin.Utils.copyToClipboard(link);
            });

            // Quick actions
            $('.slc-quick-action').on('click', function(e) {
                e.preventDefault();
                var action = $(this).data('action');
                var linkId = $(this).data('link-id');
                SLCAdmin.Dashboard.performQuickAction(action, linkId);
            });
        },

        performQuickAction: function(action, linkId) {
            $.post(config.ajax_url, {
                action: 'slc_quick_action',
                quick_action: action,
                link_id: linkId,
                nonce: config.nonce
            }, function(response) {
                if (response.success) {
                    SLCAdmin.Utils.showNotification(response.data.message, 'success');
                    location.reload();
                } else {
                    SLCAdmin.Utils.showNotification(response.data.message, 'error');
                }
            });
        }
    };

    /**
     * Link creation functionality
     */
    SLCAdmin.LinkCreator = {
        init: function() {
            // Auto-generate slug from title
            $('#title').on('keyup', SLCAdmin.LinkCreator.generateSlug);
            
            // Update slug preview
            $('#slug').on('keyup', SLCAdmin.LinkCreator.updateSlugPreview);
            
            // Show/hide keywords field
            $('#smart_link_checkbox').on('change', SLCAdmin.LinkCreator.toggleKeywordsField);
            
            // Form validation
            $('.slc-create-link-form').on('submit', SLCAdmin.LinkCreator.validateForm);
            
            // URL validation
            $('#target_url').on('blur', SLCAdmin.LinkCreator.validateUrl);
        },

        generateSlug: function() {
            var title = $(this).val();
            var slug = title.toLowerCase()
                .replace(/[^\w\s-]/g, '')
                .replace(/\s+/g, '-')
                .replace(/-+/g, '-')
                .trim();
            $('#slug').val(slug);
            SLCAdmin.LinkCreator.updateSlugPreview();
        },

        updateSlugPreview: function() {
            var slug = $('#slug').val() || 'seu-slug';
            $('#slug-preview').text(slug);
        },

        toggleKeywordsField: function() {
            if ($(this).is(':checked')) {
                $('#keywords_row').show();
            } else {
                $('#keywords_row').hide();
            }
        },

        validateForm: function(e) {
            var title = $('#title').val().trim();
            var slug = $('#slug').val().trim();
            var targetUrl = $('#target_url').val().trim();
            
            if (!title || !slug || !targetUrl) {
                e.preventDefault();
                SLCAdmin.Utils.showNotification('Por favor, preencha todos os campos obrigatórios.', 'error');
                return false;
            }
            
            var urlPattern = /^https?:\/\/.+/;
            if (!urlPattern.test(targetUrl)) {
                e.preventDefault();
                SLCAdmin.Utils.showNotification('Por favor, insira uma URL válida.', 'error');
                return false;
            }

            return true;
        },

        validateUrl: function() {
            var url = $(this).val().trim();
            if (url && !/^https?:\/\/.+/.test(url)) {
                $(this).addClass('error');
                SLCAdmin.Utils.showNotification('URL deve começar com http:// ou https://', 'error');
            } else {
                $(this).removeClass('error');
            }
        }
    };

    /**
     * Page cloner functionality
     */
    SLCAdmin.PageCloner = {
        init: function() {
            $('.slc-clone-form').on('submit', SLCAdmin.PageCloner.handleCloneSubmit);
        },

        handleCloneSubmit: function(e) {
            e.preventDefault();
            
            var form = $(this);
            var submitBtn = form.find('input[type="submit"]');
            var spinner = $('#clone-spinner');
            var progressDiv = $('#clone-progress');
            var resultDiv = $('#clone-result');
            
            var url = $('#clone_url').val().trim();
            if (!url) {
                SLCAdmin.Utils.showNotification('Por favor, insira uma URL válida.', 'error');
                return false;
            }
            
            // Show progress
            submitBtn.prop('disabled', true);
            spinner.addClass('is-active');
            progressDiv.show();
            resultDiv.hide();
            
            // Start progress simulation
            SLCAdmin.PageCloner.simulateProgress();
            
            // Submit form via AJAX
            $.ajax({
                url: config.ajax_url,
                method: 'POST',
                data: {
                    action: 'slc_clone_page',
                    nonce: config.nonce,
                    url: url,
                    title: $('#page_title').val(),
                    download_images: $('input[name="download_images"]').is(':checked') ? 1 : 0,
                    process_css: $('input[name="process_css"]').is(':checked') ? 1 : 0,
                    remove_scripts: $('input[name="remove_scripts"]').is(':checked') ? 1 : 0,
                    fix_links: $('input[name="fix_links"]').is(':checked') ? 1 : 0,
                    post_status: $('#post_status').val()
                },
                success: function(response) {
                    SLCAdmin.PageCloner.completeProgress();
                    
                    setTimeout(function() {
                        progressDiv.hide();
                        resultDiv.show();
                        
                        if (response.success) {
                            $('#result-content').html(
                                '<div class="notice notice-success"><p>Página clonada com sucesso!</p></div>' +
                                '<p><strong>Título:</strong> ' + response.data.title + '</p>' +
                                '<p><strong>Ações:</strong></p>' +
                                '<a href="' + response.data.edit_url + '" class="button button-primary">Editar Página</a> ' +
                                '<a href="' + response.data.view_url + '" class="button button-secondary" target="_blank">Visualizar</a>'
                            );
                        } else {
                            $('#result-content').html(
                                '<div class="notice notice-error"><p>' + response.data.message + '</p></div>'
                            );
                        }
                    }, 1000);
                },
                error: function() {
                    SLCAdmin.PageCloner.completeProgress();
                    progressDiv.hide();
                    resultDiv.show();
                    $('#result-content').html(
                        '<div class="notice notice-error"><p>Erro durante a clonagem. Tente novamente.</p></div>'
                    );
                },
                complete: function() {
                    submitBtn.prop('disabled', false);
                    spinner.removeClass('is-active');
                }
            });
        },

        simulateProgress: function() {
            var progress = 0;
            window.slcProgressInterval = setInterval(function() {
                progress += Math.random() * 15;
                if (progress > 90) progress = 90;
                
                $('#progress-fill').css('width', progress + '%');
                
                if (progress < 30) {
                    $('#progress-text').text('Baixando conteúdo da página...');
                } else if (progress < 60) {
                    $('#progress-text').text('Processando imagens e assets...');
                } else {
                    $('#progress-text').text('Criando página no WordPress...');
                }
            }, 500);
        },

        completeProgress: function() {
            if (window.slcProgressInterval) {
                clearInterval(window.slcProgressInterval);
            }
            $('#progress-fill').css('width', '100%');
            $('#progress-text').text('Clonagem concluída!');
        }
    };

    /**
     * Analytics functionality
     */
    SLCAdmin.Analytics = {
        init: function() {
            // Load analytics data
            if ($('#analytics-container').length) {
                SLCAdmin.Analytics.loadAnalytics();
            }

            // Export functionality
            $('.slc-export-analytics').on('click', SLCAdmin.Analytics.exportAnalytics);

            // Refresh analytics
            $('.slc-refresh-analytics').on('click', SLCAdmin.Analytics.refreshAnalytics);
        },

        loadAnalytics: function() {
            var linkId = $('#analytics-container').data('link-id') || 0;
            
            $.post(config.ajax_url, {
                action: 'slc_get_analytics',
                link_id: linkId,
                nonce: config.nonce
            }, function(response) {
                if (response.success) {
                    SLCAdmin.Analytics.renderAnalytics(response.data);
                }
            });
        },

        renderAnalytics: function(data) {
            // Render analytics charts and data
            if (typeof Chart !== 'undefined') {
                SLCAdmin.Analytics.renderCharts(data);
            }
            SLCAdmin.Analytics.renderTables(data);
        },

        renderCharts: function(data) {
            // Implementation for charts if Chart.js is available
            console.log('Charts data:', data);
        },

        renderTables: function(data) {
            // Render analytics tables
            console.log('Table data:', data);
        },

        exportAnalytics: function(e) {
            e.preventDefault();
            var linkId = $(this).data('link-id') || 0;
            
            window.location.href = config.ajax_url + '?action=slc_export_analytics&link_id=' + linkId + '&nonce=' + config.nonce;
        },

        refreshAnalytics: function(e) {
            e.preventDefault();
            SLCAdmin.Analytics.loadAnalytics();
            SLCAdmin.Utils.showNotification('Analytics atualizados!', 'success');
        }
    };

    /**
     * Link import functionality
     */
    SLCAdmin.LinkImporter = {
        init: function() {
            $('.slc-import-form').on('submit', SLCAdmin.LinkImporter.handleImport);
            $('.slc-import-source').on('change', SLCAdmin.LinkImporter.toggleImportOptions);
        },

        handleImport: function(e) {
            e.preventDefault();
            
            var form = $(this);
            var source = $('input[name="import_source"]:checked').val();
            
            if (!source) {
                SLCAdmin.Utils.showNotification('Selecione uma fonte de importação.', 'error');
                return false;
            }

            SLCAdmin.Utils.showLoading(form);
            
            $.post(config.ajax_url, {
                action: 'slc_import_links',
                source: source,
                nonce: config.nonce
            }, function(response) {
                SLCAdmin.Utils.hideLoading(form);
                
                if (response.success) {
                    SLCAdmin.Utils.showNotification(response.data.message, 'success');
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    SLCAdmin.Utils.showNotification(response.data.message, 'error');
                }
            });
        },

        toggleImportOptions: function() {
            var source = $(this).val();
            $('.slc-import-options').hide();
            $('.slc-import-options[data-source="' + source + '"]').show();
        }
    };

    /**
     * Settings functionality
     */
    SLCAdmin.Settings = {
        init: function() {
            // Save settings
            $('.slc-settings-form').on('submit', SLCAdmin.Settings.saveSettings);
            
            // Reset settings
            $('.slc-reset-settings').on('click', SLCAdmin.Settings.resetSettings);
            
            // Test connection
            $('.slc-test-connection').on('click', SLCAdmin.Settings.testConnection);
        },

        saveSettings: function(e) {
            e.preventDefault();
            
            var form = $(this);
            SLCAdmin.Utils.showLoading(form);
            
            $.post(form.attr('action'), form.serialize(), function(response) {
                SLCAdmin.Utils.hideLoading(form);
                
                if (response && response.indexOf('settings_saved') !== -1) {
                    SLCAdmin.Utils.showNotification('Configurações salvas com sucesso!', 'success');
                } else {
                    SLCAdmin.Utils.showNotification('Erro ao salvar configurações.', 'error');
                }
            });
        },

        resetSettings: function(e) {
            e.preventDefault();
            
            if (confirm('Tem certeza que deseja restaurar as configurações padrão?')) {
                $.post(config.ajax_url, {
                    action: 'slc_reset_settings',
                    nonce: config.nonce
                }, function(response) {
                    if (response.success) {
                        SLCAdmin.Utils.showNotification('Configurações restauradas!', 'success');
                        location.reload();
                    } else {
                        SLCAdmin.Utils.showNotification('Erro ao restaurar configurações.', 'error');
                    }
                });
            }
        },

        testConnection: function(e) {
            e.preventDefault();
            
            var button = $(this);
            button.prop('disabled', true).text('Testando...');
            
            $.post(config.ajax_url, {
                action: 'slc_test_connection',
                nonce: config.nonce
            }, function(response) {
                button.prop('disabled', false).text('Testar Conexão');
                
                if (response.success) {
                    SLCAdmin.Utils.showNotification('Conexão OK!', 'success');
                } else {
                    SLCAdmin.Utils.showNotification('Erro de conexão: ' + response.data.message, 'error');
                }
            });
        }
    };

    /**
     * Utility functions
     */
    SLCAdmin.Utils = {
        showNotification: function(message, type) {
            type = type || 'info';
            
            // Remove existing notifications
            $('.slc-notification').remove();
            
            var notification = $('<div class="notice notice-' + type + ' is-dismissible slc-notification"><p>' + message + '</p></div>');
            $('.wrap h1').after(notification);
            
            // Auto dismiss after 5 seconds
            setTimeout(function() {
                notification.fadeOut();
            }, 5000);
        },

        showLoading: function(element) {
            $(element).addClass('slc-loading').find('input, button').prop('disabled', true);
        },

        hideLoading: function(element) {
            $(element).removeClass('slc-loading').find('input, button').prop('disabled', false);
        },

        copyToClipboard: function(text) {
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text).then(function() {
                    SLCAdmin.Utils.showNotification('Link copiado para a área de transferência!', 'success');
                });
            } else {
                // Fallback for older browsers
                var textArea = document.createElement('textarea');
                textArea.value = text;
                document.body.appendChild(textArea);
                textArea.select();
                try {
                    document.execCommand('copy');
                    SLCAdmin.Utils.showNotification('Link copiado!', 'success');
                } catch (err) {
                    SLCAdmin.Utils.showNotification('Erro ao copiar link.', 'error');
                }
                document.body.removeChild(textArea);
            }
        },

        confirm: function(message, callback) {
            if (confirm(message)) {
                callback();
            }
        }
    };

    /**
     * Initialize all admin functionality
     */
    SLCAdmin.init = function() {
        SLCAdmin.Dashboard.init();
        SLCAdmin.LinkCreator.init();
        SLCAdmin.PageCloner.init();
        SLCAdmin.Analytics.init();
        SLCAdmin.LinkImporter.init();
        SLCAdmin.Settings.init();

        console.log('Super Links Clone admin initialized');
    };

    // Initialize when DOM is ready
    $(document).ready(function() {
        SLCAdmin.init();
    });

})(jQuery);