<?php
if (!defined('ABSPATH')) {
    die('You are not authorized to access this');
}
?>

<div class="wrap">
    <div class="container">
        <div class="py-1">
            <div class="row justify-content-center">
                <div class="col-12">
                    <h1 class="text-center mb-4"><?php TranslateHelper::printTranslate('Super Links Multisite')?></h1>
                    <p class="lead text-center"><?php TranslateHelper::printTranslate('Gerencie seus links de afiliados de forma profissional em WordPress Multisite')?></p>
                </div>
            </div>
            <hr>
        </div>
        
        <div class="row">
            <div class="col-12">
                <!-- Status do Plugin -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title text-success">
                            <i class="fas fa-check-circle"></i> 
                            <?php TranslateHelper::printTranslate('Plugin Ativo e Funcionando')?>
                        </h5>
                        <p class="card-text">
                            <?php TranslateHelper::printTranslate('Seu plugin Super Links está ativo e pronto para uso.')?>
                            <?php if(is_multisite()): ?>
                                <?php TranslateHelper::printTranslate('Detectado WordPress Multisite - todas as funcionalidades estão disponíveis.')?>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>

                <!-- Funcionalidades Disponíveis -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><?php TranslateHelper::printTranslate('Funcionalidades Disponíveis')?></h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span>
                                            <i class="fas fa-link text-primary"></i>
                                            <?php TranslateHelper::printTranslate('Encurtador de links')?>
                                        </span>
                                        <span class="badge badge-success">Ativo</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span>
                                            <i class="fas fa-whatsapp text-success"></i>
                                            <?php TranslateHelper::printTranslate('Links para WhatsApp e Telegram')?>
                                        </span>
                                        <span class="badge badge-success">Ativo</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span>
                                            <i class="fas fa-folder text-warning"></i>
                                            <?php TranslateHelper::printTranslate('Categorização de links')?>
                                        </span>
                                        <span class="badge badge-success">Ativo</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span>
                                            <i class="fas fa-chart-bar text-info"></i>
                                            <?php TranslateHelper::printTranslate('Testes A/B')?>
                                        </span>
                                        <span class="badge badge-success">Ativo</span>
                                    </li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span>
                                            <i class="fas fa-download text-secondary"></i>
                                            <?php TranslateHelper::printTranslate('Importar links de outros plugins')?>
                                        </span>
                                        <span class="badge badge-success">Ativo</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span>
                                            <i class="fas fa-copy text-primary"></i>
                                            <?php TranslateHelper::printTranslate('Clonar páginas')?>
                                        </span>
                                        <span class="badge badge-success">Ativo</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span>
                                            <i class="fas fa-cookie-bite text-warning"></i>
                                            <?php TranslateHelper::printTranslate('Ativação de Cookies')?>
                                        </span>
                                        <span class="badge badge-success">Ativo</span>
                                    </li>
                                    <?php if(is_multisite()): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span>
                                            <i class="fas fa-network-wired text-success"></i>
                                            <?php TranslateHelper::printTranslate('Suporte Multisite')?>
                                        </span>
                                        <span class="badge badge-success">Ativo</span>
                                    </li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><?php TranslateHelper::printTranslate('Ações Rápidas')?></h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <a href="admin.php?page=super_links_list_view" class="btn btn-primary btn-block">
                                    <i class="fas fa-plus"></i>
                                    <?php TranslateHelper::printTranslate('Criar Novo Link')?>
                                </a>
                            </div>
                            <div class="col-md-3 mb-3">
                                <a href="admin.php?page=super_links_list_view" class="btn btn-info btn-block">
                                    <i class="fas fa-list"></i>
                                    <?php TranslateHelper::printTranslate('Ver Todos os Links')?>
                                </a>
                            </div>
                            <div class="col-md-3 mb-3">
                                <a href="admin.php?page=super_links_import_links" class="btn btn-secondary btn-block">
                                    <i class="fas fa-download"></i>
                                    <?php TranslateHelper::printTranslate('Importar Links')?>
                                </a>
                            </div>
                            <div class="col-md-3 mb-3">
                                <a href="admin.php?page=super_links_config" class="btn btn-warning btn-block">
                                    <i class="fas fa-cog"></i>
                                    <?php TranslateHelper::printTranslate('Configurações')?>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if(is_multisite()): ?>
                <!-- Informações do Multisite -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-network-wired"></i>
                            <?php TranslateHelper::printTranslate('Informações do Multisite')?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $current_site = get_current_site();
                        $blog_id = get_current_blog_id();
                        ?>
                        <p><strong><?php TranslateHelper::printTranslate('Domínio Principal')?>:</strong> <?php echo esc_html($current_site->domain); ?></p>
                        <p><strong><?php TranslateHelper::printTranslate('Site Atual (ID)')?>:</strong> <?php echo esc_html($blog_id); ?></p>
                        <p><strong><?php TranslateHelper::printTranslate('URL do Site')?>:</strong> <?php echo esc_url(home_url()); ?></p>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <?php TranslateHelper::printTranslate('Este plugin está configurado para funcionar perfeitamente em WordPress Multisite com subdomínios.')?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Informações do Sistema -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><?php TranslateHelper::printTranslate('Informações do Sistema')?></h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong><?php TranslateHelper::printTranslate('Versão do Plugin')?>:</strong> <?php echo SUPER_LINKS_VERSION; ?></p>
                                <p><strong><?php TranslateHelper::printTranslate('Versão do WordPress')?>:</strong> <?php echo get_bloginfo('version'); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong><?php TranslateHelper::printTranslate('PHP')?>:</strong> <?php echo PHP_VERSION; ?></p>
                                <p><strong><?php TranslateHelper::printTranslate('Multisite')?>:</strong> <?php echo is_multisite() ? 'Sim' : 'Não'; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>