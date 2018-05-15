<?php

namespace Tainacan\API\EndPoints;

use \Tainacan\API\REST_Controller;
use Tainacan\Entities;
use Tainacan\Repositories;

class REST_Item_Metadata_Controller extends REST_Controller {
	private $field;
	private $item_metadata_repository;
	private $item_repository;
	private $collection_repository;
	private $field_repository;

	public function __construct() {
		$this->rest_base = 'metadata';
		parent::__construct();
		add_action('init', array(&$this, 'init_objects'), 11);
	}

	/**
	 * Initialize objects after post_type register
	 */
	public function init_objects() {
		$this->field = new Entities\Field();
		$this->field_repository = Repositories\Fields::get_instance();
		$this->item_metadata_repository = Repositories\Item_Metadata::get_instance();
		$this->item_repository = Repositories\Items::get_instance();
		$this->collection_repository = Repositories\Collections::get_instance();
	}

	/**
	 * If POST on field/collection/<collection_id>, then
	 * a field will be created in matched collection and all your item will receive this field
	 *
	 * If POST on field/item/<item_id>, then a value will be added in a field and field passed
	 * id body of requisition
	 *
	 * Both of GETs return the field of matched objects
	 */
	public function register_routes() {
		register_rest_route($this->namespace, '/item/(?P<item_id>[\d]+)/' . $this->rest_base . '/(?P<metadata_id>[\d]+)',
			array(
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array($this, 'update_item'),
					'permission_callback' => array($this, 'update_item_permissions_check'),
					'args'                => $this->get_endpoint_args_for_item_schema(\WP_REST_Server::EDITABLE)
				)
			)
		);
		register_rest_route($this->namespace,  '/item/(?P<item_id>[\d]+)/'. $this->rest_base,
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array($this, 'get_items'),
					'permission_callback' => array($this, 'get_items_permissions_check'),
					'args'                => $this->get_collection_params(),
				)
			)
		);
		register_rest_route($this->namespace,  '/item/(?P<item_id>[\d]+)/'. $this->rest_base. '/(?P<metadata_id>[\d]+)',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array($this, 'get_item_field_value'),
					'permission_callback' => array($this, 'get_items_permissions_check'),
				)
			)
		);
	}

	/**
	 * @param \WP_REST_Request $request
	 *
	 * @return object|void|\WP_Error
	 */
	public function prepare_item_for_database( $request ) {
		$meta = json_decode($request[0]->get_body(), true);

		foreach ($meta as $key => $value){
			$set_ = 'set_' . $key;
			$this->field->$set_($value);
		}

		$collection = new Entities\Collection($request[1]);

		$this->field->set_collection($collection);
	}

	/**
	 * @param mixed $item
	 * @param \WP_REST_Request $request
	 *
	 * @return array|\WP_Error|\WP_REST_Response
	 */
	public function prepare_item_for_response( $item, $request ) {
		$item_arr = $item->__toArray();

		if($request['context'] === 'edit'){
			$item_arr['current_user_can_edit'] = $item->can_edit();
		}

		return $item_arr;
	}

	/**
	 * @param \WP_REST_Request $request
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function get_items( $request ) {
		$item_id = $request['item_id'];

		$item = $this->item_repository->fetch($item_id);

		$items_metadata = $item->get_fields();

		$prepared_item = [];

		foreach ($items_metadata as $item_metadata){
			$index = array_push($prepared_item, $this->prepare_item_for_response($item_metadata, $request));
			$prepared_item[$index-1]['field']['field_type_object'] = $this->prepare_item_for_response( $item_metadata->get_field()->get_field_type_object(), $request);
		}

		return new \WP_REST_Response(apply_filters('tainacan-rest-response', $prepared_item, $request), 200);
	}
	
	/**
	 * @param \WP_REST_Request $request
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function get_item_field_value( $request ) {
		$item_id = $request['item_id'];
		$field_id = $request['metadata_id'];
		
		$item = $this->item_repository->fetch($item_id);
		
		$items_metadata = $item->get_fields();
		
		$prepared_item = '';
		
		foreach ($items_metadata as $item_metadata){
			$field = $item_metadata->get_field();
			if($field->get_id() == $field_id) {
				$prepared_item = $this->prepare_item_for_response($item_metadata, $request);
				$prepared_item['field']['field_type_object'] = $this->prepare_item_for_response( $field->get_field_type_object(), $request);
			}
		}
		
		return new \WP_REST_Response(apply_filters('tainacan-rest-response', $prepared_item, $request), 200);
	}

	/**
	 * @param \WP_REST_Request $request
	 *
	 * @return bool|\WP_Error
	 * @throws \Exception
	 */
	public function get_items_permissions_check( $request ) {
		$item = $this->item_repository->fetch($request['item_id']);

		if(($item instanceof Entities\Item)) {
			if('edit' === $request['context'] && !$item->can_read()) {
				return false;
			}

			return true;
		}

		return false;
	}

	/**
	 * @param \WP_REST_Request $request
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function update_item( $request ) {
		$body = json_decode( $request->get_body(), true );

		if($body) {

			$item_id  = $request['item_id'];
			$field_id = $request['metadata_id'];
			$value    = $body['values'];

			$item  = $this->item_repository->fetch( $item_id );
			$field = $this->field_repository->fetch( $field_id );

			$item_metadata = new Entities\Item_Metadata_Entity( $item, $field );

			if($item_metadata->is_multiple()) {
				$item_metadata->set_value( $value );
			} elseif(is_array($value)) {
				$item_metadata->set_value(implode(' ', $value));
			} else{
				$item_metadata->set_value($value);
			}

			if ( $item_metadata->validate() ) {
				if($item->can_edit()) {
					$field_updated = $this->item_metadata_repository->update( $item_metadata );

					$prepared_item =  $this->prepare_item_for_response($field_updated, $request);
					$prepared_item['field']['field_type_object'] = $this->prepare_item_for_response($field_updated->get_field()->get_field_type_object(), $request);
				}
				elseif($field->get_accept_suggestion()) {
					$log = $this->item_metadata_repository->suggest( $item_metadata );
					$prepared_item = $log->__toArray();  
				}
				else {
					return new \WP_REST_Response( [
						'error_message' => __( 'The metadata does not accept suggestions', 'tainacan' ),
					], 400 );
				}

				return new \WP_REST_Response( $prepared_item, 200 );
			} else {
				return new \WP_REST_Response( [
					'error_message' => __( 'Please verify, invalid value(s)', 'tainacan' ),
					'errors'        => $item_metadata->get_errors(),
					'item_metadata' => $this->prepare_item_for_response($item_metadata, $request),
				], 400 );
			}
		}

		return new \WP_REST_Response([
			'error_message' => 'The body could not be empty',
			'body'          => $body
		], 400);
	}

	/**
	 * @param \WP_REST_Request $request
	 *
	 * @return bool|\WP_Error
	 * @throws \Exception
	 */
	public function update_item_permissions_check( $request ) {
		if (isset($request['item_id'])) {
			$item = $this->item_repository->fetch($request['item_id']);

			if ($item instanceof Entities\Item) {
				if($item->can_edit()) {
					return $item->can_edit();
				}
				else {
					$field_id = $request['metadata_id'];
					$field = $this->field_repository->fetch( $field_id );
					return 'publish' === $field->get_status() && $field->get_accept_suggestion();
				}
			}

		}

		return false;
	}

	/**
	 * @param string $method
	 *
	 * @return array|mixed
	 */
	public function get_endpoint_args_for_item_schema( $method = null ) {
		$endpoint_args = [];

		if ($method === \WP_REST_Server::EDITABLE) {
			$endpoint_args['values'] = [
				'type'        => 'array/string/object/integer',
				'items'       => [
					'type' => 'array/string/object/integer'
				],
				'description' => __('The value(s) of item metadata')
			];
		}

		return $endpoint_args;
	}

	/**
	 *
	 * Return the queries supported when getting a collection of objects
	 *
	 * @param null $object_name
	 *
	 * @return array
	 */
	public function get_collection_params($object_name = null) {
		$query_params['context']['default'] = 'view';

		return $query_params;
	}
}

?>
