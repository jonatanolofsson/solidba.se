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
class MenuItem extends Base{
    private $_description=false;
    static private $PARENTS=array();
    static private $PLACES=array();
    private $_children=false;
    private $_link = false;
    private $_enabled = true;
    private $_icon = false;
    protected $_deletable = true;
    protected $_loadedLanguage=false;

    function __construct($id, $language=false){
        parent::__construct($id);
        global $USER;

        if($USER) {
            if(!$language) $language = $USER->settings['language'];
            $this->_loadedLanguage = $language;
        }
    }

    function suggestName($name, $language='en') {
        if($language == $this->_loadedLanguage && !$this->Name) $this->Name = $name;
    }

    function preload($ids, $aLEVEL=false) {
        parent::preload($ids, $aLEVEL);
        global $DB;
        $ids = array_diff($ids, array_keys(self::$PARENTS));
        if($ids) {
            $r = $DB->menu->get(array('id' => $ids), 'id,parent,place');
            while(false !== ($data = Database::fetchAssoc($r))) {
                self::$PARENTS[$data['id']] = $data['parent'];
                self::$PLACES[$data['id']] = $data['place'];
            }
        }
    }


    /**
     * Set which
     * @param array|strings Variable number of tags
     * @return void
     */
    function setTags() {
        $Tags = func_get_args();
        if(count($Tags)==1 && is_array($Tags[0])) $Tags = $Tags[0];
        $OldTags = $this->getTags();
        $remove = array_diff($OldTags, $Tags);
        $add 	= array_diff($Tags, $OldTags);

        global $DB;
        if(!empty($remove)) {
            $DB->tags->delete(array('id' => $this->ID, 'lang' => $this->_loadedLanguage, 'tag' => $remove));
        }
        if(!empty($add)) {
            foreach($add as $a) {
                $DB->tags->insert(array('id' => $this->ID, 'lang' => $this->_loadedLanguage, 'tag' => $a), false, true);
            }
        }
        $this->Tags = $Tags;
    }

    /**
     * Get all the tags from the database
     * @return array
     */
    function getTags($force=false, $d=false) {
        if(!$force && $this->tagged) return $this->_Tags;
        $this->tagged = true;
        global $DB;
        $this->_Tags = $DB->tags->asList(array('id' => $this->ID, 'lang' => $this->_loadedLanguage), 'tag');
        return $this->_Tags;
    }

    /**
     * Sets the variables of the object and updates the database if nescessary.
     * Unrecognized properties are forwarded to it's parent
     * @param string $property The property which to change
     * @param mixed $value The new value of the property
     * @see solidbase/lib/Base#__set($property, $value)
    */
    function __set($property, $value){
        if(in_array($property, array('place', 'enabled', 'link', 'icon', 'parent', 'description', 'Name'))) {
            if($this->ID) {
                $ipn = $property;
                switch($property){
                    case 'place':
                        $this->move($value);
                        break;
                    case 'enabled':
                        $this->enabled = $value;
                        break;
                    case 'description':
                        $ipn = '_'.$property;
                    case 'Name':
                        $this->getSelf();
                        if($value != @$this->$ipn && @$this->$ipn !== false && @$this->$ipn !== null) {
                            Metadata::$metameta = $this->_loadedLanguage;
                            Metadata::set($property, $value);
                        }
                        if($property == 'Name') {
                            parent::__set($property, $value);
                        }
                        else $this->$ipn = $value;
                        break;
                    case 'link':
                        $this->_link = $value;
                        break;
                    case 'icon':
                        $this->_icon = $value;
                        break;
                    case 'parent':
                        if(@$Controller->alias('menu_editor')->mayI(EDIT)) {
                            $this->move('last', $value);
                        }
                        break;

                 }
                return true;
            }
        } else {
            parent::__set($property, $value);
        }
    }

    /**
     * Returns the data of a given property. Unrecognized properties are forwarded to it's parent.
     * @see solidbase/lib/Base#__get($property)
    */
    function __get($property){
        if($property == 'ID') return parent::__get($property);
        if(in_array($property, array('Name', 'loadedLanguage', 'description'))) $this->getSelf();
        if(in_array($property, array('parent', 'parentID', 'link', 'enabled', 'place', 'icon', 'deletable', 'description', 'loadedLanguage'))) {
            if(in_array($property, array('parent', 'parentID', 'place'))) $this->getMenuPos();
            switch($property) {
                case 'parent':
                    if (isset(self::$PARENTS[$this->ID]) && self::$PARENTS[$this->ID]) {
                        global $Controller;
                        return $Controller->{(string)self::$PARENTS[$this->ID]}(OVERRIDE);
                    } else return false;
                    break;
                case 'parentID':
                    return @self::$PARENTS[$this->ID];
                    break;
                case 'place':
                    if(isset(self::$PLACES[$this->ID]))
                        return self::$PLACES[$this->ID];
                    else return false;
                    break;
                default:
                    return $this->{'_'.$property};
            }
        } elseif(in_array($property, array('children', 'next', 'previous'))) {
            return $this->$property();
        } elseif(in_array($property, array('parentIDs', 'parents'))) {
            return $this->parents(($property == 'parents'));
        } else {
            return parent::__get($property);
        }
    }

    /**
     * Loads the menudata on the object from the database.
     * The data is loaded once, then not reloaded unless excplicitly told to do so
     * @param bool $force Force reload
     * @return bool
    */
    function getSelf($force=false){
        global $USER;
        if(!$this->_loadedLanguage && $USER) $this->_loadedLanguage = $USER->settings['language'];
        if(!$this->__gotten || $force) {
            $this->__gotten = true;
            global $Controller, $DB;
            $this->getMetadata(array($this->_loadedLanguage, ''), $force, array('description', 'Name'));
        }
        return true;
    }
    private $__gotten=false;

    /**
     * Loads the menu relation on the object from the database.
     * @return bool
    */
    function getMenuPos($force=false){
        if(!isset(self::$PLACES[$this->ID])) self::preload(array($this->ID));
    }


    /**
     * outputs a link to the current object
     * @param $echo Send the link to output
     * @return string
     */
    public function link($echo = false, $arr = false) {
        if(!$this->link) return parent::link($echo, $arr);
        $r = '<a href="'.$this->_link.'">'.$this.'</a>';
        if($echo) echo $r;
        return $r;
    }
    /**
     * Returns which place in the menu the object is on
     * @return integer
     */
    function place(){
        $this->getSelf();
        return self::$PLACES[$this->ID];
    }

    /**
     * Returns the (menu)parent's ID or object
     * @param $return_obj If set to true (default), the parent will be returned as an object. If false, the ID is returned
     * @return mixed
     */
    function parent($return_obj=true) {
        $this->getMenuPos();
        if(!$return_obj) return self::$PARENTS[$this->ID];
        return $this->parent;
    }

    /**
     * Returns the (menu)parents
     * @param $return_obj If set to true (default), the parent will be returned as an object. If false, the ID is returned
     * @param $force_reload Forces a recalculation of relations
     * @return array
     */
    function parents($return_obj=true){
        $this->getMenuPos();
        $cid = $this->ID;
        $parents = array();
        while($cid > 0) {
            while(isset(self::$PARENTS[$cid])) {
                 $cid = self::$PARENTS[$cid];
                 if($cid) $parents[] = $cid;
            }
            if($cid) self::preload(array($cid));
            if(!isset(self::$PARENTS[$cid])) break;
        }

        global $Controller;
        return ($return_obj?$Controller->get($parents, OVERRIDE):$parents);
    }

    /**
     * Returns the children of a menuitem
     * @param $return_objs Wether to return objects or id's
     * @param $force_reload Force reload from Database
     * @return array Array of integer ID's or objects
     */
    function children($return_objs=true, $force_reload=false){
        global $DB, $Controller;
        if($this->_children && !$force_reload) {
            if(!$return_objs) return array_keys($this->_children);
            else return $this->_children;
        }
        $this->_children = $DB->menu->asList(array('parent' => $this->ID), 'id');
        if($return_objs) return $Controller->get($this->_children, OVERRIDE);
        else return $this->_children;
    }

    /**
     * Returns the next sibling to the object
     * @param $return_obj If set to true (default), the parent will be returned as an object. If false, the ID is returned
     * @return mixed
     */
    function next($return_obj=true, $force_reload=false){
        global $Controller, $DB;
        if($this->_next !== null && !$force_reload) {
            if(!$this->_next) return false;
            if($return_obj) return @$Controller->get($this->_next);
            else return $this->_next;
        }
        $this->getMenuPos();
        $e = $Controller->get($DB->menu->asList("place > ".self::$PLACES[$this->ID]." AND parent = '".self::$PARENTS[$this->ID]."'", 'id', false, false, 'place ASC'));
        if(!count($e)) {
            $this->_next = false;
            return false;
        }
        reset($e);
        $e = current($e);
        $this->_next = $e->ID;
        if($return_obj) return $e;
        return $this->_next;
    }
    private $_next=null;

    /**
     * Returns the previous sibling to the object
     * @param $return_obj If set to true (default), the parent will be returned as an object. If false, the ID is returned
     * @return mixed
     */
    function previous($return_obj=true){
        global $Controller, $DB;
        if($this->_previous !== null && !$force_reload) {
            if(!$this->_previous) return false;
            if($return_obj) return @$Controller->{(string)$this->_previous};
            else return $this->_previous;
        }
        $this->getMenuPos();
        $e = $Controller->get($DB->menu->asList("place < ".self::$PLACES[$this->ID]." AND parent = '".self::$PARENTS[$this->ID]."'", 'id', false, false, 'place DESC'));
        if(!$e) {
            $this->_previous = false;
            return false;
        }
        reset($e);
        $this->_previous = current($e);
        if(!$return_obj) return @$this->_previous->ID;
        else return $this->_previous;
    }
    private $_previous=null;

    /**
     * Moves the object to a new place in the menu.
     * Note that the place number is calculated among the siblings, so the place is relative to the parent
     * @param integer $newPlace The place to which the object should be moved to
     * @param integer|object $parent Parent ID or object. 0 if none.
     * @return bool
     */
    function move($newPlace, $parent=false){
        global $DB, $Controller, $USER;
        $this->getMenuPos();
        if($this->ID===false || $newPlace < 0) return false;
        //if($Controller->menuEditor(EDIT)) {
            if($parent === false) $parent = $this->parent;
            if($parent == false) $parent = 0;
            elseif(is_numeric($parent) || is_string($parent)) $parent = $Controller->{(string)$parent};

            if(!is_object($parent) && $parent != 0) return false;
            if(is_object($parent)) {
                if (!($parent->may($USER, EDIT))) {
                    // only move if user has edit permission on the parent
                    return false;
                }
                $pid = $parent->ID;
            }
            else {
                if (!($Controller->menuEditor(EDIT))) {
                    // menu root is off limits unless you have edit permissions on the menu editor
                    return false;
                }
                $pid = 0;
            }
            $length = $DB->menu->count(array("parent" => $pid));
            if($newPlace === 'last' || $newPlace > $length) $newPlace = $length;
            if(!is_numeric($newPlace)) return false;
            if($this->place == $newPlace && $this->parentID == $pid) return true;
            $oldParent = $this->parentID;
            $oldPlace = $this->place;
            $tonext = (	$oldParent == $pid
                        && $this->place !== false
                        && $newPlace == self::$PLACES[$this->ID] + 1);

            $DB->menu->update(	array('!!place' => '(`menu`.`place`+1)'),
                                array('place>'.($tonext?'':'=') => $newPlace, 'parent' => $pid),
                                false, false);
            $a = $DB->menu->update(	array(	"parent" => $pid,
                                            "place"  => $newPlace+$tonext),
                                    array('id' => $this->ID),
                                true);
            if($oldPlace !== false) {
                $DB->menu->update(	array('!!place' => '(`menu`.`place`-1)'),
                                    array('place>' => $oldPlace, 'parent' => $oldParent),
                                    false, false);
            }
            self::$PLACES[$this->ID] = $newPlace;
            self::$PARENTS[$this->ID] = $pid;
            return true;
        //}
    }

    /**
     * Permission-test overload to allow inheriting permissions from parent menuitem
     * @see solidbase/lib/Base#may()
     */
    function may($u, $lvl) {
        if(is_bool($pr = parent::may($u, $lvl))) {
            return $pr;
        }
        $this->getMenuPos();
        if($this->parent) {
            $pr = $this->parent->may($u,$lvl);
        }
        return $pr;
    }

    /**
     * Deletes self and passes the call to parent class
     * @see solidbase/lib/Base#delete()
     */
    function delete(){
        global $USER;
        if($this->may($USER, DELETE)) {
            self::deleteFromMenu();
            return parent::delete();
        } return false;
    }

    function deleteFromMenu(){
        global $DB, $Controller, $USER;
        if($Controller->menuEditor->may($USER, EDIT) || $this->may($USER, DELETE)) {
            $this->getSelf();
            $DB->menu->delete($this->ID);
            if(isset(self::$PLACES[$this->ID]) && self::$PLACES[$this->ID] != null) {
                $DB->query("UPDATE menu SET place=(place-1) WHERE place>'".$this->place
                    ."' AND parent".($this->parentID<=0?'<=0':"='".$this->parentID."'"));
            }
            self::$PLACES[$this->ID] = false;
            return true;
        } return false;
    }




    /**
     * Render a menu starting from the specified page
     * @param $page The page from which to start the submenu
     * @param $maxlevel Maximum depth shown
     * @param $extendActive Number of levels to extend the active menutree. May override maxlevel
     * @param $ignore_sections Do not dig into new menusections when found
     * @param $quiet Don't print the menutree
     * @param $includeDescription Include the page's description in the output
     * @param $excludeSelf Don't display the menu root itself
     * @return void
     */
    function subMenu($page=false, $maxlevel=false, $extendActive=false, $ignore_sections=false, $quiet=false, $includeDescription=false, $excludeSelf = true) {
        global $PAGE, $Controller, $DB;

        $UP = false;
        if(!$page) $page = $PAGE;
        elseif(is_numeric($page) || is_string($page)) {
            $page = $Controller->{(string)$page};
        }

        if($page->parent && $page->parent->isMe('errorPages')) {
            new Menu($page->ID);
        } else {
            $parents = $page->parents(false);
            $msparents = array_intersect($parents, $DB->spine->asList(array('id' => $parents, 'class' => 'MenuSection'), 'id'));
            $par = array_shift($msparents);
            array_pop($msparents);

            if($msparents) {
                do {
                    $UP = $parents[array_search(array_shift($msparents), $parents)-1];
                }while($msparents && !in_array($UP, $msparents));
                echo '<span class="menuUP">'.icon('large/2uparrow-16', __('Up'), url(array('id' => $UP))).'</span>';
            }

            new Menu($par, $maxlevel, $extendActive, $ignore_sections, $quiet, $includeDescription, $excludeSelf);

            //FIXME: Remove or make it work
/*
            if(isset($page->parents[1]->content['Subnav_extras']))
                echo $page->parents[1]->content['Subnav_extras'];
            else new Section('Subnav_extras');
*/

            /* uglyhax to infalte div if menu empty */
            echo "&nbsp;";
        }
    }
}

?>
