<?php

namespace Tainacan\Traits;

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );
// used by Taxonomy

trait Entity_Collections_Relation {

    public function fetch_ids() {
        return $this->get_mapped_property('collections_ids');
    }
    
    public function fetch() {
        if (isset($this->collection) && !empty($this->collection) && is_array($this->collection)){
            return $this->collection;
        }
        
        if (is_array($this->fetch_ids()) && !empty(is_array($this->fetch_ids()))) {
            
            global $Tainacan_Collections;
            $collections = [];
            
            foreach ($this->fetch_ids() as $col_id) {
                $collections[] = $Tainacan_Collections->fetch($col_id);
            }
            
            return $collections;
        }
        
        return null;
        
    }
	
	public function get_collections_ids() {
        return $this->get_mapped_property('collections_ids');
    }
	
	public function get_collections() {
    	if (isset($this->collections))
            return $this->collections;
        
        if (is_array($this->get_collections_ids()) && !empty($this->get_collections_ids())) {
            global $Tainacan_Collections;
			
			$this->collections = [];
			
			foreach ($this->get_collections_ids() as $col_id) {
				$this->collections[] = $Tainacan_Collections->fetch($col_id);
			}
			
            return $this->collections;
        }
        
        return null;
    }
	
	
    
    public function set_collections_ids(Array $value) {
        $this->set_mapped_property('collections_ids', $value);
        $this->collections = null;
    }
    
    public function set_collections(Array $collections) {
        $collections_ids = [];
        $this->collections = $collections;
        
        foreach ($collections as $collection){
            $collections_ids[] = $collection->get_id();
        }
        
        $this->set_collections_ids($collections_ids);
    }

    public function add_collection_id($new_collection_id){
    	$collections = $this->get_mapped_property('collections_ids');

    	$collections[] = $new_collection_id;

    	$this->set_collections_ids($collections);
    }
	
	public function remove_collection_id($collection_id){
    	$collections = $this->get_mapped_property('collections_ids');

		if (($key = array_search($collection_id, $collections)) !== false) {
			unset($collections[$key]);
		}

    	$this->set_collections_ids($collections);
    }

}