<?php
namespace BetterLinks\Tools\Migration;

class PTLOneClick extends BaseCSV
{
    public function insert_link( $link_id ){
        global $wpdb;
        $item = $wpdb->get_row(
            "SELECT * FROM {$wpdb->prefix}prli_links WHERE id = $link_id LIMIT 1",
            ARRAY_A
        );

        if ( empty($item['name']) || $item['name'] == 1 ) {
            $failed_links = \BetterLinks\Helper::btl_get_option("btl_failed_migration_prettylinks_links");
            if(in_array("invalid_item_name-" . $item["id"], $failed_links)){
                return true;
            }
            return $this->log_failed_links($item, "invalid_item_name-");
        }

        $author_id = get_current_user_id();

        $slug = \BetterLinks\Helper::make_slug($item['name']);
        $link = apply_filters('betterlinks/tools/migration/ptl_one_click_import_link_arg', [
            'link_author' => $author_id,
            'link_date' => $item['created_at'],
            'link_date_gmt' => $item['created_at'],
            'link_title' => $item['name'],
            'link_slug' => $slug,
            'link_note' => '',
            'link_status' => 'publish',
            'nofollow' => isset($item['nofollow']) && $item['nofollow'] == 1 ? $item['nofollow'] : '',
            'sponsored' => isset($item['sponsored']) && $item['sponsored'] == 1 ? $item['sponsored'] : '',
            'track_me' => isset($item['track_me']) && $item['track_me'] == 1 ? $item['track_me'] : '',
            'param_forwarding' => isset($item['param_forwarding']) && $item['param_forwarding'] == 1 ? $item['param_forwarding'] : '',
            'param_struct' => '',
            'redirect_type' => isset($item['redirect_type']) ? $item['redirect_type'] : '',
            'target_url' => isset($item['url']) ? $item['url'] : '',
            'short_url' => isset($item['slug']) ? trim($item['slug'], '/ ') : '',
            'link_order' => 0,
            'link_modified' => isset($item['last_updated_at']) ? $item['last_updated_at'] : '',
            'link_modified_gmt' => isset($item['last_updated_at']) ? $item['last_updated_at'] : '',
        ], $item);

        $link_id = \BetterLinks\Helper::insert_link($link);
        if ($link_id) {
            $keywords = $this->get_keywords($item['id'], '');
            if (!empty($keywords)) {
                $keywords = wp_list_pluck($keywords, 'text');
                $keywords = implode(',', $keywords);
                $this->insert_keywords($link_id, $keywords);
            }
            if (isset($item['link_cpt_id']) && !empty($item['link_cpt_id'])) {
                $term = get_the_terms($item['link_cpt_id'], 'pretty-link-category');
                $term = !empty($term) && is_array($term) ? current($term)->name : 'uncategorized';
                $terms_ids = \BetterLinks\Helper::insert_category_terms([$term]);
                if (count($terms_ids) > 0) {
                    foreach ($terms_ids as $term_id) {
                        \BetterLinks\Helper::insert_terms_relationships($term_id, $link_id);
                    }
                }
            }
            $current_links_count = \BetterLinks\Helper::btl_get_option("btl_migration_prettylinks_current_successful_links_count");
            $current_links_count = absint($current_links_count) + 1;
            \BetterLinks\Helper::btl_update_option("btl_migration_prettylinks_current_successful_links_count", $current_links_count, false, true);
            return true;
        } else {
            $failed_links = \BetterLinks\Helper::btl_get_option("btl_failed_migration_prettylinks_links");
            if(in_array("insert_link_failed-" . $item["id"], $failed_links)){
                return true;
            }
            return $this->log_failed_links($item, "insert_link_failed-");
        }
        return true;
    }

    public function insert_click( $click_id ){
        global $wpdb;
        $item = $wpdb->get_row(
            "SELECT * FROM {$wpdb->prefix}prli_clicks WHERE id = $click_id LIMIT 1",
            ARRAY_A
        );

        if ( empty( $item['uri'] ) ) {
            $failed_clicks = \BetterLinks\Helper::btl_get_option("btl_failed_migration_prettylinks_clicks");
            if(in_array("uri_doesnot_exist-" . $item["id"], $failed_clicks)){
                return true;
            }
            return $this->log_failed_clicks($item, "uri_doesnot_exist-");
        }

        $uri=current(explode('?', $item['uri']));
        $uri=\trim($uri, '/ ');
        $link = \BetterLinks\Helper::get_link_by_short_url($uri);
        if(count($link) === 0){
            $uri = current(explode('%20', $uri));
            $uri=\trim($uri, '/ ');
            $link = \BetterLinks\Helper::get_link_by_short_url($uri);
        }

        if (count($link) > 0) {
            $click = [
                'link_id' => $link[0]['ID'],
                'ip' => $item['ip'],
                'browser' => $item['browser'],
                'os' => $item['os'],
                'referer' => $item['referer'],
                'host' => $item['host'],
                'uri' => $item['uri'],
                'click_count' => '',
                'visitor_id' => $item['vuid'],
                'click_order' => '',
                'created_at' => $item['created_at'],
                'created_at_gmt' => $item['created_at'],
            ];
            $is_insert = \BetterLinks\Helper::insert_click($click);
            if ($is_insert) {
                $current_clicks_count = \BetterLinks\Helper::btl_get_option("btl_migration_prettylinks_current_successful_clicks_count");
                $current_clicks_count = absint($current_clicks_count) + 1;
                \BetterLinks\Helper::btl_update_option("btl_migration_prettylinks_current_successful_clicks_count", $current_clicks_count, false, true);
                return true;
            }else{
                $failed_clicks = \BetterLinks\Helper::btl_get_option("btl_failed_migration_prettylinks_clicks");
                if(in_array("not_inserted-" . $item["id"], $failed_clicks)){
                    return true;
                }
                return $this->log_failed_clicks($item, "not_inserted-");
            }
        }else{
            $failed_clicks = \BetterLinks\Helper::btl_get_option("btl_failed_migration_prettylinks_clicks");
            if(in_array("link_not_found-" . $item["id"], $failed_clicks)){
                return true;
            }
            return $this->log_failed_clicks($item, "link_not_found-");
        }

        return true;
    }

    public function get_keywords($link_id)
    {
        global $wpdb;
        if($wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}prli_keywords'") != "{$wpdb->prefix}prli_keywords"){
            return false;
        }
        $query = $wpdb->prepare(
            "
                SELECT text
                FROM {$wpdb->prefix}prli_keywords AS kw
                WHERE kw.link_id=%d
            ",
            $link_id
        );
        $resutls = $wpdb->get_results($query);
        if (!empty($resutls)) {
            return  $resutls;
        }
        return;
    }

    public function log_failed_links($item, $prefix = "_-"){
        $failed_links = \BetterLinks\Helper::btl_get_option("btl_failed_migration_prettylinks_links");
        $total_failed_links = count($failed_links);
        if($total_failed_links > 10000) {
            return true;
        }
        $slug = empty($item["slug"]) ? "_" : $item['slug'];
        array_push($failed_links, $prefix . $item['id'] . "-" . $slug);
        $result = \BetterLinks\Helper::btl_update_option("btl_failed_migration_prettylinks_links", $failed_links, false, true);
        return !$result;
    }
    public function log_failed_clicks($item, $prefix = "_-"){
        $failed_clicks = \BetterLinks\Helper::btl_get_option("btl_failed_migration_prettylinks_clicks");
        $total_failed_clicks = count($failed_clicks);
        if($total_failed_clicks > 10000) {
            return true;
        }
        $uri = empty($item["uri"]) ? "_" : $item['uri'];
        array_push($failed_clicks, $prefix . $item['id'] . "-" . $uri);
        $result = \BetterLinks\Helper::btl_update_option("btl_failed_migration_prettylinks_clicks", $failed_clicks, false, true);
        return !$result;
    }
}
