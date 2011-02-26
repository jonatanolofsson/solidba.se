<?php
/**
 * @author Jonatan Olofsson [joolo]
 * @version 1.0
 * @license http://creativecommons.org/licenses/by-nc/3.0/ Creative Commons Attribution-Noncommercial 3.0 Unported License
 * @package GUI
 */

/**
 * Creates a submit button
 * @package GUI
 *
 */
class Submit extends __FormField{
    /**
     * Sets up internal variables with the arguments given
     * @param string $label The (human-readable) label for the form element
     * @param string $name The (machine-readable) name of the element
     * @return void
     */
    function __construct($label, $name='submit', $class = false){
        $this->label = $label;
        $this->name = $name;
        $this->class = $class;
    }
    /**
     * (non-PHPdoc)
     * @see solidbase/lib/__FormField#render()
     */
    function render(){
        $id = idfy($this->name);
        return '<span class="formelem">'.'<input name="'.$this->name.'" type="submit" id="'.$id.'" class="submit'.($this->class?' '.$this->class:'').'" value="'.$this->label.'" />'
                    .(strpos($this->validate, 'required')!==false?'<span class="reqstar">*</span>':'')
                    .'</span';
    }
}
?>
