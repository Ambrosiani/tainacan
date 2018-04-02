<?php

namespace Tainacan\Field_Types;

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

/**
 * Class TainacanFieldType
 */
class Relationship extends Field_Type {

    function __construct(){
        // call field type constructor
        parent::__construct();
        $this->set_primitive_type('item');
        $this->set_component('tainacan-relationship');
        $this->set_form_component('tainacan-form-relationship');
    }

    /**
     * @inheritdoc
     */
    public function get_form_labels(){
       return [
           'collection_id' => [
               'title' => __( 'Collection Related', 'tainacan' ),
               'description' => __( 'Select the collection to fetch items', 'tainacan' ),
           ],
           'search' => [
               'title' => __( 'Fields for search', 'tainacan' ),
               'description' => __( 'Select the fields to help the search', 'tainacan' ),
           ],
           'repeated' => [
               'title' =>__( 'Allow repeated items', 'tainacan' ),
               'description' => __( 'Allow different items with the same item selected', 'tainacan' ),
           ]
       ];
    }

    /**
     * @param $itemMetadata \Tainacan\Entities\Item_Metadata_Entity The instace of the entity itemMetadata
     * @return string
     */

    public function render( $itemMetadata ){
        return '<tainacan-relationship 
                            collection_id="' . $this->get_options()['collection_id'] . '"
                            field_id ="'.$itemMetadata->get_field()->get_id().'" 
                            item_id="'.$itemMetadata->get_item()->get_id().'"    
                            value=\''.json_encode( $itemMetadata->get_value() ).'\'  
                            name="'.$itemMetadata->get_field()->get_name().'"></tainacan-relationship>';
    }
    
    public function validate_options(\Tainacan\Entities\Field $field) {
        if ( !in_array($field->get_status(), apply_filters('tainacan-status-require-validation', ['publish','future','private'])) )
            return true;

        if (!empty($this->get_option('collection_id')) && !is_numeric($this->get_option('collection_id'))) {
            return [
                'collection_id' => __('Collection ID invalid','tainacan')
            ];
        } else if( empty($this->get_option('collection_id'))) {
            return [
                'collection_id' => __('Collection related is required','tainacan')
            ];
        }
        return true;
    }
}