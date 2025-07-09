<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php _e('Criar Novo Link', 'super-links-clone'); ?></h1>
    
    <form method="post" action="" class="slc-create-link-form">
        <?php wp_nonce_field('slc_create_link', 'slc_nonce'); ?>
        
        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="title"><?php _e('Título', 'super-links-clone'); ?> *</label>
                    </th>
                    <td>
                        <input type="text" id="title" name="title" class="regular-text" required>
                        <p class="description"><?php _e('Nome descritivo para o link.', 'super-links-clone'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="slug"><?php _e('Slug', 'super-links-clone'); ?> *</label>
                    </th>
                    <td>
                        <input type="text" id="slug" name="slug" class="regular-text" required>
                        <p class="description">
                            <?php printf(__('URL será: %s', 'super-links-clone'), '<code>' . home_url('/' . get_option('slc_link_prefix', 'go') . '/') . '<span id="slug-preview">seu-slug</span></code>'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="target_url"><?php _e('URL de Destino', 'super-links-clone'); ?> *</label>
                    </th>
                    <td>
                        <input type="url" id="target_url" name="target_url" class="regular-text" required>
                        <p class="description"><?php _e('URL para onde o link irá redirecionar.', 'super-links-clone'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="category"><?php _e('Categoria', 'super-links-clone'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="category" name="category" class="regular-text">
                        <p class="description"><?php _e('Categoria para organização (opcional).', 'super-links-clone'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="redirect_type"><?php _e('Tipo de Redirecionamento', 'super-links-clone'); ?></label>
                    </th>
                    <td>
                        <select id="redirect_type" name="redirect_type">
                            <option value="301"><?php _e('301 - Permanente', 'super-links-clone'); ?></option>
                            <option value="302"><?php _e('302 - Temporário', 'super-links-clone'); ?></option>
                            <option value="307"><?php _e('307 - Temporário (POST mantido)', 'super-links-clone'); ?></option>
                        </select>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <h3><?php _e('Opções Avançadas', 'super-links-clone'); ?></h3>
        
        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row"><?php _e('Recursos', 'super-links-clone'); ?></th>
                    <td>
                        <fieldset>
                            <label>
                                <input type="checkbox" name="cloaked" value="1" checked>
                                <?php _e('Link Camuflado', 'super-links-clone'); ?>
                            </label>
                            <p class="description"><?php _e('Mostra o conteúdo da página de destino no seu domínio.', 'super-links-clone'); ?></p>
                            
                            <label>
                                <input type="checkbox" name="facebook_cloaked" value="1">
                                <?php _e('Clocker do Facebook', 'super-links-clone'); ?>
                            </label>
                            <p class="description"><?php _e('Protege contra detecção do Facebook Ads.', 'super-links-clone'); ?></p>
                            
                            <label>
                                <input type="checkbox" name="cookie_tracking" value="1" checked>
                                <?php _e('Rastreamento de Cookies', 'super-links-clone'); ?>
                            </label>
                            <p class="description"><?php _e('Ativa marcação dupla de cookies para proteção de comissões.', 'super-links-clone'); ?></p>
                            
                            <label>
                                <input type="checkbox" name="smart_link" value="1" id="smart_link_checkbox">
                                <?php _e('Link Inteligente', 'super-links-clone'); ?>
                            </label>
                            <p class="description"><?php _e('Substitui palavras-chave automaticamente no conteúdo.', 'super-links-clone'); ?></p>
                        </fieldset>
                    </td>
                </tr>
                
                <tr id="keywords_row" style="display: none;">
                    <th scope="row">
                        <label for="keywords"><?php _e('Palavras-chave', 'super-links-clone'); ?></label>
                    </th>
                    <td>
                        <textarea id="keywords" name="keywords" class="large-text" rows="3"></textarea>
                        <p class="description"><?php _e('Palavras-chave separadas por vírgula que serão substituídas por este link.', 'super-links-clone'); ?></p>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <p class="submit">
            <input type="submit" name="submit_link" class="button button-primary" value="<?php _e('Criar Link', 'super-links-clone'); ?>">
            <a href="<?php echo admin_url('admin.php?page=super-links-manage'); ?>" class="button button-secondary">
                <?php _e('Cancelar', 'super-links-clone'); ?>
            </a>
        </p>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    // Auto-generate slug from title
    $('#title').on('keyup', function() {
        var title = $(this).val();
        var slug = title.toLowerCase()
            .replace(/[^\w\s-]/g, '') // Remove special characters
            .replace(/\s+/g, '-') // Replace spaces with dashes
            .replace(/-+/g, '-') // Replace multiple dashes with single dash
            .trim();
        $('#slug').val(slug);
        $('#slug-preview').text(slug || 'seu-slug');
    });
    
    // Update slug preview when manually editing
    $('#slug').on('keyup', function() {
        $('#slug-preview').text($(this).val() || 'seu-slug');
    });
    
    // Show/hide keywords field based on smart link checkbox
    $('#smart_link_checkbox').on('change', function() {
        if ($(this).is(':checked')) {
            $('#keywords_row').show();
        } else {
            $('#keywords_row').hide();
        }
    });
    
    // Form validation
    $('.slc-create-link-form').on('submit', function(e) {
        var title = $('#title').val().trim();
        var slug = $('#slug').val().trim();
        var targetUrl = $('#target_url').val().trim();
        
        if (!title || !slug || !targetUrl) {
            e.preventDefault();
            alert('<?php _e('Por favor, preencha todos os campos obrigatórios.', 'super-links-clone'); ?>');
            return false;
        }
        
        // Validate URL format
        var urlPattern = /^https?:\/\/.+/;
        if (!urlPattern.test(targetUrl)) {
            e.preventDefault();
            alert('<?php _e('Por favor, insira uma URL válida (começando com http:// ou https://).', 'super-links-clone'); ?>');
            return false;
        }
    });
});
</script>

<style>
.slc-create-link-form .form-table th {
    width: 200px;
}

.slc-create-link-form fieldset label {
    display: block;
    margin: 10px 0;
}

.slc-create-link-form fieldset label input[type="checkbox"] {
    margin-right: 8px;
}

#slug-preview {
    color: #0073aa;
    font-weight: bold;
}
</style>