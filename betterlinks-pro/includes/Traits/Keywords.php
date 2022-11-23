<?php
namespace BetterLinksPro\Traits;

trait Keywords
{
    public function prepare_keyword_item_for_db($params)
    {
        return [
            'keywords' => (isset($params['keywords']) ? sanitize_text_field($params['keywords']) : ''),
            'link_id' => (isset($params['chooseLink']) ? intval(sanitize_text_field($params['chooseLink'])) : ''),
            'post_type' => (isset($params['postType']) && is_array($params['postType']) ? array_map('sanitize_text_field', $params['postType']) : ''),
            'category' => (isset($params['category']) && is_array($params['category']) ? array_map('sanitize_text_field', $params['category']) : ''),
            'tags' => (isset($params['tags']) && is_array($params['tags']) ? array_map('sanitize_text_field', $params['tags']) : ''),
            'open_new_tab' => (isset($params['openNewTab']) ? intval(sanitize_text_field($params['openNewTab'])) : '') ,
            'use_no_follow' => (isset($params['useNoFollow']) ? intval(sanitize_text_field($params['useNoFollow'])) : '') ,
            'case_sensitive' => (isset($params['caseSensitive']) ? intval(sanitize_text_field($params['caseSensitive'])) : ''),
            'left_boundary' => (isset($params['leftBoundary']) ? sanitize_text_field($params['leftBoundary']) : ''),
            'right_boundary' => (isset($params['rightBoundary']) ? sanitize_text_field($params['rightBoundary']) : ''),
            'keyword_before' => (isset($params['keywordBefore']) ? sanitize_text_field($params['keywordBefore']) : ''),
            'limit' => (isset($params['limit']) ? intval(sanitize_text_field($params['limit'])) : ''),
            'priority' => (isset($params['priority']) ? intval(sanitize_text_field($params['priority'])) : ''),
            'keyword_after' => (isset($params['keywordAfter']) ? sanitize_text_field($params['keywordAfter']) : ''),
        ];
    }
}
