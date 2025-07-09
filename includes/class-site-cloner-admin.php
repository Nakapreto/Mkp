<?php
/**
 * Site Cloner Admin Class
 *
 * @package Site_Cloner
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Site_Cloner_Admin {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('network_admin_menu', array($this, 'network_admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
    }
    
    /**
     * Add admin menu
     */
    public function admin_menu() {
        add_menu_page(
            __('Site Cloner', 'site-cloner'),
            __('Site Cloner', 'site-cloner'),
            'manage_options',
            'site-cloner',
            array($this, 'admin_page'),
            'dashicons-download',
            30
        );
        
        add_submenu_page(
            'site-cloner',
            __('Clone Site', 'site-cloner'),
            __('Clone Site', 'site-cloner'),
            'manage_options',
            'site-cloner',
            array($this, 'admin_page')
        );
        
        add_submenu_page(
            'site-cloner',
            __('Import ZIP', 'site-cloner'),
            __('Import ZIP', 'site-cloner'),
            'manage_options',
            'site-cloner-import',
            array($this, 'import_page')
        );
        
        add_submenu_page(
            'site-cloner',
            __('Status', 'site-cloner'),
            __('Status', 'site-cloner'),
            'manage_options',
            'site-cloner-status',
            array($this, 'status_page')
        );
        
        add_submenu_page(
            'site-cloner',
            __('Configurações', 'site-cloner'),
            __('Configurações', 'site-cloner'),
            'manage_options',
            'site-cloner-settings',
            array($this, 'settings_page')
        );
    }
    
    /**
     * Add network admin menu for multisite
     */
    public function network_admin_menu() {
        if (is_multisite()) {
            add_menu_page(
                __('Site Cloner', 'site-cloner'),
                __('Site Cloner', 'site-cloner'),
                'manage_network',
                'site-cloner-network',
                array($this, 'network_page'),
                'dashicons-download',
                30
            );
        }
    }
    
    /**
     * Admin init
     */
    public function admin_init() {
        // Register settings
        register_setting('site_cloner_settings', 'site_cloner_settings');
    }
    
    /**
     * Main admin page
     */
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Site Cloner', 'site-cloner'); ?></h1>
            
            <div class="site-cloner-container">
                <div class="site-cloner-form-wrapper">
                    <div class="card">
                        <h2><?php _e('Clonar Site Externo', 'site-cloner'); ?></h2>
                        <form id="site-cloner-form" method="post">
                            <?php wp_nonce_field('site_cloner_clone', 'site_cloner_nonce'); ?>
                            
                            <table class="form-table">
                                <tbody>
                                    <tr>
                                        <th scope="row">
                                            <label for="site_url"><?php _e('URL do Site', 'site-cloner'); ?></label>
                                        </th>
                                        <td>
                                            <input type="url" name="site_url" id="site_url" class="regular-text" required>
                                            <p class="description"><?php _e('Digite a URL completa do site que deseja clonar (ex: https://exemplo.com)', 'site-cloner'); ?></p>
                                        </td>
                                    </tr>
                                    
                                    <tr>
                                        <th scope="row">
                                            <label for="page_title"><?php _e('Título da Página', 'site-cloner'); ?></label>
                                        </th>
                                        <td>
                                            <input type="text" name="page_title" id="page_title" class="regular-text">
                                            <p class="description"><?php _e('Nome que será dado à página clonada (opcional - será detectado automaticamente se vazio)', 'site-cloner'); ?></p>
                                        </td>
                                    </tr>
                                    
                                    <tr>
                                        <th scope="row">
                                            <label for="page_status"><?php _e('Status da Página', 'site-cloner'); ?></label>
                                        </th>
                                        <td>
                                            <select name="page_status" id="page_status">
                                                <option value="draft"><?php _e('Rascunho', 'site-cloner'); ?></option>
                                                <option value="publish"><?php _e('Publicar', 'site-cloner'); ?></option>
                                            </select>
                                            <p class="description"><?php _e('Escolha se a página será salva como rascunho ou publicada diretamente', 'site-cloner'); ?></p>
                                        </td>
                                    </tr>
                                    
                                    <tr>
                                        <th scope="row">
                                            <label for="clone_assets"><?php _e('Baixar Assets', 'site-cloner'); ?></label>
                                        </th>
                                        <td>
                                            <fieldset>
                                                <label>
                                                    <input type="checkbox" name="clone_images" value="1" checked>
                                                    <?php _e('Imagens', 'site-cloner'); ?>
                                                </label><br>
                                                <label>
                                                    <input type="checkbox" name="clone_videos" value="1" checked>
                                                    <?php _e('Vídeos (hospedados diretamente)', 'site-cloner'); ?>
                                                </label><br>
                                                <label>
                                                    <input type="checkbox" name="clone_fonts" value="1" checked>
                                                    <?php _e('Fontes', 'site-cloner'); ?>
                                                </label><br>
                                                <label>
                                                    <input type="checkbox" name="clone_css" value="1" checked>
                                                    <?php _e('Arquivos CSS', 'site-cloner'); ?>
                                                </label><br>
                                                <label>
                                                    <input type="checkbox" name="clone_js" value="1">
                                                    <?php _e('Arquivos JavaScript', 'site-cloner'); ?>
                                                </label>
                                            </fieldset>
                                            <p class="description"><?php _e('Selecione quais tipos de assets devem ser baixados e hospedados localmente', 'site-cloner'); ?></p>
                                        </td>
                                    </tr>
                                    
                                    <tr>
                                        <th scope="row">
                                            <label for="elementor_support"><?php _e('Suporte Elementor', 'site-cloner'); ?></label>
                                        </th>
                                        <td>
                                            <label>
                                                <input type="checkbox" name="elementor_support" value="1" checked>
                                                <?php _e('Detectar e converter para Elementor', 'site-cloner'); ?>
                                            </label>
                                            <p class="description"><?php _e('Se ativado, o plugin tentará detectar se o site usa Elementor e configurará a página para edição com Elementor', 'site-cloner'); ?></p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            
                            <p class="submit">
                                <input type="submit" name="clone_site" class="button-primary" value="<?php _e('Iniciar Clone', 'site-cloner'); ?>">
                                <span class="spinner"></span>
                            </p>
                        </form>
                    </div>
                </div>
                
                <div class="site-cloner-info">
                    <div class="card">
                        <h3><?php _e('Informações Importantes', 'site-cloner'); ?></h3>
                        <ul>
                            <li><?php _e('• O plugin detecta automaticamente URLs de redirecionamento', 'site-cloner'); ?></li>
                            <li><?php _e('• Vídeos do YouTube e Vimeo são mantidos como embeds', 'site-cloner'); ?></li>
                            <li><?php _e('• Outros vídeos são baixados para o servidor', 'site-cloner'); ?></li>
                            <li><?php _e('• Links internos são convertidos automaticamente', 'site-cloner'); ?></li>
                            <li><?php _e('• Suporte completo ao Elementor/Elementor Pro', 'site-cloner'); ?></li>
                            <li><?php _e('• Assets são salvos na biblioteca de mídia', 'site-cloner'); ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Import page
     */
    public function import_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Importar ZIP', 'site-cloner'); ?></h1>
            
            <div class="card">
                <h2><?php _e('Upload de Arquivo ZIP', 'site-cloner'); ?></h2>
                <form id="site-cloner-import-form" method="post" enctype="multipart/form-data">
                    <?php wp_nonce_field('site_cloner_import', 'site_cloner_import_nonce'); ?>
                    
                    <table class="form-table">
                        <tbody>
                            <tr>
                                <th scope="row">
                                    <label for="import_file"><?php _e('Arquivo ZIP', 'site-cloner'); ?></label>
                                </th>
                                <td>
                                    <input type="file" name="import_file" id="import_file" accept=".zip" required>
                                    <p class="description"><?php _e('Selecione o arquivo ZIP gerado pelo plugin', 'site-cloner'); ?></p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="import_page_title"><?php _e('Título da Página', 'site-cloner'); ?></label>
                                </th>
                                <td>
                                    <input type="text" name="import_page_title" id="import_page_title" class="regular-text" required>
                                    <p class="description"><?php _e('Nome que será dado à página importada', 'site-cloner'); ?></p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="import_page_status"><?php _e('Status da Página', 'site-cloner'); ?></label>
                                </th>
                                <td>
                                    <select name="import_page_status" id="import_page_status">
                                        <option value="draft"><?php _e('Rascunho', 'site-cloner'); ?></option>
                                        <option value="publish"><?php _e('Publicar', 'site-cloner'); ?></option>
                                    </select>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <p class="submit">
                        <input type="submit" name="import_zip" class="button-primary" value="<?php _e('Importar ZIP', 'site-cloner'); ?>">
                        <span class="spinner"></span>
                    </p>
                </form>
            </div>
        </div>
        <?php
    }
    
    /**
     * Status page
     */
    public function status_page() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'site_cloner_jobs';
        $jobs = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC LIMIT 20");
        
        ?>
        <div class="wrap">
            <h1><?php _e('Status dos Jobs', 'site-cloner'); ?></h1>
            
            <div class="card">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('ID', 'site-cloner'); ?></th>
                            <th><?php _e('URL', 'site-cloner'); ?></th>
                            <th><?php _e('Status', 'site-cloner'); ?></th>
                            <th><?php _e('Progresso', 'site-cloner'); ?></th>
                            <th><?php _e('Criado em', 'site-cloner'); ?></th>
                            <th><?php _e('Ações', 'site-cloner'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($jobs): ?>
                            <?php foreach ($jobs as $job): ?>
                                <tr>
                                    <td><?php echo esc_html($job->id); ?></td>
                                    <td><?php echo esc_html($job->url); ?></td>
                                    <td>
                                        <span class="status-<?php echo esc_attr($job->status); ?>">
                                            <?php echo esc_html(ucfirst($job->status)); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="progress-bar">
                                            <div class="progress-fill" style="width: <?php echo esc_attr($job->progress); ?>%"></div>
                                        </div>
                                        <?php echo esc_html($job->progress); ?>%
                                    </td>
                                    <td><?php echo esc_html($job->created_at); ?></td>
                                    <td>
                                        <button class="button view-log" data-job-id="<?php echo esc_attr($job->id); ?>">
                                            <?php _e('Ver Log', 'site-cloner'); ?>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6"><?php _e('Nenhum job encontrado', 'site-cloner'); ?></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Log Modal -->
            <div id="log-modal" class="site-cloner-modal" style="display: none;">
                <div class="modal-content">
                    <span class="close">&times;</span>
                    <h2><?php _e('Log do Job', 'site-cloner'); ?></h2>
                    <div id="log-content"></div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Settings page
     */
    public function settings_page() {
        if (isset($_POST['submit'])) {
            check_admin_referer('site_cloner_settings', 'site_cloner_settings_nonce');
            
            $settings = array(
                'max_execution_time' => intval($_POST['max_execution_time']),
                'memory_limit' => sanitize_text_field($_POST['memory_limit']),
                'download_timeout' => intval($_POST['download_timeout']),
                'elementor_support' => isset($_POST['elementor_support']) ? 1 : 0,
                'multisite_support' => isset($_POST['multisite_support']) ? 1 : 0,
                'max_file_size' => intval($_POST['max_file_size'])
            );
            
            update_option('site_cloner_settings', $settings);
            echo '<div class="notice notice-success"><p>' . __('Configurações salvas!', 'site-cloner') . '</p></div>';
        }
        
        $settings = get_option('site_cloner_settings', array());
        $defaults = array(
            'max_execution_time' => 300,
            'memory_limit' => '512M',
            'download_timeout' => 60,
            'elementor_support' => 1,
            'multisite_support' => 1,
            'max_file_size' => 50
        );
        $settings = wp_parse_args($settings, $defaults);
        
        ?>
        <div class="wrap">
            <h1><?php _e('Configurações do Site Cloner', 'site-cloner'); ?></h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('site_cloner_settings', 'site_cloner_settings_nonce'); ?>
                
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label for="max_execution_time"><?php _e('Tempo Máximo de Execução (segundos)', 'site-cloner'); ?></label>
                            </th>
                            <td>
                                <input type="number" name="max_execution_time" id="max_execution_time" value="<?php echo esc_attr($settings['max_execution_time']); ?>" class="small-text">
                                <p class="description"><?php _e('Tempo máximo para executar o clone (0 = sem limite)', 'site-cloner'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="memory_limit"><?php _e('Limite de Memória', 'site-cloner'); ?></label>
                            </th>
                            <td>
                                <input type="text" name="memory_limit" id="memory_limit" value="<?php echo esc_attr($settings['memory_limit']); ?>" class="small-text">
                                <p class="description"><?php _e('Limite de memória para o processo (ex: 512M, 1G)', 'site-cloner'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="download_timeout"><?php _e('Timeout de Download (segundos)', 'site-cloner'); ?></label>
                            </th>
                            <td>
                                <input type="number" name="download_timeout" id="download_timeout" value="<?php echo esc_attr($settings['download_timeout']); ?>" class="small-text">
                                <p class="description"><?php _e('Tempo limite para download de cada arquivo', 'site-cloner'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="max_file_size"><?php _e('Tamanho Máximo de Arquivo (MB)', 'site-cloner'); ?></label>
                            </th>
                            <td>
                                <input type="number" name="max_file_size" id="max_file_size" value="<?php echo esc_attr($settings['max_file_size']); ?>" class="small-text">
                                <p class="description"><?php _e('Tamanho máximo para download de arquivos individuais', 'site-cloner'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><?php _e('Opções Avançadas', 'site-cloner'); ?></th>
                            <td>
                                <fieldset>
                                    <label>
                                        <input type="checkbox" name="elementor_support" value="1" <?php checked($settings['elementor_support'], 1); ?>>
                                        <?php _e('Suporte ao Elementor', 'site-cloner'); ?>
                                    </label><br>
                                    <label>
                                        <input type="checkbox" name="multisite_support" value="1" <?php checked($settings['multisite_support'], 1); ?>>
                                        <?php _e('Suporte ao Multisite', 'site-cloner'); ?>
                                    </label>
                                </fieldset>
                            </td>
                        </tr>
                    </tbody>
                </table>
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Network admin page
     */
    public function network_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Site Cloner - Configuração de Rede', 'site-cloner'); ?></h1>
            <p><?php _e('Configurações para toda a rede do multisite.', 'site-cloner'); ?></p>
        </div>
        <?php
    }
}