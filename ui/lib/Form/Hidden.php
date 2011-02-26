<?php
/**
 * @author Jonatan Olofsson [joolo]
 * @version 1.0
 * @license http://creativecommons.org/licenses/by-nc/3.0/ Creative Commons Attribution-Noncommercial 3.0 Unported License
 * @package GUI
 */

/**
 * Creates a hidden field
 * @package GUI
 */
class Hidden extends __FormField{
    /**
     * Sets up internal variables with the arguments given
     * @param string $name The (machine-readable) name of the element
     * @param string $value The initial contents of the element
     * @return void
     */
    function __construct($name, $value=''){
        $this->name = $name;
        $this->value = $value;
    }
    /**
     * (non-PHPdoc)
     * @see solidbase/lib/__FormField#render()
     */
    function render(){
        $id = idfy($this->name);
        return '<input name="'.$this->name.'" id="'.$id.'" type="hidden" value="'.$this->value.'" />';
    }
}
?>
