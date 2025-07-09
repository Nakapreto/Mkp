/**
 * Site Cloner Admin JavaScript
 *
 * @package Site_Cloner
 * @since 1.0.0
 */

(function($) {
    'use strict';
    
    /**
     * Site Cloner Admin Object
     */
    var SiteClonerAdmin = {
        
        /**
         * Initialize
         */
        init: function() {
            this.bindEvents();
            this.initProgressTracking();
            this.initLogModal();
            this.checkOngoingJobs();
        },
        
        /**
         * Bind Events
         */
        bindEvents: function() {
            // Clone form submission
            $('#site-cloner-form').on('submit', this.handleCloneSubmit);
            
            // Import form submission
            $('#site-cloner-import-form').on('submit', this.handleImportSubmit);
            
            // View log buttons
            $('.view-log').on('click', this.showLogModal);
            
            // Modal close
            $('.site-cloner-modal .close').on('click', this.closeModal);
            $(window).on('click', this.closeModalOutside);
            
            // Cancel job buttons
            $('.cancel-job').on('click', this.cancelJob);
            
            // URL input validation
            $('#site_url').on('blur', this.validateUrl);
            
            // File input validation
            $('#import_file').on('change', this.validateFile);
        },
        
        /**
         * Handle clone form submission
         */
        handleCloneSubmit: function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var $button = $form.find('input[type="submit"]');
            var $spinner = $form.find('.spinner');
            var url = $('#site_url').val();
            
            // Validate URL
            if (!SiteClonerAdmin.isValidUrl(url)) {
                SiteClonerAdmin.showMessage('URL inválida. Por favor, digite uma URL válida.', 'error');
                return;
            }
            
            // Confirm action
            if (!confirm(siteCloner.strings.confirm_clone)) {
                return;
            }
            
            // Prepare data
            var formData = {
                action: 'site_cloner_start_clone',
                nonce: siteCloner.nonce,
                url: url,
                page_title: $('#page_title').val(),
                page_status: $('#page_status').val(),
                clone_images: $('#clone_images:checked').length ? 1 : 0,
                clone_videos: $('#clone_videos:checked').length ? 1 : 0,
                clone_fonts: $('#clone_fonts:checked').length ? 1 : 0,
                clone_css: $('#clone_css:checked').length ? 1 : 0,
                clone_js: $('#clone_js:checked').length ? 1 : 0,
                elementor_support: $('#elementor_support:checked').length ? 1 : 0
            };
            
            // Disable form
            $button.prop('disabled', true);
            $spinner.addClass('is-active');
            $form.addClass('site-cloner-loading');
            
            // Submit request
            $.post(siteCloner.ajaxUrl, formData)
                .done(function(response) {
                    if (response.success) {
                        SiteClonerAdmin.showMessage(response.data.message, 'success');
                        SiteClonerAdmin.startProgressTracking(response.data.job_id);
                        
                        // Reset form
                        $form[0].reset();
                    } else {
                        SiteClonerAdmin.showMessage(response.data || 'Erro desconhecido', 'error');
                    }
                })
                .fail(function() {
                    SiteClonerAdmin.showMessage('Erro de conexão. Tente novamente.', 'error');
                })
                .always(function() {
                    $button.prop('disabled', false);
                    $spinner.removeClass('is-active');
                    $form.removeClass('site-cloner-loading');
                });
        },
        
        /**
         * Handle import form submission
         */
        handleImportSubmit: function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var $button = $form.find('input[type="submit"]');
            var $spinner = $form.find('.spinner');
            var fileInput = $('#import_file')[0];
            
            // Validate file
            if (!fileInput.files.length) {
                SiteClonerAdmin.showMessage('Por favor, selecione um arquivo ZIP.', 'error');
                return;
            }
            
            // Validate page title
            if (!$('#import_page_title').val().trim()) {
                SiteClonerAdmin.showMessage('Por favor, digite um título para a página.', 'error');
                return;
            }
            
            // Prepare form data
            var formData = new FormData();
            formData.append('action', 'site_cloner_import_zip');
            formData.append('nonce', siteCloner.nonce);
            formData.append('import_file', fileInput.files[0]);
            formData.append('page_title', $('#import_page_title').val());
            formData.append('page_status', $('#import_page_status').val());
            
            // Disable form
            $button.prop('disabled', true);
            $spinner.addClass('is-active');
            $form.addClass('site-cloner-loading');
            
            // Submit request
            $.ajax({
                url: siteCloner.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                timeout: 300000 // 5 minutes
            })
            .done(function(response) {
                if (response.success) {
                    SiteClonerAdmin.showMessage(response.data.message, 'success');
                    
                    // Reset form
                    $form[0].reset();
                    
                    // Redirect to edit page if available
                    if (response.data.page_id) {
                        setTimeout(function() {
                            window.open('/wp-admin/post.php?post=' + response.data.page_id + '&action=edit', '_blank');
                        }, 2000);
                    }
                } else {
                    SiteClonerAdmin.showMessage(response.data || 'Erro desconhecido', 'error');
                }
            })
            .fail(function(xhr) {
                if (xhr.statusText === 'timeout') {
                    SiteClonerAdmin.showMessage('Tempo limite excedido. O arquivo pode ser muito grande.', 'error');
                } else {
                    SiteClonerAdmin.showMessage('Erro de conexão. Tente novamente.', 'error');
                }
            })
            .always(function() {
                $button.prop('disabled', false);
                $spinner.removeClass('is-active');
                $form.removeClass('site-cloner-loading');
            });
        },
        
        /**
         * Start progress tracking for a job
         */
        startProgressTracking: function(jobId) {
            if (this.progressInterval) {
                clearInterval(this.progressInterval);
            }
            
            this.progressInterval = setInterval(function() {
                SiteClonerAdmin.checkJobProgress(jobId);
            }, 2000);
            
            // Store job ID for page refresh
            sessionStorage.setItem('site_cloner_tracking_job', jobId);
        },
        
        /**
         * Check job progress
         */
        checkJobProgress: function(jobId) {
            $.post(siteCloner.ajaxUrl, {
                action: 'site_cloner_check_progress',
                nonce: siteCloner.nonce,
                job_id: jobId
            })
            .done(function(response) {
                if (response.success) {
                    var data = response.data;
                    
                    // Update progress if on status page
                    if ($('.progress-bar').length) {
                        $('.progress-fill').css('width', data.progress + '%');
                        $('.progress-text').text(data.progress + '%');
                    }
                    
                    // Show recent messages
                    if (data.messages && data.messages.length) {
                        SiteClonerAdmin.updateProgressMessages(data.messages);
                    }
                    
                    // Stop tracking if completed
                    if (data.completed) {
                        clearInterval(SiteClonerAdmin.progressInterval);
                        sessionStorage.removeItem('site_cloner_tracking_job');
                        
                        if (data.status === 'completed') {
                            SiteClonerAdmin.showMessage('Clone concluído com sucesso!', 'success');
                        } else {
                            SiteClonerAdmin.showMessage('Clone falhou. Verifique os logs para mais detalhes.', 'error');
                        }
                        
                        // Refresh status page if we're on it
                        if (window.location.href.indexOf('site-cloner-status') > -1) {
                            setTimeout(function() {
                                location.reload();
                            }, 2000);
                        }
                    }
                }
            });
        },
        
        /**
         * Initialize progress tracking
         */
        initProgressTracking: function() {
            // Check if there's an ongoing job to track
            var trackingJobId = sessionStorage.getItem('site_cloner_tracking_job');
            if (trackingJobId) {
                this.startProgressTracking(trackingJobId);
            }
        },
        
        /**
         * Update progress messages
         */
        updateProgressMessages: function(messages) {
            var $messagesContainer = $('#progress-messages');
            if (!$messagesContainer.length) {
                // Create messages container if it doesn't exist
                $messagesContainer = $('<div id="progress-messages" class="site-cloner-progress-messages"></div>');
                $('.site-cloner-container').prepend($messagesContainer);
            }
            
            $messagesContainer.empty();
            
            messages.forEach(function(message) {
                var $messageEl = $('<div class="log-message ' + message.level + '">');
                $messageEl.append('<span class="log-timestamp">' + message.time + '</span>');
                $messageEl.append('<div>' + message.message + '</div>');
                $messagesContainer.append($messageEl);
            });
        },
        
        /**
         * Show log modal
         */
        showLogModal: function(e) {
            e.preventDefault();
            
            var jobId = $(this).data('job-id');
            
            $.post(siteCloner.ajaxUrl, {
                action: 'site_cloner_get_log',
                nonce: siteCloner.nonce,
                job_id: jobId
            })
            .done(function(response) {
                if (response.success) {
                    var log = response.data.log;
                    var content = '';
                    
                    if (log.messages && log.messages.length) {
                        log.messages.forEach(function(message) {
                            content += '[' + message.time + '] ' + message.level.toUpperCase() + ': ' + message.message + '\n';
                        });
                    } else {
                        content = 'Nenhum log disponível.';
                    }
                    
                    $('#log-content').text(content);
                    $('#log-modal').show();
                } else {
                    SiteClonerAdmin.showMessage('Erro ao carregar log', 'error');
                }
            });
        },
        
        /**
         * Initialize log modal
         */
        initLogModal: function() {
            // Create modal if it doesn't exist
            if (!$('#log-modal').length) {
                var modal = $('<div id="log-modal" class="site-cloner-modal" style="display: none;">' +
                    '<div class="modal-content">' +
                    '<span class="close">&times;</span>' +
                    '<h2>Log do Job</h2>' +
                    '<div id="log-content"></div>' +
                    '</div>' +
                    '</div>');
                $('body').append(modal);
            }
        },
        
        /**
         * Close modal
         */
        closeModal: function() {
            $('.site-cloner-modal').hide();
        },
        
        /**
         * Close modal when clicking outside
         */
        closeModalOutside: function(e) {
            if ($(e.target).hasClass('site-cloner-modal')) {
                $('.site-cloner-modal').hide();
            }
        },
        
        /**
         * Cancel a job
         */
        cancelJob: function(e) {
            e.preventDefault();
            
            if (!confirm('Tem certeza que deseja cancelar este job?')) {
                return;
            }
            
            var jobId = $(this).data('job-id');
            
            $.post(siteCloner.ajaxUrl, {
                action: 'site_cloner_cancel_job',
                nonce: siteCloner.nonce,
                job_id: jobId
            })
            .done(function(response) {
                if (response.success) {
                    SiteClonerAdmin.showMessage('Job cancelado', 'success');
                    location.reload();
                } else {
                    SiteClonerAdmin.showMessage('Erro ao cancelar job', 'error');
                }
            });
        },
        
        /**
         * Validate URL
         */
        validateUrl: function() {
            var url = $(this).val();
            var $feedback = $(this).siblings('.url-feedback');
            
            if (url && !SiteClonerAdmin.isValidUrl(url)) {
                if (!$feedback.length) {
                    $feedback = $('<div class="url-feedback site-cloner-message error">URL inválida</div>');
                    $(this).after($feedback);
                } else {
                    $feedback.text('URL inválida').removeClass('success').addClass('error');
                }
            } else if (url) {
                if (!$feedback.length) {
                    $feedback = $('<div class="url-feedback site-cloner-message success">URL válida</div>');
                    $(this).after($feedback);
                } else {
                    $feedback.text('URL válida').removeClass('error').addClass('success');
                }
            } else {
                $feedback.remove();
            }
        },
        
        /**
         * Validate file
         */
        validateFile: function() {
            var file = this.files[0];
            var $feedback = $(this).siblings('.file-feedback');
            
            if (file) {
                var isValidType = file.type === 'application/zip' || file.name.toLowerCase().endsWith('.zip');
                var isValidSize = file.size <= 100 * 1024 * 1024; // 100MB
                
                if (!isValidType) {
                    if (!$feedback.length) {
                        $feedback = $('<div class="file-feedback site-cloner-message error">Apenas arquivos ZIP são permitidos</div>');
                        $(this).after($feedback);
                    } else {
                        $feedback.text('Apenas arquivos ZIP são permitidos').removeClass('success').addClass('error');
                    }
                } else if (!isValidSize) {
                    if (!$feedback.length) {
                        $feedback = $('<div class="file-feedback site-cloner-message error">Arquivo muito grande (máximo 100MB)</div>');
                        $(this).after($feedback);
                    } else {
                        $feedback.text('Arquivo muito grande (máximo 100MB)').removeClass('success').addClass('error');
                    }
                } else {
                    if (!$feedback.length) {
                        $feedback = $('<div class="file-feedback site-cloner-message success">Arquivo válido</div>');
                        $(this).after($feedback);
                    } else {
                        $feedback.text('Arquivo válido').removeClass('error').addClass('success');
                    }
                }
            } else {
                $feedback.remove();
            }
        },
        
        /**
         * Check for ongoing jobs on page load
         */
        checkOngoingJobs: function() {
            // Only on status page
            if (window.location.href.indexOf('site-cloner-status') === -1) {
                return;
            }
            
            // Check if there are any processing jobs
            $('.status-processing, .status-pending').each(function() {
                var row = $(this).closest('tr');
                var jobId = row.find('.view-log').data('job-id');
                
                if (jobId && !SiteClonerAdmin.progressInterval) {
                    SiteClonerAdmin.startProgressTracking(jobId);
                    return false; // Only track one job at a time
                }
            });
        },
        
        /**
         * Show message
         */
        showMessage: function(message, type) {
            // Remove existing messages
            $('.site-cloner-message').remove();
            
            var $message = $('<div class="site-cloner-message ' + type + '">' + message + '</div>');
            
            // Insert after page title
            if ($('.wrap h1').length) {
                $('.wrap h1').after($message);
            } else {
                $('.wrap').prepend($message);
            }
            
            // Auto-hide success messages
            if (type === 'success') {
                setTimeout(function() {
                    $message.fadeOut(500, function() {
                        $(this).remove();
                    });
                }, 5000);
            }
        },
        
        /**
         * Check if URL is valid
         */
        isValidUrl: function(url) {
            try {
                new URL(url);
                return url.startsWith('http://') || url.startsWith('https://');
            } catch {
                return false;
            }
        },
        
        /**
         * Progress interval ID
         */
        progressInterval: null
    };
    
    /**
     * Initialize when document is ready
     */
    $(document).ready(function() {
        SiteClonerAdmin.init();
    });
    
    /**
     * Make SiteClonerAdmin globally accessible
     */
    window.SiteClonerAdmin = SiteClonerAdmin;
    
})(jQuery);