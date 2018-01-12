<?php

namespace Tainacan\Repositories;
use Tainacan\Entities;
use Tainacan\Entities\Entity;
use Tainacan;
use \Respect\Validation\Validator as v;

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

abstract class Repository {
	public $entities_type = '\Tainacan\Entities\Entity';
	
	/**
	 * Register hooks
	 */
	function __construct() {
		add_action('init', array(&$this, 'register_post_type'));
		//add_action('admin_init', array(&$this, 'init_caps'));
		add_action('init', array(&$this, 'init_capabilities'), 11);
		add_filter('tainacan-get-map-'.$this->get_name(), array($this, 'get_default_properties'));
        
        
		add_filter('map_meta_cap', array($this, 'map_meta_cap'), 10, 4);
	}
	
	/**
	 * return properties map
	 * 		@return array properties map array, format like:
	 * 		'id'             => [
     *          'map'        => 'ID',
     *          'title'       => __('ID', 'tainacan'),
	 *          'type'       => 'integer',
     *          'description'=> __('Unique identifier', 'tainacan'),
     *          'validation' => v::numeric(),
     *      ],
     *      'name'           =>  [
     *          'map'        => 'post_title',
     *          'title'       => __('Name', 'tainacan'),
     *          'type'       => 'string',
     *          'description'=> __('Name of the collection', 'tainacan'),
     *          'validation' => v::stringType(),
     *      ],
     *      'slug'           =>  [
     *          'map'        => 'post_name',
     *          'title'       => __('Slug', 'tainacan'),
     *          'type'       => 'string',
     *          'description'=> __('A unique and santized string representation of the collection, used to build the collection URL', 'tainacan'),
     *          'validation' => v::stringType(),
     *      ],
	 */
	public abstract function get_map();
    
    /**
     * Return repository name
     * 
     * @return string The repository name
     */
    public function get_name() {
        return str_replace('Tainacan\Repositories\\', '', get_class($this));
    }

	/**
	 *
	 * @param \Tainacan\Entities\Entity $obj
	 *
	 * @return \Tainacan\Entities\Entity
	 * @throws \Exception
	 */
	public function insert($obj) {
		// validate
		if ( in_array($obj->get_status(), apply_filters('tainacan-status-validation', ['publish','future','private'])) && !$obj->get_validated()){
			throw new \Exception('Entities must be validated before you can save them');
            // TODO: Throw Warning saying you must validate object before insert()
		}
		
		$map = $this->get_map();
		
		// First iterate through the native post properties
		foreach ($map as $prop => $mapped) {
			if ($mapped['map'] != 'meta' && $mapped['map'] != 'meta_multi') {
				$obj->WP_Post->{$mapped['map']} = $obj->get_mapped_property($prop);
			}
		}
		$obj->WP_Post->post_type = $obj::get_post_type();
		//$obj->WP_Post->post_status = 'publish';
		
		// TODO verificar se salvou mesmo
		$id = wp_insert_post($obj->WP_Post);
		
		// reset object
		$obj->WP_Post = get_post($id);
		
		// Now run through properties stored as postmeta
		foreach ($map as $prop => $mapped) {
			
            if ($mapped['map'] == 'meta' || $mapped['map'] == 'meta_multi') {
                $this->insert_metadata($obj, $prop);
            }
            
		}
		
		do_action('tainacan-insert', $obj);
		do_action('tainacan-insert-'.$obj->get_post_type(), $obj);
		
		// return a brand new object
		return new $this->entities_type($obj->WP_Post);
	}
    
    /**
     * Insert object property stored as postmeta into the database
     * @param  \Tainacan\Entities  $obj    The entity object
     * @param  string  $prop   the property name, as declared in the map of the repository
     * @return null|false on error
     */
    public function insert_metadata($obj, $prop) {
        $map = $this->get_map();
        
        if (!array_key_exists($prop, $map))
            return false;
        
        if ($map[$prop]['map'] == 'meta') {
            update_post_meta($obj->get_id(), $prop,  wp_slash( $obj->get_mapped_property($prop) ));
        } elseif($map[$prop]['map'] == 'meta_multi') {
            $values = $obj->get_mapped_property($prop);
            
            delete_post_meta($obj->get_id(), $prop);
            
            if (is_array($values)){
                foreach ($values as $value){
                    add_post_meta($obj->get_id(), $prop, wp_slash( $value ));
                }
            }
        }
    }

    /**
     * Prepare the output for the fetch() methods.
     *
     * Possible outputs are:
     * WP_Query (default) - returns the WP_Object itself
     * OBJECT - return an Array of Tainacan\Entities
     * 
     * @param \WP_Query $WP_Query
     * @param string $output `WP_Query` for a single WP_Query object or `OBJECT` for an array of Tainacan\Entities
     * @return array|\WP_Query
     */
	public function fetch_output(\WP_Query $WP_Query, $output = 'WP_Query' ){
        
        if (is_null($output)) $output = 'WP_Query';

        if( $output === 'WP_Query'){
            return $WP_Query;
        } else if( $output === 'OBJECT' ) {
            $result = [];

            if ( $WP_Query->have_posts() ){
                /**
                 * Using WordPress Loop here would cause problems
                 * @see https://core.trac.wordpress.org/ticket/18408
                 */
                foreach ($WP_Query->get_posts() as $p) {
                    $result[] = new $this->entities_type(  $p->ID );
                }
            }

            return $result;
        }
    }
    
    /**
     * Maps repository mapped properties to WP_Query arguments.
     *
     * This allows to build fetch arguments using both WP_Query arguments
     * and the mapped properties for the repository.
     *
     * For example, you can use any of the following methods to browse collections by name:
     * $TainacanCollections->fetch(['title' => 'test']);
     * $TainacanCollections->fetch(['name' => 'test']);
     *
     * The property `name` is transformed into the native WordPress property `post_title`. (actually only title for query purpouses)
     *
     * Example 2, this also works with properties mapped to postmeta. The following methods are the same:
     * $TainacanMetadatas->fetch(['required' => 'yes']);
     * $TainacanMetadatas->fetch(['meta_query' => [
     *     [
     *         'key' => 'required',
     *         'value' => 'yes'
     *     ]
     * ]]);
     *
     * 
     * @param  array  $args [description]
     * @return array $args new $args array with mapped properties
     */
    public function parse_fetch_args($args = []) {
        
        $map = $this->get_map();

        $wp_query_exceptions = [
            'ID'         => 'p',
            'post_title' => 'title'
        ];
        
        $meta_query = [];
        
        foreach ($map as $prop => $mapped) {
            if (array_key_exists($prop, $args)) {
                $prop_value = $args[$prop];
                
                unset($args[$prop]);
                
                if ( $mapped['map'] == 'meta' || $mapped['map'] == 'meta_multi' ) {
                    $meta_query[] = [
                        'key'   => $prop,
                        'value' => $prop_value
                    ];
                } else {
                    $prop_search_name = array_key_exists($mapped['map'], $wp_query_exceptions) ? $wp_query_exceptions[$mapped['map']] : $mapped['map'];
                    $args[$prop_search_name] = $prop_value;
                }
                
            }
        }
        
        if ( isset($args['meta_query']) && is_array($args['meta_query']) && !empty($args['meta_query'])) {
            $meta_query = array_merge($args['meta_query'],$meta_query);
        }
        
        $args['meta_query'] = $meta_query;
        
        return $args;
        
    }

	/**
	 * Return default properties
	 *
	 * @param array $map
	 *
	 * @return array
	 */
	public function get_default_properties($map) {
		if(is_array($map)) {
	    	$defaults = array(
	    		'status'	=>  array(
		   			'map'			=> 'post_status',
		   			'title'			=> __('Status', 'tainacan'),
		   			'type'			=> 'string',
		   			'description'	=> __('Status', 'tainacan'),
		   			//'validation'	=> v::stringType(),
	   			),
	    		'id'             => array(
	    			'map'        => 'ID',
	    			'title'       => __('ID', 'tainacan'),
	    			'type'       => 'integer',
	    			'description'=> __('Unique identifier', 'tainacan'),
	    			//'validation' => v::numeric(),
	    		),
	    	);
	    	return array_merge($defaults, $map);
		}
		return $map;
    }

    /**
     * return the value for a mapped property from database
     * @param Entities\Entity $entity
     * @param string $prop id of property
     * @return mixed property value
     */
    public function get_mapped_property($entity, $prop) {
    	
    	$map = $this->get_map();
    	
    	if (!array_key_exists($prop, $map)){
    		return null;
    	}
    	
    	$mapped = $map[$prop]['map'];
    	$property = '';
    	if ( $mapped == 'meta') {
    		$property = isset($entity->WP_Post->ID) ? get_post_meta($entity->WP_Post->ID, $prop, true) : null;
    	} elseif ( $mapped == 'meta_multi') {
    		$property = isset($entity->WP_Post->ID) ? get_post_meta($entity->WP_Post->ID, $prop, false) : null;
    	} elseif ( $mapped == 'termmeta' ){
    		$property = get_term_meta($entity->WP_Term->term_id, $prop, true);
    	} elseif ( isset( $entity->WP_Post )) {
    		$property = isset($entity->WP_Post->$mapped) ? $entity->WP_Post->$mapped : null;
    	} elseif ( isset( $entity->WP_Term )) {
    		$property = isset($entity->WP_Term->$mapped) ? $entity->WP_Term->$mapped : null;
    	}
    	
    	if (empty($property) && array_key_exists('default', $map[$prop])){
    		$property = $map[$prop]['default'];
    		$entity->set_mapped_property($prop, $property);
    	}
    	
    	return $property;
    }
    
    /**
     * Return array of collections db identifiers
     * @return array[]
     */
    public static function get_collections_db_identifier() {
    	global $Tainacan_Collections;
    	$collections = $Tainacan_Collections->fetch([], 'OBJECT');
    	$cpts = [];
		foreach($collections as $col) {
			$cpts[$col->get_db_identifier()] = $col;
		}
		return $cpts;
    }
    
    /**
     * 
     * @param integer|\WP_Post $post
     * @throws \Exception
     * @return \Tainacan\Entities\Entity|boolean
     */
    public static function get_entity_by_post($post) {
    	if(is_numeric($post) || is_array($post)) {
    		$post = get_post($post);
    	}
    	elseif(is_object($post) && $post instanceof Entity ) {
    		return $post;
    	}
    	
    	$post_type = $post->post_type;
    	$prefix = substr($post_type, 0, strlen(Entities\Collection::$db_identifier_prefix));
    	
    	// its is a collection Item?
    	if($prefix == Entities\Collection::$db_identifier_prefix) {
    		$cpts = self::get_collections_db_identifier();
    		if(array_key_exists($post_type, $cpts)) {
    			return $entity = new \Tainacan\Entities\Item($post);
    		}
    		else {
    			throw new \Exception('Collection object not found for this post');
    		}
    	}
    	else {
    		global $Tainacan_Collections,$Tainacan_Metadatas, $Tainacan_Item_Metadata,$Tainacan_Filters,$Tainacan_Taxonomies,$Tainacan_Terms,$Tainacan_Logs;
    		$tnc_globals = [$Tainacan_Collections,$Tainacan_Metadatas, $Tainacan_Item_Metadata,$Tainacan_Filters,$Tainacan_Taxonomies,$Tainacan_Terms,$Tainacan_Logs];
    		foreach ($tnc_globals as $tnc_repository)
    		{
    			$entity_post_type = $tnc_repository->entities_type::get_post_type();
    			if($entity_post_type == $post_type)
    			{
    				return new $tnc_repository->entities_type($post);
    			}
    		}
    	}
    	return false;
    }
    
    /**
     * Update post_type caps using WordPress basic roles
     * @param string $name //capability name
     */
    public function init_capabilities($name = '') {
    	
    	if( empty($name) ) {
    		$name = $this->entities_type::get_post_type();
    	}
    	if($name) {
	    	$wp_append_roles = apply_filters('tainacan-default-capabilities', array(
	    		'administrator' => array(
		    		'delete_'.$name.'s',
		    		'delete_private_'.$name.'s',
		    		'edit_'.$name.'s',
		    		'edit_private_'.$name.'s',
		    		'publish_'.$name.'s',
		    		'read_'.$name,
		    		'read_private_'.$name.'s',
		    		'delete_published_'.$name.'s',
		    		'edit_published_'.$name.'s',
		    		'edit_others_'.$name.'s',
		    		'delete_others_'.$name.'s',
	    		),
	    		'contributor' => array(
	    			'delete_'.$name.'s',
	    			'edit_'.$name.'s',
	    			'read_'.$name,
				),
				'subscriber' => array(
					'read_'.$name,
				),
				'author' => array(
					'delete_'.$name.'s',
					'edit_'.$name.'s',
					'publish_'.$name.'s',
					'delete_published_'.$name.'s',
					'edit_published_'.$name.'s',
				),
	    		'editor' => array(
	    			'delete_'.$name.'s',
	    			'delete_private_'.$name.'s',
	    			'edit_'.$name.'s',
	    			'edit_private_'.$name.'s',
	    			'publish_'.$name.'s',
	    			'read_'.$name,
	    			'read_private_'.$name.'s',
	    			'delete_published_'.$name.'s',
	    			'edit_published_'.$name.'s',
	    			'edit_others_'.$name.'s',
	    			'delete_others_'.$name.'s',
	    		)
	    	));
	    	// append new capabilities to WordPress default roles 
	    	foreach ($wp_append_roles as $role_name => $caps) {
	    		$role = get_role($role_name);
	    		if(!is_object($role)) {
	    			throw new \Exception(sprintf('Capability "%s" not found', $role_name));
	    		}
	    		
	    		foreach ($caps as $cap) {
	    			$role->add_cap($cap);
	    		}
	    	}
    	}
    }
    
    /**
     * @param $object
     * @return mixed
     */
    public abstract function delete($object);

    /**
     * @param $args
     * @return mixed
     */
    public abstract function fetch( $args , $output = null );

    /**
     * @param $object
     * @return mixed
     */
    public abstract function update($object);

    /**
     * @return mixed
     */
    public abstract function register_post_type();
    
    /**
     * Check if $user can edit/create a entity
     * @param int|array|\WP_Post|Entities\Entity $entity
     * @param int|\WP_User|null $user default is null for the current user
     * @return boolean
     */
    public function can_edit($entity, $user = null) {
    	if(is_null($user)) {
    		$user = get_current_user_id();
    	}
    	elseif(is_object($user)) {
    		$user = $user->ID;
    	}
    	$entity = self::get_entity_by_post($entity);
    	
    	$post_type = $entity::get_post_type();
    	if($post_type === false) { // There is no post
    		return user_can($user, 'edit_posts');
    	}
    	
    	return user_can($user, $entity->cap->edit_post, $entity);
    }
    
    /**
     * Check if $user can read the entity
     * @param Entities\Entity $entity
     * @param int|\WP_User|null $user default is null for the current user
     * @return boolean
     */
    public function can_read($entity, $user = null) {
    	if(is_null($user)) {
    		$user = get_current_user_id();
    	}
    	elseif(is_object($user)) {
    		$user = $user->ID;
    	}
    	$entity = self::get_entity_by_post($entity);
    	
   		$post_type = $entity::get_post_type();
   		if($post_type === false) { // There is no post
   			return user_can($user, 'read');
   		}
    	
   		return user_can($user, $entity->cap->read_post, $entity);
    }
    
    /**
     * Check if $user can delete the entity
     * @param Entities\Entity $entity
     * @param int|\WP_User|null $user default is null for the current user
     * @return boolean
     */
    public function can_delete($entity, $user = null) {
    	if(is_null($user)) {
    		$user = get_current_user_id();
    	}
    	elseif(is_object($user)) {
    		$user = $user->ID;
    	}
   		$entity = self::get_entity_by_post($entity);
    	$post_type = $entity::get_post_type();
    	if($post_type === false) { // There is no post
    		return user_can($user, 'delete_posts');
    	}
    	
    	return user_can($user, $entity->cap->delete_post, $entity);
    }
    
    /**
     * Check if $user can publish the entity
     * @param Entities\Entity $entity
     * @param int|\WP_User|null $user default is null for the current user
     * @return boolean
     */
    public function can_publish($entity, $user = null) {
    	if(is_null($user)) {
    		$user = get_current_user_id();
    	}
    	elseif(is_object($user)) {
    		$user = $user->ID;
    	}
    	$entity = self::get_entity_by_post($entity);
    	$post_type = $entity::get_post_type();
    	if($post_type === false) { // There is no post
    		return user_can($user, 'publish_posts');
    	}
    	return user_can($user, $entity->cap->publish_posts, $entity);
    }
    
    /**
     * Filter to handle special permissions
     *
     * @see https://developer.wordpress.org/reference/hooks/map_meta_cap/
     * 
     */
    public function map_meta_cap($caps, $cap, $user_id, $args) {
        
        // Filters meta caps edit_tainacan-collection and check if user is moderator
        $collection_cpt = get_post_type_object(Entities\Collection::get_post_type());
        if ($cap == $collection_cpt->cap->edit_post) {
            $entity = new Entities\Collection($args[0]);
            if ($entity) {
                $moderators = $entity->get_moderators_ids();
                if (in_array($user_id, $moderators)) {
                    // if user is moderator, we clear the current caps
                    // (that might fave edit_others_posts) and leave only edit_posts
                    $caps = [$collection_cpt->cap->edit_posts]
                }
            }
        }
        
        return $caps;
        
    }
    
}

?>