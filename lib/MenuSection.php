<?php

/**
 * @author Jonatan Olofsson [joolo]
 * @version 1.0
 * @copyright 2009
 * @package Menu
 */

/**
 * MenuSections are a means of creating separations in the menu between different sections of a site.
 * They are invisible in the menu, but can be used to for example define starting points for a menu.
 * @package Menu
 */
class MenuSection extends MenuItem {
    public $privilegeGroup = 'Sections';
    private $_template = false;

    /**
     * Pass the contruction call the parent and set up the name-alias relationship
     * @param integer $id The id of the object
     * @param string $alias The alias and name of the menusection
     * @return void
     */
    function __construct($id=false){
        parent::__construct($id);

        $this->loadAliases();
        $this->Name = $this->alias;
    }

    /**
     * Sustains the name-alias relationship by synchronizing the updates of the both before passing the request on to
     * the parent's __set()
     * @see solidbase/lib/MenuItem#__set($property, $value)
     */
    function __set($property, $value){
        global $USER;
        if($property == 'template') {
            $ipn = '_'.$property;
            $this->sload();
            if($this->may($USER, EDIT)) {
                if($this->$ipn !== false) {
                    if($value != @$this->$ipn) {
                        Metadata::$metameta = false;
                        Metadata::set($property, $value);
                    }
                }
            }
            $this->$ipn = $value;
        } else {
            if($property === 'Name') $property = 'alias';
            parent::__set($property, $value);
        }
    }

    /**
     * retrieve information about the object
     * @see lib/MenuItem#__get($property)
     */
    function __get($property) {
        if($property == 'template') {
            $this->sload();
            if(empty($this->_template)) return 'inherit';
            else return $this->{'_'.$property};
        }
        elseif($property == 'Name') return parent::__get('alias');
        else return parent::__get($property);
    }

    function sload($force=false){
        if($this->sloaded && !$force) return;
        $this->sloaded = true;
        $this->getMetadata();
    }
    private $sloaded = false;



    /**
     * Permission-test overload to allow display if there are any items under the section that allow so
     * @see solidbase/lib/Base#may()
     */
    function may($beneficiary, $accessLevel, $inherit=false) {
        global $Controller, $DB;
        $p = parent::may($beneficiary, $accessLevel);
        if(is_bool($p) || $this->RECURSION || !($accessLevel & READ) || !$inherit) return $p;
        $this->RECURSION = true;
        $o = $Controller->any($DB->menu->asList(array('parent' => $this->ID)), $accessLevel, $beneficiary);
        $this->RECURSION = false;
        return ($o?true:0);
    }
    private $RECURSION=false;
}
?>