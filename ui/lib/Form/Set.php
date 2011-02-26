<?php
/**
 * @author Jonatan Olofsson [joolo]
 * @version 1.0
 * @license http://creativecommons.org/licenses/by-nc/3.0/ Creative Commons Attribution-Noncommercial 3.0 Unported License
 * @package GUI
 */

/**
 * Creates a set (without the field) to wrap the contents (using an ordered list)
 * @package GUI
 */
class Set{
    public $id;
    public $selected;
    public $disabled;
    public $name;
    public $deselectable;
    public $class;
    protected $content;

    /**
     * Accepts it's content as incoming arguments
     * @param mixed $content Contained elements
     * @return void
     */
    function __construct(){
        $a = func_get_args();
        $this->content = $this->__filter($a);
    }

    function count() {
        return count($this->content);
    }

    /**
     * Arrange and flatten the incoming elements
     * @param array $args
     * @return array
     */
    function __filter($args){
        $res = array();
        foreach($args as $a) {
            if(is_array($a)) $res = array_merge($res, $this->__filter($a));
            else $res[] = $a;
        }
        return $res;
    }

    /**
     * Simplify and standardize output
     * @return void
     */
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

    /**
     * Delivers the contents of the object, wrapped in an ordered list
     * @return void
     */
    function render(){
        $r = '<span class="formelem"><ol class="set'.($this->class?' '.$this->class:'').'">';
        $hidden = '';
        $i=1;
        foreach($this->content as $c) {
            if(is_object($c) && method_exists($c, 'render')) {
                if(is_a($c, 'Hidden')) $hidden .= $c->render();
                else
                    $r .= '<li class="'.($i++%2?'evenform':'oddform').'">'.$c->render().'</li>';
            } elseif(is_string($c)) $r .= '<li class="'.($i++%2?'evenform':'oddform').'">'.$c.'</li>';
        }
        return $r.'</ol>'.$hidden.'</span>';
    }
}
?>
