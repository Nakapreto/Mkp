<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php _e('Analytics', 'super-links-clone'); ?></h1>
    
    <?php if (isset($link) && $link): ?>
        <!-- Specific Link Analytics -->
        <div class="slc-analytics-header">
            <h2><?php echo esc_html($link->title); ?></h2>
            <p class="description">
                <?php _e('Link:', 'super-links-clone'); ?> 
                <code><?php echo home_url(get_option('slc_link_prefix', 'go') . '/' . $link->slug); ?></code>
            </p>
        </div>
        
        <div class="slc-stats-grid">
            <div class="slc-stat-card">
                <h3><?php echo number_format($link->clicks); ?></h3>
                <p><?php _e('Total de Cliques', 'super-links-clone'); ?></p>
            </div>
            <div class="slc-stat-card">
                <h3><?php echo number_format($link->unique_clicks); ?></h3>
                <p><?php _e('Cliques Únicos', 'super-links-clone'); ?></p>
            </div>
            <div class="slc-stat-card">
                <h3>
                    <?php 
                    $conversion_rate = $link->clicks > 0 ? ($link->unique_clicks / $link->clicks) * 100 : 0;
                    echo number_format($conversion_rate, 1) . '%';
                    ?>
                </h3>
                <p><?php _e('Taxa de Conversão', 'super-links-clone'); ?></p>
            </div>
            <div class="slc-stat-card">
                <h3><?php echo date('d/m/Y', strtotime($link->created_at)); ?></h3>
                <p><?php _e('Data de Criação', 'super-links-clone'); ?></p>
            </div>
        </div>
        
        <?php if (!empty($analytics)): ?>
            <div class="slc-analytics-table">
                <h3><?php _e('Últimos Cliques', 'super-links-clone'); ?></h3>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Data/Hora', 'super-links-clone'); ?></th>
                            <th><?php _e('IP', 'super-links-clone'); ?></th>
                            <th><?php _e('País', 'super-links-clone'); ?></th>
                            <th><?php _e('Dispositivo', 'super-links-clone'); ?></th>
                            <th><?php _e('Navegador', 'super-links-clone'); ?></th>
                            <th><?php _e('Referrer', 'super-links-clone'); ?></th>
                            <th><?php _e('Único', 'super-links-clone'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($analytics as $record): ?>
                            <tr>
                                <td><?php echo date('d/m/Y H:i', strtotime($record->clicked_at)); ?></td>
                                <td><code><?php echo esc_html($record->ip_address); ?></code></td>
                                <td>
                                    <?php if ($record->country): ?>
                                        <span class="slc-country-flag">
                                            <?php echo strtoupper($record->country); ?>
                                        </span>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html($record->device ?: '-'); ?></td>
                                <td><?php echo esc_html($record->browser ?: '-'); ?></td>
                                <td>
                                    <?php if ($record->referer): ?>
                                        <?php $domain = parse_url($record->referer, PHP_URL_HOST); ?>
                                        <span title="<?php echo esc_attr($record->referer); ?>">
                                            <?php echo esc_html($domain); ?>
                                        </span>
                                    <?php else: ?>
                                        <?php _e('Direto', 'super-links-clone'); ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($record->is_unique): ?>
                                        <span class="slc-badge slc-badge-unique"><?php _e('Sim', 'super-links-clone'); ?></span>
                                    <?php else: ?>
                                        <span class="slc-badge slc-badge-repeat"><?php _e('Não', 'super-links-clone'); ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="slc-empty-state">
                <h3><?php _e('Nenhum clique registrado', 'super-links-clone'); ?></h3>
                <p><?php _e('Este link ainda não recebeu nenhum clique.', 'super-links-clone'); ?></p>
            </div>
        <?php endif; ?>
        
    <?php else: ?>
        <!-- General Analytics -->
        <div class="slc-analytics-overview">
            <h2><?php _e('Visão Geral', 'super-links-clone'); ?></h2>
            
            <?php if (!empty($analytics)): ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Link', 'super-links-clone'); ?></th>
                            <th><?php _e('Slug', 'super-links-clone'); ?></th>
                            <th><?php _e('Total de Cliques', 'super-links-clone'); ?></th>
                            <th><?php _e('Cliques Únicos', 'super-links-clone'); ?></th>
                            <th><?php _e('Registros Analytics', 'super-links-clone'); ?></th>
                            <th><?php _e('Ações', 'super-links-clone'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($analytics as $record): ?>
                            <tr>
                                <td><strong><?php echo esc_html($record->title); ?></strong></td>
                                <td><code><?php echo esc_html($record->slug); ?></code></td>
                                <td><?php echo number_format($record->clicks); ?></td>
                                <td><?php echo number_format($record->unique_clicks); ?></td>
                                <td><?php echo number_format($record->total_analytics); ?></td>
                                <td>
                                    <a href="<?php echo admin_url('admin.php?page=super-links-analytics&link_id=' . $record->id); ?>" 
                                       class="button button-small">
                                        <?php _e('Ver Detalhes', 'super-links-clone'); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="slc-empty-state">
                    <h3><?php _e('Nenhum analytics disponível', 'super-links-clone'); ?></h3>
                    <p><?php _e('Crie alguns links primeiro para ver os analytics.', 'super-links-clone'); ?></p>
                    <a href="<?php echo admin_url('admin.php?page=super-links-create'); ?>" class="button button-primary">
                        <?php _e('Criar Primeiro Link', 'super-links-clone'); ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    
    <!-- Export Options -->
    <?php if ((isset($link) && $analytics) || (!isset($link) && $analytics)): ?>
        <div class="slc-export-section">
            <h3><?php _e('Exportar Dados', 'super-links-clone'); ?></h3>
            <p>
                <a href="<?php echo admin_url('admin-ajax.php?action=slc_export_analytics' . (isset($link) ? '&link_id=' . $link->id : '') . '&format=csv&nonce=' . wp_create_nonce('slc_export_nonce')); ?>" 
                   class="button">
                    <?php _e('Exportar CSV', 'super-links-clone'); ?>
                </a>
                <a href="<?php echo admin_url('admin-ajax.php?action=slc_export_analytics' . (isset($link) ? '&link_id=' . $link->id : '') . '&format=json&nonce=' . wp_create_nonce('slc_export_nonce')); ?>" 
                   class="button">
                    <?php _e('Exportar JSON', 'super-links-clone'); ?>
                </a>
            </p>
        </div>
    <?php endif; ?>
</div>

<style>
.slc-analytics-header {
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 1px solid #ddd;
}

.slc-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.slc-stat-card {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 20px;
    text-align: center;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.slc-stat-card h3 {
    font-size: 2.5em;
    margin: 0 0 10px 0;
    color: #2271b1;
    font-weight: bold;
}

.slc-stat-card p {
    margin: 0;
    color: #666;
    font-weight: 500;
}

.slc-analytics-table {
    margin: 30px 0;
}

.slc-analytics-table h3 {
    margin-bottom: 15px;
}

.slc-country-flag {
    display: inline-block;
    padding: 2px 6px;
    background: #f0f0f1;
    border-radius: 3px;
    font-size: 11px;
    font-weight: bold;
}

.slc-badge-unique {
    background: #00a32a;
    color: white;
}

.slc-badge-repeat {
    background: #dba617;
    color: white;
}

.slc-analytics-overview {
    margin: 20px 0;
}

.slc-export-section {
    margin: 30px 0;
    padding: 20px;
    background: #f9f9f9;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.slc-export-section h3 {
    margin-top: 0;
}

.slc-empty-state {
    text-align: center;
    padding: 40px 20px;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
    margin: 20px 0;
}

@media (max-width: 768px) {
    .slc-stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .slc-stat-card h3 {
        font-size: 2em;
    }
}

@media (max-width: 480px) {
    .slc-stats-grid {
        grid-template-columns: 1fr;
    }
}
</style>