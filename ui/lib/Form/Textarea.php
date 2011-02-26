<?php
/**
 * @author Jonatan Olofsson [joolo]
 * @version 1.0
 * @license http://creativecommons.org/licenses/by-nc/3.0/ Creative Commons Attribution-Noncommercial 3.0 Unported License
 * @package GUI
 */

/**
 * Creates a textarea
 * @package GUI
 */
class Textarea extends __FormField{
    public $rows = 8;
    public $cols = 70;
    /**
     * Sets up internal variables with the arguments given
     * @param string $label The (human-readable) label for the form element
     * @param string $name The (machine-readable) name of the element
     * @param string $value The initial contents of the element
     * @param string $validate Adds a validation class to the element
     * @param string $description Add a description to the form element
     * @return void
     */
    function __construct($label, $name, $value='', $validate=false, $description=false, $class = false, $rows=8, $cols=70){
        $this->label = $label;
        $this->name = $name;
        $this->value = $value;
        $this->validate = $validate;
        $this->description = $description;
        $this->class = $class;
        $this->rows = $rows;
        $this->cols = $cols;
    }

    function render(){
        $id = idfy($this->name);
        return '<span class="formelem">'.($this->label === false ? '':'<label for="'.$id.'">'.$this->label.'</label>').'<textarea id="'.$id.'" name="'.$this->name.'" class="textarea'.($this->validate?' '.$this->validate:'').($this->class?' '.$this->class:'').'" rows="'.$this->rows.'" cols="'.$this->cols.'">'.$this->value.'</textarea>'
                    .(strpos($this->validate, 'required')!==false?'<span class="reqstar">*</span>':'')
                    .($this->description?'<span class="description">'.$this->description.'</span>':'')
                    .'</span>';;
    }
}
?>
