<?php

namespace Tainacan\API\EndPoints;

use Tainacan\Repositories;
use Tainacan\Entities;
use \Tainacan\API\REST_Controller;

class REST_Facets_Controller extends REST_Controller {

	private $total_pages;
	private $total_items;

	private $collection;
	private $collection_repository;
	private $metadatum_repository;
	private $filter_repository;
	private $terms_repository;
	private $taxonomy_repository;
	private $items_repository;
	private $taxonomy;

	/**
	 * REST_Facets_Controller constructor.
	 */
	public function __construct() {
		$this->rest_base = 'facets';
		$this->total_pages = 0;
		$this->total_items = 0;
		parent::__construct();
        add_action('init', array(&$this, 'init_objects'), 11);
	}
	
	/**
	 * Initialize objects after post_type register
	 */
	public function init_objects() {
        $this->collection = new Entities\Collection();
        
		$this->collection_repository = Repositories\Collections::get_instance();
		$this->metadatum_repository = Repositories\Metadata::get_instance();
		$this->filter_repository = Repositories\Filters::get_instance();
		$this->terms_repository = Repositories\Terms::get_instance();
		$this->taxonomy_repository = Repositories\Taxonomies::get_instance();
		$this->items_repository = Repositories\Items::get_instance();
        
	}

	public function register_routes() {
		register_rest_route($this->namespace, '/collection/(?P<collection_id>[\d]+)/' . $this->rest_base . '/(?P<metadatum_id>[\d]+)', array(
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array($this, 'get_items'),
				'permission_callback' => array($this, 'get_items_permissions_check')
			)
        ));
        
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<metadatum_id>[\d]+)', array(
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array($this, 'get_items'),
				'permission_callback' => array($this, 'get_items_permissions_check')
			)
		));
	}

	/**
	 * @param \WP_REST_Request $request
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function get_items( $request ) {
		
		$metadatum_id = $request['metadatum_id'];
        $metadatum = $this->metadatum_repository->fetch($metadatum_id);

		$response = $this->prepare_item_for_response($metadatum, $request );

		$rest_response = new \WP_REST_Response($response, 200);

		$rest_response->header('X-WP-Total', $this->total_items);
		$rest_response->header('X-WP-TotalPages', $this->total_pages);

        return $rest_response;
    }

	/**
	 *
	 * Receive a \WP_Query or a metadatum object and return both in JSON
	 *
	 * @param mixed $metadatum
	 * @param \WP_REST_Request $request
	 *
	 * @return mixed|string|void|\WP_Error|\WP_REST_Response
	 */
	public function prepare_item_for_response($metadatum, $request){
		$response = [];
		$metadatum_type = null;

        if( !empty($metadatum) ){

			$metadatum_type = $metadatum->get_metadata_type();
			$options = $metadatum->get_metadata_type_options();
			$args = $this->prepare_filters($request);

			// handle filter with relationship metadata

			if( $metadatum_type === 'Tainacan\Metadata_Types\Relationship' ){

				$selected =  $this->getRelationshipSelectedValues($request, $metadatum->get_id());
				$restItemsClass = new REST_Items_Controller();

				if(isset($request['number'])){
					$args['posts_per_page'] = $request['number'];
				}
	
				$items = $this->items_repository->fetch($args, $options['collection_id'], 'WP_Query');
				$ids = [];

				// retrieve selected items

				if( $selected && $request['getSelected'] && $request['getSelected'] === '1' ){
					foreach( $selected as $index => $item_id ){

						$item = new Entities\Item($item_id);
						$prepared_item = $restItemsClass->prepare_item_for_response($item, $request);
						$response[] = $prepared_item;
						$ids[] = $item_id;
					}
				}

				if ($items->have_posts()) {
					while ( $items->have_posts() ) {
						$items->the_post();
		
						$item = new Entities\Item($items->post);
						$prepared_item = $restItemsClass->prepare_item_for_response($item, $request);

						if( in_array((string) $items->post->ID,$ids) ){
							continue;
						} 

						if( isset($request['number']) && count($response) >= $request['number']){
							break;
						}
		
						array_push($response, $prepared_item);
					}
		
					wp_reset_postdata();
				}

				$this->total_items = $items->found_posts;
				$this->total_pages = ceil($this->total_items / (int) $items->query_vars['posts_per_page']);

			} 

			// handle filter with Taxonomy metadata
			
			else if ( $metadatum_type === 'Tainacan\Metadata_Types\Taxonomy' ){

				$this->taxonomy = $this->taxonomy_repository->fetch($options['taxonomy_id']);
				$selected = $this->getTaxonomySelectedValues($request, $options['taxonomy_id']);

				if( isset($request['term_id']) ){
					
					$terms[] = $this->terms_repository->fetch($request['term_id'], $this->taxonomy);
					$restTermClass = new REST_Terms_Controller();

				} else {

					$terms = $this->terms_repository->fetch($args, $this->taxonomy);

					// retrieve selected items

					if( $selected && $request['getSelected'] && $request['getSelected'] === '1' ){
						$ids = $this->get_terms_ids( $terms );
						$realResponse = [];

						foreach( $selected as $index => $term_id ){

							$term_selected = $this->terms_repository->fetch($term_id, $this->taxonomy);
							$realResponse[] = $term_selected;
						}

						foreach( $terms as $index => $term ){

							if( in_array($term->WP_Term->term_id, $selected) ){
								continue;
							}

							$realResponse[] = $term;

							if( isset($request['number']) && count($realResponse) >= $request['number']){
								break;
							}
						}

						$terms = $realResponse;
					}
					
					$restTermClass = new REST_Terms_Controller();
				}

				foreach ($terms as $term) {
					array_push($response, $restTermClass->prepare_item_for_response( $term, $request ));
				}

				$this->set_pagination_properties_term_type( $args, $response );

			} 
			
			// handle filter with Text metadata

			else {

				$metadatum_id = $metadatum->get_id();
				$offset = null;
				$number = null;
				$_search = null;
				$collection_id = ( isset($request['collection_id']) ) ? $request['collection_id'] : null;
				//$selected = $this->getTextSelectedValues($request, $metadatum_id);
				
				$query_args = $request['current_query'];
				
				
				$query_args = $this->prepare_filters($query_args);
				

				if($request['offset'] >= 0 && $request['number'] >= 1){
					$offset = $request['offset'];
					$number = $request['number'];
				}
				
				if($request['search']) {
					$_search = $request['search'];
				}
				
				$response = $this->metadatum_repository->fetch_all_metadatum_values( $collection_id, $metadatum_id, $_search, $offset, $number, $query_args );
				
				$this->total_items = $response['total'];
				$this->total_pages = $response['pages'];
				return $response['values'];	
				
				
				//$rawResponse = $response;

				// retrieve selected items

				//if( count($selected) && $request['getSelected'] && $request['getSelected'] === '1'){
				//	$rawValues = $this->get_values( $response );
				//	$realResponse = [];

				//	foreach( $selected as $index => $value ){
				//		$row = (object) ['mvalue' => $value, 'metadatum_id' => $metadatum_id ];
				//		$realResponse[] = $row;
				//	}

				//	foreach( $rawValues as $index => $row0 ){

				//		if( !in_array($row0, $selected) ){
				//			$realResponse[] = (object) ['mvalue' => $row0, 'metadatum_id' => $metadatum_id];

				//			if( isset($request['number']) && count($realResponse) >= $request['number']){
				//				break;
				//			}
				//		}
				//	}
				//	
				//	$response = $realResponse;
				//}

				//$this->set_pagination_properties_text_type( $offset, $number, $rawResponse );
			}
        }

		return $this->prepare_response( $response, $metadatum_type );
    }

	/**
	 * @param \WP_REST_Request $request
	 *
	 * @return bool|\WP_Error
	 */
	public function get_items_permissions_check( $request ) {
		return true;
	}

	/**
	 * @param array $response the original response
	 * @param string $type the metadata type
	 * 
	 * @return mixed|string|void|\WP_Error|\WP_REST_Response 
	 */
	public function prepare_response( $response, $type ){
        $result = [];

        if( $response ){
			foreach ( $response as $key => $item ) {

				if( $type === 'Tainacan\Metadata_Types\Taxonomy' ){
					$result[] = [
						'label' => $item['name'],
						'value' => $item['id'],
						'img' => ( isset($item['header_image']) ) ? $item['header_image'] : false ,
						'parent' => ( isset($item['parent']) ) ? $item['parent'] : 0,
						'total_children' => ( isset($item['total_children']) ) ? $item['total_children'] : 0,
						'type' => 'Taxonomy',
						'taxonomy_id' => $this->taxonomy->WP_Post->ID,
						'taxonomy' => ( isset($item['taxonomy']) ) ? $item['taxonomy'] : false,
					];
				} else if( $type === 'Tainacan\Metadata_Types\Relationship' ){
					$result[] = [
						'label' => $item['title'],
						'value' => $item['id'],
						'img' => ( isset($item['thumbnail']['thumb']) ) ? $item['thumbnail']['thumb'] : false,
						'parent' => false,
						'total_children' => 0,
						'type' => 'Relationship'
					];
				} else {
					$result[] = [
						'label' => $item->mvalue,
						'value' => $item->mvalue,
						'img' => false,
						'parent' => false,
						'total_children' => 0,
						'type' => 'Text'
					];
				}
			}
		}
		
		return $result;
	}

	/**
	 * set attributes for text metadata
	 *
	 * @param $offset
	 * @param $number
	 * @param $response
	 */
	private function set_pagination_properties_text_type( $offset, $number, $response ){
		if( $response && is_array( $response ) ){

			if ( $offset !== '' && $number) {
				$per_page = (int) $number;
				//$page = ceil( ( ( (int) $offset ) / $per_page ) + 1 );
			
				$this->total_items  = count( $response );
			
				$max_pages = ceil( $this->total_items / $per_page );
			
				$this->total_pages = (int) $max_pages ;	
			} else {
				$this->total_items = count( $response );
				$this->total_pages = 1;	
			}
		} else {
			$this->total_items = 0;
			$this->total_pages = 0;
		}
	}

	/**
	 * set attributes for term metadata
	 *
	 * @param $args
	 * @param $response
	 */
	private function set_pagination_properties_term_type( $args, $response ){

		if(isset($args['number'], $args['offset'])){
			$number = $args['number'];
			//$offset = $args['offset'];

			unset( $args['number'], $args['offset'] );
			$total_terms = wp_count_terms( $this->taxonomy->get_db_identifier(), $args );

			if ( ! $total_terms ) {
				$total_terms = 0;
			}

			$per_page = (int) $number;
			//$page     = ceil( ( ( (int) $offset ) / $per_page ) + 1 );
		
			$this->total_items  = (int) $total_terms ;
		
			$max_pages = ceil( $total_terms / $per_page );
		
			$this->total_pages = (int) $max_pages ;
		} else{
			$this->total_items  = count($response) ;
			$this->total_pages = 1 ;
		}
	}

	/**
	 * get text metadata selected facets
	 *
	 * @param $request
	 * @param $taxonomy_id
	 *
	 * @return array
	 */
	private function getTaxonomySelectedValues($request, $taxonomy_id){
		$selected = [];
		$restTermClass = new REST_Terms_Controller();

		if( isset($request['current_query']['taxquery']) ){

			foreach( $request['current_query']['taxquery'] as $taxquery ){
				 
				if( $taxquery['taxonomy'] === 'tnc_tax_' . $taxonomy_id ){
					return $taxquery['terms']; 
				}

			}
		}

		return [];
	}

	/**
	 * get text metadata selected facets
	 *
	 * @param $request
	 * @param $metadatum_id
	 *
	 * @return array
	 */
	private function getTextSelectedValues($request, $metadatum_id){
		if( isset($request['current_query']['metaquery']) ){

			foreach( $request['current_query']['metaquery'] as $metaquery ){
				if( $metaquery['key'] == $metadatum_id ){

					return $metaquery['value'];
				}
			}

		}

		return [];
	}

	/**
	 * get only selected relationship values
	 *
	 * @param $request
	 * @param $metadatum_id
	 *
	 * @return array
	 */
	private function getRelationshipSelectedValues($request, $metadatum_id){
		$selected = [];
		$restTermClass = new REST_Terms_Controller();

		if( isset($request['current_query']['metaquery']) ){

			foreach( $request['current_query']['metaquery'] as $metaquery ){
				if( $metaquery['key'] == $metadatum_id ){

					return $metaquery['value'];	
				}
			}

		}

		return [];
	}

	/**
	 * 
	 */
	private function get_terms_ids( $terms ){
		$ids = [];

		foreach( $terms as $term ){
            $ids[] = (string) $term->WP_Term->term_id;
		}

		return $ids;
	}

	/**
	 * @param $rows
	 *
	 * @return array
	 */
	private function get_values( $rows ){
		$values = [];

		foreach( $rows as $row ){
            $values[] = $row->mvalue;
		}

		return $values;
	}

	/**
	 * method responsible to return the total of items for the facet value
	 *
	 * @param $value
	 * @param $reference_id
	 * @param bool $is_taxonomy
	 * @param $query
	 * @param $collection_id
	 *
	 * @return int total of items found
	 */
	private function add_items_count( $value, $reference_id, $is_taxonomy = false, $query, $collection_id){
		$new_args = $query;
		$has_value = false;

		if( !$is_taxonomy  ){

			if( isset( $query['metaquery'] ) ){
				foreach( $query['metaquery'] as $index => $metaquery ){
					if( $metaquery['key'] == $metadatum_id ){
						
						$has_value = true;

						if( is_array($metaquery['value']) )
							$new_args['metaquery'][$index]['value'][] = $value;	
						else
							$new_args['metaquery'][$index]['value'] = $value;

					} 
				}
			}
			
			if( !$has_value ){

				$new_args['metaquery'][] = [
					'key' => $reference_id,
					'value' => $value
				];
			}

		} else {

			if( isset( $query['taxquery'] ) ){
				foreach( $query['taxquery'] as $taxquery ){
					if( $taxquery['taxonomy'] === 'tnc_tax_' . $reference_id ){
	
						$has_value = true;
						$new_args['taxquery'][$index]['terms'][] = $value;	
					}
				}
			}
			
			if( !$has_value ){

				$new_args['taxquery'][] = [
					'taxonomy' => 'tnc_tax_' . $reference_id,
					'value' => [$value]
				];
			}
		}

		$items = $this->items_repository->fetch($new_args, $collection_id, 'WP_Query');
		return $items->found_posts;
	}
}

?>