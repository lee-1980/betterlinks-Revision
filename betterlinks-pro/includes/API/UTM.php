<?php
namespace BetterLinksPro\API;;

use BetterLinksPro\Traits\ArgumentSchema;

class UTM extends \WP_REST_Controller
{
    use ArgumentSchema;
    /**
	 * Initialize hooks and option name
	 */
	public function __construct()
	{
        $this->namespace = BETTERLINKS_PRO_PLUGIN_SLUG . '/v1';
		$this->rest_base = 'utm';
    }
    
    /**
	 * Register the routes for the objects of the controller.
	 */
	public function register_routes()
	{
		register_rest_route($this->namespace,
        '/' . $this->rest_base, [
			[
				'methods' => \WP_REST_Server::READABLE,
				'callback' => [$this, 'get_items'],
				'permission_callback' => [$this, 'permissions_check'],
				'args' => $this->get_utm_schema(),
			],
		]);

		register_rest_route($this->namespace,
        '/' . $this->rest_base, [
			[
				'methods' => \WP_REST_Server::CREATABLE,
				'callback' => [$this, 'create_item'],
				'permission_callback' => [$this, 'permissions_check'],
				'args' => $this->get_utm_schema(),
			],
		]);

		register_rest_route(
			$this->namespace,
        '/' . $this->rest_base . '(?P<id>[\d]+)',
			array(
				'args'   => array(
					'id' => array(
						'description' => __( 'Unique identifier for the object.' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => [$this, 'permissions_check'],
					'args'                => $this->get_utm_schema(),
				),
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_item' ),
					'permission_callback' => [$this, 'permissions_check'],
					'args'                => $this->get_utm_schema(),
				),
				array(
					'methods'             => \WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_item' ),
					'permission_callback' => [$this, 'permissions_check'],
					'args'                => array(
						'force' => array(
							'type'        => 'boolean',
							'default'     => false,
							'description' => __( 'Whether to bypass Trash and force deletion.' ),
						),
					),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
    }

    /**
	 * Get UTM
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Request
	 */
	public function get_items($request)
	{
		$templates = unserialize(get_option(BETTERLINKS_PRO_UTM_OPTION_NAME ));
		
		return new \WP_REST_Response(
			$templates,
			200
		);
	}

	public function get_item($request) 
	{
		return new \WP_REST_Response(
			[],
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
		$templates = unserialize(get_option(BETTERLINKS_PRO_UTM_OPTION_NAME));
		if(!is_array($templates)){
			$templates = [];
		}
		array_push($templates, $request);
		update_option(BETTERLINKS_PRO_UTM_OPTION_NAME, serialize($templates));
		return new \WP_REST_Response(
			$templates,
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
				'success' => true,
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
			[],
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
		return current_user_can('manage_options');
	}
}