<?php
namespace BetterLinks\Traits;

trait Terms
{
    public function get_all_terms_data($args)
    {
        if (isset($args['ID'])) {
            $results = \BetterLinks\Helper::get_terms_by_link_ID_and_term_type($args['ID'], $args['term_type']);
        } else {
            $results = \BetterLinks\Helper::get_terms_all_data();
        }
        return $results;
    }
    public function create_term($args)
    {
        $term_id = \BetterLinks\Helper::insert_term($args);
        if ($term_id) {
            $args['ID'] = $term_id;
            $args['lists'] = [];
            return $args;
        }
        return [];
    }
    public function update_term($args)
    {
        \BetterLinks\Helper::insert_term([
            'ID' => $args['cat_id'],
            'term_name' => $args['cat_name'],
            'term_slug' => $args['cat_slug'],
            'term_type' => 'category'
        ], true);
        return $args;
    }
    public function delete_term($args)
    {
        if ($args['cat_id'] != 1) {
            \BetterLinks\Helper::delete_term_and_update_term_relationships($args['cat_id']);
        }
    }
}
