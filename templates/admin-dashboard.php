<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php _e('Super Links Clone - Dashboard', 'super-links-clone'); ?></h1>
    
    <div class="slc-dashboard-stats">
        <div class="slc-stat-box">
            <h3><?php _e('Total de Links', 'super-links-clone'); ?></h3>
            <div class="slc-stat-number"><?php echo number_format($total_links); ?></div>
        </div>
        
        <div class="slc-stat-box">
            <h3><?php _e('Total de Cliques', 'super-links-clone'); ?></h3>
            <div class="slc-stat-number"><?php echo number_format($total_clicks); ?></div>
        </div>
        
        <div class="slc-stat-box">
            <h3><?php _e('Cliques Únicos', 'super-links-clone'); ?></h3>
            <div class="slc-stat-number"><?php echo number_format($total_unique_clicks); ?></div>
        </div>
        
        <div class="slc-stat-box">
            <h3><?php _e('Páginas Clonadas', 'super-links-clone'); ?></h3>
            <div class="slc-stat-number"><?php echo number_format($total_cloned_pages); ?></div>
        </div>
    </div>
    
    <div class="slc-dashboard-actions">
        <h2><?php _e('Ações Rápidas', 'super-links-clone'); ?></h2>
        <div class="slc-action-buttons">
            <a href="<?php echo admin_url('admin.php?page=super-links-create'); ?>" class="button button-primary">
                <?php _e('Criar Novo Link', 'super-links-clone'); ?>
            </a>
            <a href="<?php echo admin_url('admin.php?page=super-links-clone-page'); ?>" class="button button-secondary">
                <?php _e('Clonar Página', 'super-links-clone'); ?>
            </a>
            <a href="<?php echo admin_url('admin.php?page=super-links-analytics'); ?>" class="button button-secondary">
                <?php _e('Ver Analytics', 'super-links-clone'); ?>
            </a>
        </div>
    </div>
    
    <div class="slc-recent-links">
        <h2><?php _e('Links Recentes', 'super-links-clone'); ?></h2>
        
        <?php if (!empty($recent_links)): ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Título', 'super-links-clone'); ?></th>
                        <th><?php _e('Link Curto', 'super-links-clone'); ?></th>
                        <th><?php _e('Cliques', 'super-links-clone'); ?></th>
                        <th><?php _e('Criado em', 'super-links-clone'); ?></th>
                        <th><?php _e('Ações', 'super-links-clone'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_links as $link): ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html($link->title); ?></strong>
                                <?php if ($link->cloaked): ?>
                                    <span class="slc-badge slc-badge-cloaked"><?php _e('Camuflado', 'super-links-clone'); ?></span>
                                <?php endif; ?>
                                <?php if ($link->smart_link): ?>
                                    <span class="slc-badge slc-badge-smart"><?php _e('Inteligente', 'super-links-clone'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <code><?php echo esc_html(home_url('/' . get_option('slc_link_prefix', 'go') . '/' . $link->slug)); ?></code>
                                <button class="slc-copy-link" data-link="<?php echo esc_attr(home_url('/' . get_option('slc_link_prefix', 'go') . '/' . $link->slug)); ?>">
                                    <?php _e('Copiar', 'super-links-clone'); ?>
                                </button>
                            </td>
                            <td><?php echo number_format($link->clicks); ?></td>
                            <td><?php echo date_i18n(get_option('date_format'), strtotime($link->created_at)); ?></td>
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
            <p><?php _e('Nenhum link encontrado. Crie seu primeiro link!', 'super-links-clone'); ?></p>
        <?php endif; ?>
    </div>
</div>

<style>
.slc-dashboard-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.slc-stat-box {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    text-align: center;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.slc-stat-box h3 {
    margin: 0 0 10px 0;
    font-size: 14px;
    color: #666;
}

.slc-stat-number {
    font-size: 32px;
    font-weight: bold;
    color: #0073aa;
}

.slc-dashboard-actions {
    margin: 30px 0;
}

.slc-action-buttons {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.slc-recent-links {
    margin: 30px 0;
}

.slc-badge {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: bold;
    text-transform: uppercase;
    margin-left: 8px;
}

.slc-badge-cloaked {
    background: #28a745;
    color: white;
}

.slc-badge-smart {
    background: #007cba;
    color: white;
}

.slc-copy-link {
    background: none;
    border: none;
    color: #0073aa;
    cursor: pointer;
    text-decoration: underline;
    font-size: 12px;
    margin-left: 8px;
}

.slc-copy-link:hover {
    color: #005a87;
}
</style>

<script>
jQuery(document).ready(function($) {
    $('.slc-copy-link').on('click', function() {
        var link = $(this).data('link');
        navigator.clipboard.writeText(link).then(function() {
            alert('Link copiado para a área de transferência!');
        });
    });
});
</script>