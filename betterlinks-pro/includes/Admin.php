<?php
namespace BetterLinksPro;

class Admin
{
    public function __construct()
    {
        Admin\Menu::init();
        Admin\Ajax::init();
        Admin\Metabox::init();
        $this->add_scripts();
        $this->plugin_licensing();
        $this->plugin_updater();
        add_filter('betterlinks/pro_enabled', array($this, 'pro_enabled'));
        add_filter('betterlinks/tools/export_content', array($this, 'export_content'));
        add_filter('betterlinks/tools/import_process_data', array($this, 'import_process_data'), 10, 3);
        add_filter('betterlinks/tools/migration/ptl_one_click_import_link_arg', array($this, 'ptl_one_click_import_link_arg'), 10, 2);
        add_filter('betterlinks/helper/menu_items', array($this, 'add_new_menu_item'), 10, 2);
    }
    public function add_scripts()
    {
        new Admin\Assets();
    }
    public function plugin_updater()
    {
        // Disable SSL verification
        add_filter('edd_sl_api_request_verify_ssl', '__return_false');

        // Setup the updater
        $license = get_option(BETTERLINKS_PRO_PLUGIN_SLUG . '-license-key');

        $updater = new Admin\PluginUpdater(
            BETTERLINKS_STORE_URL,
            BETTERLINKS_PRO_PLUGIN_BASENAME,
            [
                'version' => BETTERLINKS_PRO_VERSION,
                'license' => $license,
                'item_id' => BETTERLINKS_SL_ITEM_ID,
                'author' => 'WPDeveloper',
            ]
        );
    }
    /**
     * Plugin Licensing
     *
     * @since v1.0.0
     */
    public function plugin_licensing()
    {
        new Admin\License(
            BETTERLINKS_PRO_PLUGIN_SLUG,
            BETTERLINKS_SL_ITEM_NAME,
            'betterlinks-pro'
        );
    }
    public function pro_enabled()
    {
        return true;
    }
    public function export_content($content)
    {
        $content['rotations'] = \BetterLinksPro\Helper::get_all_clicks_rotations();
        return $content;
    }
    public function import_process_data($message, $data, $link_IDs)
    {
        if (isset($data['rotations']) && is_array($data['rotations']) && count($data['rotations']) > 0) {
            $message['rotations'] = $this->rotation_data_insert($data['rotations'], $link_IDs);
        }

        return $message;
    }
    public function rotation_data_insert($data, $link_IDs)
    {
        $message = [];
        foreach ($data as $item) {
            if (isset($link_IDs[$item['link_id']])) {
                $item['link_id'] = $link_IDs[$item['link_id']];
            }
            $link_id = \BetterLinksPro\Helper::insert_click_rotation($item);
            if ($link_id) {
                $message[] = 'Imported Successfully "' . $item['target_url'] . '"';
            }
        }
        return $message;
    }
    public function ptl_one_click_import_link_arg($arg, $data)
    {
        global $wpdb;
        $wpdb_prefix = $wpdb->prefix;
        $prli_link_meta = new \PrliLinkMeta();
        if ($prli_link_meta->get_link_meta($data['id'], 'enable_expire', true)) {
            $expire = [];
            $expire['status'] = true;
            $expire['type'] = $prli_link_meta->get_link_meta($data['id'], 'expire_type', true);
            $expire['date'] = $prli_link_meta->get_link_meta($data['id'], 'expire_date', true);
            $expire['clicks'] = $prli_link_meta->get_link_meta($data['id'], 'expire_clicks', true);
            $expire['redirect_status'] = $prli_link_meta->get_link_meta($data['id'], 'enable_expired_url', true);
            $expire['expired_url'] = $prli_link_meta->get_link_meta($data['id'], 'expired_url', true);
            $arg['expire'] = json_encode($expire);
        }

        if ($prli_link_meta->get_link_meta($data['id'], 'prli_dynamic_redirection', true) == 'rotate') {
            $rotate = $wpdb->get_results($wpdb->prepare("SELECT url, weight, link_id from {$wpdb_prefix}prli_link_rotations where link_id = %d", $data['id']), OBJECT);
            $redirect_url = [];
            $redirect_url[] = ['link' => $data['url'], 'weight' => (int) $prli_link_meta->get_link_meta($data['id'], 'prli-target-url-weight', true)];
            if (is_array($rotate) && count($rotate) > 0) {
                foreach ($rotate as $rotate_item) {
                    $redirect_url[] = [
                        'link' => $rotate_item->url,
                        'weight' => (int) $rotate_item->weight
                    ];
                }
            }
            $redirect = [];
            $redirect['type'] = 'rotation';
            $redirect['value'] = $redirect_url;
            $redirect['extra'] = [
                'rotation_mode' => 'weighted',
                'split_test'    => $prli_link_meta->get_link_meta($data['id'], 'prli-enable-split-test', true),
                'goal_link'     => ''
            ];
            $arg['dynamic_redirect'] = json_encode($redirect);
        } elseif ($prli_link_meta->get_link_meta($data['id'], 'prli_dynamic_redirection', true) == 'geo') {
            $countries = \BetterLinksPro\Helper::get_prettylinks_meta($data['id'], 'geo_countries');
            $countries = wp_list_pluck($countries, 'meta_value');
            $urls = \BetterLinksPro\Helper::get_prettylinks_meta($data['id'], 'geo_url');
            $urls  = wp_list_pluck($urls, 'meta_value');
            $allCountries = array_combine($urls, $countries);
            $redirect_url = [];
            if (is_array($allCountries) && count($allCountries) > 0) {
                foreach ($allCountries as $url => $country) {
                    preg_match_all("/((?<=\[).+?(?=\]))/i", $country, $countryList);
                    $redirect_url[] = [
                        'link' => $url,
                        'country' => is_array($countryList) ? $countryList[0] : []
                    ];
                }
            }
            $redirect = [];
            $redirect['type'] = 'geographic';
            $redirect['value'] = $redirect_url;
            $arg['dynamic_redirect'] = json_encode($redirect);
        } elseif ($prli_link_meta->get_link_meta($data['id'], 'prli_dynamic_redirection', true) == 'tech') {
            $tech_device = \BetterLinksPro\Helper::get_prettylinks_meta($data['id'], 'tech_device');
            $tech_device = wp_list_pluck($tech_device, 'meta_value');
            $tech_os = \BetterLinksPro\Helper::get_prettylinks_meta($data['id'], 'tech_os');
            $tech_os = wp_list_pluck($tech_os, 'meta_value');
            $tech_browser = \BetterLinksPro\Helper::get_prettylinks_meta($data['id'], 'tech_browser');
            $tech_browser = wp_list_pluck($tech_browser, 'meta_value');
            $tech_url = \BetterLinksPro\Helper::get_prettylinks_meta($data['id'], 'tech_url');
            $tech_url = wp_list_pluck($tech_url, 'meta_value');

            $redirect_url = [];
            if (is_array($tech_url) && count($tech_url) > 0) {
                foreach ($tech_url as $key => $url) {
                    $redirect_url[] = [
                        'link' => $url,
                        'device' => $tech_device[$key],
                        'os' =>  $tech_os[$key],
                        'browser' => $tech_browser[$key]
                    ];
                }
            }
            $redirect = [];
            $redirect['type'] = 'technology';
            $redirect['value'] = $redirect_url;
            $arg['dynamic_redirect'] = json_encode($redirect);
        } elseif ($prli_link_meta->get_link_meta($data['id'], 'prli_dynamic_redirection', true) == 'time') {
            $time_url = \BetterLinksPro\Helper::get_prettylinks_meta($data['id'], 'time_url');
            $time_url = wp_list_pluck($time_url, 'meta_value');
            $time_start = \BetterLinksPro\Helper::get_prettylinks_meta($data['id'], 'time_start');
            $time_start = wp_list_pluck($time_start, 'meta_value');
            $time_end = \BetterLinksPro\Helper::get_prettylinks_meta($data['id'], 'time_end');
            $time_end = wp_list_pluck($time_end, 'meta_value');

            $redirect_url = [];
            if (is_array($time_url) && count($time_url) > 0) {
                foreach ($time_url as $key => $url) {
                    $redirect_url[] = [
                        'link' => $url,
                        'start_date' => $time_start[$key],
                        'end_date' =>  $time_end[$key]
                    ];
                }
            }
            $redirect = [];
            $redirect['type'] = 'time';
            $redirect['value'] = $redirect_url;
            $arg['dynamic_redirect'] = json_encode($redirect);
        }

        return $arg;
    }
    public function add_new_menu_item($items)
    {
        $sub_menu = [];
        foreach ($items as $key => $item) {
            if ($key === 'betterlinks') {
                $sub_menu[$key] = $item;
                $sub_menu[BETTERLINKS_PLUGIN_SLUG . '-keywords-linking'] =[
                    'title' => __('Auto-Link Keywords', 'betterlinks'),
                    'capability' => 'manage_options',
                ];
                continue;
            }
            $sub_menu[$key] = $item;
        }
        return $sub_menu;
    }
}
