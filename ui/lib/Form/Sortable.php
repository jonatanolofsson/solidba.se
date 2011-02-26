<?php
/**
 * @author Jonatan Olofsson [joolo]
 * @version 1.0
 * @license http://creativecommons.org/licenses/by-nc/3.0/ Creative Commons Attribution-Noncommercial 3.0 Unported License
 * @package GUI
 */

/**
 * Creates a sortable list
 * @author Jonatan Olofsson [joolo]
 * @package GUI
 */
class Sortable extends __FormField {
    private $DBtable;

    function __construct($label, $name, $value='', $validate=false, $description=false, $class=false) {
        $this->label = $label;
        $this->name = $name;
        $this->value = $value;
        $this->validate = $validate;
        $this->description = $description;
        $this->class = $class;
    }

    function render() {
        $id = idfy($this->name);
        JS::loadjQuery();
        Head::add('$(function(){$(".sortable_list").sortable({axis:"y"});});', 'js-raw');
        $val = (array)$this->value;
        array_walk($val, array($this, 'addHiddenFormField'));
        return '<span class="formelem">'.($this->label === false? '':'<label for"'.$id.'">'.$this->label.'</label>').listify($val, 'sortable_list'.($this->validate?' '.$this->validate:''))
                    .(strpos($this->validate, 'required')!==false?'<span class="reqstar">*</span>':'')
            .($this->description?'<span class="description">'.$this->description.'</span>':'')
            .'</span>';
    }

    function addHiddenFormField(&$txt, $key) {
        $txt = new Hidden($this->name.'[]', $key).$txt;
    }
}
?>
