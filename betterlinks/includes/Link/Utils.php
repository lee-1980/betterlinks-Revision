<?php
namespace BetterLinks\Link;

use Jaybizzle\CrawlerDetect\CrawlerDetect;

class Utils
{
    public function get_slug_raw($slug)
    {
        if (BETTERLINKS_EXISTS_LINKS_JSON) {
            return apply_filters('betterlinks/link/get_link_by_slug', \BetterLinks\Helper::get_link_from_json_file($slug));
        }
        $link_options = json_decode(get_option(BETTERLINKS_LINKS_OPTION_NAME, '{}'), true);
        $is_case_sensitive = isset($link_options['is_case_sensitive']) ? $link_options['is_case_sensitive'] : false;
        $results = current(\BetterLinks\Helper::get_link_by_short_url($slug, $is_case_sensitive));
        if (!empty($results)) {
            return apply_filters('betterlinks/link/get_link_by_slug', json_decode(json_encode($results), true));
        }
        // wildcards
        $links_option = json_decode(get_option(BETTERLINKS_LINKS_OPTION_NAME), true);
        if (isset($links_option['wildcards']) && $links_option['wildcards']) {
            $results = \BetterLinks\Helper::get_link_by_wildcards(1);
            if (is_array($results) && count($results) > 0) {
                foreach ($results as $key => $item) {
                    $postion = strpos($item['short_url'], '/*');
                    if ($postion !== false) {
                        $item_short_url_substr = substr($item['short_url'], 0, $postion);
                        $slug_substr = substr($slug, 0, $postion);
                        if(!$is_case_sensitive){
                            $item_short_url_substr = strtolower($item_short_url_substr);
                            $slug_substr = strtolower($slug_substr);
                        }
                        if ($item_short_url_substr == $slug_substr) {
                            $target_postion = strpos($item['target_url'], '/*');
                            if ($target_postion !== false) {
                                $target_url = str_replace('/*', substr($slug, $postion), $item['target_url']);
                                $item['target_url'] = $target_url;
                                return apply_filters('betterlinks/link/get_link_by_slug', json_decode(json_encode($item), true));
                            }
                            return apply_filters('betterlinks/link/get_link_by_slug', json_decode(json_encode($item), true));
                        }
                    }
                }
            }
        }
    }
    public function dispatch_redirect($data, $param)
    {
        global $betterlinks;

        $data = apply_filters('betterlinks/link/before_dispatch_redirect', $data);
        if (!$data) {
            return;
        }
        if (filter_var($data['track_me'], FILTER_VALIDATE_BOOLEAN)) {
            if ($betterlinks['disablebotclicks'] && class_exists('CrawlerDetect')) {
                $CrawlerDetect = new CrawlerDetect;
                if (! $CrawlerDetect->isCrawler()) {
                    $this->start_trakcing($data);
                }
            } else {
                $this->start_trakcing($data);
            }
        }

        $robots_tags = [];
        if (filter_var($data['sponsored'], FILTER_VALIDATE_BOOLEAN)) {
            $robots_tags[] = 'sponsored';
        }
        if (filter_var($data['nofollow'], FILTER_VALIDATE_BOOLEAN)) {
            $robots_tags[] = 'noindex';
            $robots_tags[] = 'nofollow';
        }
        if (!empty($robots_tags)) {
            header('X-Robots-Tag: ' . implode(', ', $robots_tags), true);
        }

        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Cache-Control: post-check=0, pre-check=0', false);
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Cache-Control: no-cache');
        header('Pragma: no-cache');
        header('X-Redirect-Powered-By:  https://www.betterlinks.io/');

        $target_url = $this->addScheme($data['target_url']);
        if (filter_var($data['param_forwarding'], FILTER_VALIDATE_BOOLEAN) && !empty($param)) {
            $target_url = $target_url . '?' . $param;
        }

        switch ($data['redirect_type']) {
            case '301':
                wp_redirect(esc_url_raw($target_url), 301);
                exit();
            case '302':
                wp_redirect(esc_url_raw($target_url), 302);
                exit();
            case '307':
                wp_redirect(esc_url_raw($target_url), 307);
                exit();
            case 'cloak':
                do_action('betterlinks/make_cloaked_redirect', $target_url, $data);
                exit();
            default:
                wp_redirect(esc_url_raw($target_url));
                exit();
        }
    }
    public function start_trakcing($data)
    {
        do_action('betterlinks/link/before_start_tracking', $data);
        $now = current_time('mysql');
        $now_gmt = current_time('mysql', 1);
        $IP = $this->get_current_client_IP();
        $visitor_cookie = 'betterlinks_visitor';
        if (!isset($_COOKIE[$visitor_cookie])) {
            $visitor_cookie_expire_time = time() + 60 * 60 * 24 * 365; // 1 year
            $visitor_uid = uniqid('bl');
            setcookie($visitor_cookie, $visitor_uid, $visitor_cookie_expire_time, '/');
        }
        $arg = apply_filters('betterlinks/link/insert_click_arg', [
            'link_id' => $data['ID'],
            'ip' => $IP,
            'browser' => $_SERVER['HTTP_USER_AGENT'],
            'os' => '',
            'referer' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '',
            'host' => $IP,
            'uri' => $data['link_slug'],
            'click_count' => 0,
            'visitor_id' => isset($_COOKIE[$visitor_cookie]) ? sanitize_text_field($_COOKIE[$visitor_cookie]) : '',
            'click_order' => 0,
            'created_at' => $now,
            'created_at_gmt' => $now_gmt,
            'rotation_target_url' => '',
            'target_url' => $data['target_url']
        ]);

        if (BETTERLINKS_EXISTS_CLICKS_JSON) {
            $this->insert_json_into_file(BETTERLINKS_UPLOAD_DIR_PATH . '/clicks.json', $arg);
        } else {
            try {
                $click_id = \BetterLinks\Helper::insert_click($arg);
                if (!empty($click_id)) {
                    do_action('betterlinks/link/after_insert_click', $arg['link_id'], $click_id, $arg['target_url']);
                }
            } catch (\Throwable $th) {
                echo $th->getMessage();
            }
        }
    }
    public function get_current_client_IP()
    {
        $address = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : '';
        if (isset($_SERVER['HTTP_CLIENT_IP']) && $_SERVER['HTTP_CLIENT_IP'] != '127.0.0.1') {
            $address = sanitize_text_field($_SERVER['HTTP_CLIENT_IP']);
        } elseif (isset($_SERVER['HTTP_X_FORWARDED']) && $_SERVER['HTTP_X_FORWARDED'] != '127.0.0.1') {
            $address = sanitize_text_field($_SERVER['HTTP_X_FORWARDED']);
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR'] != '127.0.0.1') {
            $address = sanitize_text_field($_SERVER['HTTP_X_FORWARDED_FOR']);
        } elseif (isset($_SERVER['HTTP_FORWARDED']) && $_SERVER['HTTP_FORWARDED'] != '127.0.0.1') {
            $address = sanitize_text_field($_SERVER['HTTP_FORWARDED']);
        } elseif (isset($_SERVER['HTTP_FORWARDED_FOR']) && $_SERVER['HTTP_FORWARDED_FOR'] != '127.0.0.1') {
            $address = sanitize_text_field($_SERVER['HTTP_FORWARDED_FOR']);
        }
        $IPS = explode(',', $address);
        if (isset($IPS[1])) {
            $address = $IPS[0];
        }
        return $address;
    }
    public function addScheme($url, $scheme = 'http://')
    {
        if (strpos($url, "/") === 0) {
            return $url = site_url('/') . $url;
        }
        return apply_filters('betterlinks/link/target_url', parse_url($url, PHP_URL_SCHEME) === null ? $scheme . $url : $url);
    }

    protected function insert_json_into_file($file, $data)
    {
        $existingData = file_get_contents($file);
        $tempArray = (array) json_decode($existingData, true);
        array_push($tempArray, $data);
        return file_put_contents($file, json_encode($tempArray));
    }
}
