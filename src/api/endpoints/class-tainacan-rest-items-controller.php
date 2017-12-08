<?php

use Tainacan\Repositories;
use Tainacan\Entities;

/**
 * Represents the Items REST Controller
 * @uses Tainacan\Repositories\
 * @uses Tainacan\Entities\
*/
class TAINACAN_REST_Items_Controller extends WP_REST_Controller {
	private $items_repository;
	private $item;

	/**
	 * TAINACAN_REST_Items_Controller constructor.
	 * Define the namespace, rest base and instantiate your attributes.
	 */
	public function __construct() {
		$this->namespace = 'tainacan/v2';
		$this->rest_base = 'items';
		$this->items_repository = new Repositories\Items();
		$this->item = new Entities\Item();

		add_action('rest_api_init', array($this, 'register_routes'));
	}

	/**
	 * Register items routes, and their endpoints
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace, '/' . $this->rest_base . '/collection/(?P<collection_id>[\d]+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array($this, 'get_items'),
					//'permission_callback' => array($this, 'get_items_permissions_check'),
					'args'                => $this->get_collection_params(),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array($this, 'create_item'),
					'permission_callback' => array($this, 'create_item_permissions_check'),
					'args'                => $this->get_endpoint_args_for_item_schema(WP_REST_Server::CREATABLE),
				),
				'schema' => array($this, 'get_public_item_schema'),
		));
		register_rest_route(
			$this->namespace, '/' . $this->rest_base . '/(?P<item_id>[\d]+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array($this, 'get_item'),
					'permission_callback' => array($this, 'get_item_permissions_check'),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array($this, 'update_item'),
					'permission_callback' => array($this, 'update_item_permissions_check'),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array($this, 'delete_item'),
					'permission_callback' => array($this, 'delete_item_permissions_check'),
				),
		));
	}

	/**
	 * @param mixed $item
	 * @param WP_REST_Request $request
	 *
	 * @return mixed|string|void|WP_Error|WP_REST_Response
	 */
	public function prepare_item_for_response( $item, $request ) {
		if (!empty($item) && $item instanceof WP_Query){
			$items_as_array = [];

			if ($item->have_posts()) {
				while ( $item->have_posts() ) {
					$item->the_post();
					$ite = new Entities\Item($item->post);
					array_push($items_as_array, $ite->__toJSON());

				}
				wp_reset_postdata();
			}

			return json_encode($items_as_array);
		} elseif(!empty($item)){
			return $item->__toJSON();
		}

		return $item;
	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_item( $request ) {
		$item_id = $request['item_id'];

		$item = $this->items_repository->fetch($item_id);

		$response = $this->prepare_item_for_response($item, $request);

		return new WP_REST_Response($response, 200);
	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_items( $request ) {
		$collection_id = $request['collection_id'];
		$items = $this->items_repository->fetch([], $collection_id, 'WP_Query');

		$response = $this->prepare_item_for_response($items, $request);

		return new WP_REST_Response($response, 200);
	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return bool|WP_Error
	 */
	public function get_item_permissions_check( $request ) {
		return true;
	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return object|Entities\Item|WP_Error
	 */
	public function prepare_item_for_database( $request ) {
		$this->item->set_title($request[0]['title']);
		$this->item->set_description($request[0]['description']);

		$collection_wp_post = get_post($request[1]);
		$collection = new Entities\Collection($collection_wp_post);

		$this->item->set_collection($collection);

		return $this->item;
	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function create_item( $request ) {
		$collection_id = $request['collection_id'];
		$item = json_decode($request->get_body(), true);

		$prepared_item = $this->prepare_item_for_database([$item, $collection_id]);

		if($prepared_item->validate()){
			$item = $this->items_repository->insert($prepared_item);

			return new WP_REST_Response($item->__toJSON(), 201);
		}

		return new WP_REST_Response($prepared_item->get_errors(), 400);
	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return bool|WP_Error
	 */
	public function create_item_permissions_check( $request ) {
		return true;
	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function delete_item( $request ) {
		$item_id        = $request['item_id'];
		$is_permanently = json_decode($request->get_body(), true);

		$args = [$item_id, $is_permanently];

		$item = $this->items_repository->delete($args);

		$prepared_item = $this->prepare_item_for_response($item, $request);

		return new WP_REST_Response($prepared_item, 200);
	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return bool|WP_Error
	 */
	public function delete_item_permissions_check( $request ) {
		return true;
	}

}

?>