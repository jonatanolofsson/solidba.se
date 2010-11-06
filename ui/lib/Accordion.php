<?php
/**
 * @author Jonatan Olofsson [joolo]
 * @version 1.0
 * @package GUI
 * @license http://creativecommons.org/licenses/by-nc/3.0/ Creative Commons Attribution-Noncommercial 3.0 Unported License
 */
/**
 * This class provides a simple means to create an accordion
 * @author Jonatan Olofsson [joolo]
 * @package GUI
 */
class Accordion{
    private $tabs;
    public $params = false;
    /**
     * Constructor
     * Usage example:
     * <code>
     *new Accordion(
    *		__('Tab #1'),
    *			'Some content',
    *		__('Tab #2'),
    *			'Some content for the second tab'
    *);
     * </code>
     * @access protected
     */
    function __construct(){
        $a = func_get_args();
        $this->tabs = $a;
    }
    
    /**
     * Returns the output when asked to convert to string
     * @return string
     */
    function __toString() {
        return $this->render();
    }

    /**
     * Outputs the code and content for the accordion
     * @return string
     */
    public function render() {
        global $SITE, $CONFIG;
        JS::loadjQuery();
        JS::raw('$(function() {$(".ui-accordion-container").accordion({ header: "h3" '.($this->params ? ','.$this->params : '').'});});');
        Head::add($CONFIG->UI->jQuery_theme.'/jquery-ui-*', 'css-lib');
        
        $r  = '<ol class="ui-accordion-container">';
        $mode=0;
        $title=false;
        $selected=false;
        $i=0;
        foreach($this->tabs as $arg) {
            if($mode==0) {
                $title = $arg;
                $mode = 1;
            } else {
                $r .= '
                    <li>
                        <h3><a href="#">'.$title.'</a></h3>
                        <div>'.$arg.'<p><small>&nbsp;</small></p></div>
                    </li>';
                
                $mode = 0;
                $selected = false;
                $title = false;
                $i++;
            }
        }
        return $r.'</ol>';
    }
}
?>