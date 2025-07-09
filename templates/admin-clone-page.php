<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php _e('Clonar Página da Internet', 'super-links-clone'); ?></h1>
    
    <div class="slc-clone-info">
        <div class="notice notice-info">
            <p>
                <strong><?php _e('Como funciona:', 'super-links-clone'); ?></strong>
                <?php _e('Digite a URL de qualquer página da internet e nosso sistema irá cloná-la completamente para o seu WordPress, incluindo imagens, estilos e scripts. A página clonada será salva como rascunho para você revisar antes de publicar.', 'super-links-clone'); ?>
            </p>
        </div>
    </div>
    
    <form method="post" action="" class="slc-clone-form">
        <?php wp_nonce_field('slc_clone_page', 'slc_nonce'); ?>
        
        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="clone_url"><?php _e('URL da Página', 'super-links-clone'); ?> *</label>
                    </th>
                    <td>
                        <input type="url" id="clone_url" name="clone_url" class="large-text" required placeholder="https://exemplo.com/pagina-para-clonar">
                        <p class="description"><?php _e('URL completa da página que você deseja clonar.', 'super-links-clone'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="page_title"><?php _e('Título da Página', 'super-links-clone'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="page_title" name="page_title" class="large-text" placeholder="<?php _e('Deixe em branco para usar o título original', 'super-links-clone'); ?>">
                        <p class="description"><?php _e('Se deixar em branco, será usado o título da página original.', 'super-links-clone'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Opções de Clonagem', 'super-links-clone'); ?></th>
                    <td>
                        <fieldset>
                            <label>
                                <input type="checkbox" name="download_images" value="1" checked>
                                <?php _e('Baixar imagens para a biblioteca de mídia', 'super-links-clone'); ?>
                            </label>
                            <p class="description"><?php _e('Faz download das imagens e salva na biblioteca de mídia do WordPress.', 'super-links-clone'); ?></p>
                            
                            <label>
                                <input type="checkbox" name="process_css" value="1" checked>
                                <?php _e('Processar arquivos CSS', 'super-links-clone'); ?>
                            </label>
                            <p class="description"><?php _e('Baixa e processa arquivos CSS externos.', 'super-links-clone'); ?></p>
                            
                            <label>
                                <input type="checkbox" name="remove_scripts" value="1" checked>
                                <?php _e('Remover scripts desnecessários', 'super-links-clone'); ?>
                            </label>
                            <p class="description"><?php _e('Remove scripts de analytics, tracking e outros que podem interferir.', 'super-links-clone'); ?></p>
                            
                            <label>
                                <input type="checkbox" name="fix_links" value="1" checked>
                                <?php _e('Corrigir links relativos', 'super-links-clone'); ?>
                            </label>
                            <p class="description"><?php _e('Converte links relativos em absolutos.', 'super-links-clone'); ?></p>
                        </fieldset>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="post_status"><?php _e('Status da Página', 'super-links-clone'); ?></label>
                    </th>
                    <td>
                        <select id="post_status" name="post_status">
                            <option value="draft"><?php _e('Rascunho', 'super-links-clone'); ?></option>
                            <option value="private"><?php _e('Privado', 'super-links-clone'); ?></option>
                            <option value="publish"><?php _e('Publicado', 'super-links-clone'); ?></option>
                        </select>
                        <p class="description"><?php _e('Recomendamos usar "Rascunho" para revisar antes de publicar.', 'super-links-clone'); ?></p>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <p class="submit">
            <input type="submit" name="submit_clone" class="button button-primary" value="<?php _e('Clonar Página', 'super-links-clone'); ?>">
            <span class="spinner" id="clone-spinner"></span>
        </p>
    </form>
    
    <div id="clone-progress" style="display: none;">
        <h3><?php _e('Progresso da Clonagem', 'super-links-clone'); ?></h3>
        <div class="slc-progress-bar">
            <div class="slc-progress-fill" id="progress-fill"></div>
        </div>
        <p id="progress-text"><?php _e('Iniciando clonagem...', 'super-links-clone'); ?></p>
    </div>
    
    <div id="clone-result" style="display: none;">
        <h3><?php _e('Resultado da Clonagem', 'super-links-clone'); ?></h3>
        <div id="result-content"></div>
    </div>
    
    <?php
    // Show recent cloned pages
    $page_cloner = new SLC_Page_Cloner();
    $cloned_pages = $page_cloner->get_cloned_pages();
    
    if (!empty($cloned_pages)):
    ?>
    <div class="slc-recent-clones">
        <h2><?php _e('Páginas Clonadas Recentemente', 'super-links-clone'); ?></h2>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Título', 'super-links-clone'); ?></th>
                    <th><?php _e('URL Original', 'super-links-clone'); ?></th>
                    <th><?php _e('Criado em', 'super-links-clone'); ?></th>
                    <th><?php _e('Ações', 'super-links-clone'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cloned_pages as $page): ?>
                    <tr>
                        <td>
                            <strong><?php echo esc_html($page->title); ?></strong>
                        </td>
                        <td>
                            <a href="<?php echo esc_url($page->original_url); ?>" target="_blank">
                                <?php echo esc_html($page->original_url); ?>
                            </a>
                        </td>
                        <td><?php echo date_i18n(get_option('date_format'), strtotime($page->created_at)); ?></td>
                        <td>
                            <?php if ($page->post_id): ?>
                                <a href="<?php echo get_edit_post_link($page->post_id); ?>" class="button button-small">
                                    <?php _e('Editar', 'super-links-clone'); ?>
                                </a>
                                <a href="<?php echo get_permalink($page->post_id); ?>" class="button button-small" target="_blank">
                                    <?php _e('Ver', 'super-links-clone'); ?>
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<script>
jQuery(document).ready(function($) {
    $('.slc-clone-form').on('submit', function(e) {
        e.preventDefault();
        
        var form = $(this);
        var submitBtn = form.find('input[type="submit"]');
        var spinner = $('#clone-spinner');
        var progressDiv = $('#clone-progress');
        var resultDiv = $('#clone-result');
        
        // Validate URL
        var url = $('#clone_url').val().trim();
        if (!url) {
            alert('<?php _e('Por favor, insira uma URL válida.', 'super-links-clone'); ?>');
            return false;
        }
        
        // Show progress
        submitBtn.prop('disabled', true);
        spinner.addClass('is-active');
        progressDiv.show();
        resultDiv.hide();
        
        // Simulate progress (in real implementation, you'd get actual progress via AJAX)
        var progress = 0;
        var progressInterval = setInterval(function() {
            progress += Math.random() * 15;
            if (progress > 90) progress = 90;
            
            $('#progress-fill').css('width', progress + '%');
            
            if (progress < 30) {
                $('#progress-text').text('<?php _e('Baixando conteúdo da página...', 'super-links-clone'); ?>');
            } else if (progress < 60) {
                $('#progress-text').text('<?php _e('Processando imagens e assets...', 'super-links-clone'); ?>');
            } else {
                $('#progress-text').text('<?php _e('Criando página no WordPress...', 'super-links-clone'); ?>');
            }
        }, 500);
        
        // Submit form via AJAX
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'slc_clone_page',
                nonce: $('input[name="slc_nonce"]').val(),
                url: url,
                title: $('#page_title').val(),
                download_images: $('input[name="download_images"]').is(':checked') ? 1 : 0,
                process_css: $('input[name="process_css"]').is(':checked') ? 1 : 0,
                remove_scripts: $('input[name="remove_scripts"]').is(':checked') ? 1 : 0,
                fix_links: $('input[name="fix_links"]').is(':checked') ? 1 : 0,
                post_status: $('#post_status').val()
            },
            success: function(response) {
                clearInterval(progressInterval);
                $('#progress-fill').css('width', '100%');
                $('#progress-text').text('<?php _e('Clonagem concluída!', 'super-links-clone'); ?>');
                
                setTimeout(function() {
                    progressDiv.hide();
                    resultDiv.show();
                    
                    if (response.success) {
                        $('#result-content').html(
                            '<div class="notice notice-success"><p>' +
                            '<?php _e('Página clonada com sucesso!', 'super-links-clone'); ?>' +
                            '</p></div>' +
                            '<p><strong><?php _e('Título:', 'super-links-clone'); ?></strong> ' + response.data.title + '</p>' +
                            '<p><strong><?php _e('Ações:', 'super-links-clone'); ?></strong></p>' +
                            '<a href="' + response.data.edit_url + '" class="button button-primary"><?php _e('Editar Página', 'super-links-clone'); ?></a> ' +
                            '<a href="' + response.data.view_url + '" class="button button-secondary" target="_blank"><?php _e('Visualizar', 'super-links-clone'); ?></a>'
                        );
                    } else {
                        $('#result-content').html(
                            '<div class="notice notice-error"><p>' +
                            response.data.message +
                            '</p></div>'
                        );
                    }
                }, 1000);
            },
            error: function() {
                clearInterval(progressInterval);
                progressDiv.hide();
                resultDiv.show();
                $('#result-content').html(
                    '<div class="notice notice-error"><p>' +
                    '<?php _e('Erro durante a clonagem. Tente novamente.', 'super-links-clone'); ?>' +
                    '</p></div>'
                );
            },
            complete: function() {
                submitBtn.prop('disabled', false);
                spinner.removeClass('is-active');
            }
        });
    });
});
</script>

<style>
.slc-clone-info {
    margin: 20px 0;
}

.slc-clone-form fieldset label {
    display: block;
    margin: 10px 0;
}

.slc-clone-form fieldset label input[type="checkbox"] {
    margin-right: 8px;
}

.slc-progress-bar {
    width: 100%;
    height: 20px;
    background: #ddd;
    border-radius: 10px;
    overflow: hidden;
    margin: 10px 0;
}

.slc-progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #0073aa, #005a87);
    width: 0%;
    transition: width 0.3s ease;
}

#clone-progress, #clone-result {
    margin: 20px 0;
    padding: 20px;
    background: #f9f9f9;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.slc-recent-clones {
    margin: 40px 0 0 0;
}
</style>