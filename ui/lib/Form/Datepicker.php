<?php
/**
 * @author Jonatan Olofsson [joolo]
 * @version 1.0
 * @license http://creativecommons.org/licenses/by-nc/3.0/ Creative Commons Attribution-Noncommercial 3.0 Unported License
 * @package GUI
 */

/**
 * Creates a jQuery UI Datepicker input field
 * @author Jonatan Olofsson
 *
 */
class Datepicker extends Input {
    var $format = 'yy-mm-dd';

    /**
     *
     * @param string $label The (human-readable) label for the form element
     * @param string $name The (machine-readable) name of the element
     * @param string $format The format of the input. Defaults to ISO 8601: yy-mm-dd
     * @param string $value The initial contents of the element
     * @param string $validate Adds a validation class to the element
     * @param string $description Add a description to the form element
     * @return void
     */
    function __construct($label, $name, $value = false, $validate=false, $description = false, $class = false) {
        $this->label = $label;
        $this->name = $name;
        $this->value = $value;
        $this->validate = $validate;
        $this->description = $description;
        $this->class = $class;
    }

    function render() {
        $id = idfy($this->name);
        if(is_array($this->value)) $value = Short::parseDateAndTime($this->value);
        global $CONFIG;
        JS::loadjQuery(false);
        JS::lib(array('jquery/date', 'jquery/jquery.datePicker'));
        JS::lib('jquery/jquery.bgiframe.min', true);
        Head::add('$(function(){$(".Datepicker").datePicker();});', 'js-raw');
        Head::add('datePicker', 'css-lib');
        return '<span class="formelem">'.($this->label === false ? '':'<label for="'.$id.'">'.$this->label.'</label>').'<input name="'.$this->name.'" id="'.$id.'" class="Datepicker'.($this->validate?' '.$this->validate:'').($this->class?' '.$this->class:'').'" value="'.(is_numeric($this->value)?date('Y-m-d', $this->value):$this->value).'" />'
                    .(strpos($this->validate, 'required')!==false?'<span class="reqstar">*</span>':'')
                    .($this->description?'<span class="description">'.$this->description.'</span>':'')
                    .'</span>';
    }
}

?>
