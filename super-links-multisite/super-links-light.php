<?php
/*
Plugin Name: Super Links Multisite
Plugin URI:  https://github.com/yourrepo/super-links-multisite
Description: Plugin de criação de links usando seu próprio nome de domínio que redirecionam para seus links de afiliado. Versão modificada para WordPress Multisite.
Version:     2.0.0
Author:      Plugin Modificado para Multisite
Text Domain: super-links-multisite
Network:     true
Copyright: 2025
@since   2.0.0
@package Super_Links_Multisite
@license GPL-2.0+

GNU General Public License, Free Software Foundation <http://creativecommons.org/licenses/GPL/2.0/>
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*/

if(!defined('ABSPATH')) { die('You are not authorized to access this'); }

define('SUPER_LINKS_PLUGIN_SLUG','superLinksMultisite');
define('SUPER_LINKS_PLUGIN_NAME','super-links-multisite');
define('SUPER_LINKS_PATH',WP_PLUGIN_DIR.'/'.SUPER_LINKS_PLUGIN_NAME);
define('SUPER_LINKS_CONTROLLERS_PATH',SUPER_LINKS_PATH.'/application/controllers');
define('SUPER_LINKS_MODELS_PATH',SUPER_LINKS_PATH.'/application/models');
define('SUPER_LINKS_HELPERS_PATH',SUPER_LINKS_PATH.'/application/helpers');
define('SUPER_LINKS_VIEWS_PATH',SUPER_LINKS_PATH.'/application/views');
define('SUPER_LINKS_LIB_PATH',SUPER_LINKS_PATH.'/application/lib');
define('SUPER_LINKS_CSS_PATH',SUPER_LINKS_PATH.'/assets/css');
define('SUPER_LINKS_JS_PATH',SUPER_LINKS_PATH.'/assets/js');
define('SUPER_LINKS_IMAGES_PATH',SUPER_LINKS_PATH.'/assets/images');
define('SUPER_LINKS_BOOTSTRAP_PATH',SUPER_LINKS_PATH.'/assets/bootstrap');
define('SUPER_LINKS_LANGUAGES_PATH',SUPER_LINKS_PATH.'/languages');

define('SUPER_LINKS_URL',plugins_url($path = '/'.SUPER_LINKS_PLUGIN_NAME));
define('SUPER_LINKS_CONTROLLERS_URL',SUPER_LINKS_URL.'/application/controllers');
define('SUPER_LINKS_MODELS_URL',SUPER_LINKS_URL.'/application/models');
define('SUPER_LINKS_HELPERS_URL',SUPER_LINKS_URL.'/application/helpers');
define('SUPER_LINKS_VIEWS_URL',SUPER_LINKS_URL.'/application/views');
define('SUPER_LINKS_LIB_URL',SUPER_LINKS_URL.'/application/lib');
define('SUPER_LINKS_CSS_URL',SUPER_LINKS_URL.'/assets/css');
define('SUPER_LINKS_JS_URL',SUPER_LINKS_URL.'/assets/js');
define('SUPER_LINKS_IMAGES_URL',SUPER_LINKS_URL.'/assets/images');
define('SUPER_LINKS_BOOTSTRAP_URL',SUPER_LINKS_URL.'/assets/bootstrap');
define('SUPER_LINKS_LANGUAGES_URL',SUPER_LINKS_URL.'/languages');

define('TEMPLATE_URL', get_bloginfo('wpurl'));

// Versão do banco de dados atual
define('SUPER_LINKS_DB_VERSION', '2.0.0');

// Define os atributos declarados no cabeçalho
define('SUPER_LINKS_VERSION', super_links_plugin_info('Version'));
define('SUPER_LINKS_DISPLAY_NAME', super_links_plugin_info('Name'));

/**
 * retorna informações da declaração do plugin no cabeçalho
 */
function super_links_plugin_info($field) {
    static $plugin_folder, $plugin_file;

    if( !isset($plugin_folder) or !isset($plugin_file) ) {
        if( ! function_exists( 'get_plugins' ) ) {
            require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
        }

        $plugin_folder = get_plugins( '/' . plugin_basename( dirname( __FILE__ ) ) );
        $plugin_file = basename( ( __FILE__ ) );
    }

    if(isset($plugin_folder[$plugin_file][$field])) {
        return $plugin_folder[$plugin_file][$field];
    }

    return '';
}

/**
 * carrega automaticamente os arquivos do plugin
 * @param $class
 */
function super_links_autoloader($class) {
    if(preg_match('/^.+Controller$/', $class)) {
        $filepath = SUPER_LINKS_CONTROLLERS_PATH."/{$class}.php";
    }
    else if(preg_match('/^.+Helper$/', $class)) {
        $filepath = SUPER_LINKS_HELPERS_PATH."/{$class}.php";
    }
    else {
        $filepath = SUPER_LINKS_MODELS_PATH."/{$class}.php";

        if(!file_exists($filepath)) {
            $filepath = SUPER_LINKS_LIB_PATH."/{$class}.php";
        }
    }

    if(file_exists($filepath)) {
        require_once($filepath);
    }
}

if(is_array(spl_autoload_functions()) && in_array('__autoload', spl_autoload_functions())) {
    spl_autoload_register('__autoload');
}

spl_autoload_register('super_links_autoloader');

/**
 * Função para suporte a multisite com subdomínios
 */
function super_links_get_current_site_domain() {
    if (is_multisite()) {
        $current_site = get_current_site();
        return $current_site->domain;
    }
    return $_SERVER['HTTP_HOST'];
}

/**
 * Hook de ativação para multisite
 */
function super_links_activate($network_wide) {
    if (is_multisite() && $network_wide) {
        $sites = get_sites();
        foreach ($sites as $site) {
            switch_to_blog($site->blog_id);
            super_links_single_activate();
            restore_current_blog();
        }
    } else {
        super_links_single_activate();
    }
}

function super_links_single_activate() {
    // Ativação individual do plugin para cada site
    $coreController = new CoreController('SuperLinksModel');
    $coreController->installSuperLinks();
}

// Registra hooks de ativação
register_activation_hook(__FILE__, 'super_links_activate');

// Carrega o controller principal
require_once(SUPER_LINKS_CONTROLLERS_PATH."/CoreController.php");
$coreController = new CoreController('SuperLinksModel');