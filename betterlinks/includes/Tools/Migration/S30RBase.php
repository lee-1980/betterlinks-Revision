<?php
namespace BetterLinks\Tools\Migration;

class S30RBase
{
    public function process_links_data($data)
    {
        $author_id = get_current_user_id();
        $message = [];
        $now = current_time('mysql');
        $now_gmt = current_time('mysql', 1);
        $betterlinks_links = json_decode(get_option('betterlinks_links'));
        if (is_array($data) && count($data) > 0) {
            foreach ($data as $request => $destination) {
                $args = apply_filters('betterlinks/tools/migration/s301_one_click_migration_args', [
                    'link_author' => $author_id,
                    'link_date' => $now,
                    'link_date_gmt' => $now_gmt,
                    'link_title' => 'Simple 301 Redirects - ' . ltrim($request, '/'),
                    'link_slug' => \BetterLinks\Helper::make_slug('Simple 301 Redirects - ' . $destination),
                    'link_note' => '',
                    'link_status' => 'publish',
                    'nofollow' => $betterlinks_links->nofollow,
                    'sponsored' => $betterlinks_links->sponsored,
                    'track_me' => $betterlinks_links->track_me,
                    'param_forwarding' => $betterlinks_links->param_forwarding,
                    'param_struct' => '',
                    'redirect_type' => '301',
                    'target_url' => $this->url_schema_parse($destination),
                    'short_url' => trim($request, '/'),
                    'link_order' => 0,
                    'link_modified' => $now,
                    'link_modified_gmt' => $now_gmt,
                    'wildcards' 		=> (strpos($request, '/*') !== false ? 1 : 0),
                ]);

                $link_id = \BetterLinks\Helper::insert_link($args);
                if ($link_id) {
                    $terms_ids = \BetterLinks\Helper::insert_category_terms(['simple-301-redirects']);
                    if (count($terms_ids) > 0) {
                        foreach ($terms_ids as $term_id) {
                            \BetterLinks\Helper::insert_terms_relationships($term_id, $link_id);
                        }
                    }
                    $message[] = 'Imported Successfully "' . $destination . '"';
                } else {
                    $message[] = 'import failed "' . $destination . '" already exists';
                }
            }
        }
        $update_option = $this->save_option();
        return [
            'links' => $message,
            'wildcard' => ($update_option ? ['Import Successfully Wildcards'] : [])
        ];
    }

    public function save_option()
    {
        $wildcard = get_option('301_redirects_wildcard');
        if ($wildcard == true) {
            $links = json_decode(get_option('betterlinks_links'));
            $links->wildcards = $wildcard;
            return update_option('betterlinks_links', json_encode($links));
        }
        return;
    }

    public function url_schema_parse($url)
    {
        if (strpos($url, "/") === 0) {
            return $url;
        }
        return parse_url($url, PHP_URL_SCHEME) === null ? '/' . $url : $url;
    }
}
