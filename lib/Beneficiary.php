<?php
abstract class Beneficiary extends Page {
    private $_settings = false;
    private $_mygroupIds = false;
    private $_parentGroups = false;

    function __construct($id, $lang=false) {
        parent::__construct($id, $lang);
        $this->_settings = new Settings($this->ID);
    }

    /**
    * Returns the value of the variable asked for. If the property is unknown, question is passed to parent class.
    * @access public
    * @param string $property The property asked for
    * @return mixed
    */
    function __get($property){
        if(!in_array($property, array('groups', 'groupIds', 'settings'))) return parent::__get($property);
        switch($property) {
            case 'settings': return $this->{'_'.$property};
                break;
            case 'groupIds':
            case 'groups': return $this->groups(($property == 'groups'));
        }
    }

    function loadMyGroups($force=false) {
        if($this->_mygroupIds !== false && !$force) return;
        global $DB, $Controller;
        $this->_mygroupIds = $DB->asList("SELECT `spine`.`id`,`spine`.`id` FROM `spine` LEFT JOIN `metadata` ON `metadata`.`id` = `spine`.`id` AND `metadata`.`field` = 'GroupType' "
."WHERE `spine`.`class` = 'Group' AND ("
    ."((`metadata`.`value` != 'volpre' OR `metadata`.`value` IS NULL) AND `spine`.`id` IN (SELECT `group` FROM `group_members` WHERE `user`='".(int)$this->ID."'))"
    .(is_a($this, 'User') && $this->ID != NOBODY?" OR "
        ."(`metadata`.`value` = 'volpre' AND `spine`.`id` NOT IN (SELECT `group` FROM `group_members` WHERE `user`='".(int)$this->ID."'))"
    :'')
.")", true);
        if($this->ID != EVERYBODY_GROUP) {
            $this->_mygroupIds[] = EVERYBODY_GROUP;
            if($this->ID != NOBODY && $this->ID != MEMBER_GROUP) {
                $this->_mygroupIds[] = MEMBER_GROUP;
            }
        }
    }

    private function loadParentGroups() {
        if($this->_parentGroups === false) {
            global $Controller;
            $this->_parentGroups = array();
            $groups = $Controller->get($this->_mygroupIds,OVERRIDE);
            $parentGroups = array();
            foreach($groups as $g) {
                $parentGroups += $g->groups();
            }
            $this->_parentGroups = arrayExtract($parentGroups, 'ID');
        }
    }

    /**
     * Returns the groups that the object is a member of.
     *
     * @param $objects If true, return the groups as objects
     * @param $self -1 = Return all parent groups and their parent groups in turn etc
     * 0 = Return only the parents parents
     * 1 = Return only groups that the object is a direct member of
     * @return Array of numeric id's or objects
     */
    function groups($objects = true, $self = -1) {
        $this->loadMyGroups();
        if($self < 1) $this->loadParentGroups();
        $return = array();
        if($self) $return = $this->_mygroupIds;
        if($self < 1) $return += $this->_parentGroups;
        $return = array_values(array_unique($return));
        if($objects) {
            global $Controller;
            return $Controller->get($return, OVERRIDE);
        } else return $return;
    }


    /**
     * Adds the user to a given group
     * @param Group|integer $group Group object or ID to which to add the user
     * @return void
     */
    function addToGroup($group) {
        global $Controller;
        if(is_a($group, 'Base')) $group = $group->ID;
        if(is_numeric($group) && !in_array($group, $this->groups(false))) {
            if($g = $Controller->{(string)$group}('Group')) {
                if($g->addMember($this))
                {
                    $this->_mygroupIds[$group] = $group;
                    Log::write('Added user \'' . $this->username . '\' (id=' . $this->ID . ') to group \''.$g->Name . '\' (id=' . $g->ID . ')', 10);
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Checks if the user is member of a given group
     * @param Group|integer $group Group object or id which to test for membership in
     * @return bool
     */
    function memberOf($group, $quickndirty = false){
        if($quickndirty) {
            $this->loadMyGroups();
            if(is_object($group)) $group = $group->ID;
            return in_array($group, $this->_mygroupIds);
        }
        else {
            global $Controller;
            if(!is_object($group)) $group = $Controller->{(string)$group}('Group', OVERRIDE);

            return $group->isMember($this);
        }
    }

    /**
     * Remove the user/group from a group
     * @param Group|integer $group Group object or ID from which to remove the user
     * @return void
     */
    function removeFromGroup($group){
        global $Controller;
        if(is_a($group, 'Base')) $group = $group->ID;
        if(is_numeric($group) && in_array($group, $this->groups(false, 1))) {
            if($g = $Controller->$group('Group')) {
                if($g->removeMember($this))
                {
                    unset($this->_mygroupIds[$group]);
                    Log::write('Removed user \'' . $this->username . '\' (id=' . $this->ID . ') from group \''.$g->Name . '\' (id=' . $g->ID . ')', 10);
                }
            }
        }
    }

    function deleteFromMenu() {
        return MenuItem::deleteFromMenu();
    }
}
