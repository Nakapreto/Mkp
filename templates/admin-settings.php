<?php
if (!defined('ABSPATH')) {
    exit;
}

// Get current settings
$link_prefix = get_option('slc_link_prefix', 'go');
$enable_cookie_tracking = get_option('slc_enable_cookie_tracking', 1);
$enable_smart_links = get_option('slc_enable_smart_links', 0);
$enable_exit_redirect = get_option('slc_enable_exit_redirect', 0);
$enable_facebook_clocker = get_option('slc_enable_facebook_clocker', 0);
$default_redirect_type = get_option('slc_default_redirect_type', '301');
$enable_analytics = get_option('slc_enable_analytics', 1);
$popup_enabled = get_option('slc_popup_enabled', 0);
$popup_delay = get_option('slc_popup_delay', 5000);
$popup_content = get_option('slc_popup_content', '');
$exit_redirect_url = get_option('slc_exit_redirect_url', '');
?>

<div class="wrap">
    <h1><?php _e('Configurações', 'super-links-clone'); ?></h1>
    
    <form method="post" action="" class="slc-settings-form">
        <?php wp_nonce_field('slc_settings_nonce'); ?>
        
        <!-- General Settings -->
        <div class="slc-settings-section">
            <h2><?php _e('Configurações Gerais', 'super-links-clone'); ?></h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="link_prefix"><?php _e('Prefixo dos Links', 'super-links-clone'); ?></label>
                    </th>
                    <td>
                        <input type="text" name="link_prefix" id="link_prefix" value="<?php echo esc_attr($link_prefix); ?>" class="regular-text">
                        <p class="description">
                            <?php _e('Prefixo usado nos links curtos. Exemplo:', 'super-links-clone'); ?>
                            <code><?php echo home_url(); ?>/<strong id="prefix-preview"><?php echo esc_html($link_prefix); ?></strong>/seu-link</code>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="default_redirect_type"><?php _e('Tipo de Redirecionamento Padrão', 'super-links-clone'); ?></label>
                    </th>
                    <td>
                        <select name="default_redirect_type" id="default_redirect_type">
                            <option value="301" <?php selected($default_redirect_type, '301'); ?>><?php _e('301 - Permanente', 'super-links-clone'); ?></option>
                            <option value="302" <?php selected($default_redirect_type, '302'); ?>><?php _e('302 - Temporário', 'super-links-clone'); ?></option>
                            <option value="307" <?php selected($default_redirect_type, '307'); ?>><?php _e('307 - Temporário (preserva método)', 'super-links-clone'); ?></option>
                        </select>
                        <p class="description"><?php _e('Tipo de redirecionamento usado por padrão ao criar novos links.', 'super-links-clone'); ?></p>
                    </td>
                </tr>
            </table>
        </div>
        
        <!-- Analytics Settings -->
        <div class="slc-settings-section">
            <h2><?php _e('Analytics e Rastreamento', 'super-links-clone'); ?></h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Analytics', 'super-links-clone'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="enable_analytics" value="1" <?php checked($enable_analytics, 1); ?>>
                            <?php _e('Ativar sistema de analytics', 'super-links-clone'); ?>
                        </label>
                        <p class="description"><?php _e('Registra cliques, países, dispositivos e outros dados dos visitantes.', 'super-links-clone'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Rastreamento de Cookies', 'super-links-clone'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="enable_cookie_tracking" value="1" <?php checked($enable_cookie_tracking, 1); ?>>
                            <?php _e('Ativar dupla ativação de cookies', 'super-links-clone'); ?>
                        </label>
                        <p class="description"><?php _e('Protege comissões de afiliados com duplo cookie tracking.', 'super-links-clone'); ?></p>
                    </td>
                </tr>
            </table>
        </div>
        
        <!-- Smart Links Settings -->
        <div class="slc-settings-section">
            <h2><?php _e('Links Inteligentes', 'super-links-clone'); ?></h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Links Inteligentes', 'super-links-clone'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="enable_smart_links" value="1" <?php checked($enable_smart_links, 1); ?>>
                            <?php _e('Ativar substituição automática de palavras-chave', 'super-links-clone'); ?>
                        </label>
                        <p class="description">
                            <?php _e('Substitui automaticamente palavras-chave por links nos posts e páginas.', 'super-links-clone'); ?>
                            <a href="<?php echo admin_url('admin.php?page=super-links-smart'); ?>"><?php _e('Configurar palavras-chave', 'super-links-clone'); ?></a>
                        </p>
                    </td>
                </tr>
            </table>
        </div>
        
        <!-- Facebook Protection Settings -->
        <div class="slc-settings-section">
            <h2><?php _e('Proteção Facebook', 'super-links-clone'); ?></h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Facebook Clocker', 'super-links-clone'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="enable_facebook_clocker" value="1" <?php checked($enable_facebook_clocker, 1); ?>>
                            <?php _e('Ativar proteção contra Facebook Ads', 'super-links-clone'); ?>
                        </label>
                        <p class="description"><?php _e('Mostra conteúdo diferente para crawlers do Facebook, evitando bloqueios.', 'super-links-clone'); ?></p>
                    </td>
                </tr>
            </table>
        </div>
        
        <!-- Exit Intent Settings -->
        <div class="slc-settings-section">
            <h2><?php _e('Redirecionamento de Saída', 'super-links-clone'); ?></h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Redirecionamento de Saída', 'super-links-clone'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="enable_exit_redirect" value="1" <?php checked($enable_exit_redirect, 1); ?>>
                            <?php _e('Ativar redirecionamento quando usuário tenta sair', 'super-links-clone'); ?>
                        </label>
                        <p class="description"><?php _e('Detecta quando o usuário move o mouse para fechar a aba e redireciona para uma URL específica.', 'super-links-clone'); ?></p>
                    </td>
                </tr>
                
                <tr id="exit-redirect-url-row" style="<?php echo $enable_exit_redirect ? '' : 'display: none;'; ?>">
                    <th scope="row">
                        <label for="exit_redirect_url"><?php _e('URL de Redirecionamento', 'super-links-clone'); ?></label>
                    </th>
                    <td>
                        <input type="url" name="exit_redirect_url" id="exit_redirect_url" value="<?php echo esc_url($exit_redirect_url); ?>" class="regular-text">
                        <p class="description"><?php _e('URL para onde o usuário será redirecionado ao tentar sair do site.', 'super-links-clone'); ?></p>
                    </td>
                </tr>
            </table>
        </div>
        
        <!-- Popup Settings -->
        <div class="slc-settings-section">
            <h2><?php _e('Sistema de Popups', 'super-links-clone'); ?></h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Popups', 'super-links-clone'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="popup_enabled" value="1" <?php checked($popup_enabled, 1); ?>>
                            <?php _e('Ativar sistema de popups', 'super-links-clone'); ?>
                        </label>
                        <p class="description"><?php _e('Exibe popups personalizados para aumentar conversões.', 'super-links-clone'); ?></p>
                    </td>
                </tr>
                
                <tr id="popup-delay-row" style="<?php echo $popup_enabled ? '' : 'display: none;'; ?>">
                    <th scope="row">
                        <label for="popup_delay"><?php _e('Atraso do Popup (ms)', 'super-links-clone'); ?></label>
                    </th>
                    <td>
                        <input type="number" name="popup_delay" id="popup_delay" value="<?php echo esc_attr($popup_delay); ?>" min="0" step="1000" class="small-text">
                        <p class="description"><?php _e('Tempo em milissegundos antes de exibir o popup (padrão: 5000 = 5 segundos).', 'super-links-clone'); ?></p>
                    </td>
                </tr>
                
                <tr id="popup-content-row" style="<?php echo $popup_enabled ? '' : 'display: none;'; ?>">
                    <th scope="row">
                        <label for="popup_content"><?php _e('Conteúdo do Popup', 'super-links-clone'); ?></label>
                    </th>
                    <td>
                        <?php
                        wp_editor($popup_content, 'popup_content', array(
                            'textarea_name' => 'popup_content',
                            'media_buttons' => false,
                            'textarea_rows' => 8,
                            'teeny' => true,
                            'quicktags' => true,
                        ));
                        ?>
                        <p class="description"><?php _e('Conteúdo HTML do popup. Use {link} para inserir links dinâmicos.', 'super-links-clone'); ?></p>
                    </td>
                </tr>
            </table>
        </div>
        
        <!-- Advanced Settings -->
        <div class="slc-settings-section">
            <h2><?php _e('Configurações Avançadas', 'super-links-clone'); ?></h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Remover Dados', 'super-links-clone'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="remove_data_on_uninstall" value="1" <?php checked(get_option('slc_remove_data_on_uninstall', 0), 1); ?>>
                            <?php _e('Remover todos os dados ao desinstalar o plugin', 'super-links-clone'); ?>
                        </label>
                        <p class="description">
                            <strong><?php _e('Atenção:', 'super-links-clone'); ?></strong>
                            <?php _e('Esta opção irá remover permanentemente todas as tabelas, links e configurações ao desinstalar o plugin.', 'super-links-clone'); ?>
                        </p>
                    </td>
                </tr>
            </table>
        </div>
        
        <?php submit_button(__('Salvar Configurações', 'super-links-clone'), 'primary', 'submit_settings'); ?>
    </form>
    
    <!-- Reset Settings -->
    <div class="slc-danger-zone">
        <h3><?php _e('Zona de Perigo', 'super-links-clone'); ?></h3>
        <p><?php _e('As ações abaixo são irreversíveis. Use com cuidado.', 'super-links-clone'); ?></p>
        
        <p>
            <button type="button" class="button button-secondary slc-reset-settings">
                <?php _e('Restaurar Configurações Padrão', 'super-links-clone'); ?>
            </button>
            
            <button type="button" class="button button-link-delete slc-clear-analytics">
                <?php _e('Limpar Todos os Analytics', 'super-links-clone'); ?>
            </button>
        </p>
    </div>
    
    <!-- System Info -->
    <div class="slc-system-info">
        <h3><?php _e('Informações do Sistema', 'super-links-clone'); ?></h3>
        
        <table class="wp-list-table widefat fixed striped">
            <tbody>
                <tr>
                    <th><?php _e('Versão do Plugin', 'super-links-clone'); ?></th>
                    <td><?php echo SLC_VERSION; ?></td>
                </tr>
                <tr>
                    <th><?php _e('Versão do WordPress', 'super-links-clone'); ?></th>
                    <td><?php echo get_bloginfo('version'); ?></td>
                </tr>
                <tr>
                    <th><?php _e('Versão do PHP', 'super-links-clone'); ?></th>
                    <td><?php echo PHP_VERSION; ?></td>
                </tr>
                <tr>
                    <th><?php _e('Total de Links', 'super-links-clone'); ?></th>
                    <td>
                        <?php
                        global $wpdb;
                        $total_links = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}slc_links WHERE status = 'active'");
                        echo number_format($total_links);
                        ?>
                    </td>
                </tr>
                <tr>
                    <th><?php _e('Total de Cliques', 'super-links-clone'); ?></th>
                    <td>
                        <?php
                        $total_clicks = $wpdb->get_var("SELECT SUM(clicks) FROM {$wpdb->prefix}slc_links WHERE status = 'active'");
                        echo number_format($total_clicks ?: 0);
                        ?>
                    </td>
                </tr>
                <tr>
                    <th><?php _e('Registros de Analytics', 'super-links-clone'); ?></th>
                    <td>
                        <?php
                        $total_analytics = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}slc_analytics");
                        echo number_format($total_analytics ?: 0);
                        ?>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<style>
.slc-settings-section {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    margin: 20px 0;
    padding: 20px;
}

.slc-settings-section h2 {
    margin-top: 0;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
    color: #23282d;
}

.slc-danger-zone {
    background: #fef2f2;
    border: 1px solid #fecaca;
    border-radius: 4px;
    padding: 20px;
    margin: 30px 0;
}

.slc-danger-zone h3 {
    margin-top: 0;
    color: #dc2626;
}

.slc-system-info {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    padding: 20px;
    margin: 30px 0;
}

.slc-system-info h3 {
    margin-top: 0;
    color: #495057;
}

.slc-system-info table {
    margin: 15px 0;
}

.slc-system-info th {
    width: 200px;
    font-weight: 600;
}

#prefix-preview {
    color: #2271b1;
    font-weight: bold;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Update link prefix preview
    $('#link_prefix').on('input', function() {
        $('#prefix-preview').text($(this).val() || 'go');
    });
    
    // Toggle exit redirect URL field
    $('input[name="enable_exit_redirect"]').on('change', function() {
        if ($(this).is(':checked')) {
            $('#exit-redirect-url-row').show();
        } else {
            $('#exit-redirect-url-row').hide();
        }
    });
    
    // Toggle popup fields
    $('input[name="popup_enabled"]').on('change', function() {
        if ($(this).is(':checked')) {
            $('#popup-delay-row, #popup-content-row').show();
        } else {
            $('#popup-delay-row, #popup-content-row').hide();
        }
    });
    
    // Reset settings
    $('.slc-reset-settings').on('click', function() {
        if (confirm('<?php _e('Tem certeza que deseja restaurar todas as configurações para os valores padrão?', 'super-links-clone'); ?>')) {
            $.post(ajaxurl, {
                action: 'slc_reset_settings',
                nonce: '<?php echo wp_create_nonce('slc_admin_nonce'); ?>'
            }, function(response) {
                if (response.success) {
                    alert('<?php _e('Configurações restauradas com sucesso!', 'super-links-clone'); ?>');
                    location.reload();
                } else {
                    alert('<?php _e('Erro ao restaurar configurações.', 'super-links-clone'); ?>');
                }
            });
        }
    });
    
    // Clear analytics
    $('.slc-clear-analytics').on('click', function() {
        if (confirm('<?php _e('Tem certeza que deseja limpar TODOS os dados de analytics? Esta ação é irreversível!', 'super-links-clone'); ?>')) {
            if (confirm('<?php _e('ÚLTIMA CONFIRMAÇÃO: Todos os dados de analytics serão perdidos permanentemente. Continuar?', 'super-links-clone'); ?>')) {
                $.post(ajaxurl, {
                    action: 'slc_clear_analytics',
                    nonce: '<?php echo wp_create_nonce('slc_admin_nonce'); ?>'
                }, function(response) {
                    if (response.success) {
                        alert('<?php _e('Analytics limpos com sucesso!', 'super-links-clone'); ?>');
                        location.reload();
                    } else {
                        alert('<?php _e('Erro ao limpar analytics.', 'super-links-clone'); ?>');
                    }
                });
            }
        }
    });
    
    // Form submission
    $('.slc-settings-form').on('submit', function() {
        $(this).find('input[type="submit"]').prop('disabled', true).val('<?php _e('Salvando...', 'super-links-clone'); ?>');
    });
});
</script>