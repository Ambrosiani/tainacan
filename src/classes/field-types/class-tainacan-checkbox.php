<?php

namespace Tainacan\Field_Types;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class TainacanFieldType
 */
class Checkbox extends Field_Type {

    function __construct(){
        $this->primitive_type = 'date';
        parent::__construct();
    }

    /**
     * @param $metadata
     * @return string
     */

    function render( $metadata ){
        return '<tainacan-checkbox name="'.$metadata->get_name().'"></tainacan-checkbox>';
    }
}
new Checkbox();