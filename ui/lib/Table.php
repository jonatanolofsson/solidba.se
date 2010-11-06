<?php
class Table {
    public $content=array();
    public $class=false;
    public $id=false;
    public $border=0;
    public $style;
    protected $tag = 'table';
    protected $subtag = false;
    
    
    function __construct() {
        $args = func_get_args();
        $this->content = flatten($args);	
    }
    
    function __toString() {
        return $this->render(false);
    }
    
    function render($echo=false) {
        $res = '<'.$this->tag.($this->tag == 'table'?' border="'.$this->border.'" cellpadding="0" cellspacing="0"':'').($this->style?' style="'.$this->style.'"':'').($this->class?' class="'.$this->class.'"':'').($this->id?' id="'.$this->id.'"':'').'>';
        $i=1;
        foreach($this->content as $c) {
            if(is_a($c, 'tablerow')) $c->class = $c->class.($i++%2?'odd':'even');
            $res .= ($this->subtag?'<'.$this->subtag.' class="'.($i++%2?'odd':'even').'">':'').$c.($this->subtag?'</'.$this->subtag.'>':'');
        }
        $res .= '</'.$this->tag.'>';
        if($echo) echo $res;
        return $res;
    }
    
    function append() {
        $args = func_get_args();
        $this->content = array_merge($this->content, flatten($args));	
    }
}

class tablerow extends Table {
    protected $tag = 'tr';
    protected $subtag = 'td';
}
class tableheader extends tablerow {
    protected $subtag = 'th';
}
?>