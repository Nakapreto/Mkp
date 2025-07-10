<?php if (!defined('ABSPATH')) {
    die('You are not authorized to access this');
}

class SuperLinksController extends SuperLinksFramework
{
    protected $superLinksModel;

    public function __construct($model = null, $hooks = [], $filters = [])
    {
        $this->setScenario('super_links');

        $this->setModel($model);
        $this->loadModel();

        $this->init($hooks, $filters);
    }

    public function init($hooks = [], $filters = []){
        $hooks = array_merge($hooks, $this->basicHooks());
        $filters = array_merge($filters, $this->basicFilters());

        parent::init($hooks, $filters);
    }

    private function basicHooks()
    {
        return [];
    }

    private function basicFilters()
    {
        return [];
    }

    public function index()
    {
        $this->render(SUPER_LINKS_VIEWS_PATH . '/admin/index.php');
    }

    // Removido: método de ativação completamente
    // Plugin não necessita mais de ativação

    public function config()
    {
        $this->pageData['pageTitle'] = TranslateHelper::getTranslate('Configuração');
        if(isset($_POST['enableRedis'])){
            $enableRedis = ($_POST['enableRedis'] == 'sim')? true : false;

            update_option('enable_redis_superLinks', $enableRedis);
            wp_cache_delete('alloptions', 'options');

            $toast = TranslateHelper::getTranslate('A opção foi salva com sucesso!');
            $timeToExpire = time() + 60;

            echo "<script> document.cookie = \"toastSPL=$toast; expires=$timeToExpire; path=/\"; </script>";
        }
        
        // Adiciona configurações específicas para multisite
        if(isset($_POST['multisiteConfig'])){
            $enableMultisiteSharing = ($_POST['enableMultisiteSharing'] == 'sim')? true : false;
            update_option('enable_multisite_sharing_superLinks', $enableMultisiteSharing);
            wp_cache_delete('alloptions', 'options');
            
            $toast = TranslateHelper::getTranslate('Configurações de multisite salvas com sucesso!');
            $timeToExpire = time() + 60;
            echo "<script> document.cookie = \"toastSPL=$toast; expires=$timeToExpire; path=/\"; </script>";
        }
        
        $this->render(SUPER_LINKS_VIEWS_PATH . '/admin/config.php');
    }
}