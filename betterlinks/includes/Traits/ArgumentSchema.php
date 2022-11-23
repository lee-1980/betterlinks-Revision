<?php
namespace BetterLinks\Traits;

trait ArgumentSchema
{
    public function links_schema()
    {
        return apply_filters('betterlinks/links_schema', [
            'ID' => [
                'type' => 'integer',
                'sanitize_callback' => 'absint',
            ],
            'link_author' => [
                'type' => 'integer',
                'sanitize_callback' => 'absint',
            ],
            'link_date' => [
                'type' => 'string',
                'format' => 'date-time',
            ],
            'link_date_gmt' => [
                'type' => 'string',
                'format' => 'date-time',
            ],
            'link_title' => [
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'link_slug' => [
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'link_note' => [
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'link_status' => [
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'nofollow' => [
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'sponsored' => [
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'track_me' => [
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'param_forwarding' => [
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'param_struct' => [
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'redirect_type' => [
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'target_url' => [
                'type' => 'string',
                'sanitize_callback' => 'esc_url_raw',
            ],
            'short_url' => [
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'link_modified' => [
                'type' => 'string',
                'format' => 'date-time',
            ],
            'link_modified_gmt' => [
                'type' => 'string',
                'format' => 'date-time',
            ],
            'wildcards' => [
                'type' => 'integer',
                'sanitize_callback' => 'absint',
            ]
        ]);
    }
    public function terms_schema()
    {
        return [
            'ID' => [
                'type' => 'integer',
                'sanitize_callback' => 'absint',
            ],
            'term_name' => [
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'term_slug' => [
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'term_type' => [
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ],
        ];
    }
    public function clicks_schema()
    {
        return apply_filters('betterlinks/clicks_schema', [
            'ID' => [
                'type' => 'integer',
                'sanitize_callback' => 'absint',
            ],
            'link_id' => [
                'type' => 'integer',
                'sanitize_callback' => 'absint',
            ],
            'ip' => [
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'browser' => [
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'os' => [
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'referer' => [
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'host' => [
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'uri' => [
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'click_count' => [
                'type' => 'integer',
                'sanitize_callback' => 'absint',
            ],
            'visitor_id' => [
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'click_order' => [
                'type' => 'integer',
                'sanitize_callback' => 'absint',
            ],
            'created_at' => [
                'type' => 'string',
                'format' => 'date-time',
            ],
            'created_at_gmt' => [
                'type' => 'string',
                'format' => 'date-time',
            ],
            'goal_reached' => [
                'type' => 'integer',
                'sanitize_callback' => 'absint',
            ],
            'target_url' => [
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ],
        ]);
    }
    public function get_clicks_schema()
    {
        return $this->clicks_schema();
    }
    public function get_links_schema()
    {
        return  array_merge(
            $this->links_schema(),
            [
                'limit' => [
                    'type' => 'integer',
                    'default' => 5,
                    'sanitize_callback' => 'absint',
                ],
                'old_short_url' => [
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'cat_id' => [
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'tags_id' => [
                    'type' => 'array'
                ],
            ],
            $this->terms_schema()
        );
    }

    public function get_terms_schema()
    {
        return $this->terms_schema();
    }

    public function get_settings_schema()
    {
        return [
            'nofollow' => [
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'sponsored' => [
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'track_me' => [
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'param_forwarding' => [
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'redirect_type' => [
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ]
        ];
    }
}
