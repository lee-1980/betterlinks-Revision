<?php
/*
 * Plugin Name:		BetterLinks Pro
 * Plugin URI:		https://betterlinks.io/
 * Description:		Get access to Individual Analytics, Role Management, Google Analytics Integration & many more amazing features to track & run successful marketing campaigns.
 * Version:			1.4.0
 * Author:			WPDeveloper
 * Author URI:		https://wpdeveloper.com
 * License:			GPL-3.0+
 * License URI:		http://www.gnu.org/licenses/gpl-3.0.txt
 * Author URI:		https://wpdeveloper.com
 * Text Domain:		betterlinks-pro
 * Domain Path:		/languages
 */

if (!defined('ABSPATH')) {
    exit();
}

if (file_exists(dirname(__FILE__) . '/vendor/autoload.php')) {
    require_once dirname(__FILE__) . '/vendor/autoload.php';
}

if (!class_exists('BetterLinksPro')) {
    final class BetterLinksPro
    {
        private function __construct()
        {
            $this->define_constants();
            register_activation_hook(__FILE__, [$this, 'activate']);
            add_action('betterlinks_loaded', [$this, 'init_plugin']);
            add_action('init', array($this, 'init_dispatch'));
            $this->dispatch_hook();
        }

        public static function init()
        {
            static $instance = false;

            if (!$instance) {
                $instance = new self();
            }

            return $instance;
        }
        public function define_constants()
        {
            /**
             * Defines CONSTANTS for Whole plugins.
             */
            define('BETTERLINKS_PRO_VERSION', '1.4.0');
            define('BETTERLINKS_PRO_PLUGIN_SLUG', 'betterlinks-pro');
            define('BETTERLINKS_PRO_PLUGIN_BASENAME', plugin_basename(__FILE__));
            define('BETTERLINKS_PRO_PLUGIN_ROOT_URI', plugins_url('/', __FILE__));
            define('BETTERLINKS_PRO_ROOT_DIR_PATH', plugin_dir_path(__FILE__));
            define('BETTERLINKS_PRO_ASSETS_DIR_PATH', BETTERLINKS_PRO_ROOT_DIR_PATH . 'assets/');
            define('BETTERLINKS_PRO_ASSETS_URI', BETTERLINKS_PRO_PLUGIN_ROOT_URI . 'assets/');
            define('BETTERLINKS_STORE_URL', 'https://api.wpdeveloper.com/');
            define('BETTERLINKS_SL_ITEM_ID', 764539);
            define('BETTERLINKS_SL_ITEM_NAME', 'BetterLinks Pro');
            define('BETTERLINKS_PRO_EXTERNAL_ANALYTICS_OPTION_NAME', 'betterlinkspro_ga');
            define('BETTERLINKS_PRO_UTM_OPTION_NAME', 'betterlinkspro_utm_templates');
            define('BETTERLINKS_PRO_BROKEN_LINK_OPTION_NAME', 'betterlinkspro_broken_link');
            define('BETTERLINKS_PRO_REPORTING_OPTION_NAME', 'betterlinkspro_reporting');
            define('BETTERLINKS_PRO_ROLE_PERMISSON_OPTION_NAME', 'betterlinkspro_role_permission');
            define('BETTERLINKS_PRO_AUTOLINK_OPTION_NAME', 'betterlinks_autolink_options');

        }

    

        /**
         * Initialize the plugin
         *
         * @return void
         */
        public function init_plugin()
        {
            $this->load_textdomain();
            new BetterLinksPro\API();
            if (is_admin()) {
                new BetterLinksPro\Admin();
            } else {
                BetterLinksPro\Frontend::init();
            }

			new BetterLinksPro\Elementor();
            
            $this->run_migrator();
        }

    
        public function load_textdomain()
        {
            load_plugin_textdomain('betterlinks-pro', false, dirname(plugin_basename(__FILE__)) . '/languages/');
        }

        public function dispatch_hook()
        {
            BetterLinksPro\Link::init();
            BetterLinksPro\Cron::init();
            BetterLinksPro\Admin\Notices::init();
        }

        public function init_dispatch()
        {
            \BetterLinksPro\Admin\LinkSchedule::init();
            $BrokenLink = \BetterLinksPro\Admin\BrokenLink::getInstance();
            if ($BrokenLink->start_dispatch()) {
                $BrokenLink->dispatch();
            }
        }

        public function run_migrator()
        {
            BetterLinksPro\Migration::init();
        }

        public function activate()
        {
            new BetterLinksPro\Installer();
        }
    }
}

/**
 * Initializes the main plugin
 *
 * @return \BetterLinks
 */
if (!function_exists('BetterLinksPro_Start')) {
    function BetterLinksPro_Start()
    {
        return BetterLinksPro::init();
    }
}

// Plugin Start
BetterLinksPro_Start();
