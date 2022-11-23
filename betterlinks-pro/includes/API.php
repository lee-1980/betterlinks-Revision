<?php
namespace BetterLinksPro;

use BetterLinksPro;

class API
{
    public $user;
    public $user_permission;
    public function __construct()
    {
        $this->user = wp_get_current_user();
        $this->user_permission = json_decode(get_option(BETTERLINKS_PRO_ROLE_PERMISSON_OPTION_NAME), true);
        $this->dispatch_hook();
    }

    public function dispatch_hook()
    {
        add_action('rest_api_init', array($this, 'create_initial_rest_routes'), 99);
        new API\Clicks();
        add_filter('betterlinks/links_schema', array($this, 'links_schema'));
        add_filter('betterlinks/api/params', array($this, 'links_params'));
        add_filter('betterlinks/api/links_get_items_permissions_check', array($this, 'get_permissions_check'));
        add_filter('betterlinks/api/settings_get_items_permissions_check', array($this, 'get_permissions_check'));
        add_filter('betterlinks/api/terms_get_items_permissions_check', array($this, 'get_permissions_check'));
        add_filter('betterlinks/api/settings_update_items_permissions_check', array($this, 'get_permissions_check'));
        add_filter('betterlinks/api/links_create_item_permissions_check', array($this, 'links_create_item_permissions_check'));
        add_filter('betterlinks/api/links_update_item_permissions_check', array($this, 'links_update_item_permissions_check'));
        add_filter('betterlinks/api/links_update_favorite_permissions_check', array($this, 'links_update_favorite_permissions_check'));
        add_filter('betterlinks/api/analytics_items_permissions_check', array($this, 'analytics_items_permissions_check'));
        add_filter('betterlinkspro/api/analytics_items_permissions_check', array($this, 'analytics_items_permissions_check'));
    }
    public function create_initial_rest_routes()
    {
        // UTM
        $controller = new API\UTM;
        $controller->register_routes();
        $keywords = new API\Keywords();
        $keywords->register_routes();
    }
    public function links_schema($schema)
    {
        $schema['expire'] = [
            'type' => 'object',
            'properties' => array(
                'status'  => array(
                    'type' => 'integer',
                    'sanitize_callback' => 'absint'
                ),
                'type' => array(
                    'type' 				=> 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'clicks' => array(
                    'type' 				=> 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'date' => array(
                    'type' => 'string',
                    'format' => 'date-time',
                ),
                'redirect_status'  => array(
                    'type' => 'integer',
                    'sanitize_callback' => 'absint'
                ),
                'redirect_url' => array(
                    'type' 				=> 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            ),
        ];

        $schema['dynamic_redirect'] = [
            'type' => 'object',
            'properties' => array(
                'type'  => array(
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'value'  => array(
                    'type' => 'array',
                    'items' => array(
                        'type' => 'object',
                        'properties' => array(
                            'link' => array(
                                'type' => 'string',
                                'sanitize_callback' => 'sanitize_text_field'
                            ),
                            'weight' => array(
                                'type' => 'string',
                                'sanitize_callback' => 'sanitize_text_field'
                            )
                        )
                    )
                ),
                'extra'  => array(
                    'type' => 'object',
                    'properties' => array(
                        'split_test'  => array(
                            'type' => 'integer',
                            'sanitize_callback' => 'absint'
                        ),
                        'goal_link'  => array(
                            'type' => 'string',
                            'sanitize_callback' => 'sanitize_text_field'
                        ),
                    )
                ),
            ),
        ];


        return $schema;
    }
    public function links_params($params)
    {
        $params['expire'] = (isset($params['expire']) ? json_encode($params['expire']) : '{}');
        $params['dynamic_redirect'] = (isset($params['dynamic_redirect']) ? json_encode($params['dynamic_redirect']) : '{}');
        return $params;
    }
    public function get_permissions_check()
    {
        if (current_user_can('manage_options')) {
            return true;
        }
        if (!is_array($this->user_permission)) {
            return false;
        }
        $current_user_roles = current($this->user->roles);
        if (
            in_array($current_user_roles, $this->user_permission['writelinks']) ||
            in_array($current_user_roles, $this->user_permission['editlinks']) ||
            in_array($current_user_roles, $this->user_permission['viewlinks']) ||
            in_array($current_user_roles, $this->user_permission['editsettings']) ||
            (isset($this->user_permission['editFavorite']) && in_array($current_user_roles, $this->user_permission['editFavorite']))
        ) {
            return true;
        }
        return false;
    }
    public function links_create_item_permissions_check()
    {
        if (current_user_can('manage_options')) {
            return true;
        }
        if (!is_array($this->user_permission)) {
            return false;
        }
        $current_user_roles = current($this->user->roles);
        if (in_array($current_user_roles, $this->user_permission['writelinks'])) {
            return true;
        }
        return false;
    }
    public function links_update_item_permissions_check()
    {
        if (current_user_can('manage_options')) {
            return true;
        }
        if (!is_array($this->user_permission)) {
            return false;
        }
        $current_user_roles = current($this->user->roles);
        if (in_array($current_user_roles, $this->user_permission['editlinks'])) {
            return true;
        }
        return false;
    }
    public function links_update_favorite_permissions_check()
    {
        if (current_user_can('manage_options')) {
            return true;
        }
        if (!is_array($this->user_permission) || !isset($this->user_permission['editFavorite'])) {
            return false;
        }
        $current_user_roles = current($this->user->roles);
        if (in_array($current_user_roles, $this->user_permission['editFavorite'])) {
            return true;
        }
        return false;
    }
    public function analytics_items_permissions_check()
    {
        if (current_user_can('manage_options')) {
            return true;
        }
        if (!is_array($this->user_permission)) {
            return false;
        }
        $current_user_roles = current($this->user->roles);
        if (in_array($current_user_roles, $this->user_permission['checkanalytics'])) {
            return true;
        }
        return false;
    }
}
