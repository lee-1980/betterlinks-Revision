<?php
namespace BetterLinks\API;

use BetterLinks\Traits\ArgumentSchema;
use BetterLinks\Helper;

class Terms extends Controller
{
    use \BetterLinks\Traits\Terms;
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
        $endpoint = '/terms/';
        register_rest_route($this->namespace, $endpoint, [
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [$this, 'get_items'],
                'permission_callback' => [$this, 'get_items_permissions_check'],
                'args' => $this->get_terms_schema(),
            ],
        ]);

        register_rest_route($this->namespace, $endpoint, [
            [
                'methods' => \WP_REST_Server::CREATABLE,
                'callback' => [$this, 'create_item'],
                'permission_callback' => [$this, 'permissions_check'],
                'args' => $this->get_terms_schema(),
            ],
        ]);

        register_rest_route($this->namespace, $endpoint, [
            [
                'methods' => \WP_REST_Server::EDITABLE,
                'callback' => [$this, 'update_item'],
                'permission_callback' => [$this, 'permissions_check'],
                'args' => $this->get_terms_schema(),
            ],
        ]);

        register_rest_route($this->namespace, $endpoint, [
            [
                'methods' => \WP_REST_Server::DELETABLE,
                'callback' => [$this, 'delete_item'],
                'permission_callback' => [$this, 'permissions_check'],
                'args' => $this->get_terms_schema(),
            ],
        ]);
    }

    /**
     * Get betterlinks
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|WP_REST_Request
     */
    public function get_items($request)
    {
        $query_params = $request->get_query_params();
        $results = $this->get_all_terms_data($query_params);

        if( count($results) <= 0 ) {
            $_term = [
                'term_name' => 'Uncategorized',
                'term_slug' => 'uncategorized',
                'term_type' => 'category',
            ];
            $id = Helper::insert_term($_term);
            if( $id ) {
                $_term['ID'] = $id;
                $_term['term_order'] = 0;
            }
            $results = [ $_term ];
        }

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
        delete_transient(BETTERLINKS_CACHE_LINKS_NAME);
        $request = $request->get_params();
        $args = [
            'ID'        => (isset($request['params']['ID']) ? absint(sanitize_text_field($request['params']['ID'])) : 0),
            'term_name' => (isset($request['params']['term_name']) ? sanitize_text_field($request['params']['term_name']) : ""),
            'term_slug' => (isset($request['params']['term_slug']) ? sanitize_text_field($request['params']['term_slug']) : ""),
            'term_type' => (isset($request['params']['term_type']) ? sanitize_text_field($request['params']['term_type']) : ""),
        ];
        $results = $this->create_term($args);
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
    public function update_item($request)
    {
        delete_transient(BETTERLINKS_CACHE_LINKS_NAME);
        $request = $request->get_params();
        $args = [
            'cat_id'        => (isset($request['params']['ID']) ? absint(sanitize_text_field($request['params']['ID'])) : 0),
            'cat_name' => (isset($request['params']['term_name']) ? sanitize_text_field($request['params']['term_name']) : ""),
            'cat_slug' => (isset($request['params']['term_slug']) ? sanitize_text_field($request['params']['term_slug']) : ""),
        ];
        $this->update_term($args);
        return new \WP_REST_Response(
            [
                'success' => is_bool($request['params']['ID']),
                'data' => $request['params'],
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
        delete_transient(BETTERLINKS_CACHE_LINKS_NAME);
        $request = $request->get_params();
        $this->delete_term($request);
        return new \WP_REST_Response(
            [
                'success' => true,
                'data' => [
                    'cat_id' => $request['cat_id'],
                ],
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
        return apply_filters('betterlinks/api/terms_get_items_permissions_check', current_user_can('manage_options'));
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
