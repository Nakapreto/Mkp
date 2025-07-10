<?php

if (!defined('ABSPATH')) {
    die('You are not authorized to access this');
}

class CoreController extends SuperLinksFramework
{

    protected $superLinksModel;

    public function __construct($model = null, $hooks = [], $filters = [])
    {
        $this->setScenario('super_links');

        $this->setModel($model);
        $this->superLinksModel = $this->loadModel();

        $this->init($hooks, $filters);
    }

    public function init($hooks = [], $filters = [])
    {
        $hooks = array_merge($hooks, $this->basicHooks());
        $filters = array_merge($filters, $this->basicFilters());

        parent::init($hooks, $filters);
    }

    private function basicHooks()
    {
        $installOrUpdatePlugin = $this->superLinksModel
            ->should_install();

        $hooks = [
            ['hook' => 'init', 'function' => array($this, 'installSuperLinks')],
            ['hook' => 'admin_menu', 'function' => array($this, 'add_super_links_menu')],
            ['hook' => 'admin_head', 'function' => array($this, 'removeMenuView')],
            ['hook' => 'admin_head', 'function' => array($this, 'openAffiliateLinkBlank')],
        ];

        if(!$installOrUpdatePlugin){
            $hooks = array_merge($hooks, [['hook' => 'plugins_loaded', 'function' => array($this, 'interceptUrl')]]);
        }

        if($this->isSuperLinksPage()) {
            $specificHooksSuperLinks = [
                ['hook' => 'admin_enqueue_scripts', 'function' => array($this, 'load_scripts')],
                ['hook' => 'in_admin_header', 'function' => array($this, 'header'), 'priority' => 0],
                ['hook' => 'plugins_loaded', 'function' => array($this, 'superLinksTranslation')]
            ];
            $hooks = array_merge($hooks, $specificHooksSuperLinks);
        }

        return $hooks;
    }

    private function basicFilters()
    {
        $filters = [];

        if($this->isSuperLinksPage()) {
            $filters = array_merge($filters, [ ['hook' => 'admin_footer_text', 'function' => array($this, 'footer'), 'priority' => 1, 'accepted_args' => 2]]);
        }

        return $filters;
    }

    public function header()
    {
        require_once SUPER_LINKS_VIEWS_PATH . '/header.php';
    }

    public function footer()
    {
        require_once SUPER_LINKS_VIEWS_PATH . '/footer.php';
    }

    public function add_super_links_menu()
    {
        $superLinks = new SuperLinksController();
        $superLinksAddLink = new SuperLinksAddLinkController('SuperLinksAddLinkModel');
        $superLinksAutomaticLink = new SuperLinksAutomaticLinkController();
        $superLinksImport = new SuperLinksImportController('SuperLinksImportModel');

        add_menu_page(
            $this->getMenuLabelBySlug('super_links'),
            $this->getMenuLabelBySlug('super_links'),
            'manage_options',
            'super_links',
            array($superLinks, 'index'),
            'dashicons-admin-links',
            65
        );

        add_submenu_page(
            'super_links',
            $this->getMenuLabelBySlug('super_links_list_view'),
            $this->getMenuLabelBySlug('super_links_list_view'),
            'manage_options',
            'super_links_list_view',
            array($superLinksAddLink, 'view'),
            1
        );

        add_submenu_page(
            'super_links',
            $this->getMenuLabelBySlug('super_links_add'),
            $this->getMenuLabelBySlug('super_links_add'),
            'manage_options',
            'super_links_add',
            array($superLinksAddLink, 'create'),
            2
        );

        add_submenu_page(
            'super_links',
            $this->getMenuLabelBySlug('super_links_clone'),
            $this->getMenuLabelBySlug('super_links_clone'),
            'manage_options',
            'super_links_clone',
            array($superLinksAddLink, 'clonePages'),
            3
        );

        add_submenu_page(
            'super_links',
            $this->getMenuLabelBySlug('super_links_popups'),
            $this->getMenuLabelBySlug('super_links_popups'),
            'manage_options',
            'super_links_popups',
            array($superLinksAddLink, 'popupsSuperLinks'),
            4
        );

        add_submenu_page(
            'super_links',
            $this->getMenuLabelBySlug('super_links_automatic_list_view'),
            $this->getMenuLabelBySlug('super_links_automatic_list_view'),
            'manage_options',
            'super_links_automatic_list_view',
            array($superLinksAutomaticLink, 'view'),
            5
        );

        // Removido: menu de ativação
        // Menu de ativação foi completamente removido

        add_submenu_page(
            'super_links',
            '',
            '',
            'manage_options',
            'super_links_view_link',
            array($superLinksAddLink, 'viewLink'),
            7
        );

        add_submenu_page(
            'super_links',
            '',
            '',
            'manage_options',
            'super_links_edit_link',
            array($superLinksAddLink, 'update'),
            8
        );

        add_submenu_page(
            'super_links',
            '',
            '',
            'manage_options',
            'super_links_clone_link',
            array($superLinksAddLink, 'cloneLink'),
            9
        );

        add_submenu_page(
            'super_links',
            '',
            '',
            'manage_options',
            'super_links_view_automatic_link',
            array($superLinksAutomaticLink, 'viewLink'),
            10
        );

        add_submenu_page(
            'super_links',
            '',
            '',
            'manage_options',
            'super_links_edit_group',
            array($superLinksAddLink, 'editGroup'),
            11
        );

        add_submenu_page(
            'super_links',
            $this->getMenuLabelBySlug('super_links_cookiePost_view'),
            $this->getMenuLabelBySlug('super_links_cookiePost_view'),
            'manage_options',
            'super_links_cookiePost_view',
            array($superLinksAutomaticLink, 'viewCookies'),
            12
        );

        add_submenu_page(
            'super_links',
            $this->getMenuLabelBySlug('super_links_import_links'),
            $this->getMenuLabelBySlug('super_links_import_links'),
            'manage_options',
            'super_links_import_links',
            array($superLinksImport, 'importLinks'),
            13
        );

        add_submenu_page(
            'super_links',
            $this->getMenuLabelBySlug('super_links_config'),
            $this->getMenuLabelBySlug('super_links_config'),
            'manage_options',
            "super_links_config",
            array($superLinks, 'config'),
            14
        );
    }

    public function removeMenuView(){
        // Removido: referência ao menu de ativação
        remove_submenu_page( 'super_links', 'super_links_add' );
        remove_submenu_page( 'super_links', 'super_links_view_link' );
        remove_submenu_page( 'super_links', 'super_links_edit_link' );
        remove_submenu_page( 'super_links', 'super_links_clone_link' );
        remove_submenu_page( 'super_links', 'super_links_view_automatic_link' );
        remove_submenu_page( 'super_links', 'super_links_edit_group' );
    }

    public function scenarios(){
        return array_keys($this->menuLabels());
    }

    public function menuLabels(){
        return [
            'super_links' => TranslateHelper::getTranslate('Super Links Multisite'),
            'super_links_add' => TranslateHelper::getTranslate('Novo link'),
            'super_links_intercept' => TranslateHelper::getTranslate('Interceptador de link'),
            'super_links_list_view' => TranslateHelper::getTranslate('Criar Links'),
            'super_links_view_link' => TranslateHelper::getTranslate('Visualizar links'),
            'super_links_edit_link' => TranslateHelper::getTranslate('Editar Link'),
            'super_links_clone_link' => TranslateHelper::getTranslate('Duplicar Link'),
            'super_links_automatic_list_view' => TranslateHelper::getTranslate('Links Inteligentes'),
            'super_links_view_automatic_link' => TranslateHelper::getTranslate('Métricas dos links inteligentes'),
            'super_links_edit_group' => TranslateHelper::getTranslate('Editar categoria de links'),
            'super_links_import_links' => TranslateHelper::getTranslate('Importar Links'),
            'super_links_config' => TranslateHelper::getTranslate('Configurações'),
            'super_links_cookiePost_view' => TranslateHelper::getTranslate('Ativar Cookies'),
            'super_links_clone' => TranslateHelper::getTranslate('Clonar Páginas'),
            'super_links_popups' => TranslateHelper::getTranslate('Popups Super Links'),
        ];
    }

    public function getMenuLabelBySlug($slug){
        return $this->menuLabels()[$slug];
    }

    public function load_scripts()
    {
        wp_enqueue_script('super_links_jquery_js', SUPER_LINKS_JS_URL . '/jquery.min.js', array(), SUPER_LINKS_VERSION, true);
        wp_enqueue_script('super_links_bootstrap_js', SUPER_LINKS_BOOTSTRAP_URL . '/js/bootstrap.bundle.min.js', array(), SUPER_LINKS_VERSION, true);
        wp_enqueue_script('spl_notification_js', SUPER_LINKS_JS_URL . '/Notifier.min.js', array(), SUPER_LINKS_VERSION, true);
        wp_enqueue_script('super_links_js', SUPER_LINKS_JS_URL . '/super-links.js', array(), SUPER_LINKS_VERSION, true);
        wp_enqueue_style('super_links_bootstrap_css', SUPER_LINKS_BOOTSTRAP_URL . '/css/bootstrap.min.css', array(), SUPER_LINKS_VERSION);
        wp_enqueue_style('super_links_css', SUPER_LINKS_CSS_URL . '/super-links.css', array(), SUPER_LINKS_VERSION);
        wp_enqueue_style('super_links_fontawesome_css', SUPER_LINKS_CSS_URL . '/all.css', array(), SUPER_LINKS_VERSION);
        wp_enqueue_media();
    }


    public function installSuperLinks()
    {
        @ignore_user_abort(true);
        @set_time_limit(0);

        $this->superLinksModel
             ->superLinks_install();
    }

    private function isSuperLinksPage(){
        $currentPage = $this->getCurrentPage();
        $isSuperPage = false;

        if(!$currentPage){
            return false;
        }

        foreach($this->scenarios() as $scenario){
            if($currentPage == $scenario){
                $isSuperPage = true;
            }
        }

        return $isSuperPage;
    }

    public function superLinksTranslation() {
        load_plugin_textdomain( SUPER_LINKS_PLUGIN_NAME, false, SUPER_LINKS_LANGUAGES_PATH );
    }

    public function interceptUrl(){
        $intercepLink = new SuperLinksInterceptLinkController('SuperLinksAddLinkModel');
        $intercepLink->index();
    }

   public function openAffiliateLinkBlank() {
        // Removido: código para abrir links de afiliados
   }
}