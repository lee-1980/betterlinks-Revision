<?php
namespace BetterLinks\Tools\Migration;

class BaseCSV
{
    public function insert_link($item)
    {
        if (!isset($item['short_url'])) {
            return;
        }
        $link = \BetterLinks\Helper::get_link_by_short_url($item['short_url']);
        $link_id = 0;
        if (count($link) > 0) {
            $item['ID'] = current($link)['ID'];
            $link_id = \BetterLinks\Helper::insert_link($item, true);
            \BetterLinks\Helper::remove_terms_relationships_by_link_ID($link_id);
        } else {
            $link_id = \BetterLinks\Helper::insert_link($item);
        }
        $tags = \BetterLinks\Helper::insert_tags_terms((!empty($item['tags']) ? explode(',', $item['tags']) : []));
        $category = \BetterLinks\Helper::insert_category_terms((!empty($item['category']) ? explode(',', $item['category']) : ['uncategorized']));
        $all_terms = array_merge($tags, $category);
        if (count($all_terms) > 0 && $link_id > 0) {
            foreach ($all_terms as $term) {
                \BetterLinks\Helper::insert_terms_relationships($term, $link_id);
            }
        }
        return $link_id;
    }

    public function insert_keywords($link_id, $keywords, $arg = [])
    {
        $results = \BetterLinks\Helper::get_link_meta($link_id, 'keywords');
        if (!$results) {
            $args = wp_parse_args($arg, [
                'keywords' => $keywords,
                'link_id'   => $link_id,
                'post_type' => ['post', 'page'],
                'category'  => '',
                'tags'      => '',
                'open_new_tab' => false,
                'use_no_follow' => false,
                'case_sensitive' => false,
                'left_boundary' => '',
                'right_boundary' => '',
                'keyword_before' => '',
                'keyword_after' => '',
                'limit' => 100,
                'priority' => ''
            ]);
            return \BetterLinks\Helper::add_link_meta($link_id, 'keywords', wp_json_encode($args));
        }
        return;
    }
}
