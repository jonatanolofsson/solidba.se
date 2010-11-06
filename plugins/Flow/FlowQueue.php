<?php
class Flowqueue extends Page {
    function __construct($id, $lang = false) {
        parent::__construct($id, $lang);
    }
    

    function run() {
        global $Templates, $DB, $Controller;
        $_REQUEST->setType('item', 'numeric');
        $PERPAGE = 5;
        
        if($_REQUEST['item']) {
            $obj = $Controller->{$_REQUEST['item']};
            $content = $obj->display(false,true,true,true);
        } else {
            $QUEUE = $this->ID;
    
            $COUNT = (int)$DB->flow->count(array('queue~' => $QUEUE), 'id');
            $pagination = Pagination::getRange($PERPAGE, $COUNT);
            $Objects = Flow::retrieve('News', $PERPAGE, false, false, false, $pagination['range']['start']);
            $content = '';
            $first = true;
            foreach($Objects as $obj) {
                $content .= '<li'.($first?'':' class="cols"').'>'.$obj->display(false,$first,true).'</li>';
                $first=false;
            }
            $content = '<ul>'.$content.'</ul>'.$pagination['links'];
        }
        $r = '<h1>FlowView</h1>'.Design::row(array(Design::column('<div class="padded">'.$content.'</div>', 8, true, true, true), 
                                Design::column('Möjlighet att följa nyheterna via RSS kommer... ',4, false, true, true)),true);

        $this->setContent('main', $r);
        $this->setContent('menu', $this->submenu());
        $Templates->yweb('empty')->render();
    }
    
    function subMenu($currentQueue=false,$currentTime=false) {
        $menu = '<div id="subnav"><ul class="menu"><li'.(($currentQueue || $_REQUEST['item'])?'':' class="activeli"').'><a href="'.url(null,'id').'">'.__('All news').'</a></li>'; //</ul>';

/*
        '<h2>'.__('By category').'</h2><ul class="menu">';
        foreach($cals as $cal){
            $menu .= '<li'.($cal == $currentCal?' class="activeli"':'').'><a href="'.url(array('cal' => urlencode($cal)),'id').'">'.$cal.'</a></li>';
        }
        $menu .= '</ul><h2>'.__('By time').'</h2><ul>';
        foreach($this->getTimeList() as $month) {
            if($month<10) $month = '0'.$month;
            $menu .= '<li'.($month == substr($currentTime,5,2)?' class="activeli"':'').'><a href="'.url(array('time' => (strftime('%G',time()).'-'.$month)),'id').'">'.ucfirst(strftime('%B',mktime(0,0,0,$month))).'</a></li>';
        }
*/
        $menu .= '</ul></div>';
        return $menu;
    }
}
?>
