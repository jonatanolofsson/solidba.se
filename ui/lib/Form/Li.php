<?php
/**
 * @author Jonatan Olofsson [joolo]
 * @version 1.0
 * @license http://creativecommons.org/licenses/by-nc/3.0/ Creative Commons Attribution-Noncommercial 3.0 Unported License
 * @package GUI
 */

/**
 * The Li(st) element sums up it's arguments and returns it as a single form element
 * @package GUI
 */
class Li {
    private $content;
    /**
     * Constructor
     * @access protected
     */
    function __construct(){
        $this->content = func_get_args();
    }

    function __toString() {
        return $this->render();
    }

    /**
     * Add an element to the end of the set
     * @param mixed $what What's to be appended
     * @return void
     */
    function add($what) {
        $this->content[] = $what;
    }

    function render(){
        $value = '';
        foreach($this->content as $a) $value .= (string)$a;
        if(strpos($value, '<span class="reqstar">*</span>'))
        {
            $value = str_replace($value, '<span class="reqstar">*</span>', '').'<span class="reqstar">*</span>';
        }
        return '<span class="formli">'.$value.'</span>';
    }
}
?>
