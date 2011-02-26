<?php
/**
 * @author Jonatan Olofsson [joolo]
 * @version 1.0
 * @license http://creativecommons.org/licenses/by-nc/3.0/ Creative Commons Attribution-Noncommercial 3.0 Unported License
 * @package Menu
 */
/**
 * This class adds an abstraction layer which keeps track of the position in the menu
 * @package Menu
 */
class Formsection {
    public $elements;
    public $header;
    function __construct() {
        $this->elements = func_get_args();
        $this->header = array_shift($this->elements);
    }

    function __toString() {
        return $this->render();
    }

    function render() {
        JS::loadjQuery();
        JS::lib('viewslider');
        JS::raw('$(function(){$("div.viewslider-view").closest(".formdiv").viewslider();});');
        Head::add('viewslider/viewslider', 'css-lib');
        return '<div class="formsection viewslider-view"><h3>'.$this->header.'</h3>'.implode('', flatten($this->elements)).'</div>';
    }
}

?>
