<?php

namespace Tainacan\Entities;

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

/**
 * Represents the Item Field Entity
 */
class Item_Metadata_Entity extends Entity {
	protected static $post_type = false;
	/**
	 * {@inheritDoc}
	 * @see \Tainacan\Entities\Entity::repository
	 * @var string
	 */
	protected $repository = 'Item_Metadata';
	
	protected
        $item,
		$field,
		$parent_meta_id,
		$meta_id,
		$has_value,
		$value;
	
	/**
	 * 
	 * @param Item   $item    Item Entity
	 * @param Field  $field   Field Entity
	 * @param int $meta_id ID for a specific meta row 
	 */
    function __construct(Item $item, Field $field, $meta_id = null, $parent_meta_id = null) {
        
        $this->set_item($item);
        $this->set_field($field);
		
		if (!is_null($meta_id) && is_int($meta_id)) {
			$this->set_meta_id($meta_id);
		}
		
		if (!is_null($parent_meta_id) && is_int($parent_meta_id)) {
			$this->set_parent_meta_id($parent_meta_id);
		}
		
		
    }
	
	/**
	 * Get the value as a HTML string, with markup and links
	 * @return string
	 */
	public function  get_value_as_html(){
		$field = $this->get_field();
		
		if (is_object($field)) {
			$fto = $field->get_field_type_object();
			if (is_object($fto)) {
				
				if ( method_exists($fto, 'get_value_as_html') ) {
					return $fto->get_value_as_html($this);
				}
				
			}
		}
		
		$value = $this->get_value();
		
		$return = '';
		
		if ( $this->is_multiple() ) {
			
			$total = sizeof($value);
			$count = 0;
			
			foreach ($value as $v) {
				$return .= (string) $v;
				
				$count ++;
				if ($count <= $total)
					$return .= ', ';
			}
			
		} else {
			$return = (string) $value;
		}

		return $return;
		
		
	}
	
	/**
	 * Get the value as a plain text string
	 * @return string
	 */
	public function get_value_as_string() {
		return strip_tags($this->get_value_as_html());
	}
	
	/**
	 * Get value as an array
	 * @return [type] [description]
	 */
	public function get_value_as_array() {
		$value = $this->get_value();
		
		if ( $this->is_multiple() ) {
			
			$return = [];
			
			foreach ($value as $v) {
				if ( $v instanceof Term || $v instanceof ItemMetadataEntity ) {
					$return[] = $v->__toArray();
				} else {
					$return[] = $v;
				}
			}
			
		} else {
			
			$return = '';
			
			if ( $value instanceof Term || $value instanceof ItemMetadataEntity ) {
				$return = $value->__toArray();
			} else {
				$return = $value;
			}
		}

		return $return;

	}
	
	/**
	 * Convert the object to an Array
	 * @return array the representation of this object as an array
	 */
    public function  __toArray(){
		$as_array = [];
		
		$as_array['value'] = $this->get_value_as_array();
		$as_array['value_as_html'] = $this->get_value_as_html();
		$as_array['value_as_string'] = $this->get_value_as_string();
	    $as_array['item']  = $this->get_item()->__toArray();
	    $as_array['field'] = $this->get_field()->__toArray();

	    return $as_array;
    }
    
    /**
     * Define the item
     *
     * @param Item $item
     * @return void
     */
    function set_item(Item $item = null) {
        $this->item = $item;
    }
    
    /**
     * Define the field value
     *
     * @param [integer | string] $value
     * @return void
     */
    function set_value($value) {
        $this->value = $value;
    }
    
    /**
     * Define the field
     *
     * @param Field $field
     * @return void
     */
    function set_field(Field $field = null) {
        $this->field = $field;
    }
	
	/**
	 * Set the specific meta ID for this metadata.
	 *
	 * When this value is set, get_value() will use it to fetch the value from 
	 * the post_meta table, instead of considering the item and field IDs
	 * 
	 * @param int $meta_id the ID of a specifica post_meta row
	 */
	function set_meta_id($meta_id) {
		if (is_int($meta_id)) {
			$this->meta_id = $meta_id;
			return true;
			// TODO: Should we check here to see if this meta_id is really from this field and item?
		}
		return false;
	}
	
	/**
	 * Set parent_meta_id. Used when a item_metadata is inside a compound Field 
	 *
	 * When you have a multiple compound field, this indicates of which instace of the value this item_metadata is attached to
	 * 
	 * @param [type] $parent_meta_id [description]
	 */
	function set_parent_meta_id($parent_meta_id) {
		if (is_int($parent_meta_id)) {
			$this->parent_meta_id = $parent_meta_id;
			return true;
			// TODO: Should we check here to see if this meta_id is really from this field and item?
		}
		return false;
	}
    
    /**
     * Return the item
     *
     * @return Item
     */
    function get_item() {
        return $this->item;
    }
    
    /**
     * Return the field
     *
     * @return Field
     */
    function get_field() {
        return $this->field;
    }
	
	/**
     * Return the meta_id
     *
     * @return Field
     */
    function get_meta_id() {
        return isset($this->meta_id) ? $this->meta_id : null;
    }
	
	/**
     * Return the meta_id
     *
     * @return Field
     */
    function get_parent_meta_id() {
        return isset($this->parent_meta_id) ? $this->parent_meta_id : 0;
    }
    
    /**
     * Return the field value
     *
     * @return string | integer
     */
    function get_value() {
        if (isset($this->value))
            return $this->value;
        
        $Tainacan_Item_Metadata = \Tainacan\Repositories\Item_Metadata::get_instance();
        return $Tainacan_Item_Metadata->get_value($this);
    }
	
	/**
     * Check wether the item has a value stored in the database or not
     *
     * @return bool
     */
    function has_value() {
        if (isset($this->has_value))
            return $this->has_value;
        
		$value = $this->get_value();
		$this->has_value = !empty($value);
		return $this->has_value;
    }
    
    /**
     * Return true if field is multiple, else return false
     *
     * @return boolean
     */
    function is_multiple() {
        return $this->get_field()->is_multiple();
    }
    
    /**
     * Return true if field is key
     *
     * @return boolean
     */
    function is_collection_key() {
        return $this->get_field()->is_collection_key();
    }
    
    /**
     * Return true if field is required
     *
     * @return boolean
     */
    function is_required() {
        return $this->get_field()->is_required();
    }
    
    /**
     * Validate attributes
     *
     * @return boolean
     */
    function validate() {   
        $value = $this->get_value();
        $field = $this->get_field();
        $item = $this->get_item();
        
        if (empty($value) && $this->is_required()) {
            $this->add_error('required', $field->get_name() . ' is required');
            return false;
        }

        $classFieldType = $field->get_field_type_object();
        if( is_object( $classFieldType ) ){
            if( method_exists ( $classFieldType , 'validate' ) ){
                if( ! $classFieldType->validate( $this ) ) {
                    $this->add_error('field_type_error', $classFieldType->get_errors() );
                    return false;
                }
            }
        }
        
        if ($this->is_multiple()) {
            
            if (is_array($value)) {
                
                // if its required, at least one must be filled
                $one_filled = false;
                $valid = true;
                foreach($value as $val) {
                    if (!empty($val))
                        $one_filled = true;
                }
                
                if ($this->is_required() && !$one_filled) {
                    $this->add_error('required', $field->get_name() . ' is required');
                    return false;
                }
                
                if (!$valid) {
                    $this->add_error('invalid', $field->get_name() . ' is invalid');
                    return false;
                }
                
                $this->set_as_valid();
                return true;   
            } else {
                $this->add_error('invalid', $field->get_name() . ' is invalid');
                return false;
            }
        } else {

            if( is_array($value) ){
				$this->add_error('not_multiple', $field->get_name() . ' do not accept array as value');
                return false;
            }
            
            if ($this->is_collection_key()) {
                $Tainacan_Items = \Tainacan\Repositories\Items::get_instance();
                
                $test = $Tainacan_Items->fetch([
                    'meta_query' => [
                        [
                            'key'   => $this->field->get_id(),
                            'value' => $value
                        ],
						'post__not_in' => [$item->get_id()]
                    ]
                ], $item->get_collection());

                if ($test->have_posts()) {
                    $this->add_error('key_exists', $field->get_name() . ' is a collection key and there is another item with the same value');
                    return false;
                }
            }

            $this->set_as_valid();
            return true;   
        }   
    }
}