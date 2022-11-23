<?php

namespace BetterLinksPro\API;

use BetterLinks\API\Controller;
use BetterLinksPro\Traits\ArgumentSchema;

class Keywords extends Controller
{
    use ArgumentSchema;
    use \BetterLinksPro\Traits\Keywords;

    /**
     * Register the routes for the objects of the controller.
     */
    public function register_routes()
    {
        $endpoint = '/keywords/';
        register_rest_route($this->namespace, $endpoint, [
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [$this, 'get_items'],
                'permission_callback' => [$this, 'get_items_permissions_check'],
                'args' => $this->get_keywords_schema(),
            ],
        ]);

        register_rest_route($this->namespace, $endpoint, [
            [
                'methods' => \WP_REST_Server::CREATABLE,
                'callback' => [$this, 'create_item'],
                'permission_callback' => [$this, 'create_item_permissions_check'],
                'args' => $this->get_keywords_schema(),
            ],
        ]);

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
                    'args' => $this->get_keywords_schema(),
                ),
                array(
                    'methods'             => \WP_REST_Server::EDITABLE,
                    'callback'            => array($this, 'update_item'),
                    'permission_callback' => [$this, 'update_item_permissions_check'],
                    'args' => $this->get_keywords_schema(),
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
     * Get keywords
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|WP_REST_Request
     */
    public function get_items($request)
    {
        $results = \BetterLinks\Helper::get_keywords();
        return new \WP_REST_Response(
            [
                'success' => true,
                'data' => $results,
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
     * Create OR Update keywords meta
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|WP_REST_Request
     */
    public function create_item($request)
    {
        $request = $request->get_params();
        $params = $request['params'];
        $data = $this->prepare_keyword_item_for_db($params);
        $link_id = (isset($data['link_id']) ? $data['link_id'] : 0);
        $is_insert = \BetterLinks\Helper::add_link_meta($link_id, 'keywords', $data);
        if ($is_insert) {
            return new \WP_REST_Response(
                [
                    'success' => true,
                    'data' => $data,
                ],
                200
            );
        }
        return new \WP_REST_Response(
            [
                'success' => false,
                'data' => [],
            ],
            200
        );
    }

    /**
     * Create OR Update keywords
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|WP_REST_Request
     */
    public function update_item($request)
    {
        $request = $request->get_params();
        $params = $request['params'];
        $old_link_id = absint(isset($params['oldChooseLink']) ? $params['oldChooseLink'] : 0);
        $old_keywords = (isset($params['oldKeywords']) ? $params['oldKeywords'] : "");
        $data = $this->prepare_keyword_item_for_db($params);
        $link_id = (isset($data['link_id']) ? $data['link_id'] : 0);
        $is_update = false;
        $is_update = \BetterLinks\Helper::update_link_meta($link_id, 'keywords', $data, $old_keywords, $old_link_id);
        if ($is_update) {
            return new \WP_REST_Response(
                [
                    'success' => true,
                    'data' => array_merge($data, [
                        'old_link_id' => $old_link_id,
                        'old_keywords' => $old_keywords
                    ]),
                ],
                200
            );
        }
        return new \WP_REST_Response(
            [
                'success' => false,
                'data' => [],
            ],
            200
        );
    }
    /**
     * Delete keywords
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|WP_REST_Request
     */
    public function delete_item($request)
    {
        $request = $request->get_params();
        $id = (isset($request['id']) ? intval(sanitize_text_field($request['id'])) : 0);
        $is_delete = \BetterLinks\Helper::delete_link_meta($id, 'keywords');
        return new \WP_REST_Response(
            [
                'success' => $is_delete,
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
        return apply_filters('betterlinks/api/keywords_get_items_permissions_check', current_user_can('manage_options'));
    }

    /**
     * Check if a given request has access to update a setting
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|bool
     */
    public function create_item_permissions_check($request)
    {
        return apply_filters('betterlinks/api/keywords_create_item_permissions_check', current_user_can('manage_options'));
    }

    /**
     * Check if a given request has access to update a setting
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|bool
     */
    public function update_item_permissions_check($request)
    {
        return apply_filters('betterlinks/api/keywords_update_item_permissions_check', current_user_can('manage_options'));
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
