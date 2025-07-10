<?php
if(!defined('ABSPATH'))
    die('You are not allowed to call this page directly.');

class SuperLinksModel extends SuperLinksCoreModel {

    public function __construct() {
        parent::__construct();
    }

    public function getModelName(){
        return 'SuperLinksModel';
    }

    public function rules()
    {
        return [
            [
                'telefone,ddi', 'required'
            ],
            [
                'telefone', 'splTelefoneValido'
            ],
        ];
    }

    public function attributeLabels()
    {
        return array(
            'ddi' => TranslateHelper::getTranslate('Código do país'),
            'telefone' => TranslateHelper::getTranslate('Seu número de Telefone com whatsapp'),
        );
    }

    public function splTelefoneValido($attribute = ''){
        $attributeVal = $this->getAttribute($attribute);
        return  $this->isValidTel($attributeVal)? true : false;
    }

    private function isValidTel($attributeVal){
        $telefone = $attributeVal;
        $telefone = trim($telefone);
        $qtdTel = strlen($telefone);

        if($qtdTel > 10){
            return true;
        }

        return false;
    }

    // Removido: sistema de ativação completamente
    // Plugin agora sempre ativo e sem necessidade de licenciamento

    public function should_install() {
        $old_db_version = get_option('superLinks_db_version');
        return (SUPER_LINKS_DB_VERSION != $old_db_version);
    }

    public function superLinks_install() {
        global $wpdb;

        if($this->should_install()) {
            $char_collation = $wpdb->get_charset_collate();

            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

            $this->createTableSplGroupLink($char_collation);
            $this->createTableSplLinks($char_collation);
            $this->createTableSplAffiliateLinks($char_collation);
            $this->createTableSplLinkMetrics($char_collation);
            $this->createTableSplImport($char_collation);
            $this->updateTablesV200($char_collation);

            $this->saveDbVersion(SUPER_LINKS_DB_VERSION);
        }
    }

    private function saveDbVersion($superLinks_db_version){
        update_option('superLinks_db_version', $superLinks_db_version);
        wp_cache_delete('alloptions', 'options');
    }

    private function createTableSplGroupLink($char_collation) {
        $sql = "CREATE TABLE {$this->tables['spl_group']} (
              id int(11) NOT NULL auto_increment,
              groupName varchar(255) NOT NULL,
              defaultGroup tinyint(1) NOT NULL DEFAULT 0,
              description text default NULL,
              PRIMARY KEY  (id)
            ) {$char_collation};";

        dbDelta($sql);
    }

    private function createTableSplLinks($char_collation)
    {
        $sql = "CREATE TABLE {$this->tables['spl_link']} (
              id int(11) NOT NULL auto_increment,
              idGroup int(11) DEFAULT NULL,
              linkName varchar(255) NOT NULL,
              description text default NULL,
              keyWord varchar(255) DEFAULT NULL,
              redirectType varchar(255) DEFAULT 'html',
              redirectDelay int(2) DEFAULT 1,
              statusLink varchar(64) DEFAULT 'enabled',
              abLastTest int(2) NOT NULL DEFAULT 0,
              createdAt datetime NOT NULL,
              updatedAt datetime DEFAULT NULL,
              redirectBtn varchar(255) DEFAULT '',
              enableRedirectJavascript varchar(64) DEFAULT 'disabled',
              PRIMARY KEY  (id),
              FOREIGN KEY (idGroup) REFERENCES " . $this->tables['spl_group'] . "(id),
              KEY statusLink (statusLink),
              KEY redirectType (redirectType(191)),
              KEY createdAt (createdAt),
              KEY updatedAt (updatedAt)
            ) {$char_collation};";

        dbDelta($sql);
    }

    private function createTableSplAffiliateLinks($char_collation)
    {
        $sql = "CREATE TABLE {$this->tables['spl_affiliateLink']} (
              id int(11) NOT NULL auto_increment,
              idLink int(11) NOT NULL,
              affiliateUrl text NOT NULL,
              createdAt datetime NOT NULL,
              updatedAt datetime default NULL,
              PRIMARY KEY (id),
              FOREIGN KEY (idLink) REFERENCES " . $this->tables['spl_link'] . "(id) on update cascade on delete cascade,
              KEY createdAt (createdAt),
              KEY updatedAt (updatedAt)
            ) {$char_collation};";

        dbDelta($sql);
    }

    private function createTableSplLinkMetrics($char_collation) {
        $sql = "CREATE TABLE {$this->tables['spl_linkMetrics']} (
              id int(11) NOT NULL auto_increment,
              idAffiliateLink int(11) NOT NULL,
              accessTotal int(11) NOT NULL DEFAULT 0,
              uniqueTotalAccesses int(11) NOT NULL DEFAULT 0,
              PRIMARY KEY  (id),
              FOREIGN KEY (idAffiliateLink) REFERENCES " . $this->tables['spl_affiliateLink'] . "(id) on update cascade on delete cascade
            ) {$char_collation};";

        dbDelta($sql);
    }

    private function createTableSplImport($char_collation) {
        $sql = "CREATE TABLE {$this->tables['spl_importLinks']} (
              id int(11) NOT NULL auto_increment,
              idLink int(11) NOT NULL,
              pluginToImport varchar (255) NOT NULL,
              idLinkInPlugin varchar(255) NOT NULL,
              createdAt datetime NOT NULL,
              PRIMARY KEY  (id),
              FOREIGN KEY (idLink) REFERENCES " . $this->tables['spl_link'] . "(id) on update cascade on delete cascade,
              KEY idLink (idLink),
              KEY pluginToImport (pluginToImport),
              KEY idLinkInPlugin (idLinkInPlugin),
              KEY createdAt (createdAt)
            ) {$char_collation};";

        dbDelta($sql);
    }

    private function updateTablesV200($char_collation){
        global $wpdb;

        // Atualização para versão 2.0.0 - melhor suporte multisite
        // Alterações necessárias para compatibilidade com multisite
        $sql = "ALTER TABLE {$this->tables['spl_affiliateLink']} modify affiliateUrl text NOT NULL";
        $wpdb->query($sql);
        
        // Adiciona suporte a múltiplos domínios se necessário
        $table_name = $this->tables['spl_link'];
        
        // Verifica se a coluna site_id já existe
        $column_exists = $wpdb->get_results("SHOW COLUMNS FROM {$table_name} LIKE 'site_id'");
        
        if(empty($column_exists)) {
            $wpdb->query("ALTER TABLE {$table_name} ADD COLUMN site_id int(11) DEFAULT 1");
        }
    }
}