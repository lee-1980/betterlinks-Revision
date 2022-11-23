<?php
namespace BetterLinksPro;

class Helper
{
    public static function get_remote_plugin_data($slug = '')
    {
        if (empty($slug)) {
            return new \WP_Error('empty_arg', __('Argument should not be empty.'));
        }

        $response = wp_remote_post(
            'http://api.wordpress.org/plugins/info/1.0/',
            [
                'body' => [
                    'action' => 'plugin_information',
                    'request' => serialize((object) [
                        'slug' => $slug,
                        'fields' => [
                            'version' => false,
                        ],
                    ]),
                ],
            ]
        );

        if (is_wp_error($response)) {
            return $response;
        }

        return unserialize(wp_remote_retrieve_body($response));
    }
    
    public static function install_plugin($slug = '', $active = true)
    {
        if (empty($slug)) {
            return new \WP_Error('empty_arg', __('Argument should not be empty.'));
        }

        include_once ABSPATH . 'wp-admin/includes/file.php';
        include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        include_once ABSPATH . 'wp-admin/includes/class-automatic-upgrader-skin.php';

        $plugin_data = self::get_remote_plugin_data($slug);

        if (is_wp_error($plugin_data)) {
            return $plugin_data;
        }

        $upgrader = new \Plugin_Upgrader(new \Automatic_Upgrader_Skin());

        // install plugin
        $install = $upgrader->install($plugin_data->download_link);

        if (is_wp_error($install)) {
            return $install;
        }

        // activate plugin
        if ($install === true && $active) {
            $active = activate_plugin($upgrader->plugin_info(), '', false);

            if (is_wp_error($active)) {
                return $active;
            }

            return $active === null;
        }

        return $install;
    }
    public static function get_all_roles()
    {
        global $wp_roles;
        $all_roles = $wp_roles->roles;
        unset($all_roles['administrator']);
        return wp_list_pluck($all_roles, 'name');
    }
    public static function current_user_can_do($role, $user, $userPermission)
    {
        if (current_user_can('manage_options')) {
            return true;
        }
        $current_user_roles = current($user->roles);

        if (isset($userPermission[$role]) && in_array($current_user_roles, $userPermission[$role])) {
            return true;
        }
        return false;
    }

    public static function is_json($string, $return_data = false)
    {
        if (is_array($string)) {
            return false;
        }
        $data = json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE) ? ($return_data ? $data : true) : false;
    }

    public static function get_current_client_ip()
    {
        $ipaddress = (isset($_SERVER['REMOTE_ADDR']))?sanitize_text_field($_SERVER['REMOTE_ADDR']):'';
        if (isset($_SERVER['HTTP_CLIENT_IP']) && $_SERVER['HTTP_CLIENT_IP'] != '127.0.0.1') {
            $ipaddress = sanitize_text_field($_SERVER['HTTP_CLIENT_IP']);
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR'] != '127.0.0.1') {
            $ipaddress = sanitize_text_field($_SERVER['HTTP_X_FORWARDED_FOR']);
        } elseif (isset($_SERVER['HTTP_X_FORWARDED']) && $_SERVER['HTTP_X_FORWARDED'] != '127.0.0.1') {
            $ipaddress = sanitize_text_field($_SERVER['HTTP_X_FORWARDED']);
        } elseif (isset($_SERVER['HTTP_FORWARDED_FOR']) && $_SERVER['HTTP_FORWARDED_FOR'] != '127.0.0.1') {
            $ipaddress = sanitize_text_field($_SERVER['HTTP_FORWARDED_FOR']);
        } elseif (isset($_SERVER['HTTP_FORWARDED']) && $_SERVER['HTTP_FORWARDED'] != '127.0.0.1') {
            $ipaddress = sanitize_text_field($_SERVER['HTTP_FORWARDED']);
        }
        return $ipaddress;
    }
    public static function get_country_code()
    {
        $ip = self::get_current_client_ip();
        $country_code = get_transient('betterlinkspro' . $ip);
        if ($country_code) {
            return $country_code;
        } else {
            $apiUrl    = "http://api.ipstack.com/{$ip}?access_key=f4bcd16337bcc4911160fe764234b057";
            $response = wp_remote_get($apiUrl);
            $responseBody = wp_remote_retrieve_body($response);
            $result = json_decode($responseBody, true);
            if (is_array($result) && isset($result['country_code']) && ! is_wp_error($responseBody)) {
                set_transient('betterlinkspro' . $ip, $result['country_code'], DAY_IN_SECONDS);
                return $result['country_code'];
            }
        }
        return;
    }
    public static function get_client_info()
    {
        $useragent = (isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : '');
        if ($useragent) {
            $Browser = new Lib\BrowserDetection();
            return [
                'browser'   => $Browser->getBrowser($useragent)['browser_name'],
                'os'        => $Browser->getOS($useragent)['os_name'],
                'device'    => $Browser->getDevice($useragent)['device_type']
            ];
        }
        return;
    }

    public static function check_in_range($start_date, $end_date, $date_from_user)
    {
        // Convert to timestamp
        $start = strtotime($start_date);
        $end = strtotime($end_date);
        $check = is_numeric($date_from_user) ? $date_from_user : strtotime($date_from_user);
        // Check that user date is between start & end
        return (($start <= $check) && ($check <= $end));
    }
    public static function addScheme($url, $scheme = 'http://')
    {
        if (strpos($url, "/") === 0) {
            return $url = site_url('/') . $url;
        }
        return apply_filters('betterlinks/link/target_url', parse_url($url, PHP_URL_SCHEME) === null ? $scheme . $url : $url);
    }
    public static function url_http_response_is_broken($url)
    {
        $client = new \GuzzleHttp\Client();
        try {
            $response = $client->request('GET', $url, ['http_errors' => false, 'verify' => false]);
            $statusCode = intval($response->getStatusCode());
            return $statusCode > 400 && $statusCode < 599 ? true : false;
        } catch (\Throwable $th) {
            return true;
        }
        return;
    }
    public static function get_timestamp_from_string($string)
    {
        if ($string === 'yearly') {
            return time() + YEAR_IN_SECONDS;
        } elseif ($string === 'monthly') {
            return time() + MONTH_IN_SECONDS;
        } elseif ($string === 'weekly') {
            return time() + WEEK_IN_SECONDS;
        } elseif ($string === 'daily') {
            return time() + DAY_IN_SECONDS;
        }
        return time();
    }
    public static function calculate_schedule_timestamp($scan_mode, $scan_day, $scan_time)
    {
        $timestamp = self::get_timestamp_from_string($scan_mode);
        $daynum = (int) date("w", strtotime($scan_day));
        $todayWeekDayNumbeer = (int) date("w", time());
        if ($daynum < $todayWeekDayNumbeer) {
            return $timestamp = strtotime($scan_time . ' ' . $scan_day.' next week', $timestamp);
        }
        $daynum =  abs($daynum - $todayWeekDayNumbeer);
        $timestamp = strtotime('+'.$daynum.' days ' . $scan_time, $timestamp);
        return $timestamp;
    }
    public static function get_individual_link_analytics($args)
    {
        $ID = isset($args['id']) ? sanitize_text_field($args['id']) : '';
        $from = isset($args['from']) ? sanitize_text_field($args['from']) : date('Y-m-d', strtotime(' - 30 days'));
        $to = isset($args['to']) ? sanitize_text_field($args['to']) : date('Y-m-d');
        global $wpdb;
        $prefix = $wpdb->prefix;
        $results = $wpdb->get_results(
            $wpdb->prepare("SELECT CLICKS.ID as 
            click_ID, link_id, browser, created_at, referer, short_url, target_url, ip, {$prefix}betterlinks.link_title,
            (select count(id) from {$prefix}betterlinks_clicks where CLICKS.ip = {$prefix}betterlinks_clicks.ip group by ip) as IPCOUNT
            from {$prefix}betterlinks_clicks as CLICKS left join {$prefix}betterlinks on {$prefix}betterlinks.id = CLICKS.link_id WHERE created_at BETWEEN %s AND %s AND {$prefix}betterlinks.id = %d group by CLICKS.id ORDER BY CLICKS.created_at DESC", $from . ' 00:00:00', $to . ' 23:59:00', $ID),
            ARRAY_A
        );
        return $results;
    }
    public static function get_split_test_analytics_data($args)
    {
        global $wpdb;
        $prefix = $wpdb->prefix;
        $ID = isset($args['id']) ? $args['id'] : '';
        $results = $wpdb->get_results(
            $wpdb->prepare("SELECT cr.target_url as url, COUNT(cr.target_url) as clicks, COUNT(DISTINCT cl.visitor_id) as uniques, (select COUNT(DISTINCT {$prefix}betterlinks_clicks.visitor_id) from {$prefix}betterlinks_clicks where {$prefix}betterlinks_clicks.rotation_target_url = cr.target_url) as conversions  FROM {$prefix}betterlinks_clicks cl JOIN {$prefix}betterlinks_clicks_rotations cr ON cl.id=cr.click_id WHERE cl.link_id=%d GROUP BY cr.target_url", $ID),
            ARRAY_A
        );
        return $results;
    }

    public static function insert_click_rotation($args)
    {
        global $wpdb;
        $defaults = apply_filters('betterlinkspro/insert_click_rotation_args', array(
            'link_id' => '',
            'click_id' => '',
            'target_url' => ''
        ));
        $args = wp_parse_args($args, $defaults);
        $wpdb->query(
            $wpdb->prepare(
                "INSERT INTO {$wpdb->prefix}betterlinks_clicks_rotations ( 
                        link_id,click_id,target_url
                    ) VALUES ( %d, %d, %s )",
                array(
                        $args['link_id'],$args['click_id'],$args['target_url']
                    )
            )
        );
        return $wpdb->insert_id;
    }
    public static function get_all_clicks_rotations()
    {
        global $wpdb;
        $rotations = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}betterlinks_clicks_rotations",
            ARRAY_A
        );
        return $rotations;
    }
    public static function get_prettylinks_meta($id, $meta_key)
    {
        global $wpdb;
        $wpdb_prefix = $wpdb->prefix;
        $results = $wpdb->get_results($wpdb->prepare("SELECT meta_key, meta_value from {$wpdb_prefix}prli_link_metas where meta_key=%s AND link_id = %d", $meta_key, $id), OBJECT);
        return $results;
    }
}
