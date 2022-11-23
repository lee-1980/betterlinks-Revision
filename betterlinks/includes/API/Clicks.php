<?php
namespace BetterLinks\API;

use BetterLinks\Traits\ArgumentSchema;

class Clicks extends Controller
{
    use \BetterLinks\Traits\Clicks;
    use ArgumentSchema;
    /**
     * Initialize hooks and option name
     */
    public function __construct()
    {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    /**
     * Register the routes for the objects of the controller.
     */
    public function register_routes()
    {
        $endpoint = '/clicks/';
        register_rest_route($this->namespace, $endpoint, [
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [$this, 'get_items'],
                'permission_callback' => [$this, 'get_items_permissions_check'],
                'args' => $this->get_clicks_schema(),
            ],
        ]);

        register_rest_route($this->namespace, $endpoint, [
            [
                'methods' => \WP_REST_Server::CREATABLE,
                'callback' => [$this, 'create_item'],
                'permission_callback' => [$this, 'permissions_check'],
                'args' => $this->get_clicks_schema(),
            ],
        ]);

        register_rest_route($this->namespace, $endpoint, [
            [
                'methods' => \WP_REST_Server::EDITABLE,
                'callback' => [$this, 'update_item'],
                'permission_callback' => [$this, 'permissions_check'],
                'args' => $this->get_clicks_schema(),
            ],
        ]);

        register_rest_route($this->namespace, $endpoint, [
            [
                'methods' => \WP_REST_Server::DELETABLE,
                'callback' => [$this, 'delete_item'],
                'permission_callback' => [$this, 'permissions_check'],
                'args' => $this->get_clicks_schema(),
            ],
        ]);

        do_action('betterlinks_register_clicks_routes', $this);
    }

    /**
     * Get betterlinks
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|WP_REST_Request
     */
    public function get_items($request)
    {
        $request = $request->get_params();
        $from = isset($request['from']) ? $request['from'] : date('Y-m-d', strtotime(' - 30 days'));
        $to = isset($request['to']) ? $request['to'] : date('Y-m-d');
        $results = $this->get_clicks_data($from, $to);
        return new \WP_REST_Response(
            [
                'success' => true,
                'data' => $results,
            ],
            200
        );
    }

    /**
     * Create OR Update betterlinks
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|WP_REST_Request
     */
    public function create_item($request)
    {
        return new \WP_REST_Response(
            [
                'success' => false,
                'data' => [],
            ],
            200
        );
    }

    /**
     * Create OR Update betterlinks
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|WP_REST_Request
     */
    public function update_item($request)
    {
        return new \WP_REST_Response(
            [
                'success' => false,
                'data' => [],
            ],
            200
        );
    }

    /**
     * Delete betterlinks
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|WP_REST_Request
     */
    public function delete_item($request)
    {
        return new \WP_REST_Response(
            [
                'success' => false,
                'data' => [],
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
    public function get_items_permissions_check($request)
    {
        return apply_filters('betterlinks/api/analytics_items_permissions_check', current_user_can('manage_options'));
    }
    /**
     * Check if a given request has access to update a setting
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|bool
     */
    public function permissions_check($request)
    {
        return current_user_can('manage_options');
    }
}
