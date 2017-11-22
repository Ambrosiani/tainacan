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
    
    public function set_collections_ids(Array $value) {
        return $this->set_mapped_property('collection_id', $value);
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

}