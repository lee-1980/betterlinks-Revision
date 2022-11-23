<?php

namespace BetterLinks\Admin;

use BetterLinks\Cron;

class Ajax
{
    use \BetterLinks\Traits\Links;
    use \BetterLinks\Traits\Terms;
    use \BetterLinks\Traits\Clicks;
    use \BetterLinks\Traits\ArgumentSchema;
    public function __construct()
    {
        // link & clicks
        add_action('wp_ajax_betterlinks/admin/search_clicks_data', [$this, 'search_clicks_data']);
        add_action('wp_ajax_betterlinks/admin/links_reorder', [$this, 'links_reorder']);
        add_action('wp_ajax_betterlinks/admin/links_move_reorder', [$this, 'links_move_reorder']);
        add_action('wp_ajax_betterlinks/admin/get_links_by_short_url', [$this, 'get_links_by_short_url']);
        add_action('wp_ajax_betterlinks/admin/write_json_links', [$this, 'write_json_links']);
        add_action('wp_ajax_betterlinks/admin/write_json_clicks', [$this, 'write_json_clicks']);
        add_action('wp_ajax_betterlinks/admin/analytics', [$this, 'analytics']);
        add_action('wp_ajax_betterlinks/admin/short_url_unique_checker', [$this, 'short_url_unique_checker']);
        add_action('wp_ajax_betterlinks/admin/cat_slug_unique_checker', [$this, 'cat_slug_unique_checker']);
        // prettylinks
        add_action('wp_ajax_betterlinks/admin/get_prettylinks_data', [$this, 'get_prettylinks_data']);
        add_action('wp_ajax_betterlinks/admin/run_prettylinks_migration', [$this, 'run_prettylinks_migration']);
        add_action('wp_ajax_betterlinks/admin/migration_prettylinks_notice_hide', [$this, 'migration_prettylinks_notice_hide']);
        add_action('wp_ajax_betterlinks/admin/deactive_prettylinks', [$this, 'deactive_prettylinks']);
        // simple 301
        add_action('wp_ajax_betterlinks/admin/get_simple301redirects_data', [$this, 'get_simple301redirects_data']);
        add_action('wp_ajax_betterlinks/admin/run_simple301redirects_migration', [$this, 'run_simple301redirects_migration']);
        add_action('wp_ajax_betterlinks/admin/migration_simple301redirects_notice_hide', [$this, 'migration_simple301redirects_notice_hide']);
        add_action('wp_ajax_betterlinks/admin/deactive_simple301redirects', [$this, 'deactive_simple301redirects']);
        // Thirsty affiliates
        add_action('wp_ajax_betterlinks/admin/get_thirstyaffiliates_data', [$this, 'get_thirstyaffiliates_data']);
        add_action('wp_ajax_betterlinks/admin/run_thirstyaffiliates_migration', [$this, 'run_thirstyaffiliates_migration']);
        add_action('wp_ajax_betterlinks/admin/deactive_thirstyaffiliates', [$this, 'deactive_thirstyaffiliates']);
        // API Fallbck Ajax
        add_action('wp_ajax_betterlinks/admin/get_all_links', [$this, 'get_all_links']);
        add_action('wp_ajax_betterlinks/admin/create_link', [$this, 'create_new_link']);
        add_action('wp_ajax_betterlinks/admin/update_link', [$this, 'update_existing_link']);
        add_action('wp_ajax_betterlinks/admin/handle_favorite', [$this, 'handle_links_favorite_option']);
        add_action('wp_ajax_betterlinks/admin/delete_link', [$this, 'delete_existing_link']);
        add_action('wp_ajax_betterlinks/admin/get_settings', [$this, 'get_settings']);
        add_action('wp_ajax_betterlinks/admin/update_settings', [$this, 'update_settings']);
        add_action('wp_ajax_betterlinks/admin/get_terms', [$this, 'get_terms']);
        add_action('wp_ajax_betterlinks/admin/create_new_term', [$this, 'create_new_term']);
        add_action('wp_ajax_betterlinks/admin/update_term', [$this, 'update_existing_term']);
        add_action('wp_ajax_betterlinks/admin/delete_term', [$this, 'delete_existing_term']);
        add_action('wp_ajax_betterlinks/admin/fetch_analytics', [$this, 'fetch_analytics']);
        add_action('wp_ajax_betterlinks/admin/get_all_keywords', [$this, 'get_all_keywords']);

        // post type, tags, categories
        add_action('wp_ajax_betterlinks/admin/get_post_types', [$this, 'get_post_types']);
        add_action('wp_ajax_betterlinks/admin/get_post_tags', [$this, 'get_post_tags']);
        add_action('wp_ajax_betterlinks/admin/get_post_categories', [$this, 'get_post_categories']);
    }

    public function get_prettylinks_data()
    {
        check_ajax_referer('betterlinks_admin_nonce', 'security');
        if (!current_user_can('manage_options')) {
            wp_die();
        }
        $links_count = \BetterLinks\Helper::get_prettylinks_links_count();
        $clicks_count = \BetterLinks\Helper::get_prettylinks_clicks_count();
        set_transient('betterlinks_migration_data_prettylinks', ['links_count' => $links_count, 'clicks_count' => $clicks_count], 60 * 5);
        wp_send_json_success(['links_count' => $links_count, 'clicks_count' => $clicks_count]);
    }

    public function run_prettylinks_migration()
    {
        check_ajax_referer('betterlinks_admin_nonce', 'security');
        if (!current_user_can('manage_options')) {
            wp_die();
        }
        // give betterlinks a lot of time to properly set the migration work for background
        if(function_exists("ini_set")){
            ini_set('max_execution_time', 300);
        }
        if(\BetterLinks\Helper::btl_get_option("btl_prettylink_migration_should_not_start_in_background")){
            // preventing multiple migration call to prevent duplicate datas from migrating
            wp_send_json_error(["duplicate_migration_detected__so_prevented_it_here" => true]);
        }
        \BetterLinks\Helper::btl_update_option("btl_prettylink_migration_should_not_start_in_background", true, true);
        global $wpdb;
        $query = "DELETE FROM {$wpdb->prefix}options WHERE option_name IN(
                'betterlinks_notice_ptl_migration_running_in_background',
                'btl_failed_migration_prettylinks_links',
                'btl_failed_migration_prettylinks_clicks',
                'btl_migration_prettylinks_current_successful_links_count',
                'btl_migration_prettylinks_current_successful_clicks_count'
        )";
        $wpdb->query($query);
        \BetterLinks\Helper::btl_update_option("btl_failed_migration_prettylinks_links", [], true);
        \BetterLinks\Helper::btl_update_option("btl_failed_migration_prettylinks_clicks", [], true);
        \BetterLinks\Helper::btl_update_option("btl_migration_prettylinks_current_successful_links_count", 0, true);
        \BetterLinks\Helper::btl_update_option("btl_migration_prettylinks_current_successful_clicks_count", 0, true);

        $type = isset($_POST['type']) ? strtolower(sanitize_text_field($_POST['type'])) : '';
        $total_links_clicks = get_transient("betterlinks_migration_data_prettylinks");
        $should_migrate_links = !(strpos($type, "links") === false);
        $should_migrate_clicks = !(strpos($type, "clicks") === false);

        $installer = new \BetterLinks\Installer();
        if( $should_migrate_links && !empty($total_links_clicks["links_count"]) ){
            $links_count = absint($total_links_clicks["links_count"]);
            $installer = \BetterLinks\Helper::run_migration_for_ptrl_links_in_background($installer, $links_count);
        }

        if( $should_migrate_clicks && !empty($total_links_clicks["clicks_count"]) ){
            $clicks_count = absint($total_links_clicks["clicks_count"]);
            $installer = \BetterLinks\Helper::run_migration_for_ptrl_clicks_in_background($installer, $clicks_count);
        }

        $installer->data( [ 'betterlinks_notice_ptl_migrate' ] )->save();
        $installer->dispatch();
        \BetterLinks\Helper::btl_update_option('betterlinks_notice_ptl_migration_running_in_background', true, true);
        wp_send_json_success(["btl_prettylinks_migration_running_in_background" => true]);
    }

    public function migration_prettylinks_notice_hide()
    {
        check_ajax_referer('betterlinks_admin_nonce', 'security');
        if (!current_user_can('manage_options')) {
            wp_die();
        }
        $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : '';
        if ($type == 'deactive') {
            update_option('betterlinks_hide_notice_ptl_deactive', true);
        } elseif ($type == 'migrate') {
            update_option('betterlinks_hide_notice_ptl_migrate', true);
        }
        wp_die();
    }
    public function deactive_prettylinks()
    {
        check_ajax_referer('betterlinks_admin_nonce', 'security');
        if (!current_user_can('manage_options')) {
            wp_die();
        }
        $deactivate = deactivate_plugins('pretty-link/pretty-link.php');
        wp_send_json_success($deactivate);
        wp_die();
    }
    public function write_json_links()
    {
        check_ajax_referer('betterlinks_admin_nonce', 'security');
        if (apply_filters('betterlinks/admin/current_user_can_edit_settings', current_user_can('manage_options'))) {
            $Cron = new Cron();
            $resutls = $Cron->write_json_links();
            wp_send_json_success($resutls);
            wp_die();
        }
        wp_die();
    }
    public function write_json_clicks()
    {
        check_ajax_referer('betterlinks_admin_nonce', 'security');
        if (apply_filters('betterlinks/admin/current_user_can_edit_settings', current_user_can('manage_options')) && !BETTERLINKS_EXISTS_CLICKS_JSON) {
            $emptyContent = '{}';
            $file_handle = @fopen(trailingslashit(BETTERLINKS_UPLOAD_DIR_PATH) . 'clicks.json', 'wb');
            if ($file_handle) {
                fwrite($file_handle, $emptyContent);
                fclose($file_handle);
            }
            wp_send_json_success(true);
            wp_die();
        }
        wp_send_json_error(false);
        wp_die();
    }
    public function analytics()
    {
        check_ajax_referer('betterlinks_admin_nonce', 'security');
        if (apply_filters('betterlinks/admin/current_user_can_edit_settings', current_user_can('manage_options'))) {
            $Cron = new Cron();
            $resutls = $Cron->analytics();
            wp_send_json_success($resutls);
            wp_die();
        }
        wp_die();
    }
    public function short_url_unique_checker()
    {
        check_ajax_referer('betterlinks_admin_nonce', 'security');
        if (apply_filters('betterlinks/admin/current_user_can_edit_settings', current_user_can('manage_options'))) {
            $ID = isset($_POST['ID']) ? sanitize_text_field($_POST['ID']) : '';
            $slug = isset($_POST['slug']) ? sanitize_text_field($_POST['slug']) : '';
            $alreadyExists = false;
            $resutls = [];
            if (!empty($slug)) {
                $resutls = \BetterLinks\Helper::get_link_by_short_url($slug);
                if (count($resutls) > 0) {
                    $alreadyExists = true;
                    $resutls = current($resutls);
                    if ($resutls['ID'] == $ID) {
                        $alreadyExists = false;
                    }
                }
            }
            wp_send_json_success($alreadyExists);
            wp_die();
        }
        wp_die();
    }
    public function cat_slug_unique_checker()
    {
        check_ajax_referer('betterlinks_admin_nonce', 'security');
        if (!current_user_can('manage_options')) {
            wp_die();
        }
        $ID = isset($_POST['ID']) ? sanitize_text_field($_POST['ID']) : '';
        $slug = isset($_POST['slug']) ? sanitize_text_field($_POST['slug']) : '';
        $alreadyExists = false;
        $resutls = [];
        if (!empty($slug)) {
            $resutls = \BetterLinks\Helper::get_term_by_slug($slug);
            if (count($resutls) > 0) {
                $alreadyExists = true;
                $resutls = current($resutls);
                if ($resutls['ID'] == $ID) {
                    $alreadyExists = false;
                }
            }
        }
        wp_send_json_success($alreadyExists);
        wp_die();
    }
    public function get_simple301redirects_data()
    {
        check_ajax_referer('betterlinks_admin_nonce', 'security');
        if (!current_user_can('manage_options')) {
            wp_die();
        }
        $links = get_option('301_redirects');
        wp_send_json_success($links);
        wp_die();
    }
    public function run_simple301redirects_migration()
    {
        check_ajax_referer('betterlinks_admin_nonce', 'security');
        if (!current_user_can('manage_options')) {
            wp_die();
        }
        try {
            $simple_301_redirects = get_option('301_redirects');
            $migrator = new \BetterLinks\Tools\Migration\S301ROneClick();
            $resutls = $migrator->run_importer(array_reverse($simple_301_redirects));
            do_action('betterlinks/admin/after_import_data');
            update_option('betterlinks_notice_s301r_migrate', true);
            wp_send_json_success($resutls);
            wp_die();
        } catch (\Throwable $th) {
            wp_send_json_error($th->getMessage());
            wp_die();
        }
    }
    public function migration_simple301redirects_notice_hide()
    {
        check_ajax_referer('betterlinks_admin_nonce', 'security');
        if (!current_user_can('manage_options')) {
            wp_die();
        }
        $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : '';
        if ($type == 'deactive') {
            update_option('betterlinks_hide_notice_s301r_deactive', true);
        } elseif ($type == 'migrate') {
            update_option('betterlinks_notice_s301r_migrate', true);
        }
        wp_die();
    }
    public function deactive_simple301redirects()
    {
        check_ajax_referer('betterlinks_admin_nonce', 'security');
        if (!current_user_can('manage_options')) {
            wp_die();
        }
        $deactivate = deactivate_plugins('simple-301-redirects/wp-simple-301-redirects.php');
        wp_send_json_success($deactivate);
        wp_die();
    }
    public function search_clicks_data()
    {
        check_ajax_referer('betterlinks_admin_nonce', 'security');
        if (!current_user_can('manage_options')) {
            wp_die();
        }
        $title = isset($_GET['title']) ? sanitize_text_field($_GET['title']) : '';
        $results = \BetterLinks\Helper::search_clicks_data($title);
        wp_send_json_success($results);
        wp_die();
    }
    public function links_reorder()
    {
        check_ajax_referer('betterlinks_admin_nonce', 'security');
        if (!current_user_can('manage_options')) {
            wp_die();
        }
        $links = (isset($_POST['links']) ? explode(',', sanitize_text_field($_POST['links'])) : []);
        if (count($links) > 0) {
            foreach ($links as $key => $value) {
                \BetterLinks\Helper::insert_link(['ID' => $value, 'link_order' =>  $key], true);
            }
        }
        wp_send_json_success([]);
        wp_die();
    }
    public function links_move_reorder()
    {
        check_ajax_referer('betterlinks_admin_nonce', 'security');
        if (!current_user_can('manage_options')) {
            wp_die();
        }
        $source = (isset($_POST['source']) ? explode(',', sanitize_text_field($_POST['source'])) : []);
        $destination = (isset($_POST['destination']) ? explode(',', sanitize_text_field($_POST['destination'])) : []);
        if (count($source) > 0) {
            foreach ($source as $key => $value) {
                \BetterLinks\Helper::insert_link(['ID' => $value, 'link_order' =>  $key], true);
            }
        }
        if (count($destination) > 0) {
            foreach ($destination as $key => $value) {
                \BetterLinks\Helper::insert_link(['ID' => $value, 'link_order' =>  $key], true);
            }
        }
        wp_send_json_success([]);
        wp_die();
    }

    public function get_thirstyaffiliates_data()
    {
        check_ajax_referer('betterlinks_admin_nonce', 'security');
        if (!current_user_can('manage_options')) {
            wp_die();
        }
        $response = \BetterLinks\Helper::get_thirstyaffiliates_links();
        wp_send_json_success($response);
        wp_die();
    }

    public function run_thirstyaffiliates_migration()
    {
        check_ajax_referer('betterlinks_admin_nonce', 'security');
        if (!current_user_can('manage_options')) {
            wp_die();
        }
        try {
            $links = \BetterLinks\Helper::get_thirstyaffiliates_links();
            $migrator = new \BetterLinks\Tools\Migration\TAOneClick();
            $resutls = $migrator->run_importer($links);
            do_action('betterlinks/admin/after_import_data');
            update_option('betterlinks_notice_ta_migrate', true);
            wp_send_json_success($resutls);
            wp_die();
        } catch (\Throwable $th) {
            wp_send_json_error($th->getMessage());
            wp_die();
        }
    }

    public function deactive_thirstyaffiliates()
    {
        check_ajax_referer('betterlinks_admin_nonce', 'security');
        if (!current_user_can('manage_options')) {
            wp_die();
        }
        $deactivate = deactivate_plugins('thirstyaffiliates/thirstyaffiliates.php');
        wp_send_json_success($deactivate);
        wp_die();
    }

    public function get_links_by_short_url()
    {
        check_ajax_referer('betterlinks_admin_nonce', 'security');
        if (!current_user_can('manage_options')) {
            wp_die();
        }
        $short_url = (isset($_POST['short_url']) ? sanitize_text_field($_POST['short_url']) : '');
        $results = \BetterLinks\Helper::get_link_by_short_url($short_url);
        wp_send_json_success(is_array($results) ? current($results) : false);
        wp_die();
    }

    public function get_all_links()
    {
        check_ajax_referer('betterlinks_admin_nonce', 'security');
        if (!apply_filters('betterlinks/api/links_get_items_permissions_check', current_user_can('manage_options'))) {
            wp_die();
        }
        $cache_data = get_transient(BETTERLINKS_CACHE_LINKS_NAME);
        if (empty($cache_data) || !json_decode($cache_data, true)) {
            $results = \BetterLinks\Helper::get_prepare_all_links();
            set_transient(BETTERLINKS_CACHE_LINKS_NAME, json_encode($results));
            wp_send_json_success(
                [
                    'success' => true,
                    'cache' => false,
                    'data' => $results,
                ],
                200
            );
            wp_die();
        }
        wp_send_json_success(
            [
                'success' => true,
                'cache' => true,
                'data' => json_decode($cache_data),
            ],
            200
        );
        wp_die();
    }
    public function create_new_link()
    {
        check_ajax_referer('betterlinks_admin_nonce', 'security');
        if (!apply_filters('betterlinks/api/links_create_item_permissions_check', current_user_can('manage_options'))) {
            wp_die();
        }
        delete_transient(BETTERLINKS_CACHE_LINKS_NAME);
        $args = $this->sanitize_links_data($_POST);
        $results = $this->insert_link($args);
        if ($results) {
            wp_send_json_success(
                $results,
                200
            );
            wp_die();
        }
        wp_send_json_error(
            $results,
            200
        );
        wp_die();
    }
    public function update_existing_link()
    {
        check_ajax_referer('betterlinks_admin_nonce', 'security');
        if (!apply_filters('betterlinks/api/links_update_item_permissions_check', current_user_can('manage_options'))) {
            wp_die();
        }
        delete_transient(BETTERLINKS_CACHE_LINKS_NAME);
        $args = $this->sanitize_links_data($_POST);
        $results = $this->update_link($args);
        if ($results) {
            wp_send_json_success(
                $args,
                200
            );
            wp_die();
        }
        wp_send_json_error(
            $args,
            200
        );
        wp_die();
    }
    public function handle_links_favorite_option()
    {
        if (isset($_POST["favForAll"]) && isset($_POST["ID"])) {
            check_ajax_referer('betterlinks_admin_nonce', 'security');
            if (!apply_filters('betterlinks/api/links_update_favorite_permissions_check', current_user_can('manage_options'))) {
                wp_die();
            }
            delete_transient(BETTERLINKS_CACHE_LINKS_NAME);
            $params = [
                "ID" => absint($_POST["ID"]),
                "data" => [
                    "favForAll" => $_POST["favForAll"] === 'true' ? true : false
                ]
            ];
            $result = $this->update_link_favorite($params);
            $response = [
                "ID" => $params["ID"],
                "favForAll" => $params["data"]["favForAll"],
            ];
            if ($result) {
                wp_send_json_success(
                    $response,
                    200
                );
                wp_die();
            }
            wp_send_json_error(
                $response,
                200
            );
            wp_die();
        }
    }
    public function delete_existing_link()
    {
        check_ajax_referer('betterlinks_admin_nonce', 'security');
        if (!current_user_can('manage_options')) {
            wp_die();
        }
        delete_transient(BETTERLINKS_CACHE_LINKS_NAME);
        $args  = [
            'ID' => ($_REQUEST['ID'] ? sanitize_text_field($_REQUEST['ID']) : ''),
            'short_url' => ($_REQUEST['short_url'] ? sanitize_text_field($_REQUEST['short_url']) : ''),
            'term_id' => ($_REQUEST['term_id'] ? sanitize_text_field($_REQUEST['term_id']) : ''),
        ];
        $this->delete_link($args);
        // the folowing commented because it shouldn't happen after deleting a link
        // if (!empty($args['ID'])) {
        //     \BetterLinks\Helper::delete_link_meta($args['ID'], 'keywords');
        // }
        wp_send_json_success(
            $args,
            200
        );
        wp_die();
    }
    public function get_settings()
    {
        check_ajax_referer('betterlinks_admin_nonce', 'security');
        if (!apply_filters('betterlinks/api/settings_get_items_permissions_check', current_user_can('manage_options'))) {
            wp_die();
        }
        $results = get_option(BETTERLINKS_LINKS_OPTION_NAME);
        if ($results) {
            wp_send_json_success(
                $results,
                200
            );
            wp_die();
        }
        wp_send_json_success(
            [
                'success' => false,
                'data' => '{}',
            ],
            200
        );
        wp_die();
    }
    public function update_settings()
    {
        check_ajax_referer('betterlinks_admin_nonce', 'security');
        if (!apply_filters('betterlinks/api/settings_update_items_permissions_check', current_user_can('manage_options'))) {
            wp_die();
        }
        $response = \BetterLinks\Helper::fresh_ajax_request_data($_POST);
        $response = \BetterLinks\Helper::sanitize_text_or_array_field($response);
        update_option(
            BETTERLINKS_AUTOLINK_OPTION_NAME,
            [
                "is_show_icon" => isset($response["is_autolink_icon"]) ? $response["is_autolink_icon"] : false,
                "is_autolink_in_heading" => isset($response["is_autolink_headings"]) ? $response["is_autolink_headings"] : false,
            ]
        );
        $response = json_encode($response);
        if ($response) {
            update_option(BETTERLINKS_LINKS_OPTION_NAME, $response);
        }
        // regenerate links for wildcards option update
        \BetterLinks\Helper::write_links_inside_json(); // it's better to write the links instantly here than scheduling/corning it
        wp_send_json_success(
            $response,
            200
        );
        wp_die();
    }
    public function get_terms()
    {
        check_ajax_referer('betterlinks_admin_nonce', 'security');
        if (!apply_filters('betterlinks/api/settings_get_items_permissions_check', current_user_can('manage_options'))) {
            wp_die();
        }
        $args = [];
        if (isset($_REQUEST['ID'])) {
            $args['ID'] = sanitize_text_field($_REQUEST['ID']);
        }
        if (isset($_REQUEST['term_type'])) {
            $args['term_type'] = sanitize_text_field($_REQUEST['term_type']);
        }

        $results = $this->get_all_terms_data($args);
        if ($results) {
            wp_send_json_success(
                $results,
                200
            );
            wp_die();
        }
        wp_send_json_error(
            [],
            200
        );
        wp_die();
    }
    public function create_new_term()
    {
        check_ajax_referer('betterlinks_admin_nonce', 'security');
        if (!current_user_can('manage_options')) {
            wp_die();
        }
        delete_transient(BETTERLINKS_CACHE_LINKS_NAME);
        $args = [
            'ID'        => (isset($_REQUEST['ID']) ? absint(sanitize_text_field($_REQUEST['ID'])) : 0),
            'term_name' => (isset($_REQUEST['term_name']) ? sanitize_text_field($_REQUEST['term_name']) : ""),
            'term_slug' => (isset($_REQUEST['term_slug']) ? sanitize_text_field($_REQUEST['term_slug']) : ""),
            'term_type' => (isset($_REQUEST['term_type']) ? sanitize_text_field($_REQUEST['term_type']) : ""),
        ];
        $results = $this->create_term($args);
        wp_send_json_success(
            $results,
            200
        );
        wp_die();
    }
    public function update_existing_term()
    {
        check_ajax_referer('betterlinks_admin_nonce', 'security');
        if (!current_user_can('manage_options')) {
            wp_die();
        }
        delete_transient(BETTERLINKS_CACHE_LINKS_NAME);
        $args = [
            'cat_id'        => (isset($_REQUEST['ID']) ? absint(sanitize_text_field($_REQUEST['ID'])) : 0),
            'cat_name' => (isset($_REQUEST['term_name']) ? sanitize_text_field($_REQUEST['term_name']) : ""),
            'cat_slug' => (isset($_REQUEST['term_slug']) ? sanitize_text_field($_REQUEST['term_slug']) : ""),
        ];
        $this->update_term($args);
        wp_send_json_success(
            [
                'ID' => $args['cat_id'],
                'term_name' => $args['cat_name'],
                'term_slug' => $args['cat_slug'],
            ],
            200
        );
        wp_die();
    }
    public function delete_existing_term()
    {
        check_ajax_referer('betterlinks_admin_nonce', 'security');
        if (!current_user_can('manage_options')) {
            wp_die();
        }
        delete_transient(BETTERLINKS_CACHE_LINKS_NAME);
        $args = [
            'cat_id'        => (isset($_REQUEST['cat_id']) ? absint(sanitize_text_field($_REQUEST['cat_id'])) : 0),
        ];
        $this->delete_term($args);
        wp_send_json_success(
            $args,
            200
        );
        wp_die();
    }
    public function fetch_analytics()
    {
        check_ajax_referer('betterlinks_admin_nonce', 'security');
        if (!apply_filters('betterlinks/api/analytics_items_permissions_check', current_user_can('manage_options'))) {
            wp_die();
        }
        $from = isset($_REQUEST['from']) ? sanitize_text_field($_REQUEST['from']) : date('Y-m-d', strtotime(' - 30 days'));
        $to = isset($_REQUEST['to']) ? sanitize_text_field($_REQUEST['to']) : date('Y-m-d');
        $ID = (isset($_REQUEST['ID']) ? sanitize_text_field($_REQUEST['ID']) : '');
        if (!empty($ID) && class_exists('BetterLinksPro')) {
            $results = \BetterLinksPro\Helper::get_individual_link_analytics(['id' => $ID, 'from' => $from, 'to' => $to]);
        } else {
            $results = $this->get_clicks_data($from, $to);
        }
        wp_send_json_success(
            $results,
            200
        );
        wp_die();
    }
    public function get_post_types()
    {
        $post_types = get_post_types(array('public' => true));
        wp_send_json_success(
            $post_types,
            200
        );
        wp_die();
    }
    public function get_post_tags()
    {
        $tags = get_tags(array('get' => 'all'));
        $tags = wp_list_pluck($tags, 'name', 'slug');
        wp_send_json_success(
            $tags,
            200
        );
        wp_die();
    }
    public function get_post_categories()
    {
        $categories = get_categories(array(
            'orderby' => 'name'
        ));
        $categories = wp_list_pluck($categories, 'name', 'slug');
        wp_send_json_success(
            $categories,
            200
        );
        wp_die();
    }
    public function get_all_keywords()
    {
        check_ajax_referer('betterlinks_admin_nonce', 'security');
        if (!apply_filters('betterlinks/api/keywords_get_items_permission_check', current_user_can('manage_options'))) {
            wp_die();
        }
        $results = \BetterLinks\Helper::get_keywords();
        wp_send_json_success(
            $results,
            200
        );
        wp_die();
    }
}
