<?php

/**
 * @author Jonatan Olofsson [joolo]
 * @version 1.0
 * @copyright 2008
 * @package Menu
 */

/**
 * Handles the menu...
 * @package Menu
 */
class Menu{
    private $fMenu;

    /**
     *
     * @param string $which Which (alias) the menu should begin rendering from
     * @param bool $quiet If set to false, the menu will not be printed, rather just put in $fMenu.
     * @return void
     */
    function __construct($which, $maxlevel=false, $extendActive=false, $ignore_sections=false, $quiet=false, $includeDescription=false, $excludeSelf = false){
    global $DB, $Controller, $CURRENT, $USER;
        if($extendActive == -1) $extendActive = 10e4;
        if(is_string($which) || is_numeric($which)) $which = $Controller->{(string)$which}(OVERRIDE);
        if($which
            && ((is_a($which, 'MenuSection') && $which->may($USER, READ, true))
                || $which->mayI(READ))) {
            $which = $which->ID;
        } else return false;

        if(!is_numeric($which) || !$which) return false;
        $ids = array();
        $AllObjects = array();
        $parents = array();
        $pids = array($which);
        $active = $CURRENT->parentIDs;
        $active[] = $CURRENT->ID;

        $menuparentObject = $Controller->$which;
        if(!is_a($menuparentObject, 'MenuSection') && !$excludeSelf) {
            $AllObjects[$which] = $menuparentObject;
        }
        $i=0;

        while(count($pids)>0) {
            $dbids = $DB->menu->asList(array('parent' => $pids), 'id', false, false, 'place');
            if(!$dbids) break;
            $newO = $Controller->get($dbids, OVERRIDE, false, false);
            $newObjects = array();
            global $USER;
            foreach($newO as $o) {
                if(is_a($o, 'MenuSection')) {
                    if($o->may($USER, READ, true)) {
                        $newObjects[] = $o;
                    }
                } elseif($o->mayI(READ)) $newObjects[] = $o;
            }


            $pids = array();
            foreach($newObjects as $obj) {
                if(is_a($obj, 'MenuSection')) {
                    if(!$ignore_sections) {
                        if($singleDepthIDS = $this->singleVirtualDepth($obj->ID)) {
                            $AllObjects = arrayKeyMergeRecursive($AllObjects, $Controller->get($singleDepthIDS, READ, false, false));
                            $parents = arrayKeyMergeRecursive($parents, array_fill_keys($singleDepthIDS, $obj->parentID));
                            $pids = array_merge($pids, $singleDepthIDS);
                        }
                    }
                } else {
                    $AllObjects[$obj->ID] = $obj;
                    $pids[] = $obj->ID;
                    $parents[$obj->ID] = $obj->parentID;
                }
            }
            if($maxlevel !== false) {
                $maxlevel--;
                if($maxlevel <= 0) {
                    $pids = array_intersect($pids, $active);
                }
                if($extendActive !== true && $maxlevel + $extendActive <= 0) break;
            }
        }

        $menu = array();
        foreach($AllObjects as $m) {
            if(is_a($m, 'MenuItem') && $m->isActive()) {
                $menu[] = array(	"id" => $m->ID,
                                    "parent" => @$parents[$m->ID],
                                    "item" => $m);
            }
        }
        $inflatedMenu = inflate($menu);
        $this->fMenu = $this->format($inflatedMenu, $includeDescription);
        if(!$quiet) echo $this->fMenu;
    }

    /**
     * This function returns the ID's of all menuitems on the same level, i.e. ignoring MenuSections in the hierarchy
     * @param int $id ID of the parent
     * @return array
     */
    private function singleVirtualDepth($id) {
        global $DB;
        $IDS = array();
        $r = $DB->{'menu,spine'}->get(array('menu.parent' => $id), 'spine.id,spine.class', false, 'place');
        while($row = Database::fetchAssoc($r)) {
            if($row['class'] == 'MenuSection')
                $IDS = array_merge($IDS, $this->singleVirtualDepth($row['id']));
            else $IDS[] = $row['id'];
        }
        return $IDS;
    }

    /**
     * Simplifies output
     * @return string
     */
    function __toString() {
        return (string)$this->fMenu;
    }

    /**
    * Formats the menu
    * @access private
    * @param array $list Inflated list
    * @param bool $first This variable is only used internally for recursion handling
    * @return string
    */
    private function format($list, $includeDescription=false){
        global $ID, $USER, $CURRENT;
        static $level = -1;
        $level++;
        $r = "";
        $c=0;
        $active = array_merge($CURRENT->parentIDs, array($CURRENT->ID));
        if($level===0) $c = count($list)-1;
        $i=0;
        foreach($list as $item)
        {
            if(is_array($item))
            {
                if(!is_a($item['item'], 'MenuSection')) {
                    $classes = array();
                    if($level === 0) {
                        if($i==0) $classes[] = 'mfirst';
                        if($i==$c) $classes[] = 'mlast';
                    }
                    if(in_array($item['id'], $active)) {$classes[] = 'activeli';}
                    $r .= '<li'.(empty($classes) ? '' : ' class="'.join(' ', $classes).'"').'>';
                    if($item['item']->link === false) $link = array("id" => ($item['item']->alias?$item['item']->alias:$item['item']->ID));
                    else $link = $item['item']->link;
                    $class="";
                    if($item['id'] == $ID) $class.=" current";
                    if(in_array($item['id'], $active)) $class.=" active";
                    if(isset($item['children']) && is_array($item['children']) && !empty($item['children']))
                        $class .= ' daddy';
                    $r .= '<a href="' . url($link, false, true, false, false) . '"'
                    .($class?' class="'.substr($class, 1).'"':'')
                    .'>' . ($item['item']->icon != false ? icon($item['item']->icon):'')
                    . $item['item'];
                    if($includeDescription) $r.= '<span>'.$item['item']->description.'</span>';
                    $r .= '</a>';

                    if(isset($item['children']) && is_array($item['children']) && !empty($item['children']))
                        $r .= $this->format($item['children'], $includeDescription);

                    $r .= '</li>';
                } else {
                    if(isset($item['children']) && is_array($item['children']) && !empty($item['children']))
                        $r .= $this->format($item['children'], $includeDescription);
                }
            }
            $i++;
        }

        if(!empty($r)) $r = "<ul".($level===0?' class="menu"':'').">".$r."</ul>";
        $level--;

        return $r;
    }


    /**
     * Output the submenu from a page
     * @param $page Which page
     * @return void
     */
    function subMenu($page=false)
    {
        global $Controller, $PAGE;

        $UP = false;
        if(!$page) $page = $PAGE;
        elseif(is_numeric($page) || is_string($page)) {
            $page = $Controller->{(string)$page};
        }
        $args = func_get_args();

        call_user_func_array(array($page, 'subMenu'), $args);
    }
}

?>
