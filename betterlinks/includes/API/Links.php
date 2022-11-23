<?php

namespace BetterLinks\API;

use BetterLinks\Traits\ArgumentSchema;

class Links extends Controller
{
    use ArgumentSchema;
    use \BetterLinks\Traits\Links;
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
        $endpoint = '/links/';
        $favorite_endpoint = '/links_favorite/';
        register_rest_route($this->namespace, $endpoint, [
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [$this, 'get_items'],
                'permission_callback' => [$this, 'get_items_permissions_check'],
                'args' => $this->get_links_schema(),
            ],
        ]);

        register_rest_route($this->namespace, $endpoint, [
            [
                'methods' => \WP_REST_Server::CREATABLE,
                'callback' => [$this, 'create_item'],
                'permission_callback' => [$this, 'create_item_permissions_check'],
                'args' => $this->get_links_schema(),
            ],
        ]);

        register_rest_route(
            $this->namespace,
            $favorite_endpoint . '(?P<id>[\d]+)',
            array(
                'args'   => array(
                    'id' => array(
                        'description' => __('Unique identifier for the object.'),
                        'type'        => 'integer',
                    ),
                ),
                array(
                    'methods'             => \WP_REST_Server::EDITABLE,
                    'callback'            => array($this, 'update_item_favorite'),
                    'permission_callback' => [$this, 'update_favorite_permissions_check'],
                    'args'                => [
                        'ID' => [
                            'type' => 'integer',
                            'sanitize_callback' => 'absint',
                        ],
                        'favForAll' => [
                            'type' => 'boolean',
                        ],
                    ],
                ),
            )
        );

        register_rest_route(
            $this->namespace,
            $endpoint . '(?P<id>[\d]+)',
            array(
                'args'   => array(
                    'id' => array(
                        'description' => __('Unique identifier for the object.'),
                        'type'        => 'integer',
                    ),
                ),
                array(
                    'methods'             => \WP_REST_Server::READABLE,
                    'callback'            => array($this, 'get_item'),
                    'permission_callback' => [$this, 'permissions_check'],
                    'args'                => $this->get_links_schema(),
                ),
                array(
                    'methods'             => \WP_REST_Server::EDITABLE,
                    'callback'            => array($this, 'update_item'),
                    'permission_callback' => [$this, 'update_item_permissions_check'],
                    'args'                => $this->get_links_schema(),
                ),
                array(
                    'methods'             => \WP_REST_Server::DELETABLE,
                    'callback'            => array($this, 'delete_item'),
                    'permission_callback' => [$this, 'permissions_check'],
                    'args'                => array(
                        'force' => array(
                            'type'        => 'boolean',
                            'default'     => false,
                            'description' => __('Whether to bypass Trash and force deletion.'),
                        ),
                    ),
                ),
                // 'schema' => array( $this, 'get_public_item_schema' ),
            )
        );
    }

    /**
     * Get betterlinks
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|WP_REST_Request
     */
    public function get_items($request)
    {
        $cache_data = get_transient(BETTERLINKS_CACHE_LINKS_NAME);
        if (empty($cache_data) || !json_decode($cache_data, true)) {
            $results = \BetterLinks\Helper::get_prepare_all_links();
            set_transient(BETTERLINKS_CACHE_LINKS_NAME, json_encode($results));
            return new \WP_REST_Response(
                [
                    'success' => true,
                    'cache' => false,
                    'data' => $results,
                ],
                200
            );
        }
        return new \WP_REST_Response(
            [
                'success' => true,
                'cache' => true,
                'data' => json_decode($cache_data),
            ],
            200
        );
    }

    public function get_item($request)
    {
        return new \WP_REST_Response(
            [
                'success' => true,
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
    public function create_item($request)
    {
        $request = $request->get_params();
        delete_transient(BETTERLINKS_CACHE_LINKS_NAME);
        $args = $this->sanitize_links_data($request['params']);
        $results = $this->insert_link($args);
        if ($results) {
            return new \WP_REST_Response(
                [
                    'success' => true,
                    'data' => $results,
                ],
                200
            );
        }
        return new \WP_REST_Response(
            [
                'success' => false,
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
        $request = $request->get_params();
        delete_transient(BETTERLINKS_CACHE_LINKS_NAME);
        $args = $this->sanitize_links_data($request['params']);
        $this->update_link($args);
        return new \WP_REST_Response(
            [
                'success' => true,
                'data' => $request['params'],
            ],
            200
        );
    }

    /**
     * Update betterlinks favorite option
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|WP_REST_Request
     */
    public function update_item_favorite($request)
    {
        $request = $request->get_params();
        delete_transient(BETTERLINKS_CACHE_LINKS_NAME);
        if (isset($request["id"]) && isset($request["params"]) && isset($request["params"]["favForAll"])) {
            $params = [
                "ID" => absint($request["id"]),
                "data" => [
                    "favForAll" => $request["params"]["favForAll"]
                ]
            ];
            $result = $this->update_link_favorite($params);
            $response = [
                "ID" => $params["ID"],
                "favForAll" => $params["data"]["favForAll"],
            ];
            return new \WP_REST_Response(
                [
                    'success' => $result,
                    'data' => $response,
                ]
            );
        }
    }
    /**
     * Delete betterlinks
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|WP_REST_Request
     */
    public function delete_item($request)
    {
        $request = $request->get_params();
        delete_transient(BETTERLINKS_CACHE_LINKS_NAME);
        $this->delete_link($request);
        if (isset($request['id'])) {
            \BetterLinks\Helper::delete_link_meta($request['id'], 'keywords');
        }
        return new \WP_REST_Response(
            [
                'success' => true,
                'data' => [
                    'term_id' => $request['term_id'],
                    'ID' => $request['id'],
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
        return apply_filters('betterlinks/api/links_get_items_permissions_check', current_user_can('manage_options'));
    }

    /**
     * Check if a given request has access to update a setting
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|bool
     */
    public function create_item_permissions_check($request)
    {
        return apply_filters('betterlinks/api/links_create_item_permissions_check', current_user_can('manage_options'));
    }

    /**
     * Check if a given request has access to update a setting
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|bool
     */
    public function update_item_permissions_check($request)
    {
        return apply_filters('betterlinks/api/links_update_item_permissions_check', current_user_can('manage_options'));
    }

    /**
     * Check if a given request has access to update a setting
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|bool
     */
    public function update_favorite_permissions_check($request)
    {
        return apply_filters('betterlinks/api/links_update_favorite_permissions_check', current_user_can('manage_options'));
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
