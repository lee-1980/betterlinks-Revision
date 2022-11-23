<?php
namespace BetterLinksPro\Traits;

trait ArgumentSchema
{
    public function utm_schema()
    {
        return apply_filters('betterlinks/utm_schema', [
            'template_name' => [
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'utm_source' => [
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'utm_medium' => [
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'utm_campaign' => [
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'utm_term' => [
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'utm_content' => [
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ]
        ]);
    }
    
    public function get_utm_schema()
    {
        return  $this->utm_schema();
    }

    public function get_keywords_schema()
    {
        return apply_filters('betterlinks/keywords_schema', [
            'keywords' => [
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'link_id' => [
                'type' => 'integer',
                'sanitize_callback' => 'absint',
            ],
            'post_type' => [
                'type' => 'array',
                'items' => array(
                    'type' => 'string'
                ),
            ],
            'category' => [
                'type' => 'array',
                'items' => array(
                    'type' => 'string'
                ),
            ],
            'tags' => [
                'type' => 'array',
                'items' => array(
                    'type' => 'string'
                ),
            ],
            'open_new_tab' => [
                'type' => 'integer',
                'sanitize_callback' => 'absint',
            ],
            'use_no_follow' => [
                'type' => 'integer',
                'sanitize_callback' => 'absint',
            ],
            'case_sensitive' => [
                'type' => 'integer',
                'sanitize_callback' => 'absint',
            ],
            'left_boundary' => [
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'right_boundary' => [
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'keyword_before' => [
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'keyword_after' => [
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'limit' => [
                'type' => 'integer',
                'sanitize_callback' => 'absint',
            ],
            'priority' => [
                'type' => 'integer',
                'sanitize_callback' => 'absint',
            ],
        ]);
    }
}
