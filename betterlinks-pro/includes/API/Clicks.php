<?php
namespace BetterLinksPro\API;

use BetterLinks\Traits\ArgumentSchema;

class Clicks
{
    use ArgumentSchema;
    private $namespace;
    public function __construct()
    {
        $this->namespace = BETTERLINKS_PLUGIN_SLUG . '/v1';
        add_action('betterlinks_register_clicks_routes', array($this, 'register_routes'));
    }

    public function register_routes()
    {
        register_rest_route(
            $this->namespace,
            '/clicks/individual/(?P<id>[\d]+)',
            array(
                'args'   => array(
                    'id' => array(
                        'description' => __('Unique identifier for the object.'),
                        'type'        => 'integer',
                    ),
                ),
                array(
                    'methods'             => \WP_REST_Server::READABLE,
                    'callback'            => array( $this, 'get_item' ),
                    'permission_callback' => [$this, 'permissions_check'],
                    'args'                => $this->get_clicks_schema(),
                ),
            )
        );
        register_rest_route(
            $this->namespace,
            '/clicks/splittest/(?P<id>[\d]+)',
            array(
                'args'   => array(
                    'id' => array(
                        'description' => __('Unique identifier for the object.'),
                        'type'        => 'integer',
                    ),
                ),
                array(
                    'methods'             => \WP_REST_Server::READABLE,
                    'callback'            => array( $this, 'get_split_test_item' ),
                    'permission_callback' => [$this, 'permissions_check'],
                    'args'                => $this->get_clicks_schema(),
                ),
            )
        );
    }

    public function get_item($request)
    {
        $request = $request->get_params();
        $results = \BetterLinksPro\Helper::get_individual_link_analytics($request);
        return new \WP_REST_Response(
            [
                'success' => true,
                'data' => $results,
            ],
            200
        );
    }
    
    public function get_split_test_item($request)
    {
        $request = $request->get_params();
        $results = \BetterLinksPro\Helper::get_split_test_analytics_data($request);
        return new \WP_REST_Response(
            [
                'success' => true,
                'data' => $results,
            ],
            200
        );
    }

    /**
     * Check if a given request has access to update a setting
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|bool
     */
    public function permissions_check($request)
    {
        return apply_filters('betterlinkspro/api/analytics_items_permissions_check', current_user_can('manage_options'));
    }
}
