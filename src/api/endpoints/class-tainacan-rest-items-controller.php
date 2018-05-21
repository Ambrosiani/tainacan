<?php

namespace Tainacan\API\EndPoints;

use \Tainacan\API\REST_Controller;
use Tainacan\Repositories;
use Tainacan\Entities;

/**
 * Represents the Items REST Controller
 * @uses Tainacan\Repositories\
 * @uses Tainacan\Entities\
*/
class REST_Items_Controller extends REST_Controller {
	private $items_repository;
	private $item;
	private $item_metadata;
	private $collections_repository;

	/**
	 * REST_Items_Controller constructor.
	 * Define the namespace, rest base and instantiate your attributes.
	 */
	public function __construct() {
		$this->rest_base = 'items';
		parent::__construct();
		add_action('init', array(&$this, 'init_objects'), 11);
	}
	
	/**
	 * Initialize objects after post_type register
	 */
	public function init_objects() {
		$this->items_repository = Repositories\Items::get_instance();
		$this->item = new Entities\Item();
		$this->item_metadata = Repositories\Item_Metadata::get_instance();
		$this->collections_repository = Repositories\Collections::get_instance();
	}

	/**
	 * Register items routes, and their endpoints
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace, '/collection/(?P<collection_id>[\d]+)/' . $this->rest_base,
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array($this, 'get_items'),
					'permission_callback' => array($this, 'get_items_permissions_check'),
					'args'                => $this->get_collection_params(),
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array($this, 'create_item'),
					'permission_callback' => array($this, 'create_item_permissions_check'),
					'args'                => $this->get_endpoint_args_for_item_schema(\WP_REST_Server::CREATABLE),
				),
			)
		);
		register_rest_route(
			$this->namespace, '/' . $this->rest_base . '/(?P<item_id>[\d]+)',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array($this, 'get_item'),
					'permission_callback' => array($this, 'get_item_permissions_check'),
					'args'                => $this->get_endpoint_args_for_item_schema(\WP_REST_Server::READABLE),
				),
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array($this, 'update_item'),
					'permission_callback' => array($this, 'update_item_permissions_check'),
					'args'                => $this->get_endpoint_args_for_item_schema(\WP_REST_Server::EDITABLE),
				),
				array(
					'methods'             => \WP_REST_Server::DELETABLE,
					'callback'            => array($this, 'delete_item'),
					'permission_callback' => array($this, 'delete_item_permissions_check'),
					'args'                => array(
						'permanently' => array(
							'description' => __('To delete permanently, you can pass \'permanently\' as true. By default this will only trash collection'),
							'default'     => 'false'
						),
					)
				),
			)
		);
	}

	/**
	 * @param $item_object
	 * @param $item_array
	 *
	 * @param array $args
	 *
	 * @return mixed
	 */
	private function add_metadata_to_item($item_object, $item_array, $args = []){
		$item_metadata = $item_object->get_fields($args);

		foreach($item_metadata as $index => $me){
			$field               = $me->get_field();
			$slug                = $field->get_slug();
			$item_metadata_array = $me->_toArray();

			$item_array['metadata'][ $slug ]['name']            = $field->get_name();
			$item_array['metadata'][ $slug ]['value']           = $item_metadata_array['value'];
			$item_array['metadata'][ $slug ]['value_as_html']   = $item_metadata_array['value_as_html'];
			$item_array['metadata'][ $slug ]['value_as_string'] = $item_metadata_array['value_as_string'];
			if($field->get_field_type_object()->get_primitive_type() === 'date') {
				$item_array['metadata'][ $slug ]['date_i18n'] = $item_metadata_array['date_i18n'];
			}
			$item_array['metadata'][ $slug ]['multiple']        = $field->get_multiple();
		}

		return $item_array;
	}

	/**
	 * @param mixed $item
	 * @param \WP_REST_Request $request
	 *
	 * @return mixed|string|void|\WP_Error|\WP_REST_Response
	 */
	public function prepare_item_for_response( $item, $request ) {
		if(!empty($item)){

			if(!isset($request['fetch_only'])) {
				$item_arr = $item->_toArray();

				if ( $request['context'] === 'edit' ) {
					$item_arr['current_user_can_edit'] = $item->can_edit();
				}

				$img_size = 'large';

				if($request['doc_img_size']){
					$img_size = $request['doc_img_size'];
				}

				$item_arr['document_as_html'] = $item->get_document_html($img_size);

				return $this->add_metadata_to_item( $item, $item_arr );
			}

			$attributes_to_filter = $request['fetch_only'];

			# Always returns id
			if(is_array($attributes_to_filter)) {
				$attributes_to_filter[] = 'id';
			} else {
				$attributes_to_filter = array($attributes_to_filter, 'id');
			}

			$item_arr = $this->filter_object_by_attributes($item, $attributes_to_filter);

			if(is_array($attributes_to_filter) && array_key_exists('meta', $attributes_to_filter)){

				$args = array('post__in' => $attributes_to_filter['meta']);

				$item_arr = $this->add_metadata_to_item($item, $item_arr, $args);
			}

			return $item_arr;
		}

		return $item;
	}

	/**
	 * @param \WP_REST_Request $request
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function get_item( $request ) {
		$item_id = $request['item_id'];

		$item = $this->items_repository->fetch($item_id);

		$response = $this->prepare_item_for_response($item, $request);

		return new \WP_REST_Response(apply_filters('tainacan-rest-response', $response, $request), 200);
	}

	/**
	 * @param \WP_REST_Request $request
	 *
	 * @return \WP_Error|\WP_REST_Response
	 * @throws \Exception
	 */
	public function get_items( $request ) {
		$args = $this->prepare_filters($request);

		$collection_id = $request['collection_id'];
		$items = $this->items_repository->fetch($args, $collection_id, 'WP_Query');

		$response = [];
		if ($items->have_posts()) {
			while ( $items->have_posts() ) {
				$items->the_post();

				$item = new Entities\Item($items->post);

				$prepared_item = $this->prepare_item_for_response($item, $request);

				array_push($response, $prepared_item);
			}

			wp_reset_postdata();
		}

		$total_items  = $items->found_posts;
		$max_pages = ceil($total_items / (int) $items->query_vars['posts_per_page']);

		$rest_response = new \WP_REST_Response($response, 200);

		$rest_response->header('X-WP-Total', (int) $total_items);
		$rest_response->header('X-WP-TotalPages', (int) $max_pages);

		return $rest_response;
	}

	/**
	 * @param \WP_REST_Request $request
	 *
	 * @return bool|\WP_Error
	 */
	public function get_item_permissions_check( $request ) {
		$item = $this->items_repository->fetch($request['item_id']);

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
	 * @return bool|\WP_Error
	 */
	public function get_items_permissions_check( $request ) {
		$collection = $this->collections_repository->fetch($request['collection_id']);

		if(($collection instanceof Entities\Collection)) {
			if('edit' === $request['context'] && !$collection->can_read()) {
				return false;
			}

			return true;
		}

		return false;
	}

	/**
	 * @param \WP_REST_Request $request
	 *
	 * @return object|Entities\Item|\WP_Error
	 * @throws \Exception
	 */
	public function prepare_item_for_database( $request ) {

		$item_as_array = $request[0];

		foreach ($item_as_array as $key => $value){
			$set_ = 'set_' . $key;
			$this->item->$set_($value);
		}

		$collection = $this->collections_repository->fetch($request[1]);

		$this->item->set_collection($collection);

		return $this->item;
	}

	/**
	 * @param \WP_REST_Request $request
	 *
	 * @return \WP_Error|\WP_REST_Response
	 * @throws \Exception
	 */
	public function create_item( $request ) {
		$collection_id = $request['collection_id'];
		$item          = json_decode($request->get_body(), true);

		if(empty($item)){
			return new \WP_REST_Response([
				'error_message' => __('Body can not be empty.', 'tainacan'),
				'item'          => $item
			], 400);
		}

		try {
			$this->prepare_item_for_database( [ $item, $collection_id ] );
		} catch (\Exception $exception){
			return new \WP_REST_Response($exception->getMessage(), 400);
		}

		if($this->item->validate()) {
			$item = $this->items_repository->insert($this->item );

			return new \WP_REST_Response($this->prepare_item_for_response($item, $request), 201 );
		}


		return new \WP_REST_Response([
			'error_message' => __('One or more values are invalid.', 'tainacan'),
			'errors'        => $this->item->get_errors(),
			'item'          => $this->prepare_item_for_response($this->item, $request)
		], 400);
	}

	/**
	 * @param \WP_REST_Request $request
	 *
	 * @return bool|\WP_Error
	 * @throws \Exception
	 */
	public function create_item_permissions_check( $request ) {
		$collection = $this->collections_repository->fetch($request['collection_id']);

		if ($collection instanceof Entities\Collection) {
            return current_user_can($collection->get_items_capabilities()->edit_posts);
        }

        return false;
	}

	/**
	 * @param \WP_REST_Request $request
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function delete_item( $request ) {
		$item_id     = $request['item_id'];
		$permanently = $request['permanently'];

		$args = [$item_id, $permanently];

		$item = $this->items_repository->delete($args);

		$prepared_item = $this->prepare_item_for_response($item, $request);

		return new \WP_REST_Response($prepared_item, 200);
	}

	/**
	 * @param \WP_REST_Request $request
	 *
	 * @return bool|\WP_Error
	 * @throws \Exception
	 */
	public function delete_item_permissions_check( $request ) {
		$item = $this->items_repository->fetch($request['item_id']);

		if ($item instanceof Entities\Item) {
			return $item->can_delete();
		}

		return false;
	}

	/**
	 * @param \WP_REST_Request $request
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function update_item( $request ) {
		$item_id = $request['item_id'];

		$body = json_decode($request->get_body(), true);

		if(!empty($body)){
			$attributes = [];

			foreach ($body as $att => $value){
				$attributes[$att] = $value;
			}

			$item = $this->items_repository->fetch($item_id);

			if($item){
				$prepared_item = $this->prepare_item_for_updating($item, $attributes);

				if($prepared_item->validate()){
					$updated_item = $this->items_repository->update($prepared_item);
					
					do_action('tainacan-api-item-updated', $updated_item, $attributes);
					
					return new \WP_REST_Response($this->prepare_item_for_response($updated_item, $request), 200);
				}

				return new \WP_REST_Response([
					'error_message' => __('One or more values are invalid.', 'tainacan'),
					'errors'        => $prepared_item->get_errors(),
					'item'          => $this->prepare_item_for_response($prepared_item, $request)
				], 400);
			}

			return new \WP_REST_Response([
				'error_message' => __('An item with this ID was not found', 'tainacan' ),
				'item_id'       => $item_id
			], 400);
		}

		return new \WP_REST_Response([
			'error_message' => __('The body could not be empty', 'tainacan'),
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
		$item = $this->items_repository->fetch($request['item_id']);

		if ($item instanceof Entities\Item) {
			return $item->can_edit();
		}

		return false;
	}


	/**
	 * @param string $method
	 *
	 * @return array|mixed
	 */
	public function get_endpoint_args_for_item_schema( $method = null ){
		$endpoint_args = [];

		if($method === \WP_REST_Server::READABLE) {
			$endpoint_args['fetch_only'] = array(
				'type'        => 'string/array',
				'description' => __( 'Fetch only specific attribute. The specifics attributes are the same in schema.' ),
			);

			$endpoint_args['context'] = array(
				'type'    => 'string',
				'default' => 'view',
				'items'   => array( 'view, edit' )
			);
		} elseif ($method === \WP_REST_Server::CREATABLE || $method === \WP_REST_Server::EDITABLE) {
			$map = $this->items_repository->get_map();

			foreach ($map as $mapped => $value){
				$set_ = 'set_'. $mapped;

				// Show only args that has a method set
				if( !method_exists($this->item, "$set_") ){
					unset($map[$mapped]);
				}
			}

			$endpoint_args = $map;
		}

		return $endpoint_args;
	}

	/**
	 * @param null $object_name
	 *
	 * @return array|void
	 */
	public function get_collection_params($object_name = null) {
		$query_params['context']['default'] = 'view';

		array_merge($query_params, parent::get_collection_params('item'));

		$query_params['title'] = array(
			'description' => __('Limits the result set to items with a specific title'),
			'type'        => 'string',
		);

		$query_params = array_merge($query_params, parent::get_meta_queries_params());

		return $query_params;
	}
}

?>