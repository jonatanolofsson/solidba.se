<?php
/**
 * @author Jonatan Olofsson [joolo]
 * @version 1.0
 * @license http://creativecommons.org/licenses/by-nc/3.0/ Creative Commons Attribution-Noncommercial 3.0 Unported License
 * @package GUI
 */

/**
 * Creates an input to select one or more users
 * @package GUI
 */
class UserSelect extends __FormField {

    /**
     * Sets up internal variables with the arguments given
     * @param string $label The (human-readable) label for the form element
     * @param string $name The (machine-readable) name of the element
     * @param array $data An associative array with a $value => $text relationship
     * @param string|array $selected The value of the selected element(s)
     * @param bool $multiple Adds the "multiple" option to the select-box, making it possible to select multiple values.
     * @param string $startEmpty Pass a string to provide an empty default element at the top of the dropdown-box
     * @param string $validate Adds a validation class to the element
     * @param string $description Add a description to the form element
     * @param string $class Additional classes of the element
     * @return void
     */
    function __construct($label, $name, $data, $selected=false, $multiple=false, $startEmpty = false, $validate = false, $description=false, $class=false){
        $this->label = $label;
        $this->name = $name;
        $this->data = $data;
        $this->selected = (is_array($selected)||is_bool($selected)?$selected:array($selected));
        $this->multiple = $multiple;
        $this->startEmpty = $startEmpty;
        $this->validate = $validate;
        $this->description = $description;
        $this->class = $class;
    }

    function render() {

    }
}
?>
