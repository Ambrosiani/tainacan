<?php

/**
 * Test Importer
 *
 * Example Importer class 
 *
 * used to learn how to write an importer and to 
 * create test collections and items
 *
 */

namespace Tainacan\Importer;
use \Tainacan\Entities;

class Test_Importer extends Importer {
	
	protected $steps = [
		
		[
			'name' => 'Create Taxonomies',
			'progress_label' => 'Creating taxonomies',
			'callback' => 'create_taxonomies'
		],
		[
			'name' => 'Create Collections',
			'progress_label' => 'Creating Collections',
			'callback' => 'create_collections'
		],
		[
			'name' => 'Import Items',
			'progress_label' => 'Importing items',
			'callback' => 'process_collections'
		],
		[
			'name' => 'Link relationship',
			'progress_label' => 'Link relationship',
			'callback' => 'link_relationship'
		],
		[
			'name' => 'Post-configure taxonomies',
			'progress_label' => 'post processing taxonomies',
			'callback' => 'close_taxonomies'
		],
		[
			'name' => 'Finalize',
			'progress_label' => 'Finalizing',
			'callback' => 'finish_processing',
			'total' => 5
		]
		
	];
	
	protected $selectbox_values = [
		'good', 'awesome', 'disgusting', 'bad', 'horrible', 'regular'
	];

	protected $date_values = [
		'03/04/1993', '03/12/1998', '10/09/2001', '03/01/2018', '13/11/2016', '22/04/1993'
	];

	protected $numeric_values = [
		10, 99.9, 0.189, 76543.90, 20000, 900
	];

	protected $text_values = [
		'rice', 'beans', 'tomatoes', 'pasta', 'sushi'
	];

	protected $textarea_values = [
		'value 1 lorem ipsum lorem ipsum', 
		'value 2 lorem ipsum lorem ipsum', 
		'value 3 lorem ipsum lorem ipsum', 
		'value 4 lorem ipsum lorem ipsum', 
		'value 5 lorem ipsum lorem ipsumsushi'
	];

	protected $extra_values = [
		'extra val 1', 'extra val 2', 'extra val 3', 'extra val 4', 'extra val 5'
	];
	
	public function __construct($attributes = array()) {
		parent::__construct($attributes);
		
		$this->tax_repo = \Tainacan\Repositories\Taxonomies::get_instance();
		$this->col_repo = \Tainacan\Repositories\Collections::get_instance();
		$this->items_repo = \Tainacan\Repositories\Items::get_instance();
		$this->metadata_repo = \Tainacan\Repositories\Metadata::get_instance();
		
		$this->remove_import_method('file');
		$this->remove_import_method('url');
		
		$this->set_default_options([
            'items_col_1' => 20
		]);
		
	}
	
	public function options_form() {

		$form = '<div class="field">';
        $form .= '<label class="label">' . __('Number of items in collection 1', 'tainacan') . '</label>';
		$form .= '<div class="control">';
		$form .= '<input type="text" class="input" name="items_col_1" value="' . $this->get_option('items_col_1') . '" />';
		$form .= '</div>';
		$form .= '</div>';

		$form .= '<div class="field">';
		$form .= '<label class="label">' . __('Number of additonal metadata to create in first collection', 'tainacan') . '</label>';
		$form .= '<div class="control">';
		$form .= '<input type="text" class="input" name="additonal_metadata" value="' . $this->get_option('additonal_metadata') . '" />';
		$form .= '</div>';
		$form .= '</div>';

		$form .= '<div class="field">';   
        $form .= '<label class="label">' . __('Create second collection with a relationship with the first collection', 'tainacan') . '</label>';
  
        $not_create = ( !$this->get_option('second_collection') || $this->get_option('second_collection') === 'no' ) ? 'checked' : '';
        $create = ( !$this->get_option('second_collection') && $this->get_option('second_collection') === 'yes' ) ? 'checked' : '';

        $form .= '<div class="field">';
        $form .= '<label class="b-radio radio is-small">';
        $form .= '<input type="radio"  name="second_collection" value="yes" '. $create . ' />';
        $form .= '<span class="check"></span>';
        $form .= '<span class="control-label">';
        $form .=  __('Yes', 'tainacan') . '</span></label>';
		$form .= '</div>';
        
        $form .= '<div class="field">';
        $form .= '<label class="b-radio radio is-small">';
        $form .= '<input type="radio"  name="second_collection" value="no" '. $not_create . ' />';
        $form .= '<span class="check"></span>';
        $form .= '<span class="control-label">';
        $form .=  __('No', 'tainacan') . '</span></label>';
        $form .= '</div>';

        $form .= '</div>';

		$form .= '<div class="field">';
		$form .= '<label class="label">' . __('Number of items in collection 2', 'tainacan') . '</label>';
		$form .= '<div class="control">';
		$form .= '<input type="text" class="input" name="items_col_2" value="' . $this->get_option('items_col_2') . '" />';
		$form .= '</div>';
		$form .= '</div>';
		
        return $form;

    }
	
	public function create_taxonomies() {
		
		$tax1 = new Entities\Taxonomy();
		$tax1->set_name('Terms for taxonomy metadata');
		$tax1->set_allow_insert('yes');
		$tax1->set_status('publish');
		
		if ($tax1->validate()) {
			$tax1 = $this->tax_repo->insert($tax1);
		} else {
			
			/**
			 * In these set up steps, if we have an error adding 
			 * a taxonomy, collection or metadatum, there is no point 
			 * in continuing running the importer. So we throw an exception 
			 * to abort it, because an error here would cause errors in the next 
			 * steps anyway.
			 */
			$this->add_error_log('Error creating taxonomy Color');
			$this->add_error_log($tax1->get_errors());
			$this->abort();
			return false;
			
		}
		
		$this->add_transient('tax_1_id', $tax1->get_id());
		
		$tax2 = new Entities\Taxonomy();
		$tax2->set_name('Quality');
		$tax2->set_allow_insert('yes');
		$tax2->set_status('publish');
		if ($tax2->validate()) {
			$tax2 = $this->tax_repo->insert($tax2);
		} else {
			$this->add_error_log('Error creating taxonomy Quality');
			$this->add_error_log($tax2->get_errors());
			$this->abort();
			return false;
			
		}
		
		$this->add_transient('tax_2_id', $tax2->get_id());
		
		return false;
		
	}
	
	public function create_collections() {
		
		$col1 = new Entities\Collection();
		$col1->set_name('Collection test 1');
		$col1->set_status('publish');
		if ($col1->validate()) {
			$col1 = $this->col_repo->insert($col1);
		} else {
			$this->add_error_log('Error creating Collection 1');
			$this->add_error_log($col1->get_errors());
			$this->abort();
			return false;
			
		}
		
		
		$col1_map = [];
		
		// metadata
		
		// core metadata
		$col1_core_title = $col1->get_core_title_metadatum();
		$col1_core_description = $col1->get_core_description_metadatum();
		$col1_map[$col1_core_title->get_id()] = 'field1';
		$col1_map[$col1_core_description->get_id()] = 'field2';
		
		// Taxonomy type
		$metadatum = $this->create_metadata( [
			'name' => 'Taxonomy type',
			'type' => 'Tainacan\Metadata_Types\Taxonomy',
			'options' => [
			'taxonomy_id' => $this->get_transient('tax_1_id'),
			'allow_new_terms' => true
			]
		], $col1 );

		if(!$metadatum)
			return false;
		
		$col1_map[$metadatum->get_id()] = 'field3';
		$this->add_transient('tax_1_metadatum', $metadatum->get_id());
		
		// Selectbox type
		$metadatum = $this->create_metadata( [
			'name' => 'Selectbox type',
			'type' => 'Tainacan\Metadata_Types\Selectbox',
			'options' => [
				'options' => implode('\\n', $this->selectbox_values)
			]
		], $col1 );
		
		if(!$metadatum)
			return false;

		$col1_map[$metadatum->get_id()] = 'field4';

		// Date type
		$metadatum = $this->create_metadata( [
			'name' => 'Date type',
			'type' => 'Tainacan\Metadata_Types\Date'
		], $col1 );
		
		if(!$metadatum)
			return false;

		$col1_map[$metadatum->get_id()] = 'field5';

		// Numeric type
		$metadatum = $this->create_metadata( [
			'name' => 'Numeric type',
			'type' => 'Tainacan\Metadata_Types\Numeric'
		], $col1 );
		
		if(!$metadatum)
			return false;

		$col1_map[$metadatum->get_id()] = 'field6';

		// Text type
		$metadatum = $this->create_metadata( [
			'name' => 'Text type',
			'type' => 'Tainacan\Metadata_Types\Text'
		], $col1 );
		
		if(!$metadatum)
			return false;

		$col1_map[$metadatum->get_id()] = 'field7';

		// Textarea type
		$metadatum = $this->create_metadata( [
			'name' => 'Textarea type',
			'type' => 'Tainacan\Metadata_Types\Textarea'
		], $col1 );
		
		if(!$metadatum)
			return false;

		$col1_map[$metadatum->get_id()] = 'field8';

		if($this->get_option('additonal_metadata')){
			$total_extra = absint($this->get_option('additonal_metadata'));
			$counter = 0;

			while( $counter < $total_extra ){
				$metadatum = $this->create_metadata( [
					'name' => 'Extra Metadata ' . ($counter + 1),
					'type' => 'Tainacan\Metadata_Types\Text'
				], $col1 );	
				$col1_map[$metadatum->get_id()] = 'field' . ($counter + 9);

				$counter++;
			}
		}
		
		// insert map in collection
		$this->add_collection([
			'id' => $col1->get_id(),
			'mapping' => $col1_map,
			'total_items' => $this->get_col1_number_of_items(),
			'source_id' => 'col1'
		]);
		
		// if collection 2 is allowed to be created
		if( $this->get_option('second_collection') === 'yes' ){
			$col2 = new Entities\Collection();
			$col2->set_name('Collection test 2');
			$col2->set_status('publish');
			if ($col2->validate()) {
				$col2 = $this->col_repo->insert($col2);
			} else {
				$this->add_error_log('Error creating Collection 2');
				$this->add_error_log($col2->get_errors());
				$this->abort();
				return false;
				
			}	

			$col2_map = [];

			// core metadata
			$col2_core_title = $col2->get_core_title_metadatum();
			$col2_core_description = $col2->get_core_description_metadatum();
			$col2_map[$col2_core_title->get_id()] = 'field1';
			$col2_map[$col2_core_description->get_id()] = 'field2';
			
			$metadatum = new Entities\Metadatum();
			$metadatum->set_name('Test Metadatum');
			$metadatum->set_collection($col2);
			$metadatum->set_metadata_type('Tainacan\Metadata_Types\Text');
			$metadatum->set_status('publish');
			
			if ($metadatum->validate()) {
				$metadatum = $this->metadata_repo->insert($metadatum);
			} else {
				$this->add_error_log('Error creating field3');
				$this->add_error_log($metadatum->get_errors());
				$this->abort();
				return false;
			}
			$col2_map[$metadatum->get_id()] = 'field3';
			
			$this->add_collection([
				'id' => $col2->get_id(),
				'mapping' => $col2_map,
				'total_items' => $this->get_col2_number_of_items(),
				'source_id' => 'col2'
			]);

			// Create Relationship
			$metadatum = $this->create_metadata( [
				'name' => 'Relationship type',
				'type' => 'Tainacan\Metadata_Types\Relationship',
				'options' => [
				'collection_id' => $col2->get_id(),
				'repeated' => 'yes'
				]
			], $col1 );

			$this->add_transient('relationship_id', $metadatum->get_id());
		}	
		
		return false;
	}

	/**
	 * link relationship metadata in first collection with a random item in second collection
	 */
	public function link_relationship(){
        if( $this->get_transient('relationship_id') ){
			$this->add_log('linking relationship');	

			$collections = $this->get_collections();

			if ( isset($collections[0]) && $collections[0]['source_id'] === 'col1' ) {

				$col1 = new Entities\Collection($collections[0]['id']);
				$items_first = $Tainacan_Items->fetch( ['order'=> 'DESC', 'orderby' => 'ID'], $col1, 'OBJECT' );

				$col2 = new Entities\Collection($collections[1]['id']);
				$items_second = $Tainacan_Items->fetch( ['order'=> 'DESC', 'orderby' => 'ID'], $col1, 'OBJECT' );

				$metadatum = new Entities\Metadatum($this->get_transient('relationship_id'));

				// iterate over all items in first collection and randomly finds
				//  an item in second collection
				
				if( $metadatum && $items_first && count($items_first) > 0 && count($items_second) > 0 ){
					foreach ($items_first as $item_first) {
						
						$item_metadata = new Entities\Item_Metadata_Entity($item_first, $metadatum);
						$rand_item = $items_second[array_rand($items_second)];	
						
						$item_metadata->set_value($rand_item->get_id());

						if($item_metadata->validate()){
							$item_metadata = $Tainacan_Item_Metadata->insert($item_metadata);
						}
					}
				}
			}
		}
	}
	
	public function close_taxonomies() {
		
		$this->add_log('closing taxonomies');
		
		$tax1 = $this->tax_repo->fetch( $this->get_transient('tax_1_id') );
		$tax1->set_allow_insert('no');
		if ($tax1->validate()) {
			$tax1 = $this->tax_repo->insert($tax1);
			$this->add_log('tax 1 closed');
		} else {
			/**
			 * This is an example of an error that 
			 * we just want to log, but dont want to abort the process.
			 */
			$this->add_error_log('Error closing ' . $tax1->get_name());
			$this->add_error_log($tax1->get_errors());
		}
		
		
		$metadatum1 = $this->metadata_repo->fetch( $this->get_transient('tax_1_metadatum') );
		if ($metadatum1) {
			$options = $metadatum1->get_metadata_type_options();
			$options['allow_new_terms'] = false;
			$metadatum1->set_metadata_type_options($options);
			if ($metadatum1->validate()) {
				$this->metadata_repo->insert($metadatum1);
				$this->add_log('metadatum 1 closed');
			} else {
				$this->add_error_log('Error closing ' . $metadatum1->get_name());
				$this->add_error_log($metadatum1->get_errors());
			}
		}

		return false;
		
	}
	
	public function finish_processing() {
		
		// Lets just pretend we are doing something really important
		$important_stuff = 5;
		$current = $this->get_in_step_count();
		if ($current <= $important_stuff) {
			// This is very important
			sleep(5);
			$current ++;
			return $current;
		} else {
			return false;
		}
		
	}
	
	public function process_item($index, $collection_definition) {
		
		$method = 'get_' . $collection_definition['source_id'] . '_item';
		$item = $this->$method($index);
		return $item;
		
	}
	
	public function get_col1_number_of_items() {
		return $this->get_option('items_col_1');
	}
	public function get_col2_number_of_items() {
		return $this->get_option('items_col_2');
	}

	/**
	 * @param $args array with name, type and options for metadata create
	 * @param $collection
	 * 
	 */
	private function create_metadata( $args, $collection ){
		$metadatum = new Entities\Metadatum();
		$metadatum->set_name($args['name']);
		$metadatum->set_collection($collection);
		$metadatum->set_metadata_type($args['type']);
		$metadatum->set_metadata_type_options($args['options']);
		$metadatum->set_status('publish');
		if ($metadatum->validate()) {
			$metadatum = $this->metadata_repo->insert($metadatum);
		} else {
			$this->add_error_log('Error creating metadata');
			$this->add_error_log($metadatum->get_errors());
			$this->abort();
			return false;
		}

		$this->add_log('metadata created ' . $args['name'] );
		return $metadatum;
	}
	
	/**
	 * Dummy methods
	 *
	 * This could be reading from a file, or making requests to an API
	 *
	 * Here we are just returning random values
	 */
	public function get_col1_item($index) {
		
		$terms_for_taxonomy1 = [
			'orange', 'red', 'purple', 'blue', 'black', 'yellow'
		];
		
		$array = [
			'field1' => 'Title ' . $index,
			'field2' => 'Description ' . $index,
			'field3' => $terms_for_taxonomy1[array_rand($terms_for_taxonomy1)],
			'field4' => $this->selectbox_values[array_rand($this->selectbox_values)],
			'field5' => $this->date_values[array_rand($this->date_values)],
			'field6' => $this->numeric_values[array_rand($this->numeric_values)],
			'field7' => $this->text_values[array_rand($this->text_values)],
			'field8' => $this->textarea_values[array_rand($this->textarea_values)]
		];

		if(is_numeric($this->get_option('additonal_metadata'))){
			$total_extra = absint($this->get_option('additonal_metadata'));
			$counter = 0;

			while( $counter < $total_extra ){
				$array['field' . ($counter + 9) ] = $this->extra_values[array_rand($this->extra_values)];
				$counter++; 
			}

			$this->add_log('extra metadata mapped' );
		}

		return $array;
	}
	public function get_col2_item($index) {
		return [
			'field1' => 'Collection 2 item ' . $index,
			'field2' => 'Collection 2 item description ' . $index,
			'field3' => 'Collection 2 whatever ' . $index,
		];
	}
	
	
	
}