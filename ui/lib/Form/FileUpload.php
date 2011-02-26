<?php
/**
 * @author Jonatan Olofsson [joolo]
 * @version 1.0
 * @license http://creativecommons.org/licenses/by-nc/3.0/ Creative Commons Attribution-Noncommercial 3.0 Unported License
 * @package GUI
 */


/**
 * Creates an upload field
 * @package GUI
 */
class FileUpload extends __FormField{
    /**
     * Sets up internal variables with the arguments given
     * @param string $label The (human-readable) label for the form element
     * @param string $name The (machine-readable) name of the element
     * @param string $validate Adds a validation class to the element
     * @param string $description Add a description to the form element
     * @return void
     */
    function __construct($label, $name, $validate=false, $description=false, $class=false){
        $this->label = $label;
        $this->name = $name;
        $this->validate = $validate;
        $this->description = $description;
        $this->class = $class;
    }
    /**
     * (non-PHPdoc)
     * @see solidbase/lib/__FormField#render()
     */
    function render(){
        $id = idfy($this->name);
        return '<span class="formelem">'.($this->label === false ? '':'<label for="'.$id.'">'.$this->label.'</label>').'<input name="'.$this->name.'" id="'.$id.'" type="file" class="file'.($this->validate?' '.$this->validate:'').($this->validate?' ':'').$this->class.'" />'
                    .(strpos($this->validate, 'required')!==false?'<span class="reqstar">*</span>':'')
                    .($this->description?'<span class="description">'.$this->description.'</span>':'')
                    .'</span>';
    }
}
?>
