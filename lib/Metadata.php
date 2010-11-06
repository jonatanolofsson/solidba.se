<?php
/**
 * This file contains the metadata class
 * @package Base
 */

/**
 * Stores and retrieves data about the calling object
 * @author Jonatan Olofsson
 * @package Base
 */
class Metadata {
    static $metameta = false;
    static $injecting = false;

    /**
     * retrieve a set of metadata. The values are returned in the same order as the arguments
     * <code>
     * list($name, $description) = Metadata::get('name','description');
     * </code>
     * @param strings The names of the metadata wanted
     * @return array Array of values in the same order as the incoming arguments
     */
    function get() {
        if(isset($this)) {
            global $DB;
            $args = func_get_args();
            if(isset(self::$metameta) && self::$metameta !== false)
                $r = $DB->metadata->get(array('id' => $this->ID, 'field' => $args, 'metameta' => self::$metameta), 'field,value');
            else
                $r = $DB->metadata->get(array('id' => $this->ID, 'field' => $args), 'field,value');
            $return = array();
            while(false !== ($row = Database::fetchAssoc($r))) {
                $return[array_search($row['field'], $args)] = $row['value'];
            }
            ksort($return);
            self::$metameta = false;
            return $return;
        }
        self::$metameta = false;
    }

    /**
     * Automatically set the properties in $this corresponding to the data asked for.
     * If the metadata is not set, the variable will not be set.
     * <code>
     * Metadata::inject('name');
     * echo $this->name;
     * </code>
     * @return void
     */
    function inject() {
        if(isset($this)) {
            global $DB;
            $args = func_get_args();
            if(isset(self::$metameta) && self::$metameta !== false) {
                $r = $DB->metadata->get(array('id' => $this->ID, 'field' => $args, 'metameta' => self::$metameta), 'field,value');
            }
            else {
                $r = $DB->metadata->get(array('id' => $this->ID, 'field' => $args), 'field,value');
            }

            self::$metameta = false;
            self::$injecting = true;
            while(false !== ($row = Database::fetchAssoc($r))) {
                $this->__set($row['field'], $row['value']);
            }
            self::$injecting = false;
        }
        self::$metameta = false;
    }

    /**
     * Automatically set the properties in $this corresponding to the data asked for.
     * If the metadata is not set, the variable will be set to an empty string.
     * <code>
     * Metadata::injectAll('name');
     * echo $this->name;
     * </code>
     * @return void
     */
    function injectAll() {
        if(isset($this)) {
            global $DB;
            $args = func_get_args();
            if(isset(self::$metameta) && self::$metameta !== false) {
                $r = $DB->metadata->get(array('id' => $this->ID, 'field' => $args, 'metameta' => self::$metameta), 'field,value', false, false, 'field');
            }
            else {
                $r = $DB->metadata->get(array('id' => $this->ID, 'field' => $args), 'field,value', false, false, 'field');
            }
            self::$metameta = false;
            self::$injecting = true;
            while(false !== ($row = Database::fetchAssoc($r))) {
                $this->__set($row['field'], $row['value']);
                $args = arrayRemove($args, $row['field']);
            }
            foreach($args as $a) {
                $this->$a = '';
            }
            self::$injecting = false;
        }
        self::$metameta = false;
    }

    /**
     * Automatically sets all stored properties in $this
     * <code>
     * Metadata::injectMe();
     * echo $this->name;
     * </code>
     * @return void
     */
    function injectMe() {
        if(isset($this)) {
            global $DB;
            if(isset(self::$metameta) && self::$metameta !== false) {
                $r = $DB->metadata->get(array('id' => $this->ID, 'metameta' => self::$metameta), 'field,value', false, false, 'field');
            }
            else {
                $r = $DB->metadata->get(array('id' => $this->ID), 'field,value', false, false, 'field');
            }
            self::$metameta = false;
            self::$injecting = true;
            while(false !== ($row = Database::fetchAssoc($r))) {
                $this->__set($row['field'], $row['value']);
            }
            self::$injecting = false;
        }
        self::$metameta = false;
    }

    /**
     * retrieves all the metadata of an object as an array with a key => value relationship
     * @return array
     */
    function extract() {
        global $DB;
        if(isset($this)) {
            if(isset(self::$metameta) && self::$metameta !== false){
                $r = $DB->metadata->asList(array('id' => $this->ID, 'metameta' => self::$metameta),'field,value',false,true, false, 'field');
            }
            else {
                $r = $DB->metadata->asList(array('id' => $this->ID),'field,value',false,true, false, 'field');
            }
            self::$metameta = false;
            return $r;
        } else {
            self::$metameta = false;
            return false;
        }
    }

    /**
     * Stores data about the object.
     * @param array|string $arg1 May be an array with key => value relationship, OR a string with the name of the data
     * @param string $arg2 IF $arg1 is given as a string, $arg2 contains the value of the data specified in $arg1
     * @return void
     */
    function set($arg1, $arg2=false) {
        if(isset($this) && (self::$injecting === false)) {
            if(is_array($arg1)) $values = $arg1;
            elseif($arg2 !== false) $values = array($arg1 => $arg2);
            else return false;
            global $DB;

            if(isset(self::$metameta) && self::$metameta !== false) {
                foreach($values as $field => $value) {
                    $DB->metadata->update(array('value' => $value), array('id' => $this->ID, 'field' => $field, 'metameta' => self::$metameta), true);
                }
            } else {
                foreach($values as $field => $value) {
                    $DB->metadata->update(array('value' => $value), array('id' => $this->ID, 'field' => $field), true);
                }
            }
        }
        self::$metameta = false;
    }
}
?>
