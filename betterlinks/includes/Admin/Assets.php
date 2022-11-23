<?php

namespace BetterLinks\Admin;

class Assets
{
    public function __construct()
    {
        add_action('admin_enqueue_scripts', [$this, 'plugin_scripts']);
        add_action('enqueue_block_editor_assets', [$this, 'block_editor_assets']);
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
            wp_enqueue_style('betterlinks-admin-style', BETTERLINKS_ASSETS_URI . 'css/betterlinks.css', [], 'cc7d1ed3e50e336c37ea43a0d361013c', 'all');
            $dependencies = include_once BETTERLINKS_ASSETS_DIR_PATH . 'js/betterlinks.core.min.asset.php';
            wp_enqueue_script(
                'betterlinks-admin-core',
                BETTERLINKS_ASSETS_URI . 'js/betterlinks.core.min.js',
                $dependencies['dependencies'],
                $dependencies['version'],
                true
            );
            wp_localize_script('betterlinks-admin-core', 'betterLinksGlobal', [
                'betterlinks_nonce' => wp_create_nonce('betterlinks_admin_nonce'),
                'nonce' => wp_create_nonce('wp_rest'),
                'rest_url' => rest_url(),
                'namespace' => BETTERLINKS_PLUGIN_SLUG . '/v1/',
                'plugin_root_url' => BETTERLINKS_PLUGIN_ROOT_URI,
                'plugin_root_path' => BETTERLINKS_ROOT_DIR_PATH,
                'site_url' => site_url(),
                'route_path' => parse_url(admin_url(), PHP_URL_PATH),
                'exists_links_json' => BETTERLINKS_EXISTS_LINKS_JSON,
                'exists_clicks_json' => BETTERLINKS_EXISTS_CLICKS_JSON,
                'page' => isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '',
                'is_pro_enabled' => apply_filters('betterlinks/pro_enabled', false),
            ]);
        }
        wp_set_script_translations('betterlinks-admin-core', 'betterlinks', BETTERLINKS_ROOT_DIR_PATH . 'languages/');
        wp_enqueue_style('betterlinks-admin-notice', BETTERLINKS_ASSETS_URI . 'css/betterlinks-admin-notice.css', [], null, 'all');
    }

    /**
     * Enqueue Guten Scripts
     */
    public function block_editor_assets()
    {
        wp_enqueue_style(
            'betterlinks-gutenberg',
            BETTERLINKS_ASSETS_URI . 'css/betterlinks-gutenberg.css',
            [],
            filemtime(BETTERLINKS_ASSETS_DIR_PATH . 'css/betterlinks-gutenberg.css')
        );

        wp_enqueue_script(
            'betterlinks-gutenberg',
            BETTERLINKS_ASSETS_URI . 'js/betterlinks-gutenberg.core.min.js',
            ['wp-edit-post', 'wp-plugins', 'wp-core-data', 'wp-data', 'wp-block-editor', 'wp-editor', 'wp-components', 'wp-blocks', 'wp-keycodes', 'wp-dom', 'wp-i18n', 'wp-hooks', 'react', 'react-dom'],
            filemtime(BETTERLINKS_ASSETS_DIR_PATH . 'js/betterlinks-gutenberg.core.min.js')
        );
        wp_localize_script('betterlinks-gutenberg', 'betterLinksGlobal', [
            'betterlinks_nonce' => wp_create_nonce('betterlinks_admin_nonce'),
            'nonce' => wp_create_nonce('wp_rest'),
            'rest_url' => rest_url(),
            'namespace' => BETTERLINKS_PLUGIN_SLUG . '/v1/',
            'plugin_root_url' => BETTERLINKS_PLUGIN_ROOT_URI,
            'plugin_root_path' => BETTERLINKS_ROOT_DIR_PATH,
            'site_url' => site_url(),
            'route_path' => parse_url(admin_url(), PHP_URL_PATH),
            'is_pro_enabled' => apply_filters('betterlinks/pro_enabled', false),
        ]);
        wp_set_script_translations('betterlinks-gutenberg', 'betterlinks', BETTERLINKS_ROOT_DIR_PATH . 'languages/');
    }
}
