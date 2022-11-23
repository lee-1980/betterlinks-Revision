<?php

namespace BetterLinksPro\Analytics;

class PixelAnalytics
{
    // Send Pageview Event to Facebook Pixel as Server-Side Analytics
    // https://developers.facebook.com/docs/marketing-api/conversions-api/using-the-api
    public function pixel_send_pageview($data,  $server,  $pixel_id, $access_token)
    {
        $url = "https://graph.facebook.com/v15.0/{$pixel_id}/events";
        
        $pixel_data = [
            [
                "event_name" =>  "PageView",
                "event_time" =>  $server['REQUEST_TIME'],
                "user_data" =>  [
                    "client_ip_address" =>  $server['REMOTE_ADDR'],
                    "client_user_agent" =>  $server['HTTP_USER_AGENT'],
                ],
                "event_source_url" =>  get_site_url(null, "/" . $data["short_url"]),
                "action_source" =>  "website"
            ]
        ];

        wp_remote_post(
            $url,
            array(
                'method'      => 'POST',
                'body'        => array(
                    'data' => wp_json_encode($pixel_data),
                    'access_token' => $access_token
                ),
            )
        );
    }
}
