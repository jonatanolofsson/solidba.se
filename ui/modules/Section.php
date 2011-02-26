<?php
/**
 * @author Jonatan Olofsson [joolo]
 * @version 1.0
 * @copyright 2008
 * @package Content
 */
/**
 * Section
 * Defines a section in the template which can be filled with static or dynamical content
 * @package Content
 */
class Section{
    /**
     * Defines a new section
     * @param string $section Section name
     * @param bool $echo Wether to print the result or return it (defaults to true)
     * @return void|string
     */
    function __construct($section){
        global $PAGE;
        echo $PAGE->getContent($section);
    }
}

?>
