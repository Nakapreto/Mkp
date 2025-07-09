<?php
if (!defined('ABSPATH')) {
    exit;
}

$enable_smart_links = get_option('slc_enable_smart_links', 0);
$smart_keywords = get_option('slc_smart_keywords', '');
?>

<div class="wrap">
    <h1><?php _e('Links Inteligentes', 'super-links-clone'); ?></h1>
    
    <div class="slc-info-box">
        <h3><?php _e('O que são Links Inteligentes?', 'super-links-clone'); ?></h3>
        <p><?php _e('Links inteligentes substituem automaticamente palavras-chave específicas no conteúdo do seu site por links de afiliados ou outros URLs. Isso ajuda a monetizar seu conteúdo sem precisar adicionar links manualmente.', 'super-links-clone'); ?></p>
    </div>
    
    <form method="post" action="">
        <?php wp_nonce_field('slc_smart_links_nonce'); ?>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <?php _e('Ativar Links Inteligentes', 'super-links-clone'); ?>
                </th>
                <td>
                    <label>
                        <input type="checkbox" name="enable_smart_links" value="1" <?php checked($enable_smart_links, 1); ?>>
                        <?php _e('Ativar substituição automática de palavras-chave', 'super-links-clone'); ?>
                    </label>
                    <p class="description">
                        <?php _e('Quando ativado, as palavras-chave definidas abaixo serão automaticamente convertidas em links nos posts e páginas.', 'super-links-clone'); ?>
                    </p>
                </td>
            </tr>
            
            <tr id="keywords-section" style="<?php echo $enable_smart_links ? '' : 'display: none;'; ?>">
                <th scope="row">
                    <label for="smart_keywords"><?php _e('Palavras-chave e Links', 'super-links-clone'); ?></label>
                </th>
                <td>
                    <textarea name="smart_keywords" id="smart_keywords" class="large-text" rows="10" placeholder="<?php _e('palavra-chave|https://example.com/link&#10;outra palavra|https://example.com/outro-link', 'super-links-clone'); ?>"><?php echo esc_textarea($smart_keywords); ?></textarea>
                    <p class="description">
                        <?php _e('Digite uma palavra-chave e seu link correspondente por linha, separados por "|" (pipe). Exemplo:', 'super-links-clone'); ?><br>
                        <code>WordPress|<?php echo home_url('go/wordpress'); ?></code><br>
                        <code>hosting|<?php echo home_url('go/hosting-offer'); ?></code>
                    </p>
                </td>
            </tr>
        </table>
        
        <div id="smart-links-preview" style="<?php echo $enable_smart_links && $smart_keywords ? '' : 'display: none;'; ?>">
            <h3><?php _e('Pré-visualização dos Links', 'super-links-clone'); ?></h3>
            <div id="keywords-preview"></div>
        </div>
        
        <?php submit_button(__('Salvar Configurações', 'super-links-clone'), 'primary', 'submit_smart_links'); ?>
    </form>
    
    <div class="slc-smart-links-stats">
        <h3><?php _e('Estatísticas de Links Inteligentes', 'super-links-clone'); ?></h3>
        
        <?php
        global $wpdb;
        $smart_links = $wpdb->get_results("
            SELECT title, slug, clicks, unique_clicks, keywords
            FROM {$wpdb->prefix}slc_links 
            WHERE smart_link = 1 AND status = 'active'
            ORDER BY clicks DESC
        ");
        ?>
        
        <?php if (!empty($smart_links)): ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Link', 'super-links-clone'); ?></th>
                        <th><?php _e('Palavras-chave', 'super-links-clone'); ?></th>
                        <th><?php _e('Cliques', 'super-links-clone'); ?></th>
                        <th><?php _e('Únicos', 'super-links-clone'); ?></th>
                        <th><?php _e('Ações', 'super-links-clone'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($smart_links as $link): ?>
                        <tr>
                            <td><strong><?php echo esc_html($link->title); ?></strong></td>
                            <td>
                                <?php 
                                $keywords = explode("\n", $link->keywords);
                                $keywords = array_filter(array_map('trim', $keywords));
                                if ($keywords) {
                                    echo '<code>' . implode('</code>, <code>', array_slice($keywords, 0, 3)) . '</code>';
                                    if (count($keywords) > 3) {
                                        echo ' <span class="description">+' . (count($keywords) - 3) . ' ' . __('mais', 'super-links-clone') . '</span>';
                                    }
                                } else {
                                    echo '-';
                                }
                                ?>
                            </td>
                            <td><?php echo number_format($link->clicks); ?></td>
                            <td><?php echo number_format($link->unique_clicks); ?></td>
                            <td>
                                <a href="<?php echo admin_url('admin.php?page=super-links-analytics&link_id=' . $link->id); ?>" class="button button-small">
                                    <?php _e('Analytics', 'super-links-clone'); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="slc-empty-state">
                <h3><?php _e('Nenhum link inteligente encontrado', 'super-links-clone'); ?></h3>
                <p><?php _e('Crie links com a opção "Link Inteligente" ativada para vê-los aqui.', 'super-links-clone'); ?></p>
                <a href="<?php echo admin_url('admin.php?page=super-links-create'); ?>" class="button button-primary">
                    <?php _e('Criar Link Inteligente', 'super-links-clone'); ?>
                </a>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="slc-tips-section">
        <h3><?php _e('Dicas para Links Inteligentes', 'super-links-clone'); ?></h3>
        <ul>
            <li><?php _e('Use palavras-chave específicas e relevantes para seu conteúdo.', 'super-links-clone'); ?></li>
            <li><?php _e('Evite palavras muito comuns que podem gerar muitos links desnecessários.', 'super-links-clone'); ?></li>
            <li><?php _e('Teste sempre em um ambiente de desenvolvimento antes de ativar em produção.', 'super-links-clone'); ?></li>
            <li><?php _e('Monitore o desempenho dos links através dos analytics.', 'super-links-clone'); ?></li>
            <li><?php _e('Use links camuflados para melhor proteção dos seus links de afiliado.', 'super-links-clone'); ?></li>
        </ul>
    </div>
</div>

<style>
.slc-info-box {
    background: #e7f3ff;
    border: 1px solid #b8daff;
    border-radius: 4px;
    padding: 15px;
    margin: 20px 0;
}

.slc-info-box h3 {
    margin-top: 0;
    color: #004085;
}

.slc-smart-links-stats {
    margin: 30px 0;
}

.slc-tips-section {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    padding: 20px;
    margin: 30px 0;
}

.slc-tips-section h3 {
    margin-top: 0;
    color: #495057;
}

.slc-tips-section ul {
    margin: 15px 0;
    padding-left: 20px;
}

.slc-tips-section li {
    margin-bottom: 8px;
    line-height: 1.5;
}

#keywords-preview {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 15px;
    margin: 10px 0;
}

.slc-keyword-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid #eee;
}

.slc-keyword-item:last-child {
    border-bottom: none;
}

.slc-keyword-text {
    font-weight: bold;
    color: #2271b1;
}

.slc-keyword-link {
    font-family: monospace;
    background: #f0f0f1;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 12px;
}

.slc-empty-state {
    text-align: center;
    padding: 40px 20px;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
    margin: 20px 0;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Toggle keywords section
    $('input[name="enable_smart_links"]').on('change', function() {
        if ($(this).is(':checked')) {
            $('#keywords-section').show();
            updatePreview();
        } else {
            $('#keywords-section').hide();
            $('#smart-links-preview').hide();
        }
    });
    
    // Update preview when keywords change
    $('#smart_keywords').on('input', updatePreview);
    
    function updatePreview() {
        var keywords = $('#smart_keywords').val().trim();
        var $preview = $('#keywords-preview');
        var $section = $('#smart-links-preview');
        
        if (!keywords) {
            $section.hide();
            return;
        }
        
        var lines = keywords.split('\n');
        var html = '';
        
        lines.forEach(function(line) {
            line = line.trim();
            if (line && line.indexOf('|') > -1) {
                var parts = line.split('|');
                if (parts.length >= 2) {
                    var keyword = parts[0].trim();
                    var link = parts[1].trim();
                    
                    html += '<div class="slc-keyword-item">';
                    html += '<span class="slc-keyword-text">' + keyword + '</span>';
                    html += '<span class="slc-keyword-link">' + link + '</span>';
                    html += '</div>';
                }
            }
        });
        
        if (html) {
            $preview.html(html);
            $section.show();
        } else {
            $section.hide();
        }
    }
    
    // Initial preview update
    if ($('input[name="enable_smart_links"]').is(':checked')) {
        updatePreview();
    }
});
</script>