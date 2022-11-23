<?php
namespace BetterLinksPro\Admin;

class Assets
{
    public function __construct()
    {
        add_action('admin_enqueue_scripts', [$this, 'plugin_scripts']);
    }

    /**
     * Enqueue Files on Start Plugin
     *
     * @function plugin_script
     */
    public function plugin_scripts($hook)
    {
        if (\BetterLinks\Helper::plugin_page_hook_suffix($hook)) {
            add_action(
                'wp_print_scripts',
                function () {
                    $isSkip = apply_filters('BetterLinks/Admin/skip_no_conflict', false);

                    if ($isSkip) {
                        return;
                    }

                    global $wp_scripts;
                    if (!$wp_scripts) {
                        return;
                    }

                    $pluginUrl = plugins_url();
                    foreach ($wp_scripts->queue as $script) {
                        $src = $wp_scripts->registered[$script]->src;
                        if (strpos($src, $pluginUrl) !== false && !strpos($src, BETTERLINKS_PLUGIN_SLUG) !== false) {
                            wp_dequeue_script($wp_scripts->registered[$script]->handle);
                        }
                    }
                },
                1
            );

            wp_enqueue_style('betterlinks-pro-admin-style', BETTERLINKS_PRO_ASSETS_URI . 'css/betterlinks-pro.css', [], '082e8cff18f338e420c987d58f41bbb2', 'all');


            $dependencies = include_once BETTERLINKS_PRO_ASSETS_DIR_PATH . 'js/betterlinkspro.core.min.asset.php';

            wp_enqueue_script(
                'betterlinkspro-admin-core',
                BETTERLINKS_PRO_ASSETS_URI . 'js/betterlinkspro.core.min.js',
                array_merge($dependencies['dependencies'], ['betterlinks-admin-core']),
                $dependencies['version'],
                true
            );

            $user = wp_get_current_user();
            $user_permission = json_decode(get_option(BETTERLINKS_PRO_ROLE_PERMISSON_OPTION_NAME, '{}'), true);
            wp_localize_script('betterlinkspro-admin-core', 'betterLinksProGlobal', [
                'betterlinkspro_nonce' => wp_create_nonce('betterlinkspro_admin_nonce'),
                'nonce' => wp_create_nonce('wp_rest'),
                'rest_url' => rest_url(),
                'site_url' => site_url(),
                'namespace' => BETTERLINKS_PRO_PLUGIN_SLUG . '/v1/',
                'plugin_root_url' => BETTERLINKS_PRO_PLUGIN_ROOT_URI,
                'plugin_root_path' => BETTERLINKS_PRO_ROOT_DIR_PATH,
                'route_path' => parse_url(admin_url(), PHP_URL_PATH),
                'page' => isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '',
                'roles' => \BetterLinksPro\Helper::get_all_roles(),
                'user_can_write_links' => \BetterLinksPro\Helper::current_user_can_do('writelinks', $user, $user_permission),
                'user_can_view_links' => \BetterLinksPro\Helper::current_user_can_do('viewlinks', $user, $user_permission),
                'user_can_edit_links' => \BetterLinksPro\Helper::current_user_can_do('editlinks', $user, $user_permission),
                'user_can_edit_settings' => \BetterLinksPro\Helper::current_user_can_do('editsettings', $user, $user_permission),
                'user_can_edit_favorite' => \BetterLinksPro\Helper::current_user_can_do('editFavorite', $user, $user_permission),
                'user_can_check_analytics' => \BetterLinksPro\Helper::current_user_can_do('checkanalytics', $user, $user_permission),
                'user_can_manage_options' => current_user_can('manage_options'),
            ]);
            wp_set_script_translations('betterlinkspro-admin-core', 'betterlinks-pro', BETTERLINKS_PRO_ROOT_DIR_PATH . 'languages/');
        }
    }
}
