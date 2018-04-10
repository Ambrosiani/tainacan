<?php

class TAINACAN_REST_Controller extends WP_REST_Controller {


	/**
	 * TAINACAN_REST_Controller constructor.
	 */
	public function __construct() {
		//add_action( 'rest_api_init', function () {
		//	register_rest_field( 'user',
		//		'meta',
		//		array(
		//			'update_callback' => array($this, 'up_user_meta'),
		//			'get_callback'    => array($this, 'gt_user_meta'),
		//		)
		//	);
		//} );
	}

	/**
	 * @param $object
	 * @param $attributes
	 *
	 * @return array
	 */
	protected function filter_object_by_attributes($object, $attributes){
		$object_filtered = [];

		if (is_array($attributes)) {
			foreach ( $attributes as $attribute ) {
				try {
					$get_              = 'get_' . $attribute;
					$object_filtered[$attribute] = $object->$get_();
				} catch ( \Error $error ) {
					// Do nothing
				}
			}
		} else {
			try{
				$get_ = 'get_' . $attributes;
				$object_filtered[$attributes] = $object->$get_();
			} catch (\Error $error){
				// Do nothing
			}
		}

		return $object_filtered;
	}

	/**
	 * @param $object
	 * @param $new_values
	 *
	 * @return Tainacan\Entities\Entity
	 */
	protected function prepare_item_for_updating($object, $new_values){

		foreach ($new_values as $key => $value) {
			try {
				$set_ = 'set_' . $key;
				$object->$set_( $value );
			} catch (\Error $error){
				// Do nothing
			}
		}

		return $object;
	}

	/**
	 * @param $request
	 *
	 * @return array
	 */
	protected function prepare_filters($request){
		$queries = [
			'name'         => 'title',
			'title'        => 'title',
			'id'           => 'p',
			'authorid'     => 'author_id',
			'authorname'   => 'author_name',
			'search'       => 's',
			'status'       => 'post_status',
			'offset'       => 'offset',
			'metaquery'    => 'meta_query',
			'datequery'    => 'date_query',
			'taxquery'     => 'tax_query',
			'order'        => 'order',
			'orderby'      => 'orderby',
			'metakey'      => 'meta_key',
			'metavalue'    => 'meta_value',
			'metavaluenum' => 'meta_value_num',
			'metacompare'  => 'meta_compare',
			'hideempty'    => 'hide_empty',
			'perpage'      => 'posts_per_page',
			'paged'        => 'paged',
			'postin'       => 'post__in',
			'relation'     => 'relation',
			'meta_key'     => 'meta_key',
			'meta_type'     => 'meta_type'
		];

		$meta_query = [
			'key'       => 'key',
			'value'     => 'value',
			'compare'   => 'compare',
			'relation'  => 'relation',
			'fieldtype' => 'type',
		];

		$date_query = [
			'year'      => 'year',
			'month'     => 'month',
			'day'       => 'day',
			'week'      => 'week',
			'hour'      => 'hour',
			'minute'    => 'minute',
			'second'    => 'second',
			'compare'   => 'compare',
			'dayofweek' => 'dayofweek',
			'inclusive' => 'inclusive',
			'before'    => 'before',
			'after'     => 'after',
		];

		$tax_query = [
			'taxonomy' => 'taxonomy',
			'field'    => 'field',
			'terms'    => 'terms',
			'operator' => 'operator',
			'relation' => 'relation',
		];

		$args = [];

		foreach ($queries as $mapped => $mapped_v){
			if(isset($request[$mapped])){
				if($mapped === 'metaquery'){
					$args = $this->prepare_meta($mapped, $request, $meta_query, $mapped_v, $args);
				} elseif($mapped === 'datequery'){
					$args = $this->prepare_meta($mapped, $request, $date_query, $mapped_v, $args);
				} elseif($mapped === 'taxquery'){
					$args = $this->prepare_meta($mapped, $request, $tax_query, $mapped_v, $args);
				}
				else {
					$args[ $mapped_v ] = $request[ $mapped ];
				}
			}
		}

		$args['perm'] = 'readable';

		return $args;
	}

	/**
	 * @param $data
	 * @param $field_name
	 * @param $request
	 *
	 * @return WP_Error
	 */
	function gt_user_meta( $data, $field_name, $request ) {
		if( $data['id'] ){
			$user_meta = get_user_meta( $data['id'] );
		}

		if ( !$user_meta ) {
			return new WP_Error( 'No user meta found', 'No user meta found', array( 'status' => 404 ) );
		}

		foreach ($user_meta as $key => $value) {
			$data[$key] = $value;
		}

		return $data;
	}

	/**
	 * @param $meta
	 * @param $user
	 * @param $field_name
	 *
	 * @param $request
	 *
	 * @return mixed|WP_Error
	 */
	public function up_user_meta( $meta, $user, $field_name, $request ) {
		if ( !$user->ID ) {
			return new WP_Error( 'No user found', 'No user found', array( 'status' => 404 ) );
		}

		$user_id = $user->ID;
		$metas = $field_name === 'meta' ? $meta : [];

		$map = [
			'metakey',
			'metavalue',
			'prevvalue',
		];

		if ($request['delete'] === 'true'){
			if($this->contains_array($metas, $map)){
				foreach ($metas as $index => $meta){
					if (isset($meta[$map[0]], $meta[$map[1]])){
						delete_user_meta($user_id, $meta[$map[0]], $meta[$map[1]]);
					}
				}
			} else {
				foreach ($metas as $meta){
					if (isset($meta[$map[0]], $meta[$map[1]])){
						delete_user_meta($user_id, $meta[$map[0]], $meta[$map[1]]);
					}
				}
			}
		} elseif($this->contains_array($metas, $map)){
			foreach ($metas as $index => $meta){
				if(isset($meta[$map[0]], $meta[$map[1]], $meta[$map[2]])){

					update_user_meta($user_id, $meta[$map[0]], $meta[$map[1]], $meta[$map[2]]);
				} elseif (isset($meta[$map[0]], $meta[$map[1]])){

					add_user_meta($user_id, $meta[$map[0]], $meta[$map[1]]);
				}
			}
		} else {
			foreach ($metas as $meta){
				if(isset($meta[$map[0]], $meta[$map[1]], $meta[$map[2]])){

					update_user_meta($user_id, $meta[$map[0]], $meta[$map[1]], $meta[$map[2]]);
				} elseif (isset($meta[$map[0]], $meta[$map[1]])){

					add_user_meta($user_id, $meta[$map[0]], $meta[$map[1]]);
				}
			}
		}

	}

	/**
	 * @param $mapped
	 * @param $request
	 * @param $query
	 * @param $mapped_v
	 * @param $args
	 *
	 * @return mixed
	 * @throws Exception
	 */
	private function prepare_meta($mapped, $request, $query, $mapped_v, $args){
		$request_meta_query = $request[$mapped];

		// if the meta/date/taxquery has a root relation
        if( isset( $request_meta_query['relation']) )
            $args[ $mapped_v ]['relation'] = $request_meta_query['relation'];

		// If is a multidimensional array (array of array)
		if($this->contains_array($request_meta_query, $query)) {
			foreach ( $request_meta_query as $index1 => $a ) {

                // handle core field
                if( array_key_exists("key", $a) ){
                    $field = new Tainacan\Entities\Field($a['key']);
                    if( strpos( $field->get_field_type(), 'Core_Title') !== false ){
                        $args[ 'post_title_in' ] = [
                            'relation' => ( isset( $request_meta_query['relation']) ) ? $request_meta_query['relation'] : 'AND' ,
                            'value' => ( is_array( $a['value'] ) ) ? $a['value'] : [$a['value']]
                        ];
                        continue;
                    } else if( strpos( $field->get_field_type(), 'Core_Description') !== false ) {
                        $args[ 'post_content_in' ] = [
                            'relation' => ( isset( $request_meta_query['relation']) ) ? $request_meta_query['relation'] : 'AND' ,
                            'value' => ( is_array( $a['value'] ) ) ? $a['value'] : [$a['value']]
                        ];
                        continue;
                    }
                }

				foreach ( $query as $mapped_meta => $meta_v ) {
					if ( isset( $a[ $meta_v ] ) ) {
						$args[ $mapped_v ][ $index1 ][ $meta_v ] = $request[ $mapped ][ $index1 ][ $meta_v ];
					}
				}
			}
		} else {
			foreach ( $query as $mapped_meta => $meta_v ) {
				if(isset($request[$mapped][$meta_v])) {
					$args[ $mapped_v ][ $meta_v ] = $request[ $mapped ][ $meta_v ];
				}
			}
		}

		return $args;
	}

	/**
	 * @param $array
	 *
	 * @param $query
	 *
	 * @return bool
	 */
	protected function contains_array($array, $query){
		foreach ($array as $index => $value){
			// Not will pass named meta query, which use reserved names
			if(is_array($value) && !key_exists($index, $query)){
				return true;
			}
		}

		return false;
	}

	/**
	 * Return the common params
	 *
	 * @param string $object_name
	 *
	 * @return array|void
	 */
	public function get_collection_params($object_name = null){
		$query_params['id'] = array(
			'description' => __("Limit result to $object_name with specific id."),
			'type'        => 'integer',
		);

		$query_params['search'] = array(
			'description'        => __( 'Limit results to those matching a string.' ),
			'type'               => 'string',
			'sanitize_callback'  => 'sanitize_text_field',
			'validate_callback'  => 'rest_validate_request_arg',
		);

		$query_params['authorid'] = array(
			'description' => __("Limit result set to $object_name assigned to specific authors by id."),
			'type'        => 'array',
			'items'       => array(
				'type'    => 'integer',
			),
		);

		$query_params['authorname'] = array(
			'description' => __("Limit result set to $object_name assigned to specific authors by name"),
			'type'        => 'string',
		);

		$query_params['status'] = array(
			'description' => __("Limit result set to $object_name assigned one or more statuses."),
			'type'        => 'array',
			'items'       => array(
				'enum'    => array_merge(array_keys(get_post_stati()), array('any')),
				'type'    => 'string',
			),
			'sanitize_callback' => array($this, 'sanitize_post_statuses'),
		);

		$query_params['offset'] = array(
			'description'        => __( "Offset the result set by a specific number of $object_name." ),
			'type'               => 'integer',
		);

		$query_params['order'] = array(
			'description'        => __( 'Order sort attribute ascending or descending.' ),
			'type'               => 'string/array',
			'default'            => 'desc',
			'enum'               => array( 'asc', 'desc' ),
		);

		$query_params['orderby'] = array(
			'description'        => __( "Sort $object_name by object attribute." ),
			'type'               => 'string/array',
			'default'            => 'date',
			'enum'               => array(
				'author',
				'date',
				'id',
				'include',
				'modified',
				'parent',
				'relevance',
				'slug',
				'include_slugs',
				'title',
				'meta_value',
				'meta_value_num'
			),
		);

		$query_params['perpage'] = array(
			'description'        => __( "Maximum number of $object_name to be returned in result set." ),
			'type'               => 'integer',
			'default'            => 10,
			'minimum'            => 1,
			'maximum'            => 100,
			'sanitize_callback'  => 'absint',
			'validate_callback'  => 'rest_validate_request_arg',
		);

		$query_params['paged'] = array(
			'description' => __("Show the $object_name that would normally show up just on page X"),
			'type'        => 'integer',
		);

		return $query_params;
	}

	/**
	 * Return the common meta, date and tax queries params
	 *
	 * @return array
	 */
	protected function get_meta_queries_params(){
		return array(
			'metakey'      => array(
				'type'        => 'integer/string',
				'description' => __('Custom field key.'),
			),
			'metavalue'    => array(
				'type'        => 'string/array',
				'description' => __('Custom field value'),
			),
			'metavaluenum' => array(
				'type'        => 'number',
				'description' => __('Custom field value'),
			),
			'metacompare'  => array(
				'type'        => 'string',
				'description' => __('Operator to test the meta_value. Possible values are =, !=, >, >=, <, <=, LIKE, NOT LIKE, IN, NOT IN, BETWEEN, NOT BETWEEN, NOT EXISTS, REGEXP, NOT REGEXP or RLIKE.'),
				'default'     => '=',
			),
			'metaquery'    => array(
				'description' => __('Limit result set to items that have specific custom fields'),
				'type'        => 'array/object',
				'items'       => array(
					'keys' => array(
						'key'      => array(
							'type'        => 'string',
							'description' => __('Custom field key.'),
						),
						'value'    => array(
							'type'        => 'string/array',
							'description' => __('Custom field value. It can be an array only when compare is IN, NOT IN, BETWEEN, or NOT BETWEEN. You dont have to specify a value when using the EXISTS or NOT EXISTS comparisons in WordPress 3.9 and up.
	(Note: Due to bug #23268, value is required for NOT EXISTS comparisons to work correctly prior to 3.9. You must supply some string for the value parameter. An empty string or NULL will NOT work. However, any other string will do the trick and will NOT show up in your SQL when using NOT EXISTS. Need inspiration? How about \'bug #23268\'.)'),
						),
						'compare'  => array(
							'type'        => 'string',
							'description' => __('Operator to test. Possible values are =, !=, >, >=, <, <=, LIKE, NOT LIKE, IN, NOT IN, BETWEEN, NOT BETWEEN, EXISTS and NOT EXISTS.'),
							'default'     => '='
						),
						'relation' => array(
							'type'        => 'string',
							'description' => __('OR or AND, how the sub-arrays should be compared.'),
							'default'     => 'AND',
						),
						'fieldtype' => array(
							'type'        => 'string',
							'description' => __('Custom field type. Possible values are NUMERIC, BINARY, CHAR, DATE, DATETIME, DECIMAL, SIGNED, TIME, UNSIGNED. Default value is CHAR. You can also specify precision and scale for the DECIMAL and NUMERIC types (for example, DECIMAL(10,5) or NUMERIC(10) are valid). The type DATE works with the compare value BETWEEN only if the date is stored at the format YYYY-MM-DD and tested with this format.'),
						),
					),
					'type'            => 'array'
				),
			),
			'datequery'    => array(
				'description' => __('Limit result set to items that was created in specific date.'),
				'type'        => 'array/object',
				'items'       => array(
					'keys' => array(
						'year'      => array(
							'type'        => 'integer',
							'description' => __('4 digit year (e.g. 2018).'),
						),
						'month'     => array(
							'type'        => 'integer',
							'description' => __('Month number (from 1 to 12).'),
						),
						'day'       => array(
							'type'        => 'integer',
							'description' => __('Day of the month (from 1 to 31).'),
						),
						'week'      => array(
							'type'        => 'integer',
							'description' => __('Week of the year (from 0 to 53).'),
						),
						'hour'      => array(
							'type'        => 'integer',
							'description' => __('Hour (from 0 to 23).'),
						),
						'minute'    => array(
							'type'        => 'integer',
							'description' => __('Minute (from 0 to 59).'),
						),
						'second'    => array(
							'type'        => 'integer',
							'description' => __('Second (from 0 to 59).')
						),
						'compare'   => array(
							'type'        => 'string',
							'description' => __('Operator to test. Possible values are =, !=, >, >=, <, <=, LIKE, NOT LIKE, IN, NOT IN, BETWEEN, NOT BETWEEN, EXISTS and NOT EXISTS.'),
							'default'     => '='
						),
						'dayofweek' => array('type' => 'array'),
						'inclusive' => array(
							'type'        => 'boolean',
							'description' => __('For after/before, whether exact value should be matched or not.'),
						),
						'before'    => array(
							'type'        => 'string/array',
							'description' => __('Date to retrieve posts before. Accepts strtotime()-compatible string, or array of year, month, day '),
						),
						'after'     => array(
							'type'        => 'string/array',
							'description' => __('Date to retrieve posts after. Accepts strtotime()-compatible string, or array of year, month, day '),
						),
					),
					'type'      => 'array'
				),
			),
			'taxquery'     => array(
				'description' => __('Show items associated with certain taxonomy.'),
				'type'        => 'array/object',
				'items'       => array(
					'keys' => array(
						'taxonomy' => array(
							'type'        => 'string',
							'description' => __('The taxonomy data base identifier.')
						),
						'field'    => array(
							'type'        => 'string',
							'description' => __('Select taxonomy term by. Possible values are term_id, name, slug or term_taxonomy_id. Default value is term_id.')
						),
						'terms'    => array(
							'type'        => 'int/string/array',
							'description' => __('Taxonomy term(s).'),
						),
						'operator' => array(
							'type'        => 'string',
							'description' => __('Operator to test. Possible values are IN, NOT IN, AND, EXISTS and NOT EXISTS'),
							'default'     => 'IN'
						),
						'relation' => array(
							'type'        => 'string',
							'description' => __('The logical relationship between each inner taxonomy array when there is more than one. Possible values are AND, OR. Do not use with a single inner taxonomy array.'),
							'default'     => 'AND'
						),
					),
					'type'     => 'array'
				),
			),
		);
	}
}

?>