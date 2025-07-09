<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php _e('Gerenciar Links', 'super-links-clone'); ?></h1>
    
    <div class="slc-admin-header">
        <a href="<?php echo admin_url('admin.php?page=super-links-create'); ?>" class="button button-primary">
            <?php _e('Criar Novo Link', 'super-links-clone'); ?>
        </a>
    </div>
    
    <?php if (empty($links)): ?>
        <div class="slc-empty-state">
            <h3><?php _e('Nenhum link encontrado', 'super-links-clone'); ?></h3>
            <p><?php _e('Você ainda não criou nenhum link.', 'super-links-clone'); ?></p>
            <a href="<?php echo admin_url('admin.php?page=super-links-create'); ?>" class="button button-primary">
                <?php _e('Criar Primeiro Link', 'super-links-clone'); ?>
            </a>
        </div>
    <?php else: ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th scope="col"><?php _e('Título', 'super-links-clone'); ?></th>
                    <th scope="col"><?php _e('Link Curto', 'super-links-clone'); ?></th>
                    <th scope="col"><?php _e('URL de Destino', 'super-links-clone'); ?></th>
                    <th scope="col"><?php _e('Cliques', 'super-links-clone'); ?></th>
                    <th scope="col"><?php _e('Únicos', 'super-links-clone'); ?></th>
                    <th scope="col"><?php _e('Status', 'super-links-clone'); ?></th>
                    <th scope="col"><?php _e('Ações', 'super-links-clone'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($links as $link): ?>
                    <tr>
                        <td>
                            <strong><?php echo esc_html($link->title); ?></strong>
                            <?php if ($link->smart_link): ?>
                                <span class="slc-badge slc-badge-smart"><?php _e('Smart', 'super-links-clone'); ?></span>
                            <?php endif; ?>
                            <?php if ($link->cloaked): ?>
                                <span class="slc-badge slc-badge-cloaked"><?php _e('Camuflado', 'super-links-clone'); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <code class="slc-short-link">
                                <?php 
                                $link_prefix = get_option('slc_link_prefix', 'go');
                                $short_link = home_url($link_prefix . '/' . $link->slug);
                                echo esc_html($short_link);
                                ?>
                            </code>
                            <button type="button" class="slc-copy-link button button-small" data-link="<?php echo esc_attr($short_link); ?>">
                                <?php _e('Copiar', 'super-links-clone'); ?>
                            </button>
                        </td>
                        <td>
                            <a href="<?php echo esc_url($link->target_url); ?>" target="_blank" rel="noopener">
                                <?php echo esc_html(strlen($link->target_url) > 50 ? substr($link->target_url, 0, 50) . '...' : $link->target_url); ?>
                            </a>
                        </td>
                        <td><?php echo number_format($link->clicks); ?></td>
                        <td><?php echo number_format($link->unique_clicks); ?></td>
                        <td>
                            <span class="slc-status slc-status-<?php echo esc_attr($link->status); ?>">
                                <?php echo esc_html(ucfirst($link->status)); ?>
                            </span>
                        </td>
                        <td>
                            <div class="slc-actions">
                                <a href="<?php echo admin_url('admin.php?page=super-links-analytics&link_id=' . $link->id); ?>" 
                                   class="button button-small">
                                    <?php _e('Analytics', 'super-links-clone'); ?>
                                </a>
                                
                                <button type="button" class="button button-small slc-edit-link" 
                                        data-link-id="<?php echo $link->id; ?>">
                                    <?php _e('Editar', 'super-links-clone'); ?>
                                </button>
                                
                                <form method="post" style="display: inline-block;">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="link_id" value="<?php echo $link->id; ?>">
                                    <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('slc_delete_link_' . $link->id); ?>">
                                    <button type="submit" class="button button-small button-link-delete" 
                                            onclick="return confirm('<?php _e('Tem certeza que deseja deletar este link?', 'super-links-clone'); ?>')">
                                        <?php _e('Deletar', 'super-links-clone'); ?>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<!-- Edit Link Modal -->
<div id="slc-edit-modal" class="slc-modal" style="display: none;">
    <div class="slc-modal-content">
        <div class="slc-modal-header">
            <h3><?php _e('Editar Link', 'super-links-clone'); ?></h3>
            <span class="slc-modal-close">&times;</span>
        </div>
        <div class="slc-modal-body">
            <form id="slc-edit-form">
                <input type="hidden" id="edit-link-id" name="link_id">
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="edit-title"><?php _e('Título', 'super-links-clone'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="edit-title" name="title" class="regular-text" required>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="edit-slug"><?php _e('Slug', 'super-links-clone'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="edit-slug" name="slug" class="regular-text" required>
                            <p class="description">
                                <?php _e('Link será:', 'super-links-clone'); ?> 
                                <code><?php echo home_url(get_option('slc_link_prefix', 'go') . '/'); ?><span id="edit-slug-preview">slug</span></code>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="edit-target-url"><?php _e('URL de Destino', 'super-links-clone'); ?></label>
                        </th>
                        <td>
                            <input type="url" id="edit-target-url" name="target_url" class="regular-text" required>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="edit-redirect-type"><?php _e('Tipo de Redirecionamento', 'super-links-clone'); ?></label>
                        </th>
                        <td>
                            <select id="edit-redirect-type" name="redirect_type">
                                <option value="301"><?php _e('301 - Permanente', 'super-links-clone'); ?></option>
                                <option value="302"><?php _e('302 - Temporário', 'super-links-clone'); ?></option>
                                <option value="307"><?php _e('307 - Temporário (preserva método)', 'super-links-clone'); ?></option>
                            </select>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Opções', 'super-links-clone'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" id="edit-cloaked" name="cloaked" value="1">
                                <?php _e('Link camuflado', 'super-links-clone'); ?>
                            </label>
                            <br>
                            <label>
                                <input type="checkbox" id="edit-facebook-cloaked" name="facebook_cloaked" value="1">
                                <?php _e('Proteção Facebook', 'super-links-clone'); ?>
                            </label>
                            <br>
                            <label>
                                <input type="checkbox" id="edit-cookie-tracking" name="cookie_tracking" value="1">
                                <?php _e('Rastreamento de cookies', 'super-links-clone'); ?>
                            </label>
                            <br>
                            <label>
                                <input type="checkbox" id="edit-smart-link" name="smart_link" value="1">
                                <?php _e('Link inteligente', 'super-links-clone'); ?>
                            </label>
                        </td>
                    </tr>
                    
                    <tr id="edit-keywords-row" style="display: none;">
                        <th scope="row">
                            <label for="edit-keywords"><?php _e('Palavras-chave', 'super-links-clone'); ?></label>
                        </th>
                        <td>
                            <textarea id="edit-keywords" name="keywords" class="large-text" rows="3"></textarea>
                            <p class="description"><?php _e('Uma palavra-chave por linha', 'super-links-clone'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="edit-category"><?php _e('Categoria', 'super-links-clone'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="edit-category" name="category" class="regular-text">
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <button type="submit" class="button button-primary">
                        <?php _e('Atualizar Link', 'super-links-clone'); ?>
                    </button>
                    <button type="button" class="button slc-modal-close">
                        <?php _e('Cancelar', 'super-links-clone'); ?>
                    </button>
                </p>
            </form>
        </div>
    </div>
</div>

<style>
.slc-admin-header {
    margin: 20px 0;
}

.slc-empty-state {
    text-align: center;
    padding: 40px 20px;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.slc-badge {
    display: inline-block;
    padding: 2px 6px;
    font-size: 11px;
    font-weight: bold;
    text-transform: uppercase;
    border-radius: 3px;
    margin-left: 5px;
}

.slc-badge-smart {
    background: #2271b1;
    color: white;
}

.slc-badge-cloaked {
    background: #d63638;
    color: white;
}

.slc-short-link {
    background: #f0f0f1;
    padding: 4px 8px;
    border-radius: 3px;
    font-size: 12px;
}

.slc-copy-link {
    margin-left: 10px;
}

.slc-status {
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: bold;
    text-transform: uppercase;
}

.slc-status-active {
    background: #00a32a;
    color: white;
}

.slc-actions {
    display: flex;
    gap: 5px;
    flex-wrap: wrap;
}

.slc-modal {
    position: fixed;
    z-index: 999999;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.slc-modal-content {
    background-color: #fefefe;
    margin: 5% auto;
    padding: 0;
    border-radius: 4px;
    width: 80%;
    max-width: 600px;
    max-height: 90vh;
    overflow-y: auto;
}

.slc-modal-header {
    padding: 20px;
    background: #f0f0f1;
    border-bottom: 1px solid #ddd;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.slc-modal-header h3 {
    margin: 0;
}

.slc-modal-close {
    color: #aaa;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}

.slc-modal-close:hover {
    color: #000;
}

.slc-modal-body {
    padding: 20px;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Edit link functionality
    $('.slc-edit-link').on('click', function() {
        var linkId = $(this).data('link-id');
        
        // Get link data via AJAX
        $.post(ajaxurl, {
            action: 'slc_get_link',
            link_id: linkId,
            nonce: '<?php echo wp_create_nonce('slc_admin_nonce'); ?>'
        }, function(response) {
            if (response.success) {
                var link = response.data;
                
                // Populate form
                $('#edit-link-id').val(link.id);
                $('#edit-title').val(link.title);
                $('#edit-slug').val(link.slug);
                $('#edit-target-url').val(link.target_url);
                $('#edit-redirect-type').val(link.redirect_type);
                $('#edit-category').val(link.category);
                $('#edit-keywords').val(link.keywords);
                
                // Set checkboxes
                $('#edit-cloaked').prop('checked', link.cloaked == 1);
                $('#edit-facebook-cloaked').prop('checked', link.facebook_cloaked == 1);
                $('#edit-cookie-tracking').prop('checked', link.cookie_tracking == 1);
                $('#edit-smart-link').prop('checked', link.smart_link == 1);
                
                // Show/hide keywords field
                if (link.smart_link == 1) {
                    $('#edit-keywords-row').show();
                }
                
                // Update slug preview
                $('#edit-slug-preview').text(link.slug);
                
                // Show modal
                $('#slc-edit-modal').show();
            }
        });
    });
    
    // Close modal
    $('.slc-modal-close').on('click', function() {
        $('#slc-edit-modal').hide();
    });
    
    // Update slug preview
    $('#edit-slug').on('keyup', function() {
        $('#edit-slug-preview').text($(this).val() || 'slug');
    });
    
    // Toggle keywords field
    $('#edit-smart-link').on('change', function() {
        if ($(this).is(':checked')) {
            $('#edit-keywords-row').show();
        } else {
            $('#edit-keywords-row').hide();
        }
    });
    
    // Submit edit form
    $('#slc-edit-form').on('submit', function(e) {
        e.preventDefault();
        
        $.post(ajaxurl, $(this).serialize() + '&action=slc_update_link&nonce=<?php echo wp_create_nonce('slc_admin_nonce'); ?>', function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert('Erro ao atualizar link: ' + response.data.message);
            }
        });
    });
});
</script>