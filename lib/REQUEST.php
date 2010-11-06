<?php

/**
 * @author Jonatan Olofsson [joolo]
 * @version 1.0
 * @license http://creativecommons.org/licenses/by-nc/3.0/ Creative Commons Attribution-Noncommercial 3.0 Unported License
 * @package Base
 */
/**
 * This class overrides the normal $_REQUEST, $_POST and $_GET objects, replacing them
 * and adding some nifty new features
 * @package Base
 */
class XSSProtection extends ArrayObject{
    private $TYPES;
    /**
     * Returns the property asked for
     * @param string $property The name of the property
     * @return mixed
     */
    function __get($property){
        if($property == 'rawDATA') return $this->DATA;
        if(!$this->offsetExists($property)) return false;
        return $this->safe($property);
    }

    /**
     * Returns an escaped version of the user data input
     * @param $property The property to escape and return
     * @return mixed
     */
    public function safe($property){
        if(!isset($this->TYPES[$property])) return false;
        $data = parent::offsetGet($property);
        if(!$this->valid($property)) return false;
        return $this->escape($data, $this->TYPES[$property]['filter']);
    }

    /**
     * Returns all (valid) data as an array
     * @return array
     */
    public function extract() {
        $res = array();
        foreach($this as $var => $val) {
            if($val !== false) {
                $res[$var] = $val;
            }
        }
        return $res;
    }

    /**
     * Returns all keys with valid data
     * @return array
     */
    public function keys() {
        $res = array();
        foreach($this as $var => $val) {
            if($val !== false) {
                $res[] = $var;
            }
        }
        return $res;
    }

    /**
     * Escapes the data according to the set type
     * @param $data The data to be escaped
     * @param $type The type which to escape to. May be a set type or a regexp to match
     * @return mixed If the data doesn't match the set type, false is returned. Else, the (possible altered) data is returned.
     */
    private function escape($data,$types) {
        if(!is_array($types)) $types = array($types);
        if(is_array($data)) {
            foreach($data as &$d) {
                $d = $this->escape($d, $types);
            }
            return $data;
        } else {
            if(in_array('any', $types)) return $data;
            elseif(in_array('numeric', $types) && is_numeric($data)) return $data;
            elseif(in_array('string', $types)) return htmlspecialchars(strip_tags($data));
            else {
                foreach($types as $type)
                    if(!in_array($type, array('any','numeric','string'))
                        && !preg_match('/^[a-z0-9]/i', $type )
                        && preg_match($type, $data)) return $data;
            }return false;
        }
    }

    /**
     * Return the original user input
     * @param string $property The name of the property
     * @return mixed
     */
    public function raw($property){
        return ($this->offsetExists($property)) ? parent::offsetGet($property) : null;
    }

    /**
     * See if a given value would match the property's set type
     * @param $property Property to check for match
     * @param $value Value to validate
     * @return bool Returns true if the property's set type would allow the given value
     */
    public function validate($property, $value) {
        if(!isset($this->TYPES[$property])) return false;
        return $this->validData($value, $this->TYPES[$property]['filter']);
    }

    /**
     * Returns wether the varable is valid according to the set type
     * @param $property The property to check
     * @return bool
     */
    public function valid() {
        $properties = func_get_args();
        foreach($properties as $property) {
            if(!isset($this->TYPES[$property]) || !$this->offsetExists($property)) return false;
            $data = parent::offsetGet($property);
            if($this->TYPES[$property]['array']<=1
                && is_array($data) xor $this->TYPES[$property]['array']) return false;
            if(!$this->validData($data, $this->TYPES[$property]['filter'])) return false;
        }
        return true;
    }

    function validNotEmpty() {
        $properties = func_get_args();
        foreach($properties as $property) {
            if(!isset($this->TYPES[$property]) || !$this->offsetExists($property)) return false;
            $data = parent::offsetGet($property);
            if(empty($data)) return false;
            if($this->TYPES[$property]['array']<=1
                && is_array($data) xor $this->TYPES[$property]['array']) return false;
            if(!$this->validData($data, $this->TYPES[$property]['filter'])) return false;
        }
        return true;
    }

    /**
     * Returns wether the data is valid accordingly to the set type
     * @param mixed $data Data to be validated
     * @param string $type Type to validate against
     * @return bool
     */
    private function validData($data, $types) {
        if(!is_array($types)) $types = array($types);
        if(is_array($data)) {
            foreach($data as &$d) {
                $d = $this->validData($d, $types);
            }
            return $data;
        } else {
            if(in_array('any', $types)) return true;
            elseif(in_array('numeric', $types) && is_numeric($data)) return true;
            elseif(in_array('string', $types)) return true;
            else {
                foreach($types as $type)
                    if(!in_array($type, array('any','numeric','string'))
                        && !preg_match('/^[a-z0-9]/i', $type )
                        && preg_match($type, $data)) return true;
            }
            return false;
        }
    }

    /**
     * Returns wether the data in the property is nonempty
     * @param string $property Property name
     * @return bool
     */
    public function nonempty($property) {
        if(!$this->offsetExists($property)) return false;
        $a = $this->offsetGet($property);
        return !empty($a);
    }

    /**
     * Checks if the property is an array
     * @param string $property Property to check
     * @return bool
     */
    public function isArray($property) {
        return is_array($this->__get($property));
    }

    /**
     * Checks if the property is numeric
     * @param string $property Property to check
     * @return bool
     */
    public function numeric($property) {
        return is_numeric($this->__get($property));
    }

    /**
     * Removes properties specified in the arguments
     * @param strings $args The properties to delete (as several arguments)
     * @return void
     */
    public function clear() {
        $args = func_get_args();
        foreach($args as $property) {
            @parent::offsetUnset($property);
        }
    }

    /**
     * Adds an expected type of the variable. This has to match the real value and must be set.
     * @param string $index name of the variable
     * @param string $filter Regexp filter or one of the predefined values ('string', 'numeric', 'any').
     * @param bool $array Wether the user data is expected to come as an array or not
     * @return void
     */
    public function addType($index, $filters, $array=false) {
        if(!is_array($filters)) $filters = array($filters);
        if(!isset($this->TYPES[$index])) {
            $this->TYPES[$index]['filter'] = $filters;
            $this->TYPES[$index]['array'] = (int)(bool)$array;
        } else {
            $this->TYPES[$index]['filter'] = array_merge($this->TYPES[$index]['filter'], $filters);
            $this->TYPES[$index]['array'] += 	(int)($this->TYPES[$index]['array'] <= 1 &&
                                                        ($array xor $this->TYPES[$index]['array']))
                                                +(int)($this->TYPES[$index]['array']===0 && $array);
                                                /**
                                                 * Algorithm above explained:
                                                 * Possible ['array'] values: 0 => Not an array, 1 => Is an array 2 => May, or may not, be an array
                                                 *
                                                 * If the new type should be an array:
                                                 * 	If the property already is an array, add nothing, else set ['array'] to 2 to allow both types simultaneously
                                                 * Else if the new type should not be an array
                                                 * 	If the previous type is an array, set to 2 to allow both types. Else add nothing.
                                                 *
                                                 * The logical algorithm can be visualized as
                                                 * filter	array	add_1	add_2
                                                 * 0		0		0		0
                                                 * 0		1		1		1
                                                 * 1		0		1		0
                                                 * 1		1		0		0
                                                 */
        }
    }

    /**
     * Sets the expected type of an incoming variable. If this not matches the real case, no value will be returned if the variabel is asked for
     * <code>$_REQUEST->setType('id', 'numeric');</code>
     * @param $index The name of the variable
     * @param $filters Regexp or any of the set types, ['string', 'numeric', 'any']
     * @param $array Set to true if the variable is expected to come as an array
     * @return void
     */
    public function setType($index, $filters, $array=false) {
        if(!is_array($filters)) $filters = array($filters);
        $this->TYPES[$index]['filter'] = $filters;
        $this->TYPES[$index]['array'] = (int)(bool)$array;
    }

    /**
     * Returns the set type of a variable. Return false if the type is not set.
     * @param $index The name of the variable
     * @return array The set type of the reuquest variable
     */
    public function getType($index) {
        if(isset($this->TYPES[$index])) return $this->TYPES[$index];
        else return false;
    }

    /**
     * Returns wether the the property is set
     * @param string $property Property name
     * @return bool
     */
    public function __isset($property) {
        return $this->offsetExists($property);
    }

    /**
     * Provides the unset() function with a handle so it works as usual
     * @param string $property Property to unset
     * @return void
     */
    public function __unset($property) {
        parent::offsetUnset($property);
    }

    /**
     * retrieves an element of the array
     * @param $offset
     * @return unknown_type
     */
    public function offsetGet($offset) {
        return $this->__get($offset);
    }
}

/**
 * Strip magic quotes and initialize the XSSProtection
 */
if (get_magic_quotes_gpc()) {
    $in = array(&$_GET, &$_POST, &$_REQUEST, &$_COOKIE);
    while (list($k,$v) = each($in)) {
        foreach ($v as $key => $val) {
            if (!is_array($val)) {
                $in[$k][$key] = stripslashes($val);
                continue;
            }
            $in[] =& $in[$k][$key];
        }
    }
    unset($in);
}
$_REQUEST	= new XSSProtection($_REQUEST);
$_POST		= new XSSProtection($_POST);
$_GET		= new XSSProtection($_GET);
?>
