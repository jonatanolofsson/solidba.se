<?php

/**
 *
 *
 * @version $Id$
 * @copyright 2009
 */
/**
 *
 *
 */
class Callback{
    /**
     * Constructor
     */
    function __construct($object, $function){
        $this->object = $object;
        $this->id = $object->ID;
        $this->function = $function;
    }

    function __sleep(){
        return array('id', 'function');
    }

    function __toString(){
        return self::__invoke();
    }

    function __invoke(){
        if(!$this->object) {
            if($this->id) $this->object = $Controller->{(string)$this->ID};
            else return '';
        }
        $params = func_get_args();
        return call_user_func_array(array($this->object, $this->function), $params);
    }
}

?>
