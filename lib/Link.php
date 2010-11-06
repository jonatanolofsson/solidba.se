<?php
/**
 * This file contains the link menu item
 * @author Jonatan Olofsson
 *
 */

/**
 * A menuitem link
 * @author Jonatan Olofsson
 *
 */
class Link extends MenuItem{
    /**
     * Constructor. Load the properties of the link
     * @param integer $id The id from the controller
     * @return void
     */
    function __construct($id, $language=false) {
        parent::__construct($id, $language);
        $this->getMetadata(array($language, ''));
    }
    
    /**
     * Store incoming data about the link
     * @see lib/MenuItem#__set($property, $value)
     */
    function __set($property, $value) {
        global $DB;
        if($property == 'link') {
            if($value !== $this->$property && ($value || $this->$property)) {
                Metadata::set($property, $value);
            }
        }
        parent::__set($property, $value);
    }
    
    function __get($property) {
        if($property == 'rawLink') return parent::__get('link');
        
        $parentProp = parent::__get($property);
        if($property == 'link') {
            global $USER, $ID;
            return str_replace(array('$uid', '$pid'), array($USER->ID, $ID), $parentProp);
        }
        return $parentProp;
    }
    
    function run() {
        redirect($this->__get('link'));
    }
    
    function link() {
        return '<a href="'.$this->__get('link').'">'.$this->Name.'</a>';
    }
}
?>
