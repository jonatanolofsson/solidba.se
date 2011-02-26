<?PHP

class Icon {
    public $which, $alt, $url, $print_alt, $class;

    function __construct($which, $alt='', $url=false, $print_alt = false, $class=false) {
        $this->which    = $which;
        $this->alt      = $alt;
        $this->url      = $url;
        $this->print_alt= $print_alt;
        $this->class    = $class;
    }

    function render() {
        $r='<span class="nobr">';
        if($this->url) $r.='<a href="'.$this->url.'" title="'.(!$this->print_alt?$this->alt:'').'"'
            .($this->class?' class="'.$this->class.'"':'').'>';

        $r.='<img src="/3rdParty/icons/'.$this->which.'.png" title="'.(!$this->print_alt?$this->alt:'').'" alt="'.$this->alt.'" class="icon'.(($this->class && !$this->url)?' '.$this->class:'').'" />';
        if($this->print_alt) $r .= $this->alt;
        if($this->url) $r.='</a>';
        return $r.'</span>';
    }

    function __toString() {
        return $this->render();
    }
}
