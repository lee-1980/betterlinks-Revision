<?php
namespace BetterLinks\Tools\Migration;

use BetterLinks\Interfaces\ImportOneClickInterface;

class TAOneClick extends BaseCSV implements ImportOneClickInterface
{
    public function run_importer($data)
    {
        $message = [];
        if (is_array($data) && count($data) > 0) {
            foreach ($data as $item) {
                if (!empty($item['link_title']) && !empty($item['short_url'])) {
                    $link_id = \BetterLinks\Helper::insert_link($item);
                    if ($link_id) {
                        if (!empty($item['keywords'])) {
                            $this->insert_keywords($link_id, $item['keywords'], ['limit' => $item['limit']]);
                        }
                        $terms_ids = \BetterLinks\Helper::insert_category_terms($item['terms']);
                        if (count($terms_ids) > 0) {
                            foreach ($terms_ids as $term_id) {
                                \BetterLinks\Helper::insert_terms_relationships($term_id, $link_id);
                            }
                        }
                        $message[] = 'Imported Successfully "' . $item['short_url'] . '"';
                    } else {
                        $message[] = 'Imported Failed "' . $item['short_url'] . '" already exists.';
                    }
                }
            }
        }
        return [
            'links' => $message
        ];
    }
}
