<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php _e('Importar Links', 'super-links-clone'); ?></h1>
    
    <div class="slc-import-info">
        <h3><?php _e('Importar links de outros plugins', 'super-links-clone'); ?></h3>
        <p><?php _e('Você pode importar links de outros plugins populares de gestão de links ou de arquivos CSV.', 'super-links-clone'); ?></p>
    </div>
    
    <form method="post" action="" class="slc-import-form">
        <?php wp_nonce_field('slc_import_nonce'); ?>
        
        <h2><?php _e('Selecionar Fonte de Importação', 'super-links-clone'); ?></h2>
        
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Fonte', 'super-links-clone'); ?></th>
                <td>
                    <fieldset>
                        <legend class="screen-reader-text"><?php _e('Fonte de importação', 'super-links-clone'); ?></legend>
                        
                        <label>
                            <input type="radio" name="import_source" value="pretty_links" class="slc-import-source">
                            <strong><?php _e('Pretty Links', 'super-links-clone'); ?></strong>
                            <p class="description"><?php _e('Importar links do plugin Pretty Links.', 'super-links-clone'); ?></p>
                        </label>
                        <br><br>
                        
                        <label>
                            <input type="radio" name="import_source" value="thirsty_affiliates" class="slc-import-source">
                            <strong><?php _e('ThirstyAffiliates', 'super-links-clone'); ?></strong>
                            <p class="description"><?php _e('Importar links do plugin ThirstyAffiliates.', 'super-links-clone'); ?></p>
                        </label>
                        <br><br>
                        
                        <label>
                            <input type="radio" name="import_source" value="csv" class="slc-import-source">
                            <strong><?php _e('Arquivo CSV', 'super-links-clone'); ?></strong>
                            <p class="description"><?php _e('Importar links de um arquivo CSV.', 'super-links-clone'); ?></p>
                        </label>
                    </fieldset>
                </td>
            </tr>
        </table>
        
        <!-- Pretty Links Options -->
        <div class="slc-import-options" data-source="pretty_links" style="display: none;">
            <h3><?php _e('Opções do Pretty Links', 'super-links-clone'); ?></h3>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Status dos links', 'super-links-clone'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="import_pretty_links_active" value="1" checked>
                            <?php _e('Importar apenas links ativos', 'super-links-clone'); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Analytics', 'super-links-clone'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="import_pretty_links_analytics" value="1" checked>
                            <?php _e('Importar dados de analytics (cliques)', 'super-links-clone'); ?>
                        </label>
                    </td>
                </tr>
            </table>
            
            <div id="pretty-links-preview">
                <!-- Preview will be loaded via AJAX -->
            </div>
        </div>
        
        <!-- ThirstyAffiliates Options -->
        <div class="slc-import-options" data-source="thirsty_affiliates" style="display: none;">
            <h3><?php _e('Opções do ThirstyAffiliates', 'super-links-clone'); ?></h3>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Categorias', 'super-links-clone'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="import_thirsty_categories" value="1" checked>
                            <?php _e('Importar categorias como categorias de links', 'super-links-clone'); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Configurações', 'super-links-clone'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="import_thirsty_settings" value="1">
                            <?php _e('Importar configurações de camuflagem', 'super-links-clone'); ?>
                        </label>
                    </td>
                </tr>
            </table>
            
            <div id="thirsty-affiliates-preview">
                <!-- Preview will be loaded via AJAX -->
            </div>
        </div>
        
        <!-- CSV Options -->
        <div class="slc-import-options" data-source="csv" style="display: none;">
            <h3><?php _e('Opções do arquivo CSV', 'super-links-clone'); ?></h3>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="csv_file"><?php _e('Arquivo CSV', 'super-links-clone'); ?></label>
                    </th>
                    <td>
                        <input type="file" name="csv_file" id="csv_file" accept=".csv">
                        <p class="description">
                            <?php _e('Faça upload de um arquivo CSV com as colunas: title, slug, target_url, redirect_type, category', 'super-links-clone'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="csv_delimiter"><?php _e('Separador', 'super-links-clone'); ?></label>
                    </th>
                    <td>
                        <select name="csv_delimiter" id="csv_delimiter">
                            <option value=","><?php _e('Vírgula (,)', 'super-links-clone'); ?></option>
                            <option value=";"><?php _e('Ponto e vírgula (;)', 'super-links-clone'); ?></option>
                            <option value="\t"><?php _e('Tab', 'super-links-clone'); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Primeira linha', 'super-links-clone'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="csv_has_header" value="1" checked>
                            <?php _e('A primeira linha contém os cabeçalhos das colunas', 'super-links-clone'); ?>
                        </label>
                    </td>
                </tr>
            </table>
            
            <div class="slc-csv-template">
                <h4><?php _e('Modelo de arquivo CSV', 'super-links-clone'); ?></h4>
                <p><?php _e('Baixe um modelo de arquivo CSV para facilitar a importação:', 'super-links-clone'); ?></p>
                <a href="<?php echo admin_url('admin-ajax.php?action=slc_download_csv_template&nonce=' . wp_create_nonce('slc_csv_template')); ?>" 
                   class="button">
                    <?php _e('Baixar Modelo CSV', 'super-links-clone'); ?>
                </a>
            </div>
        </div>
        
        <div class="slc-import-summary" style="display: none;">
            <h3><?php _e('Resumo da Importação', 'super-links-clone'); ?></h3>
            <div id="import-summary-content"></div>
        </div>
        
        <p class="submit">
            <input type="submit" name="submit_import" class="button button-primary" 
                   value="<?php _e('Iniciar Importação', 'super-links-clone'); ?>" disabled>
            <button type="button" id="preview-import" class="button" style="display: none;">
                <?php _e('Pré-visualizar', 'super-links-clone'); ?>
            </button>
        </p>
    </form>
    
    <div class="slc-import-history">
        <h3><?php _e('Histórico de Importações', 'super-links-clone'); ?></h3>
        
        <?php
        $import_history = get_option('slc_import_history', array());
        if (!empty($import_history)):
        ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Data', 'super-links-clone'); ?></th>
                        <th><?php _e('Fonte', 'super-links-clone'); ?></th>
                        <th><?php _e('Links Importados', 'super-links-clone'); ?></th>
                        <th><?php _e('Status', 'super-links-clone'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_reverse($import_history) as $import): ?>
                        <tr>
                            <td><?php echo date('d/m/Y H:i', $import['date']); ?></td>
                            <td><?php echo esc_html($import['source']); ?></td>
                            <td><?php echo number_format($import['count']); ?></td>
                            <td>
                                <span class="slc-status slc-status-<?php echo esc_attr($import['status']); ?>">
                                    <?php echo esc_html(ucfirst($import['status'])); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="slc-empty-state">
                <p><?php _e('Nenhuma importação realizada ainda.', 'super-links-clone'); ?></p>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.slc-import-info {
    background: #e7f3ff;
    border: 1px solid #b8daff;
    border-radius: 4px;
    padding: 15px;
    margin: 20px 0;
}

.slc-import-info h3 {
    margin-top: 0;
    color: #004085;
}

.slc-import-options {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    padding: 20px;
    margin: 20px 0;
}

.slc-import-options h3 {
    margin-top: 0;
}

.slc-csv-template {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 15px;
    margin: 15px 0;
}

.slc-csv-template h4 {
    margin-top: 0;
}

.slc-import-summary {
    background: #d1ecf1;
    border: 1px solid #bee5eb;
    border-radius: 4px;
    padding: 15px;
    margin: 20px 0;
}

.slc-import-summary h3 {
    margin-top: 0;
    color: #0c5460;
}

.slc-import-history {
    margin: 30px 0;
}

.slc-status {
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: bold;
    text-transform: uppercase;
}

.slc-status-success {
    background: #00a32a;
    color: white;
}

.slc-status-error {
    background: #d63638;
    color: white;
}

.slc-status-pending {
    background: #dba617;
    color: white;
}

.slc-empty-state {
    text-align: center;
    padding: 20px;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
    color: #666;
}

#import-summary-content {
    background: #fff;
    border: 1px solid #ccc;
    border-radius: 4px;
    padding: 15px;
    margin: 10px 0;
}

.slc-preview-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid #eee;
}

.slc-preview-item:last-child {
    border-bottom: none;
}

.slc-preview-title {
    font-weight: bold;
}

.slc-preview-url {
    font-family: monospace;
    font-size: 12px;
    color: #666;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Handle import source selection
    $('.slc-import-source').on('change', function() {
        var source = $(this).val();
        
        // Hide all options
        $('.slc-import-options').hide();
        
        // Show selected source options
        $('.slc-import-options[data-source="' + source + '"]').show();
        
        // Enable submit button
        $('input[name="submit_import"]').prop('disabled', false);
        
        // Show preview button for non-CSV sources
        if (source !== 'csv') {
            $('#preview-import').show();
            loadSourcePreview(source);
        } else {
            $('#preview-import').hide();
        }
    });
    
    // Preview import
    $('#preview-import').on('click', function() {
        var source = $('input[name="import_source"]:checked').val();
        
        if (!source) {
            alert('<?php _e('Selecione uma fonte de importação primeiro.', 'super-links-clone'); ?>');
            return;
        }
        
        loadSourcePreview(source, true);
    });
    
    // Load source preview
    function loadSourcePreview(source, showSummary) {
        showSummary = showSummary || false;
        
        $.post(ajaxurl, {
            action: 'slc_preview_import',
            source: source,
            nonce: '<?php echo wp_create_nonce('slc_import_nonce'); ?>'
        }, function(response) {
            if (response.success) {
                var previewContainer = '#' + source.replace('_', '-') + '-preview';
                $(previewContainer).html(response.data.preview);
                
                if (showSummary) {
                    $('#import-summary-content').html(response.data.summary);
                    $('.slc-import-summary').show();
                }
            } else {
                alert('Erro ao carregar preview: ' + response.data.message);
            }
        });
    }
    
    // CSV file validation
    $('#csv_file').on('change', function() {
        var file = this.files[0];
        
        if (file) {
            var fileType = file.name.split('.').pop().toLowerCase();
            
            if (fileType !== 'csv') {
                alert('<?php _e('Por favor, selecione um arquivo CSV válido.', 'super-links-clone'); ?>');
                $(this).val('');
                return;
            }
            
            // Preview CSV content
            var reader = new FileReader();
            reader.onload = function(e) {
                var csv = e.target.result;
                var lines = csv.split('\n').slice(0, 5); // Show first 5 lines
                
                var html = '<h4><?php _e('Pré-visualização do arquivo', 'super-links-clone'); ?></h4>';
                html += '<table class="wp-list-table widefat fixed striped"><tbody>';
                
                lines.forEach(function(line, index) {
                    if (line.trim()) {
                        html += '<tr><td><strong>Linha ' + (index + 1) + ':</strong> ' + line + '</td></tr>';
                    }
                });
                
                html += '</tbody></table>';
                
                $('#import-summary-content').html(html);
                $('.slc-import-summary').show();
            };
            reader.readAsText(file);
        }
    });
    
    // Form submission
    $('.slc-import-form').on('submit', function(e) {
        var source = $('input[name="import_source"]:checked').val();
        
        if (!source) {
            e.preventDefault();
            alert('<?php _e('Por favor, selecione uma fonte de importação.', 'super-links-clone'); ?>');
            return false;
        }
        
        if (source === 'csv' && !$('#csv_file').val()) {
            e.preventDefault();
            alert('<?php _e('Por favor, selecione um arquivo CSV.', 'super-links-clone'); ?>');
            return false;
        }
        
        if (!confirm('<?php _e('Tem certeza que deseja iniciar a importação?', 'super-links-clone'); ?>')) {
            e.preventDefault();
            return false;
        }
        
        // Show loading
        $(this).find('input[type="submit"]').prop('disabled', true).val('<?php _e('Importando...', 'super-links-clone'); ?>');
    });
});
</script>