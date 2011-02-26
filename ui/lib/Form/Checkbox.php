<?php
/**
 * @author Jonatan Olofsson [joolo]
 * @version 1.0
 * @license http://creativecommons.org/licenses/by-nc/3.0/ Creative Commons Attribution-Noncommercial 3.0 Unported License
 * @package GUI
 */

/**
 * Creates a single Checkbox
 * @package GUI
 */
class Checkbox extends __FormField{
    public $checked = false;
    /**
     * Sets up internal variables with the arguments given
     * @param string $label The (human-readable) label for the form element
     * @param string $name The (machine-readable) name of the element
     * @param string $value The initial contents of the element
     * @param string $validate Adds a validation class to the element
     * @param string $description Add a description to the form element
     * @return void
     */
    function __construct($label, $name, $checked = false, $value = false, $validate=false, $description=false, $class = false){
        $this->label = $label;
        $this->name = $name;
        if(!$value) $value = $name;
        $this->value = $value;
        $this->checked = $checked;
        $this->validate = $validate;
        $this->description = $description;
        $this->class = $class;
    }

    function render(){
        $id = idfy($this->name);
        return '<span class="formelem">'.($this->label === false ? '':'<label for="'.$id.'">'.$this->label.'</label>').'<input name="'.$this->name.'" value="'.$this->value.'" id="'.$id.'" type="'.strtolower(get_class()).'" class="'.strtolower(get_class()).($this->validate?' '.$this->validate:'').($this->class?' '.$this->class:'').'"'.($this->checked?' checked="checked"':'').' />'
                    .(strpos($this->validate, 'required')!==false?'<span class="reqstar">*</span>':'')
                    .($this->description?'<span class="description">'.$this->description.'</span>':'')
                    .'</span>';
    }
}
?>
