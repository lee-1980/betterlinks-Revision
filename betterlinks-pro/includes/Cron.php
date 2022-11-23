<?php
namespace BetterLinksPro;

class Cron
{
    public static function init()
    {
        $self = new self();
        add_filter('cron_schedules', array($self, 'cron_time_intervals'));
        add_action('betterlinkspro/expire_link_status_handler', array($self, 'expire_link_status_handler'));
        add_action('betterlinkspro/send_google_analytics_data', array($self, 'send_google_analytics_data'), 10, 3);
        add_filter('betterlinks/before_write_json_links', array($self, 'append_google_analytic_data'));
        add_action('betterlinkspro/broken_link_checker', array($self, 'broken_link_checker'));
        add_action('betterlinks/scheduled_link_publish', [$self, 'scheduled_link_publish'], 10, 1);
        $self->dispatch_brokenlink_checker();
    }
    

    public function cron_time_intervals($schedules)
    {
        $schedules['monthly'] = array(
            'interval' => MONTH_IN_SECONDS,
            'display' => 'Once Monthly'
        );
        $schedules['yearly'] = array(
            'interval' => YEAR_IN_SECONDS,
            'display' => 'Once Yearly'
        );
        return $schedules;
    }

    public function dispatch_brokenlink_checker()
    {
        if (! wp_next_scheduled('betterlinkspro/broken_link_checker')) {
            $settings_broken = json_decode(get_option(BETTERLINKS_PRO_BROKEN_LINK_OPTION_NAME, '{}'), true);
            if (isset($settings_broken['enable_scan']) && $settings_broken['enable_scan'] == true) {
                $scan_mode = isset($settings_broken['scan_mode']) ? $settings_broken['scan_mode'] : 'weekly';
                $scan_day = isset($settings_broken['scan_day']) ? $settings_broken['scan_day'] : 'sunday';
                $scan_time = isset($settings_broken['scan_time']) ? substr($settings_broken['scan_time'], 2, -2) : '12:00';
                $timestamp = \BetterLinksPro\Helper::calculate_schedule_timestamp(
                    $scan_mode,
                    $scan_day,
                    $scan_time
                );
                wp_schedule_event($timestamp, $scan_mode, 'betterlinkspro/broken_link_checker');
            }
        }
    }

    public function expire_link_status_handler($data)
    {
        // for clicks
        if ($data['expire']['type'] == 'clicks') {
            // clear clicks json data
            $clicks = new \BetterLinks\Cron();
            $clicks->analytics();
            $analytic = json_decode(get_option('betterlinks_analytics_data'), true);
            if (isset($analytic[$data['ID']])) {
                $analytic = $analytic[$data['ID']];
                if (intval($analytic['link_count']) >= intval($data['expire']['clicks'])) {
                    $this->change_links_status_by_id($data['ID'], 'expired');
                    if (BETTERLINKS_EXISTS_LINKS_JSON) {
                        // change status
                        $data['link_status'] = 'expired';
                        \BetterLinks\Helper::update_json_into_file(trailingslashit(BETTERLINKS_UPLOAD_DIR_PATH) . 'links.json', $data, $data['short_url']);
                    }
                }
            }
        } else {
            // for date
            if (!empty($data['expire']['date'])) {
                $timezone = wp_timezone_string();
                $specificTime = new \DateTime($data['expire']['date'], new \DateTimeZone($timezone));
                $now = new \DateTime("now", new \DateTimeZone($timezone));
                if ($specificTime->getTimestamp() < $now->getTimestamp()) {
                    $this->change_links_status_by_id($data['ID'], 'expired');
                    if (BETTERLINKS_EXISTS_LINKS_JSON) {
                        // change status
                        $data['link_status'] = 'expired';
                        \BetterLinks\Helper::update_json_into_file(trailingslashit(BETTERLINKS_UPLOAD_DIR_PATH) . 'links.json', $data, $data['short_url']);
                    }
                }
            }
        }
    }
    public function send_google_analytics_data($data, $page_url, $ga_tracking_code)
    {
        if ($ga_tracking_code) {
            $analytic = new \BetterLinksPro\Analytics\GoogleAnalytics();
            $analytic->ga_send_pageview(get_site_url(), $page_url, $data['link_slug'], $ga_tracking_code);
        }
    }

    public function change_links_status_by_id($ID, $status)
    {
        $retults = \BetterLinks\Helper::insert_link(['ID' => $ID, 'link_status' => $status], true);
        return $retults;
    }
    public function append_google_analytic_data($data)
    {
        $ga = json_decode(get_option(BETTERLINKS_PRO_GA_OPTION_NAME, '{}'), true) ;
        if (is_array($ga) && is_array($data)) {
            return array_merge($ga, $data);
        }
        return $data;
    }
    public function broken_link_checker()
    {
        $brokenlink = Admin\BrokenLink::getInstance();
        if (!$brokenlink->doing_dispatch()) {
            global $wpdb;
            $betterlinks = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}betterlinks", ARRAY_A);
            delete_option('betterlinkspro_broken_links_logs');
            $brokenlink->init();
            foreach ($betterlinks as $link) {
                $brokenlink->push_to_queue($link);
            }
            $brokenlink->push_to_queue('send_report_to_email');
            $brokenlink->save();
        }
    }
    public function scheduled_link_publish($link_id)
    {
        delete_transient(BETTERLINKS_CACHE_LINKS_NAME);
        $retults = \BetterLinks\Helper::insert_link(['ID' => $link_id, 'link_status' => 'publish'], true);
        $Cron = new \BetterLinks\Cron();
        $Cron->write_json_links();
        return $retults;
    }
}
