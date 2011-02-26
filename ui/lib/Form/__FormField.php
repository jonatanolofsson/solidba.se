<?php
/**
 * @author Jonatan Olofsson [joolo]
 * @version 1.0
 * @license http://creativecommons.org/licenses/by-nc/3.0/ Creative Commons Attribution-Noncommercial 3.0 Unported License
 * @package GUI
 */

 /**
 * The abstract class which all form elements extend
 * Contains property definitions, output functions and the demand
 * for a render()-function in each form object.
 * @package GUI
 */
abstract class __FormField{
    public $label;
    public $name;
    public $value;
    public $type;
    public $validate;
    public $description;
    public $class;
    private $out;

    /**
     * Each form element must contain a function
     * named render() to comply with the abstract class
     * Returns the HTML-representation of the form element
     * @return string
     */
    abstract function render();

    /**
     * Simplifies output of form
     * @return string
     */
    function __toString() {
        return $this->render();
    }
}
?>
