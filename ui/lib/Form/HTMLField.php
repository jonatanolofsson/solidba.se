<?php
/**
 * @author Jonatan Olofsson [joolo]
 * @version 1.0
 * @license http://creativecommons.org/licenses/by-nc/3.0/ Creative Commons Attribution-Noncommercial 3.0 Unported License
 * @package GUI
 */

/**
 * Creates and initializes a tinyMCE editing area on top of a textfield
 * @package GUI
 */
class HTMLField extends __FormField{
    /**
     * Sets up internal variables with the arguments given
     * @param string $label The (human-readable) label for the form element
     * @param string $name The (machine-readable) name of the element
     * @param string $value The initial contents of the element
     * @param string $validate Adds a validation class to the element
     * @param string $description Add a description to the form element
     * @return void
     */
    function __construct($label, $name, $value='', $validate=false, $description=false, $class=false){
        $this->label = $label;
        $this->name = $name;
        $this->value = $value;
        $this->validate = $validate;
        $this->description = $description;
        $this->class = $class;
    }

    function render(){
        $id = idfy($this->name);
        JS::loadjQuery();
/*
        Head::add('3rdParty/tiny_mce/tiny_mce_gzip.js', 'js-url', true, false, false);
*/
/*
        Head::add('tinyMCEGZinit', 'js-lib', true, false, false);
*/
        //Head::add('3rdParty/tiny_mce/tiny_mce.js', 'js-url', true, false, false);
/*
        Head::add('tinyMCEinit', 'js-lib', true, false, false);
*/
//testar ny editor
Head::add('3rdParty/ckeditor/ckeditor.js', 'js-url', false, false);
Head::add('3rdParty/ckeditor/adapters/jquery.js', 'js-url', false, false);
JS::raw('CKEDITOR.replace("'.$id.'",{customConfig : "/3rdParty/ckeditor/config.js"});');

        return '<span class="formelem">'.($this->label === false ? '':'<label for="'.$id.'">'.$this->label.'</label>')
/*
        .'<div class="tinyMCEtoggle" style="text-align: right; width: 100%; display: hidden;">
            <span class="editor_type">'.__('Select editor').': </span><select name="editor_type" class="editor_type">
                <option value="simple">'.__('Simple').'</option>
                <option value="advanced">'.__('Advanced').'</option>
                <option value="ckeditor">ckeditor</option>
                <option value="off">'.__('Off').'</option>
            </select></div>'
*/
            .'<textarea id="'.$id.'" name="'.$this->name.'" class="textarea mceEditor'.($this->validate?' '.$this->validate:'').($this->validate?' ':'').$this->class.'" rows="8" cols="70">'.$this->value.'</textarea>'
                    .(strpos($this->validate, 'required')!==false?'<span class="reqstar">*</span>':'')
                .($this->description?'<span class="description">'.$this->description.'</span>':'')
                .'</span>';
    }
}
?>
