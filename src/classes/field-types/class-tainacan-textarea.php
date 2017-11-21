<?php

namespace Tainacan\Field_Types;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class TainacanFieldType
 */
class Textarea extends Field_Type {

    function __construct(){
        $this->primitive_type = 'string';
        parent::__construct();
    }

    /**
     * @param $metadata
     * @return string
     */

    function render( $metadata ){
        return '<tainacan-textarea name="'.$metadata->get_name().'"></tainacan-textarea>';
    }
}
