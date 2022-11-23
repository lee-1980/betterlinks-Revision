<?php
namespace BetterLinksPro;

class Link
{
    public static function init()
    {
        $self = new self();
        add_filter('betterlinks/link/get_link_by_slug', array($self, 'get_link_by_slug'));
        add_action('betterlinks/pre_before_redirect', array($self, 'pre_before_redirect'));
        add_action('betterlinks/before_redirect', array($self, 'before_redirect'));
        add_filter('betterlinks/link/before_dispatch_redirect', array($self, 'before_dispatch_redirect'));
        add_filter('betterlinks/link/target_url', array($self, 'target_url'));
        // analytic
        add_action('betterlinks/link/before_start_tracking', array($self, 'before_start_tracking'));
        add_action('betterlinks/link/after_insert_click', array($self, 'after_insert_click'), 10, 3);
        add_filter('betterlinks/link/insert_click_arg', array($self, 'rotation_target_url_set'), 10, 2);
        // cloaked link redirect
        add_action('betterlinks/make_cloaked_redirect', array($self, 'make_cloaked_redirect'), 10, 2);
    }
    public function get_link_by_slug($link)
    {
        if (isset($link['expire']) && Helper::is_json($link['expire'])) {
            $link['expire'] = json_decode($link['expire'], true);
        }
        if (isset($link['dynamic_redirect']) && Helper::is_json($link['dynamic_redirect'])) {
            $link['dynamic_redirect'] = json_decode($link['dynamic_redirect'], true);
        }
        return $link;
    }
    public function before_redirect($data)
    {
        if (!is_array($data['expire'])) {
            $data['expire'] = json_decode($data['expire'], true);
        }
        
        if ($data['link_status'] == 'publish' && isset($data['expire']['status']) && $data['expire']['status'] == true) {
            // wp_clear_scheduled_hook('betterlinkspro/expire_link_status_handler');
            wp_schedule_single_event(time(), 'betterlinkspro/expire_link_status_handler', array($data));
        }
        $this->start_google_analytic_cron_job($data);
    }

    public function start_google_analytic_cron_job($data)
    {
        global $betterlinks;
        if (BETTERLINKS_EXISTS_LINKS_JSON && isset($betterlinks['is_enable_ga']) && $betterlinks['is_enable_ga'] == true && !empty($betterlinks['ga_tracking_code'])) {
            wp_schedule_single_event(time(), 'betterlinkspro/send_google_analytics_data', array($data, $_SERVER['REQUEST_URI'], $betterlinks['ga_tracking_code']));
        } else {
            $ga = json_decode(get_option(BETTERLINKS_PRO_GA_OPTION_NAME, '{}'), true);
            if (count($ga) > 0) {
                wp_schedule_single_event(time(), 'betterlinkspro/send_google_analytics_data', array($data, $_SERVER['REQUEST_URI'], $ga['ga_tracking_code']));
            }
        }
    }
    
    public function pre_before_redirect($data)
    {
        if ($data && ($data['link_status'] === 'draft' || $data['link_status'] === 'scheduled')) {
            return false;
        }
        return true;
    }
    public function before_dispatch_redirect($data)
    {
        if (!is_array($data['expire'])) {
            $data['expire'] = json_decode($data['expire'], true);
        }
        if ($data['link_status'] == 'expired') {
            if (isset($data['expire']) && filter_var($data['expire']['status'], FILTER_VALIDATE_BOOLEAN) == true && isset($data['expire']['redirect_status']) && filter_var($data['expire']['redirect_status'], FILTER_VALIDATE_BOOLEAN) == true) {
                $data['target_url'] = $data['expire']['redirect_url'];
                return $data;
            }
            return;
        }
        // dynamic redirect
        if (isset($data['dynamic_redirect']) && !empty($data['dynamic_redirect'])) {
            if ($data['dynamic_redirect']['type'] == 'rotation') {
                if (is_array($data['dynamic_redirect']['value'])) {
                    $index = ($data['dynamic_redirect']['extra']['rotation_mode'] == 'random' ? rand(0, count($data['dynamic_redirect']['value']) - 1) : (int) $this->random_weight_index($data['dynamic_redirect']['value']));
                    if (isset($data['dynamic_redirect']['value'][$index]['link'])) {
                        $data['target_url'] = $data['dynamic_redirect']['value'][$index]['link'];
                    }
                }
            } elseif ($data['dynamic_redirect']['type'] == 'geographic') {
                $country_code = Helper::get_country_code();
                if (is_array($data['dynamic_redirect']['value'])) {
                    foreach ($data['dynamic_redirect']['value'] as $item) {
                        if (isset($item['country']) && is_array($item['country']) && in_array($country_code, $item['country'])) {
                            $data['target_url'] = $item['link'];
                            break;
                        }
                    }
                }
            } elseif ($data['dynamic_redirect']['type'] == 'technology') {
                $client_info = Helper::get_client_info();
                if (is_array($client_info) && is_array($data['dynamic_redirect']['value'])) {
                    foreach ($data['dynamic_redirect']['value'] as $item) {
                        if (
                            (isset($item['device']) && $item['device'] == 'any' || stripos($client_info['device'], $item['device']) !== false) &&
                            (isset($item['browser']) && $item['browser'] == 'any' || stripos($client_info['browser'], $item['browser'])  !== false) &&
                            isset($item['os']) &&  stripos($client_info['os'], $item['os'])  !== false
                        ) {
                            $data['target_url'] = $item['link'];
                        }
                    }
                }
            } elseif ($data['dynamic_redirect']['type'] == 'time') {
                if (is_array($data['dynamic_redirect']['value'])) {
                    $local_time  = current_datetime();
                    $now = $local_time->getTimestamp() + $local_time->getOffset();
                    $offset  = (float) get_option('gmt_offset');
                    foreach ($data['dynamic_redirect']['value'] as $item) {
                        if (isset($item['start_date']) && isset($item['end_date'])) {
                            if (Helper::check_in_range($item['start_date']. ' ' . $offset . ' hours', $item['end_date']. ' ' . $offset . ' hours', $now)) {
                                $data['target_url'] = $item['link'];
                                break;
                            }
                        }
                    }
                }
            }
        }
        return $data;
    }

    public function random_weight_index($data)
    {
        $r = mt_rand(1, 1000);
        $offset = 0;
        foreach ($data as $k => $item) {
            $offset += $item['weight']*10;
            if ($r <= $offset) {
                return $k;
            }
        }
    }

    public function after_insert_click($link_id, $click_id, $target_url)
    {
        $link = current(\BetterLinks\Helper::get_link_by_ID($link_id));
        if (isset($link['dynamic_redirect']) && Helper::is_json($link['dynamic_redirect'])) {
            $dynamic_redirect = (array) json_decode($link['dynamic_redirect'], true);
            if (isset($dynamic_redirect['type']) &&
                $dynamic_redirect['type'] == 'rotation' &&
                isset($dynamic_redirect['extra']['split_test']) &&
                filter_var($dynamic_redirect['extra']['split_test'], FILTER_VALIDATE_BOOLEAN) == true
            ) {
                \BetterLinksPro\Helper::insert_click_rotation(array('link_id' => $link_id, 'click_id' => $click_id, 'target_url' => $target_url));
            }
        }
    }

    public function before_start_tracking($data)
    {
        if (isset($data['dynamic_redirect'])) {
            if (Helper::is_json($data['dynamic_redirect'])) {
                $dynamic_redirect = json_decode($data['dynamic_redirect'], true);
            } else {
                $dynamic_redirect = $data['dynamic_redirect'];
            }
            if (isset($dynamic_redirect['type']) && $dynamic_redirect['type'] == 'rotation' && $dynamic_redirect['extra']['split_test'] == true) {
                if (isset($dynamic_redirect['extra']['goal_link'])) {
                    //Set Cookie if it doesn't exist
                    $cookie_name = 'betterlinks_pro_goal_link_' . $dynamic_redirect['extra']['goal_link'];
                    //Used for unique click tracking
                    $cookie_expire_time = time()+60*60*24*7; // Expire in 7 days
                    if (!isset($_COOKIE[$cookie_name])) {
                        setcookie($cookie_name, $data['target_url'], $cookie_expire_time, '/');
                    }
                }
            }
        }
    }

    public function rotation_target_url_set($arg)
    {
        $cookie_name = 'betterlinks_pro_goal_link_' . $arg['link_id'];
        if (isset($_COOKIE[$cookie_name])) {
            $cookie_value = $_COOKIE[$cookie_name];
            if (setcookie($cookie_name, 0, time() - 3600, '/')) {
                $arg['rotation_target_url'] = $cookie_value;
            }
        }
        return $arg;
    }
    public static function target_url($url)
    {
        global $betterlinks;
        if (isset($betterlinks['force_https']) && $betterlinks['force_https']) {
            return preg_replace("/^http:/i", "https:", $url);
        }
        return $url;
    }
    public static function make_cloaked_redirect($target_url, $data)
    {
        header("Content-Type: text/html");
        global $wpdb;
        $prefix = $wpdb->prefix;
        $query = $wpdb->prepare("SELECT link_title,link_note FROM {$prefix}betterlinks WHERE ID = %d", $data["ID"]);
        $curr_item = current($wpdb->get_results($query, ARRAY_A));
        require_once(BETTERLINKS_PRO_ROOT_DIR_PATH . '/includes/cloaked.php');
    }
}
