<?php 
class Breadcrumbs {
    function __construct() {
        global $CURRENT, $USER;
        $p = array_merge($CURRENT->parents, array($CURRENT));
        $r='<ul>';
        $c = count($p)-1;
        foreach($p as $i => $crumb) {
            if(is_a($crumb, 'MenuSection')) continue;
            if(!$crumb->may($USER, READ)) break;
            $r .= '<li'.($i==$c?' class="current"':'').'>';
            if(@$crumb->link === false) $link = array("id" => $crumb->ID);
            else $link = $crumb->link;
            if($i!=$c) $r .= '<a href="' . url($link) . '">';
            $r .= $crumb;
            if($i!=$c) $r .= '</a> >';
            $r .= '</li>';
        }
        $r .= '</ul>';
        echo $r;
    }
}
?>