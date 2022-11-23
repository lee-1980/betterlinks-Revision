<?php
namespace BetterLinksPro\Admin;

use DynamicOOOS\PayPalHttp\Serializer\Json;

class Ajax
{
    use \BetterLinksPro\Traits\BrokenLinks;
    use \BetterLinksPro\Traits\Keywords;
    public static function init()
    {
        $self = new self();
        add_action('wp_ajax_betterlinkspro/admin/get_role_management', [$self, 'get_role_management']);
        add_action('wp_ajax_betterlinkspro/admin/role_management', [$self, 'role_management']);
        add_action('wp_ajax_betterlinkspro/admin/get_external_analytics', [$self, 'get_external_analytics']);
        add_action('wp_ajax_betterlinkspro/admin/external_analytics', [$self, 'external_analytics']);
        add_action('wp_ajax_betterlinkspro/admin/get_links', [$self, 'get_links']);
        add_action('wp_ajax_betterlinkspro/admin/get_broken_links_data', [$self, 'get_broken_links_data']);
        add_action('wp_ajax_betterlinkspro/admin/run_instant_broken_link_checker', [$self, 'run_instant_broken_link_checker']);
        add_action('wp_ajax_betterlinkspro/admin/delete_broken_link_checker_logs', [$self, 'delete_broken_link_checker_logs']);
        add_action('wp_ajax_betterlinkspro/admin/run_broken_links_checker', [$self, 'run_broken_links_checker']);
        add_action('wp_ajax_betterlinkspro/admin/run_single_broken_link_checker', [$self, 'run_single_broken_link_checker']);
        add_action('wp_ajax_betterlinkspro/admin/update_broken_link', [$self, 'update_broken_link']);
        add_action('wp_ajax_betterlinkspro/admin/remove_broken_link', [$self, 'remove_broken_link']);
        add_action('wp_ajax_betterlinkspro/admin/remove_multi_broken_link', [$self, 'remove_multi_broken_link']);
        add_action('wp_ajax_betterlinkspro/admin/get_broken_link_settings', [$self, 'get_broken_link_settings']);
        add_action('wp_ajax_betterlinkspro/admin/save_broken_link_settings', [$self, 'save_broken_link_settings']);
        add_action('wp_ajax_betterlinkspro/admin/get_split_test_analytics', [$self, 'get_split_test_analytics']);
        add_action('wp_ajax_betterlinkspro/admin/get_reporting_settings', [$self, 'get_reporting_settings']);
        add_action('wp_ajax_betterlinkspro/admin/saved_reporting_settings', [$self, 'saved_reporting_settings']);
        add_action('wp_ajax_betterlinkspro/admin/test_report_mail', [$self, 'test_report_mail']);
        // filter
        add_filter('betterlinks/admin/current_user_can_edit_settings', [$self, 'current_user_can_edit_settings']);
        add_filter('betterlinkspro/admin/current_user_can_edit_settings', [$self, 'current_user_can_edit_settings']);
        // keywords
        add_action('wp_ajax_betterlinks/admin/get_links_by_exclude_keywords', [$self, 'get_links_by_exclude_keywords']);
        add_action('wp_ajax_betterlinks/admin/get_keyword_saved_link', [$self, 'get_keyword_saved_link']);
        // API Fallbck Ajax
        add_action('wp_ajax_betterlinks/admin/create_keyword', [$self, 'create_keyword']);
        add_action('wp_ajax_betterlinks/admin/update_keyword', [$self, 'update_keyword']);
        add_action('wp_ajax_betterlinks/admin/delete_keyword', [$self, 'delete_keyword']);
    }
    public function get_role_management()
    {
        check_ajax_referer('betterlinkspro_admin_nonce', 'security');
        if (apply_filters('betterlinkspro/admin/current_user_can_edit_settings', current_user_can('manage_options'))) {
            $data = get_option(BETTERLINKS_PRO_ROLE_PERMISSON_OPTION_NAME, '{}');
            wp_send_json_success(json_decode($data, true));
            wp_die();
        }
        wp_die();
    }
    public function role_management()
    {
        check_ajax_referer('betterlinkspro_admin_nonce', 'security');
        if (apply_filters('betterlinkspro/admin/current_user_can_edit_settings', current_user_can('manage_options'))) {
            $viewlinks = (isset($_POST['viewlinks']) ? explode(',', sanitize_text_field($_POST['viewlinks'])) : []);
            $writelinks = (isset($_POST['writelinks']) ? explode(',', sanitize_text_field($_POST['writelinks'])) : []);
            $editlinks = (isset($_POST['editlinks']) ? explode(',', sanitize_text_field($_POST['editlinks'])) : []);
            $checkanalytics = (isset($_POST['checkanalytics']) ? explode(',', sanitize_text_field($_POST['checkanalytics'])) : []);
            $editsettings = (isset($_POST['editsettings']) ? explode(',', sanitize_text_field($_POST['editsettings'])) : []);
            $editFavorite = (isset($_POST['editFavorite']) ? explode(',', sanitize_text_field($_POST['editFavorite'])) : []);
            $update = update_option(BETTERLINKS_PRO_ROLE_PERMISSON_OPTION_NAME, json_encode(array(
                'viewlinks' => $viewlinks,
                'writelinks' => $writelinks,
                'editlinks' => $editlinks,
                'checkanalytics' => $checkanalytics,
                'editsettings' => $editsettings,
                'editFavorite' => $editFavorite,
            )));
            wp_send_json_success($update);
            wp_die();
        }
        wp_die();
    }

    public function get_external_analytics()
    {
        check_ajax_referer('betterlinkspro_admin_nonce', 'security');
        if (apply_filters('betterlinkspro/admin/current_user_can_edit_settings', current_user_can('manage_options'))) {
            $data = get_option(BETTERLINKS_PRO_EXTERNAL_ANALYTICS_OPTION_NAME, []);
            if(is_string($data)){
                $data = json_decode($data, true);
            }
            wp_send_json_success($data);
            wp_die();
        }
        wp_die();
    }

    public function external_analytics()
    {
        check_ajax_referer('betterlinkspro_admin_nonce', 'security');
        if (apply_filters('betterlinkspro/admin/current_user_can_edit_settings', current_user_can('manage_options'))) {
            $is_enable_ga = filter_var((isset($_POST['is_enable_ga']) ? sanitize_text_field($_POST['is_enable_ga']) : false), FILTER_VALIDATE_BOOLEAN);
            $is_enable_pixel = filter_var((isset($_POST['is_enable_pixel']) ? sanitize_text_field($_POST['is_enable_pixel']) : false), FILTER_VALIDATE_BOOLEAN);
            $ga_tracking_code = (isset($_POST['ga_tracking_code']) ? sanitize_text_field($_POST['ga_tracking_code']) : '');
            $pixel_id = (isset($_POST['pixel_id']) ? sanitize_text_field($_POST['pixel_id']) : '');
            $pixel_access_token = (isset($_POST['pixel_access_token']) ? sanitize_text_field($_POST['pixel_access_token']) : '');
            $analytic_data = array(
                'is_enable_ga' => $is_enable_ga,
                'ga_tracking_code' => $ga_tracking_code,
                'is_enable_pixel' => $is_enable_pixel,
                'pixel_id' => $pixel_id,
                'pixel_access_token' => $pixel_access_token,
            );
            $update = update_option(BETTERLINKS_PRO_EXTERNAL_ANALYTICS_OPTION_NAME, $analytic_data);
            if(defined('BETTERLINKS_EXISTS_LINKS_JSON') && BETTERLINKS_EXISTS_LINKS_JSON){
                $formattedArray = \BetterLinks\Helper::get_links_for_json();
                file_put_contents(BETTERLINKS_UPLOAD_DIR_PATH . '/links.json', json_encode($formattedArray));
            }
            wp_send_json_success($update);
        }
        wp_die();
    }
    public function get_links()
    {
        check_ajax_referer('betterlinkspro_admin_nonce', 'security');
        if (! apply_filters('betterlinks/api/links_get_items_permissions_check', current_user_can('manage_options'))) {
            wp_die();
        }
        global $wpdb;
        $ID = (isset($_POST['ID']) ? sanitize_text_field($_POST['ID']) : '');
        if ($ID) {
            $results = $wpdb->get_results("SELECT ID, link_title FROM {$wpdb->prefix}betterlinks WHERE ID={$ID}", OBJECT);
        } else {
            $results = $wpdb->get_results("SELECT ID, link_title FROM {$wpdb->prefix}betterlinks", OBJECT);
        }
        wp_send_json_success($results);
        wp_die();
    }
    public function get_broken_links_data()
    {
        check_ajax_referer('betterlinkspro_admin_nonce', 'security');
        if (! $this->current_user_can_edit_settings()) {
            wp_die();
        }
        global $wpdb;
        $results = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}betterlinks", OBJECT);
        $links = json_encode($results);
        $logs = get_option('betterlinkspro_broken_links_logs');
        wp_send_json_success(['links' =>  $links, 'logs' => $logs]);
        wp_die();
    }
    public function run_instant_broken_link_checker()
    {
        check_ajax_referer('betterlinkspro_admin_nonce', 'security');
        if (! $this->current_user_can_edit_settings()) {
            wp_die();
        }
        global $wpdb;
        $results = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}betterlinks", OBJECT);
        $links = json_encode($results);
        $logs = get_option('betterlinkspro_broken_links_logs');
        wp_send_json_success(['links' =>  $links, 'logs' => $logs]);
        wp_die();
    }
    public function delete_broken_link_checker_logs()
    {
        check_ajax_referer('betterlinkspro_admin_nonce', 'security');
        if (! $this->current_user_can_edit_settings()) {
            wp_die();
        }
        $result = delete_option('betterlinkspro_broken_links_logs');
        wp_send_json_success($result);
        wp_die();
    }
    public function run_broken_links_checker()
    {
        check_ajax_referer('betterlinkspro_admin_nonce', 'security');
        if (! $this->current_user_can_edit_settings()) {
            wp_die();
        }
        $data = \BetterLinks\Helper::fresh_ajax_request_data($_POST);
        if (is_array($data) && count($data)) {
            $this->check_broken_link($data);
        }
        wp_send_json_success(get_option('betterlinkspro_broken_links_logs'));
        wp_die();
    }
    public function run_single_broken_link_checker()
    {
        check_ajax_referer('betterlinkspro_admin_nonce', 'security');
        if (! $this->current_user_can_edit_settings()) {
            wp_die();
        }
        
        global $wpdb;
        $ID = (isset($_REQUEST['ID']) ? sanitize_text_field($_REQUEST['ID']) : 0);
        $result = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}betterlinks WHERE ID={$ID}", OBJECT);
        $result = current($result);
        $target_url = '';
        $target_url = \BetterLinksPro\Helper::addScheme($result->target_url);
        if (\BetterLinksPro\Helper::url_http_response_is_broken($target_url)) {
            wp_send_json_error(esc_html__('Oops… Your Link is Broken. ', 'betterlinks-pro'));
        } else {
            wp_send_json_success(esc_html__('Your Link is Active', 'betterlinks-pro'));
        }
        wp_die();
    }
    
    public function update_broken_link()
    {
        check_ajax_referer('betterlinkspro_admin_nonce', 'security');
        if (! $this->current_user_can_edit_settings()) {
            wp_die();
        }
        do_action('betterlinks/write_json_links');
        $ID = (isset($_REQUEST['ID']) ? sanitize_text_field($_REQUEST['ID']) : 0);
        $target_url = (isset($_REQUEST['target_url']) ? sanitize_text_field($_REQUEST['target_url']) : 0);
        $logs = json_decode(get_option('betterlinkspro_broken_links_logs'), true);
        $log_item =  $logs[$ID];
        $log_item['target_url'] = $target_url;
        $logs[$ID] = $log_item;
        update_option('betterlinkspro_broken_links_logs', json_encode($logs), false);
        wp_send_json_success(json_encode($logs));
        wp_die();
    }
    public function remove_broken_link()
    {
        check_ajax_referer('betterlinkspro_admin_nonce', 'security');
        if (! $this->current_user_can_edit_settings()) {
            wp_die();
        }
        $ID = (isset($_REQUEST['ID']) ? sanitize_text_field($_REQUEST['ID']) : 0);
        $results = json_decode(get_option('betterlinkspro_broken_links_logs'), true);
        unset($results[$ID]);
        update_option('betterlinkspro_broken_links_logs', json_encode($results), false);
        wp_send_json_success(json_encode($results));
        wp_die();
    }
    public function remove_multi_broken_link()
    {
        check_ajax_referer('betterlinkspro_admin_nonce', 'security');
        if (! $this->current_user_can_edit_settings()) {
            wp_die();
        }
        $logs = \BetterLinks\Helper::fresh_ajax_request_data($_REQUEST);
        $results = json_decode(get_option('betterlinkspro_broken_links_logs'), true);
        foreach ($logs as $ID) {
            unset($results[$ID]);
        }
        update_option('betterlinkspro_broken_links_logs', json_encode($results), false);
        wp_send_json_success(json_encode($results));
        wp_die();
    }


    public function current_user_can_edit_settings()
    {
        if (current_user_can('manage_options')) {
            return true;
        }
        $user = wp_get_current_user();
        $user_permission = json_decode(get_option(BETTERLINKS_PRO_ROLE_PERMISSON_OPTION_NAME), true);
        $current_user_roles = current($user->roles);
        if (
            in_array($current_user_roles, $user_permission['editsettings'])
        ) {
            return true;
        }
        return false;
    }

    public function get_broken_link_settings()
    {
        check_ajax_referer('betterlinkspro_admin_nonce', 'security');
        if (! $this->current_user_can_edit_settings()) {
            wp_die();
        }
        $restuls = get_option(BETTERLINKS_PRO_BROKEN_LINK_OPTION_NAME, '{}');
        wp_send_json_success($restuls);
        wp_die();
    }

    public function save_broken_link_settings()
    {
        check_ajax_referer('betterlinkspro_admin_nonce', 'security');
        if (! $this->current_user_can_edit_settings()) {
            wp_die();
        }
        $data = \BetterLinks\Helper::fresh_ajax_request_data($_POST);
        $data = \BetterLinks\Helper::sanitize_text_or_array_field($data);
        wp_clear_scheduled_hook('betterlinkspro/broken_link_checker');
        update_option(BETTERLINKS_PRO_BROKEN_LINK_OPTION_NAME, json_encode($data));
        wp_send_json_success(json_encode($data));
    }
    public function get_split_test_analytics()
    {
        check_ajax_referer('betterlinkspro_admin_nonce', 'security');
        if (!apply_filters('betterlinkspro/api/analytics_items_permissions_check', current_user_can('manage_options'))) {
            wp_die();
        }
        $ID = (isset($_REQUEST['ID']) ? $_REQUEST['ID'] : "");
        $results = \BetterLinksPro\Helper::get_split_test_analytics_data(['id' => $ID]);
        wp_send_json_success(
            $results,
            200
        );
        wp_die();
    }
    public function get_reporting_settings()
    {
        check_ajax_referer('betterlinkspro_admin_nonce', 'security');
        if (! $this->current_user_can_edit_settings()) {
            wp_die();
        }
        $restuls = get_option(BETTERLINKS_PRO_REPORTING_OPTION_NAME, '{}');
        wp_send_json_success(
            $restuls,
            200
        );
        wp_die();
    }
    public function saved_reporting_settings()
    {
        check_ajax_referer('betterlinkspro_admin_nonce', 'security');
        if (! $this->current_user_can_edit_settings()) {
            wp_die();
        }
        $data = \BetterLinks\Helper::fresh_ajax_request_data($_POST);
        $data = \BetterLinks\Helper::sanitize_text_or_array_field($data);
        update_option(BETTERLINKS_PRO_REPORTING_OPTION_NAME, json_encode($data));
        wp_send_json_success(
            json_encode($data),
            200
        );
        wp_die();
    }
    public function test_report_mail()
    {
        check_ajax_referer('betterlinkspro_admin_nonce', 'security');
        if (! $this->current_user_can_edit_settings()) {
            wp_die();
        }
        $email = $this->send_mail();
        wp_send_json_success(
            $email,
            200
        );
        wp_die();
    }
    public function get_links_by_exclude_keywords()
    {
        check_ajax_referer('betterlinks_admin_nonce', 'security');
        if (! current_user_can('manage_options')) {
            wp_die();
        }
        $results = \BetterLinks\Helper::get_links_by_exclude_keywords();
        wp_send_json_success($results);
        wp_die();
    }
    public function get_keyword_saved_link()
    {
        check_ajax_referer('betterlinks_admin_nonce', 'security');
        if (! current_user_can('manage_options')) {
            wp_die();
        }
        $link_id = (isset($_POST['link_id']) ? intval(sanitize_text_field($_POST['link_id'])) : 0);
        if ($link_id > 0) {
            $link = \BetterLinks\Helper::get_link_by_ID($link_id);
            wp_send_json_success($link);
            wp_die();
        }
        wp_send_json_error(null);
        wp_die();
    }
    public function create_keyword()
    {
        check_ajax_referer('betterlinks_admin_nonce', 'security');
        if (! apply_filters('betterlinks/api/keywords_create_item_permission_check', current_user_can('manage_options'))) {
            wp_die();
        }
        $data = \BetterLinks\Helper::fresh_ajax_request_data($_POST);
        $item = $this->prepare_keyword_item_for_db($data);
        $link_id = (isset($item['link_id']) ? $item['link_id'] : 0);
        \BetterLinks\Helper::add_link_meta($link_id, 'keywords', $item);
        wp_send_json_success(
            $item,
            200
        );
        wp_die();
    }
    public function update_keyword()
    {
        check_ajax_referer('betterlinks_admin_nonce', 'security');
        if (! apply_filters('betterlinks/api/keywords_update_item_permission_check', current_user_can('manage_options'))) {
            wp_die();
        }
        $data = \BetterLinks\Helper::fresh_ajax_request_data($_POST);
        $old_link_id = absint(isset($data['oldChooseLink']) ? $data['oldChooseLink'] : 0);
        $link_id = absint(isset($data['chooseLink']) ? $data['chooseLink'] : 0);
        $old_keywords = (isset($data['oldKeywords']) ? $data['oldKeywords'] : "");
        $item = $this->prepare_keyword_item_for_db($data);
        $is_update = \BetterLinks\Helper::update_link_meta($link_id, 'keywords', $item, $old_keywords, $old_link_id);
        if($is_update){
            wp_send_json_success(
                array_merge($item, [
                    'old_link_id' => $old_link_id,
                    'old_keywords' => $old_keywords,
                ]),
                200
            );
            wp_die();
        }else{
            wp_send_json_error( "updated link meta failed " );
        }
    }
    public function delete_keyword()
    {
        check_ajax_referer('betterlinks_admin_nonce', 'security');
        if (! apply_filters('betterlinks/api/keywords_delete_item_permission_check', current_user_can('manage_options'))) {
            wp_die();
        }
        $id = (isset($_POST['id']) ? intval(sanitize_text_field($_POST['id'])) : 0);
        $keywords = (isset($_POST['keywords']) ? (sanitize_text_field($_POST['keywords'])) : "");
        $is_delete = \BetterLinks\Helper::delete_link_meta($id, 'keywords', "", $keywords);
        wp_send_json_success(
            $is_delete,
            200
        );
        wp_die();
    }
}
