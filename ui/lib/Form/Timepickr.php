<?php
/**
 * @author Jonatan Olofsson [joolo]
 * @version 1.0
 * @license http://creativecommons.org/licenses/by-nc/3.0/ Creative Commons Attribution-Noncommercial 3.0 Unported License
 * @package GUI
 */
/**
 * Creates a jQuery Timepickr input field
 * @author Jonatan Olofsson
 *
 */
class Timepickr extends Input {

    /**
     *
     * @param string $label The (human-readable) label for the form element
     * @param string $name The (machine-readable) name of the element
     * @param string $value The initial contents of the element
     * @param string $validate Adds a validation class to the element
     * @param string $description Add a description to the form element
     * @return void
     */
    function __construct($label, $name, $value=false, $validate=false, $description=false, $class = false) {
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
        JS::loadjQuery(true);
        JS::lib('jquery/jquery.timePicker');
        Head::add('timePicker', 'css-lib');

        Head::add('$(function(){$("input.time").timePicker();});', 'js-raw');

        return '<span class="formelem">'.($this->label === false ? '':'<label for="'.$id.'">'.$this->label.'</label>').'<input name="'.$this->name.'" id="'.$id.'" class="time'.($this->validate?' '.$this->validate:'').($this->class?' '.$this->class:'').'" value="'.(is_numeric($this->value)?date('H:i', $this->value):$this->value).'" />'
                    .(strpos($this->validate, 'required')!==false?'<span class="reqstar">*</span>':'')
                    .($this->description?'<span class="description">'.$this->description.'</span>':'')
                    .'</span>';
    }
}
?>
