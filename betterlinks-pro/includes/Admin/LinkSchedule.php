<?php
namespace BetterLinksPro\Admin;

class LinkSchedule
{
    public static function init()
    {
        $self = new self();
        add_action('betterlinks/after_insert_link', [$self, 'create_schedule_link_publish_event'], 10, 2);
        add_action('betterlinks/after_update_link', [$self, 'create_schedule_link_publish_event'], 10, 2);
    }
    public function create_schedule_link_publish_event($link_id, $data)
    {
        if ($data['link_status'] === 'scheduled') {
            $timestamp = strtotime($data['link_date'] . wp_timezone_string());
            wp_schedule_single_event($timestamp, 'betterlinks/scheduled_link_publish', array( $link_id));
        }
    }
}
