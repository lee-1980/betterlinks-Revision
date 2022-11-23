<?php
namespace BetterLinks;

use BetterLinks\Helper;

class Cron
{
    public static function init()
    {
        $self = new self();
        add_filter('cron_schedules', [$self, 'add_cron_schedule']);
        add_action('betterlinks/write_json_links', [$self, 'write_json_links']);
        if (!wp_next_scheduled('betterlinks/analytics')) {
            $timestamp = time() + (60 * 60);
            wp_schedule_event($timestamp, 'hourly', 'betterlinks/analytics');
        }
        add_action('betterlinks/analytics', [$self, 'analytics']);
    }

    public function add_cron_schedule($schedules)
    {
        $schedules['every_one_and_half_hours'] = [
            'interval' => 5400, // Every 90 Minutes
            'display' => __('Every 90 Minutes'),
        ];
        return $schedules;
    }
    public function write_json_links()
    {
        $formattedArray = \BetterLinks\Helper::get_links_for_json();
        return file_put_contents(BETTERLINKS_UPLOAD_DIR_PATH . '/links.json', json_encode($formattedArray));
    }

    public function analytics()
    {
        Helper::clear_query_cache();
        try {
            // insert clicks json data into db
            if (BETTERLINKS_EXISTS_CLICKS_JSON) {
                $Clicks = json_decode(file_get_contents(BETTERLINKS_UPLOAD_DIR_PATH . '/clicks.json'), true);
                // link id already exists or not in links table
                if (is_array($Clicks)) {
                    foreach ($Clicks as $key => $item) {
                        $click_id = Helper::insert_click($item);
                        if (!empty($click_id)) {
                            do_action('betterlinks/link/after_insert_click', $item['link_id'], $click_id, $item['target_url']);
                        }
                    }
                    file_put_contents(BETTERLINKS_UPLOAD_DIR_PATH . '/clicks.json', '{}');
                }
            }

            $is_update = Helper::update_links_analytics();
            return $is_update;
        } catch (\Throwable $th) {
            return $th->getMessage();
        }
        return;
    }
}
