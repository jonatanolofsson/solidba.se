<?php
/**
 * @author Jonatan Olofsson [joolo]
 * @version 1.0
 * @license http://creativecommons.org/licenses/by-nc/3.0/ Creative Commons Attribution-Noncommercial 3.0 Unported License
 * @package GUI
 */

/**
 *
 *
 */
class FormText{
    var $label;
    var $text;
    /**
     * Constructor
     */
    function __construct($label, $text){
        $this->label = $label;
        $this->text = $text;
    }

    function render(){
        return '<span class="formelem">'.($this->label === false ? '':'<label>'.$this->label.'</label>')
            .($this->text?$this->text:'').
            '</span>';
    }
}
?>
