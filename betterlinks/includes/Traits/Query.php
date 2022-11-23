<?php

namespace BetterLinks\Traits;

trait Query
{
    public static function insert_link($item, $is_update = false)
    {
        global $wpdb;
        if ($is_update) {
            $defaults = self::get_link_by_ID($item['ID']);
            $item = wp_parse_args($item, current($defaults));
            $favorite_exist = isset($item['favorite']);
            $link_data_array = array(
                'link_author' => $item['link_author'], 'link_date' => $item['link_date'], 'link_date_gmt' => $item['link_date_gmt'], 'link_title' => $item['link_title'], 'link_slug' => $item['link_slug'], 'link_note' => $item['link_note'], 'link_status' => $item['link_status'], 'nofollow' => $item['nofollow'], 'sponsored' => $item['sponsored'], 'track_me' => $item['track_me'], 'param_forwarding' => $item['param_forwarding'], 'param_struct' => $item['param_struct'], 'redirect_type' => $item['redirect_type'], 'target_url' => $item['target_url'], 'short_url' => $item['short_url'], 'link_order' => $item['link_order'], 'link_modified' => $item['link_modified'], 'link_modified_gmt' => $item['link_modified_gmt'], 'wildcards' => $item['wildcards'], 'expire' => $item['expire'], 'dynamic_redirect' => $item['dynamic_redirect']
            );
            $link_data_place_array = array(
                '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s'
            );
            if ($favorite_exist) {
                $link_data_array['favorite'] = $item['favorite'];
                $link_data_place_array[] = '%s';
            }
            $wpdb->update(
                "{$wpdb->prefix}betterlinks",
                $link_data_array,
                array('ID' => $item['ID']),
                $link_data_place_array,
                array('%d')
            );
            do_action('betterlinks/after_update_link', $item['ID'], $item);
            return $item['ID'];
        } else {
            $betterlinks = self::get_link_by_short_url($item['short_url']);
            if (count($betterlinks) === 0) {
                $favorite_exist = isset($item['favorite']);
                $initial_defaults_arr = array(
                    'link_author' => get_current_user_id(),
                    'link_date' => current_time('mysql'),
                    'link_date_gmt' => current_time('mysql', 1),
                    'link_title' => '',
                    'link_slug' => '',
                    'link_note' => '',
                    'link_status' => 'publish',
                    'nofollow' => '',
                    'sponsored' => '',
                    'track_me' => '',
                    'param_forwarding' => '',
                    'param_struct' => '',
                    'redirect_type' => '',
                    'target_url' => '',
                    'short_url' => '',
                    'link_order' => '',
                    'link_modified' => current_time('mysql'),
                    'link_modified_gmt' => current_time('mysql', 1),
                    'wildcards' => '',
                    'expire' => '',
                    'dynamic_redirect' => '',
                );
                if ($favorite_exist) {
                    $initial_defaults_arr['favorite'] = "";
                }
                $defaults = apply_filters('betterlinks/insert_link_default_args', $initial_defaults_arr);
                $item = wp_parse_args($item, $defaults);
                if ($favorite_exist) {
                    $wpdb->query(
                        $wpdb->prepare(
                            "INSERT INTO {$wpdb->prefix}betterlinks (
                            link_author,link_date,link_date_gmt,link_title,link_slug,link_note,link_status,nofollow,sponsored,track_me,param_forwarding,param_struct,redirect_type,target_url,short_url,link_order,link_modified,link_modified_gmt,wildcards,expire,dynamic_redirect,favorite
                        ) VALUES ( %d, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %d, %s, %s, %s )",
                            array(
                                $item['link_author'], $item['link_date'], $item['link_date_gmt'], $item['link_title'], $item['link_slug'], $item['link_note'], $item['link_status'], $item['nofollow'], $item['sponsored'], $item['track_me'], $item['param_forwarding'], $item['param_struct'], $item['redirect_type'], $item['target_url'], $item['short_url'], $item['link_order'], $item['link_modified'], $item['link_modified_gmt'], $item['wildcards'], $item['expire'], $item['dynamic_redirect'], $item['favorite']
                            )
                        )
                    );
                } else {
                    $wpdb->query(
                        $wpdb->prepare(
                            "INSERT INTO {$wpdb->prefix}betterlinks (
                            link_author,link_date,link_date_gmt,link_title,link_slug,link_note,link_status,nofollow,sponsored,track_me,param_forwarding,param_struct,redirect_type,target_url,short_url,link_order,link_modified,link_modified_gmt,wildcards,expire,dynamic_redirect
                        ) VALUES ( %d, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %d, %s, %s )",
                            array(
                                $item['link_author'], $item['link_date'], $item['link_date_gmt'], $item['link_title'], $item['link_slug'], $item['link_note'], $item['link_status'], $item['nofollow'], $item['sponsored'], $item['track_me'], $item['param_forwarding'], $item['param_struct'], $item['redirect_type'], $item['target_url'], $item['short_url'], $item['link_order'], $item['link_modified'], $item['link_modified_gmt'], $item['wildcards'], $item['expire'], $item['dynamic_redirect']
                            )
                        )
                    );
                }

                do_action('betterlinks/after_insert_link', $wpdb->insert_id, $item);
                return $wpdb->insert_id;
            }
        }
        return;
    }
    public static function delete_link($ID)
    {
        global $wpdb;
        $wpdb->delete("{$wpdb->prefix}betterlinks", array('ID' => $ID), array('%d'));
        $wpdb->delete("{$wpdb->prefix}betterlinks_clicks", array('link_id' => $ID), array('%d'));
        $wpdb->delete("{$wpdb->prefix}betterlinks_terms_relationships", array('link_id' => $ID), array('%d'));
    }
    public static function remove_terms_relationships_by_link_ID($ID)
    {
        global $wpdb;
        $wpdb->delete("{$wpdb->prefix}betterlinks_terms_relationships", array('link_id' => $ID), array('%d'));
    }
    public static function get_prepare_all_links()
    {
        global $wpdb;
        $prefix = $wpdb->prefix;
        $analytic = get_option('betterlinks_analytics_data');
        $analytic = $analytic ? json_decode($analytic, true) : [];
        $results = $wpdb->get_results("SELECT
            {$prefix}betterlinks_terms.ID as cat_id,
            {$prefix}betterlinks_terms.term_name,
            {$prefix}betterlinks_terms.term_slug,
            {$prefix}betterlinks_terms.term_type,
            {$prefix}betterlinks.ID,
            {$prefix}betterlinks.link_title,
            {$prefix}betterlinks.link_slug,
            {$prefix}betterlinks.link_note,
            {$prefix}betterlinks.link_status,
            {$prefix}betterlinks.nofollow,
            {$prefix}betterlinks.sponsored,
            {$prefix}betterlinks.track_me,
            {$prefix}betterlinks.param_forwarding,
            {$prefix}betterlinks.param_struct,
            {$prefix}betterlinks.redirect_type,
            {$prefix}betterlinks.target_url,
            {$prefix}betterlinks.short_url,
            {$prefix}betterlinks.link_date,
            {$prefix}betterlinks.wildcards,
            {$prefix}betterlinks.expire,
            {$prefix}betterlinks.favorite,
            {$prefix}betterlinks.dynamic_redirect
            FROM {$prefix}betterlinks_terms
            LEFT JOIN  {$prefix}betterlinks_terms_relationships ON {$prefix}betterlinks_terms.ID = {$prefix}betterlinks_terms_relationships.term_id
            LEFT JOIN  {$prefix}betterlinks ON {$prefix}betterlinks.ID = {$prefix}betterlinks_terms_relationships.link_id
            WHERE {$prefix}betterlinks_terms.term_type = 'category' ORDER BY {$prefix}betterlinks.link_order ASC", OBJECT);
        $results = \BetterLinks\Helper::parse_link_response($results, $analytic);
        return $results;
    }
    public static function get_link_by_short_url($short_url, $is_case_sensitive = false)
    {
        global $wpdb;
        $link = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM {$wpdb->prefix}betterlinks WHERE short_url=%s", $short_url),
            ARRAY_A
        );
        if (isset($link[0]['short_url']) && $is_case_sensitive && $link[0]['short_url'] != $short_url) return [];
        return $link;
    }
    public static function get_link_by_wildcards($wildcards)
    {
        global $wpdb;
        $link = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM {$wpdb->prefix}betterlinks WHERE wildcards=%d", $wildcards),
            ARRAY_A
        );
        return $link;
    }
    public static function get_link_by_ID($ID)
    {
        global $wpdb;
        $link = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM {$wpdb->prefix}betterlinks WHERE ID=%d", $ID),
            ARRAY_A
        );
        return $link;
    }

    /**
     * Get All BetterLinks Uploads Links JSON File
     *
     * @return array
     */
    public static function get_links_for_json()
    {
        global $wpdb;
        $prefix = $wpdb->prefix;
        $formattedArray = [];
        $items = $wpdb->get_results("SELECT ID,redirect_type,short_url,link_slug,link_status,target_url,nofollow,sponsored,param_forwarding,track_me,wildcards,expire,dynamic_redirect FROM {$prefix}betterlinks");
        $options = json_decode(get_option(BETTERLINKS_LINKS_OPTION_NAME));
        $formattedArray['is_case_sensitive'] = isset($options->is_case_sensitive) ? $options->is_case_sensitive : false;
        $is_links_case_sensitive = $formattedArray['is_case_sensitive'];
        if (!empty($options)) {
            $formattedArray['wildcards_is_active'] = $options->wildcards;
            $formattedArray['disablebotclicks'] = $options->disablebotclicks;
            $formattedArray['force_https'] = $options->force_https;
        }
        if (is_array($items) && count($items) > 0) {
            foreach ($items as $item) {
                $short_url = $is_links_case_sensitive ? $item->short_url : strtolower($item->short_url);
                if ($item->wildcards == true) {
                    $formattedArray['wildcards'][$short_url] = $item;
                } else {
                    $formattedArray['links'][$short_url] = $item;
                }
            }
        }
        if (defined('BETTERLINKS_PRO_EXTERNAL_ANALYTICS_OPTION_NAME') && BETTERLINKS_PRO_EXTERNAL_ANALYTICS_OPTION_NAME) {
            $analytic_data = get_option(BETTERLINKS_PRO_EXTERNAL_ANALYTICS_OPTION_NAME, []);
            if(is_array($analytic_data)){
                $formattedArray = array_merge($analytic_data, $formattedArray);
            }else{
                $analytic_data = is_string($analytic_data) ? json_decode($analytic_data, true) : [];
                $formattedArray = array_merge($analytic_data, $formattedArray);
            }
        }
        return $formattedArray;
    }

    public static function insert_term($item, $is_update = false)
    {
        global $wpdb;
        if ($is_update) {
            $wpdb->update(
                "{$wpdb->prefix}betterlinks_terms",
                array(
                    'term_name' => $item['term_name'], 'term_slug' => $item['term_slug'], 'term_type' => $item['term_type']
                ),
                array('ID' => $item['ID']),
                array(
                    '%s', '%s', '%s'
                ),
                array('%d')
            );
            return  $item['ID'];
        } else {
            $terms = self::get_term_by_slug($item['term_slug']);
            if (count($terms) === 0) {
                $wpdb->query(
                    $wpdb->prepare(
                        "INSERT INTO {$wpdb->prefix}betterlinks_terms ( term_name, term_slug, term_type ) VALUES ( %s, %s, %s )",
                        array($item['term_name'], $item['term_slug'], $item['term_type'])
                    )
                );
                return $wpdb->insert_id;
            } elseif (isset(current($terms)['ID'])) {
                return current($terms)['ID'];
            }
        }
        return;
    }
    public static function insert_tags_terms($tags)
    {
        $terms_ids = [];
        if (is_array($tags) && count($tags) > 0) {
            foreach ($tags as $tag) {
                $insert_id = self::insert_term([
                    'term_name' => $tag,
                    'term_slug' => \BetterLinks\Helper::make_slug($tag),
                    'term_type' => 'tags'
                ]);
                if ($insert_id) {
                    $terms_ids[] = $insert_id;
                }
            }
        }
        return $terms_ids;
    }

    public static function insert_category_terms($categories)
    {
        $terms_ids = [];
        if (is_array($categories) && count($categories) > 0) {
            foreach ($categories as $category) {
                $insert_id = self::insert_term([
                    'term_name' => $category,
                    'term_slug' => \BetterLinks\Helper::make_slug($category),
                    'term_type' => 'category'
                ]);
                if ($insert_id) {
                    $terms_ids[] = $insert_id;
                }
            }
        }
        return $terms_ids;
    }
    public static function insert_terms_relationships($term_id, $link_id)
    {
        global $wpdb;
        $wpdb->query(
            $wpdb->prepare(
                "INSERT INTO {$wpdb->prefix}betterlinks_terms_relationships ( term_id, link_id ) VALUES ( %d, %d )",
                array($term_id, $link_id)
            )
        );
        return $wpdb->insert_id;
    }

    /**
     * Delete term and update Term relationship to uncategorized
     *
     * @param term_id
     * @return boolean
     */
    public static function delete_term_and_update_term_relationships($term_id)
    {
        global $wpdb;
        $wpdb->query("START TRANSACTION");
        $is_delete = $wpdb->delete($wpdb->prefix . 'betterlinks_terms', array('ID' => $term_id), array('%d'));
        if ($is_delete) {
            $term = self::get_term_by_slug('uncategorized');
            if (count($term) > 0) {
                $wpdb->update(
                    "{$wpdb->prefix}betterlinks_terms_relationships",
                    array(
                        'term_id' => current($term)['ID']
                    ),
                    array('term_id' => $term_id),
                    array(
                        '%d',
                    ),
                    array('%d')
                );
            }
        }
        $wpdb->query("COMMIT");
        return $is_delete;
    }

    public static function insert_terms_and_terms_relationship($link_id, $request, $is_update = false)
    {
        global $wpdb;
        $term_data = [];
        $newTermList = [];
        // store tags relation data
        if (isset($request['cat_id']) && !empty($request['cat_id'])) {
            if (is_numeric($request['cat_id'])) {
                $term_data[] = [
                    'term_id' => $request['cat_id'],
                    'link_id' => $link_id,
                ];
            } else {
                $newTermList[] = [
                    'term_name' => $request['cat_id'],
                    'term_slug' => $request['cat_id'],
                    'term_type' => 'category',
                ];
            }
        }
        if (isset($request['tags_id']) && is_array($request['tags_id'])) {
            foreach ($request['tags_id'] as $key => $value) {
                if (is_numeric($value)) {
                    $term_data[] = [
                        'term_id' => $value,
                        'link_id' => $link_id,
                    ];
                } else {
                    $newTermList[] = [
                        'term_name' => $value,
                        'term_slug' => $value,
                        'term_type' => 'tags',
                    ];
                }
            }
        }

        // insert new tags or category
        if (count($newTermList) > 0) {
            foreach ($newTermList as $item) {
                $term_id = \BetterLinks\Helper::insert_term($item);
                $term_data[] = [
                    'term_id' => $term_id,
                    'link_id' => $link_id,
                ];
            }
        }
        // make term and link relation
        // delete term relation
        if ($is_update && count($term_data) > 0) {
            $is_delete = $wpdb->delete($wpdb->prefix . 'betterlinks_terms_relationships', array('link_id' => $link_id), array('%d'));
            if ($is_delete) {
                foreach ($term_data as $term) {
                    \BetterLinks\Helper::insert_terms_relationships($term['term_id'], $term['link_id']);
                }
            }
        } else {
            foreach ($term_data as $term) {
                \BetterLinks\Helper::insert_terms_relationships($term['term_id'], $term['link_id']);
            }
        }
        return $term_data;
    }

    public static function get_term_by_slug($slug)
    {
        global $wpdb;
        $link = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM {$wpdb->prefix}betterlinks_terms WHERE term_slug=%s", $slug),
            ARRAY_A
        );
        return $link;
    }

    public static function get_terms_by_link_ID_and_term_type($link_ID, $term_type = 'categroy')
    {
        global $wpdb;
        $prefix = $wpdb->prefix;
        $link = $wpdb->get_results(
            $wpdb->prepare("SELECT
            {$prefix}betterlinks_terms.ID as term_id,
            {$prefix}betterlinks_terms.term_name,
            {$prefix}betterlinks_terms.term_slug,
            {$prefix}betterlinks_terms.term_type
            FROM {$prefix}betterlinks_terms
            LEFT JOIN  {$prefix}betterlinks_terms_relationships ON {$prefix}betterlinks_terms.ID = {$prefix}betterlinks_terms_relationships.term_id
            LEFT JOIN  {$prefix}betterlinks ON {$prefix}betterlinks.ID = {$prefix}betterlinks_terms_relationships.link_id
            WHERE {$prefix}betterlinks_terms_relationships.link_id = %d
            AND {$prefix}betterlinks_terms.term_type = %s", $link_ID, $term_type),
            ARRAY_A
        );
        return $link;
    }

    public static function get_terms_all_data()
    {
        global $wpdb;
        $link = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}betterlinks_terms",
            ARRAY_A
        );
        return $link;
    }

    public static function insert_click($item)
    {
        global $wpdb;
        $betterlinks = [];
        if (isset($item['short_url'])) {
            $betterlinks = self::get_link_by_short_url($item['short_url']);
        } elseif (isset($item['link_id'])) {
            $betterlinks = self::get_link_by_ID($item['link_id']);
        }
        if (isset(current($betterlinks)['ID'])) {
            $wpdb->query(
                $wpdb->prepare(
                    "INSERT INTO {$wpdb->prefix}betterlinks_clicks (
                        link_id, ip, browser, os, referer, host, uri, click_count, visitor_id, click_order, created_at, created_at_gmt
                    ) VALUES ( %d, %s, %s, %s, %s, %s, %s, %d, %s, %d, %s, %s )",
                    array(
                        current($betterlinks)['ID'], $item['ip'], $item['browser'], $item['os'], $item['referer'], $item['host'], $item['uri'], $item['click_count'], $item['visitor_id'], $item['click_order'], $item['created_at'], $item['created_at_gmt']
                    )
                )
            );
            return $wpdb->insert_id;
        }
        return;
    }

    public static function get_linksNips_count()
    {
        global $wpdb;

        $query = "select link_id, ip, ipc, t2.lidc from ( select ip, link_id, count(ip) as ipc from {$wpdb->prefix}betterlinks_clicks group by ip, link_id ) as t1
        left join ( select link_id as lid, sum(ipc) as lidc from ( select ip, link_id, count(ip) as ipc from {$wpdb->prefix}betterlinks_clicks group by ip, link_id ) as t3 group by link_id ) as t2
        on t1.link_id = t2.lid";

        $results = $wpdb->get_results($query, ARRAY_A);
        return $results;
    }

    public static function get_links_analytics()
    {
        global $wpdb;
        $prefix = $wpdb->prefix;
        $results = $wpdb->get_results(
            "SELECT DISTINCT link_id, ip,
			(select count(ip) from {$prefix}betterlinks_clicks WHERE CLICKS.ip = {$prefix}betterlinks_clicks.ip  group by ip) as IPCOUNT,
			(select count(link_id) from {$prefix}betterlinks_clicks WHERE CLICKS.link_id = {$prefix}betterlinks_clicks.link_id group by link_id) as LINKCOUNT
			from {$prefix}betterlinks_clicks as CLICKS group by CLICKS.id",
            ARRAY_A
        );
        return $results;
    }

    public static function search_clicks_data($keyword)
    {
        global $wpdb;
        $prefix = $wpdb->prefix;
        $results = $wpdb->get_results(
            $wpdb->prepare("SELECT CLICKS.ID as
            click_ID, link_id, browser, created_at, referer, short_url, target_url, ip, {$prefix}betterlinks.link_title,
            (select count(id) from {$prefix}betterlinks_clicks where CLICKS.ip = {$prefix}betterlinks_clicks.ip group by ip) as IPCOUNT
            from {$prefix}betterlinks_clicks as CLICKS left join {$prefix}betterlinks on {$prefix}betterlinks.id = CLICKS.link_id WHERE {$prefix}betterlinks.link_title LIKE %s  group by CLICKS.id ORDER BY CLICKS.created_at DESC", '%' . $keyword . '%'),
            ARRAY_A
        );
        return $results;
    }

    public static function get_clicks_by_date($from, $to)
    {
        global $wpdb;
        $prefix = $wpdb->prefix;
        $results = $wpdb->get_results(
            $wpdb->prepare("SELECT CLICKS.ID as
            click_ID, link_id, browser, created_at, referer, short_url, target_url, ip, {$prefix}betterlinks.link_title,
            (select count(id) from {$prefix}betterlinks_clicks where CLICKS.ip = {$prefix}betterlinks_clicks.ip group by ip) as IPCOUNT
            from {$prefix}betterlinks_clicks as CLICKS left join {$prefix}betterlinks on {$prefix}betterlinks.id = CLICKS.link_id WHERE created_at BETWEEN  %s AND %s group by CLICKS.id ORDER BY CLICKS.created_at DESC", $from . ' 00:00:00', $to . ' 23:59:00'),
            ARRAY_A
        );
        return $results;
    }

    public static function get_thirstyaffiliates_links()
    {
        $thirstylinks = get_posts(array(
            'posts_per_page' => -1,
            'post_type'      => 'thirstylink',
            'post_status'    => 'publish',
        ));
        $response = [];
        $betterlinks_links = json_decode(get_option('betterlinks_links', '{}'), true);
        foreach ($thirstylinks as $thirstylink) {
            $term =  wp_get_post_terms($thirstylink->ID, 'thirstylink-category', array('fields' => 'names'));
            $nofollow = get_post_meta($thirstylink->ID, '_ta_no_follow', true);
            $nofollow = ($nofollow == 'global' ? get_option('ta_no_follow', true) : $nofollow);
            $redirect_type = get_post_meta($thirstylink->ID, '_ta_redirect_type', true);
            $redirect_type = ($redirect_type == 'global' ? get_option('ta_link_redirect_type', true) : $redirect_type);
            $param_forwarding = get_post_meta($thirstylink->ID, '_ta_pass_query_str', true);
            $param_forwarding = ($param_forwarding == 'global' ? get_option('ta_pass_query_str', true) : $param_forwarding);
            $dynamic_redirect = [];
            $geolocation_links = get_post_meta($thirstylink->ID, '_ta_geolocation_links', true);
            if ($geolocation_links && is_array($geolocation_links)) {
                $dynamic_redirect_value = [];
                foreach ($geolocation_links as $key => $geolocation_link) {
                    $dynamic_redirect_value[] = [
                        'link'      => $geolocation_link,
                        'country'   => explode(',', $key)
                    ];
                }
                $dynamic_redirect = [
                    'type'        =>    'geographic',
                    'value'     => $dynamic_redirect_value,
                    'extra' => []
                ];
            }
            $link_date = get_post_meta($thirstylink->ID, '_ta_link_start_date', true);
            // expire
            $expire = [];
            $expire_date = get_post_meta($thirstylink->ID, '_ta_link_expire_date', true);
            $expire_redirect_url = get_post_meta($thirstylink->ID, '_ta_after_expire_redirect', true);
            if (!empty($expire_date)) {
                $expire = [
                    'status' => 1,
                    'type'   => 'date',
                    'date'  => $expire_date,
                ];
            }
            if (!empty($expire_redirect_url)) {
                $expire['redirect_status'] = 1;
                $expire['redirect_url'] = $expire_redirect_url;
            }
            // link status
            $link_status = 'publish';
            $now = time();
            if (!empty($link_date) && $now < strtotime($link_date)) {
                $link_status = 'scheduled';
            }
            if (!empty($expire_date) && $now > strtotime($expire_date)) {
                $link_status = 'draft';
            }
            // keywords
            $keywords = get_post_meta($thirstylink->ID, '_ta_autolink_keyword_list', true);
            $limit = get_post_meta($thirstylink->ID, '_ta_autolink_keyword_limit', true);
            $response[] = [
                'link_title' => $thirstylink->post_title,
                'link_slug' => $thirstylink->post_name,
                'link_date' => $link_date ? $link_date : "",
                'link_date_gmt' => $link_date ? $link_date : "",
                'link_status'   => $link_status,
                'short_url' => trim(\BetterLinks\Helper::force_relative_url(get_the_permalink($thirstylink->ID)), '/'),
                'link_author' => $thirstylink->post_author,
                'link_date' => $thirstylink->post_date,
                'link_date_gmt' => $thirstylink->post_date_gmt,
                'nofollow'  => ($nofollow == 'yes' ? 1 : 0),
                'sponsored'  => $betterlinks_links['sponsored'],
                'track_me'  => $betterlinks_links['track_me'],
                'redirect_type'  => $redirect_type,
                'param_forwarding' => ($param_forwarding == 'yes' ? 1 : 0),
                'target_url' => get_post_meta($thirstylink->ID, '_ta_destination_url', true),
                'link_modified' => $thirstylink->post_modified,
                'link_modified_gmt' => $thirstylink->post_modified_gmt,
                'terms'  => $term,
                'expire'  => json_encode($expire),
                'dynamic_redirect'  => json_encode($dynamic_redirect),
                'keywords'  => $keywords,
                'limit'     => $limit
            ];
        }
        return $response;
    }

    public static function get_prettylinks_links_count()
    {
        global $wpdb;
        $links = $wpdb->get_var("SELECT COUNT(id) FROM {$wpdb->prefix}prli_links");
        return $links;
    }
    public static function get_prettylinks_clicks_count()
    {
        global $wpdb;
        $clicks = $wpdb->get_var("SELECT COUNT(id) FROM {$wpdb->prefix}prli_clicks");
        return $clicks;
    }

    public static function get_link_meta($link_id, $meta_key)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'betterlinkmeta';
        if (empty($link_id) || empty($meta_key)) {
            return false;
        }
        $query = $wpdb->prepare("SELECT meta_value FROM $table WHERE meta_key = %s AND link_id = %d", $meta_key, $link_id);
        $results = $wpdb->get_results($query);
        if (!empty($results)) {
            return json_decode(current($results)->meta_value);
        }
        return;
    }

    public static function add_link_meta($link_id, $meta_key, $meta_value)
    {
        global $wpdb;
        $meta_key   = wp_unslash($meta_key);
        $meta_value = wp_unslash($meta_value);
        if (isset($meta_value["keywords"])) {
            $meta_value["keywords"] = preg_replace('/\’|\'|\‘/', "'", $meta_value["keywords"]);
        }
        $meta_value = \BetterLinks\Helper::maybe_json($meta_value);
        if (empty($link_id) || empty($meta_key)) {
            return false;
        }
        $result = $wpdb->insert(
            $wpdb->prefix . 'betterlinkmeta',
            array(
                'link_id'    => $link_id,
                'meta_key'   => $meta_key,
                'meta_value' => $meta_value,
            )
        );
        if (!$result) {
            return false;
        }
        return (int) $wpdb->insert_id;
    }
    public static function update_link_meta($link_id, $meta_key, $meta_value, $old_keywords = false, $old_link_id = false)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'betterlinkmeta';
        $link_id = absint($link_id);
        $meta_key   = wp_unslash($meta_key);
        $meta_value = wp_unslash($meta_value);
        if (isset($meta_value["keywords"])) {
            $meta_value["keywords"] = preg_replace('/\’|\'|\‘/', "'", $meta_value["keywords"]);
        }
        $meta_value = \BetterLinks\Helper::maybe_json($meta_value);
        if (empty($link_id) || empty($meta_key)) {
            return false;
        }
        $result = false;
        if ($old_keywords && $old_link_id) {
            $keywordPattern = wp_slash('%"keywords":' . wp_json_encode(wp_unslash($old_keywords)) . ',"link_id":%');
            $result = $wpdb->query($wpdb->prepare(
                "UPDATE $table
                SET meta_value = %s, link_id = %d
                WHERE link_id = %d AND meta_key=%s AND meta_value LIKE %s LIMIT 1",
                $meta_value,
                $link_id,
                $old_link_id,
                $meta_key,
                $keywordPattern
            ));
        } else {
            $result = $wpdb->query($wpdb->prepare(
                "UPDATE $table
                SET meta_value = %s
                WHERE link_id = %d AND meta_key=%s",
                $meta_value,
                $link_id,
                $meta_key
            ));
        }
        return !!$result;
    }

    public static function delete_link_meta($link_id, $meta_key, $meta_value = '', $keywords = false)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'betterlinkmeta';
        if (empty($link_id) || empty($meta_key)) {
            return false;
        }
        $query = $wpdb->prepare("SELECT link_id FROM $table WHERE meta_key = %s AND link_id = %d", $meta_key, $link_id);
        if (!empty($keywords)) {
            $keywordPattern = wp_slash('%"keywords":' . wp_json_encode(wp_unslash($keywords)) . ',"link_id":%');
            $query = $wpdb->prepare(
                "SELECT meta_id FROM $table WHERE meta_key = %s AND link_id = %d AND meta_value LIKE %s LIMIT 1",
                $meta_key,
                $link_id,
                $keywordPattern
            );
        }
        if (!empty($meta_value)) {
            $query .= $wpdb->prepare(' AND meta_value = %s', $meta_value);
        }
        $meta_ids = $wpdb->get_col($query);
        if (!count($meta_ids)) {
            return false;
        }
        $query = "DELETE FROM $table WHERE meta_id IN( " . implode(',', $meta_ids) . ' )';
        $count = $wpdb->query($query);
        return !!$count;
    }

    public static function get_keywords()
    {
        global $wpdb;
        $results = $wpdb->get_results(
            $wpdb->prepare("SELECT meta_value FROM {$wpdb->prefix}betterlinkmeta WHERE meta_key=%s ORDER BY meta_id DESC", 'keywords'),
            ARRAY_A
        );
        $results = array_column($results, 'meta_value');
        return $results;
    }

    public static function get_links_by_exclude_keywords()
    {
        global $wpdb;
        $results = $wpdb->get_results(
            // following query commented and written new one because we should get all the links in autolink
            // "SELECT betterlinks.ID, betterlinks.link_title, betterlinks.short_url FROM {$wpdb->prefix}betterlinks betterlinks WHERE NOT EXISTS (SELECT betterlinkmeta.link_id FROM {$wpdb->prefix}betterlinkmeta betterlinkmeta WHERE betterlinks.ID = betterlinkmeta.link_id)",
            "SELECT betterlinks.ID, betterlinks.link_title, betterlinks.short_url FROM {$wpdb->prefix}betterlinks betterlinks",
            ARRAY_A
        );
        return $results;
    }
}
