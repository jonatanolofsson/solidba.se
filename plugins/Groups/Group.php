<?php
/**
 * Group.php
 * Here the Group class is defined, along with some constants
 * defining the ID's of the standard groups
 *
 * @author Jonatan Olofsson [joolo]
 * @version 1.5
 * @license http://creativecommons.org/licenses/by-nc/3.0/ Creative Commons Attribution-Noncommercial 3.0 Unported License
 * @package Privileges
 */

/**
 * Define the ID's of the system groups
  * @var integer
 */
global $CONFIG;
if(@$CONFIG->base->ADMIN_GROUP) {
    define('ADMIN_GROUP',@$CONFIG->base->ADMIN_GROUP);
    define('EVERYBODY_GROUP',@$CONFIG->base->EVERYBODY_GROUP);
    define('MEMBER_GROUP', @$CONFIG->base->MEMBER_GROUP);
}

/**
 * Each Group object represents a group, each containing any number of users, which in turn
 * inherits privileges from the groups
 * @package Privileges
 */
class Group extends Beneficiary{
    private $_GroupType = false;
    private $DBMemberTable = 'group_members';
    private $_MEMBERS=false;
    static public function installable() {return __CLASS__;}
    static public function uninstallable() {return __CLASS__;}

    public $editable = array(
        'PermissionEditor' => EDIT_PRIVILEGES,
        'GroupAdmin' => EDIT
    );

    function __construct($id) {
        parent::__construct($id);
        Base::registerMetadata('GroupType');
        Base::registerMetadata('DisplayMembers');
        Base::registerMetadata('Image');
    }

    /**
     * Returns the data of a given property. Unrecognized properties are forwarded to it's parent.
     * @see solidbase/lib/Base#__get($property)
     */
    function __get($property){
        switch($property) {
            case 'MEMBERS':
                $this->loadMembers();
            case 'GroupType':
                return $this->{'_'.$property};
            default: return parent::__get($property);
        }
    }

    /**
     * Load the group details
     * @param bool $reload Force reload
     * @return void
     */
    function loadMembers() {
        if($this->_MEMBERS !== false) return;
        global $Controller, $DB;
        if($this->ID == EVERYBODY_GROUP || $this->ID == MEMBER_GROUP) {
            $this->_MEMBERS = $DB->spine->asList(array('class' => 'User'), 'id', false, true);
            if($this->ID == MEMBER_GROUP) unset($this->_MEMBERS[NOBODY]);
        }
        elseif($this->GroupType == 'volpre') {
            $this->_MEMBERS = $DB->asList("SELECT `spine`.`id` FROM `spine` WHERE `spine`.`class` IN ('User','Group') AND `spine`.`id` NOT IN (SELECT `user` FROM `group_members` WHERE `group`='".(int)$this->ID."') AND `spine`.`id` != '".(int)$this->ID."'");
        } else {
            $this->_MEMBERS = $DB->group_members->asList(array('group' => $this->ID), 'user');
        }
    }

    function run() {
        global $Templates, $Controller;

        $r = '';
        if($this->Image && $img = $Controller->{$this->Image}) $r .= $img->htmltag();
        if($this->DisplayMembers) $r .= $this->userList();
        $this->appendContent('main', $r);

        parent::run();
    }

    function userList(){
        global $Controller;
        $this->loadMembers();
        $members = $Controller->get($this->_MEMBERS);
        $r = '<ul class="userlist">';
        foreach($members as $user){
            if(is_a($user, 'User'))
                //FIXME: Använda funktioner i User
/* 				$r .= $user->infoBox(); */
                $r .= '<li>'.icon('large/identity-64').'<span class="info"><h3>'.$user->Name.' ('.$user->username.')</h3></span></li>';
        }
        $r .= '<ul>';
        return $r;
    }

    /**
     * Returns all users that are members of either the group itself, or any of its subgroups.
     * @param bool If set, only members of the group itself (not any of its subgroups) are returned.
     * @return array
     */
    function memberUsers($onlyMe=false, $ids_only = false) {
        global $Controller, $DB;
        $this->loadMembers();

        $onlyMe = (bool)$onlyMe;
        if($this->_memberUsers[$onlyMe]) {
            $users = $this->_memberUsers[$onlyMe];
        }
        elseif($this->_MEMBERS) {
            $groups = $DB->spine->asList(array('class' => 'Group', 'id' => $this->_MEMBERS), 'id');
            if($groups) {
                $users = array_diff($this->_MEMBERS, $groups);
                if(!$onlyMe) {
                    foreach($groups as $group) {
                        if($group == EVERYBODY_GROUP || $group == MEMBER_GROUP) continue;
                        $group = $Controller->$group(OVERRIDE);
                        if($group) $users = array_merge($users, $group->memberUsers(false, true));
                    }
                }
            } else $users = $this->_MEMBERS;
            $this->_memberUsers[$onlyMe] = $users;
        } else return array();
        $users = array_unique($users);
        if($ids_only) return $users;
        else return $Controller->get($users, OVERRIDE);
    }
    private $_memberUsers = array(array(), array());

    /**
     * Checks if a certain user is a member of the group
     * @param $id
     * @return unknown_type
     */
    function isMember($id){
        global $DB, $USER, $Controller;

        if(is_a($id, 'Base')) $id = $id->ID;

        if($this->ID == EVERYBODY_GROUP) return true;
        if($this->ID == MEMBER_GROUP && $id != NOBODY) return true;

        $this->loadMembers();
        if(!$this->_MEMBERS) return false;

        if(in_array($id, $this->_MEMBERS)) return true;
        elseif($this->_GroupType == 'volpre') return false;

        if($this->memberGroups === false) {
            $this->memberGroups = $DB->spine->asList(array('class' => 'Group', 'id' => $this->_MEMBERS));
        }
        if(!$this->memberGroups) return false;


        $m = $this->_MEMBERS;

        foreach($this->memberGroups as $group) {
            $g = $Controller->$group(OVERRIDE);
            if($g->isMember($id)) {
                return true;
            }
        }
        return false;
    }
    private $memberGroups = false;

    /**
     * Adds a member to the group
     * @param $id
     * @return bool
     */
    function addMember($id) {
        global $DB, $Controller, $USER;
        if(is_numeric($id)) {
            $add = $Controller->{$id};
            if(!(is_a($add, 'User') || is_a($add, 'Group'))) return false;
        }
        elseif(is_a($id, 'User') || is_a($id, 'Group')) {
            $add = $id;
            $id = $id->ID;
        }
        else return false;

        $this->loadMembers();
        if($id == $this->ID || (is_a($add, 'Group') && in_array($id, $this->groups(false)))) return false;
        if((in_array($this->GroupType, array('vol', 'volpre')) && $id == $USER->ID)
             || $Controller->adminGroups(EDIT)) {
            if(!in_array($id, $this->_MEMBERS)) {
                if($this->_GroupType == 'volpre') {
                    $DB->group_members->delete(array('user' => $id, 'group' => $this->ID));
                } else {
                    $DB->group_members->insert(array('user' => $id, 'group' => $this->ID), false, true, true);
                }
                $this->_MEMBERS[] = $id;
                Log::write('Added new member \'' . $add->Name . '\' (id=' . $id . ') to group \'' . $this->Name . '\' (id=' . $this->ID . ')', 10);
            }
            return true;
        }
        return false;
    }

    function resetMembers() {
        global $DB, $Controller, $USER;
        /*
         * Prevent deletion of all the administrators
         */
        if($this->ID === ADMIN_GROUP) return false;

        if($Controller->adminGroups(EDIT)) {
            $DB->group_members->delete(array('group' => $this->ID), false);
            $this->_MEMBERS = array();
            Log::write('Reset members of group \'' . $this->Name . '\' (id=' . $this->ID . ')', 10);

            return true;
        } else
            return false;
    }

    /**
     * Adds new members to the group
     * @param $id
     * @return bool
     */
    function addMembers($ids) {
        global $DB, $Controller, $USER;

        $this->loadMembers();
        $add = array();
        $mygroups = $this->groups(false);
        foreach($ids as $id) {
            if($id == $this->ID || ($Controller->$id('Group') && in_array($id, $mygroups))) continue;
            $add[] = $id;
        }
        if(!$add) return false;
        if($Controller->adminGroups(EDIT)) {
            if($this->_GroupType == 'volpre') {
                $DB->group_members->delete(array('user' => $add, 'group' => $this->ID), false);
            } else {
                $DB->group_members->insertMultiple(array('user' => $add, 'group' => $this->ID), false, true, true);
            }
            $this->_MEMBERS[] = $id;
            Log::write('Added new members to group \'' . $this->Name . '\' (id=' . $this->ID . ')', 10);
            return true;
        }
        return false;
    }

    /**
     * Removes a member from the group
     * @param User|integer $id The user or the user ID to insert into the group
     * @return bool
     */
    function removeMember($id) {
        global $DB, $Controller, $USER;

        if(is_numeric($id)) {
            $obj = $Controller->get($id);
        }
        elseif(is_a($id, 'Base')) {
            $obj = $id;
            $id = $obj->ID;
        }
        else {
            return false;
        }

        if((in_array($this->GroupType, array('vol', 'volpre')) && $id == $USER->ID)
             || $this->mayI(EDIT)) {
            $this->loadMembers();
            if(in_array($id, $this->_MEMBERS) XOR $this->GroupType == 'volpre') {
                /*
                 * Prevent deletion of the last administrator
                 */
                if($this->ID === ADMIN_GROUP && count($this->_MEMBERS) == 1) return false;

                if($this->GroupType == 'volpre') {
                    $DB->group_members->insert(array('user' => $id, 'group' => $this->ID), false, true, true);
                } else {
                    $DB->group_members->delete(array('user' => $id, 'group' => $this->ID));
                }
                $this->_MEMBERS = arrayRemove($this->_MEMBERS, $id, true);
                Log::write('Removed member \'' . $obj->Name . '\' (id=' . $id . ') from group \'' . $this->Name . '\' (id=' . $this->ID . ')', 10);

                return true;
            } else return false;
        } else
            return false;
    }

    /**
     * Deletes self and passes the call to parent
     * @see solidbase/lib/Base#delete()
     */
    function delete() {
        global $DB, $USER, $Controller;
        if(!in_array($this->ID, array(ADMIN_GROUP, EVERYBODY_GROUP, MEMBER_GROUP))
            && $Controller->alias('adminGroups')->may($USER, DELETE)) {
            Log::write('Deleted group \'' . $this->Name . '\' (id=' . $this->ID . ')', 20);
            $DB->group_members->delete(array('group' => $this->ID));
            $DB->privileges->delete(array('beneficiary' => $this->ID));
            parent::delete();
        }
    }

    /**
     * Creates the nescessary database tables on install
     * @return bool
     */
    function install() {
        global $DB, $USER, $CONFIG, $Controller;
        $DB->query("CREATE TABLE IF NOT EXISTS `".$this->DBMemberTable."` (
  `group` int(11) NOT NULL,
  `user` int(11) NOT NULL,
  KEY `group` (`group`,`user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;");

        $admin_group = $Controller->newObj('Group');
        $CONFIG->base->ADMIN_GROUP = $admin_group->ID;
        $CONFIG->base->setType('ADMIN_GROUP', 'not_editable');
        $DB->groups->{$admin_group->ID} = array('name' => 'Administrators');

        $member_group = $Controller->newObj('Group');
        $CONFIG->base->MEMBER_GROUP = $member_group->ID;
        $CONFIG->base->setType('MEMBER_GROUP', 'not_editable');
        $DB->groups->{$member_group->ID} = array('name' => 'Members');

        $everybody_group = $Controller->newObj('Group');
        $CONFIG->base->EVERYBODY_GROUP = $everybody_group->ID;
        $CONFIG->base->setType('EVERYBODY_GROUP', 'not_editable');
        $DB->groups->{$everybody_group->ID} = array('name' => 'Everybody');


        define('ADMIN_GROUP',$admin_group->ID);
        define('EVERYBODY_GROUP',$member_group->ID);
        define('MEMBER_GROUP', $member_group->ID);
        return true;
    }

    /**
     * Drops the databases on uninstall
     * @return bool
     */
    function uninstall() {
        global $DB, $USER;
        if(!$USER->may(INSTALL)) return false;
        $DB->dropTable($this->DBMemberTable);
        $DB->dropTable($this->DBTable);
        return true;
    }

    /**
     * (non-PHPdoc)
     * @see lib/Page#deleteFromMenu()
     */
    function deleteFromMenu() {
        return MenuItem::deleteFromMenu();
    }

    /**
     * Returns the email associated with the group
     * @return string Email
     */
    function getEmail() {
        return (isEmail(@$this->settings['email_sender'])
            ?$this->settings['email_sender']
            :false);
    }

    /**
     * Send an email to each of the members in the group
     * @param $html
     * @param $headers
     * @param $text
     * @return unknown_type
     */
    function mail($html, $headers, $text = false) {
        $this->loadMembers();
        global $Controller;
        foreach($this->_MEMBERS as $member) {
            $Controller->$member(OVERRIDE, false)->mail($html, $headers, $text);
        }
    }

    /**
     * Give members right to see the group by default
     * (non-PHPdoc)
     * @see lib/MenuItem#may($u, $lvl)
     */
    function may($u, $lvl) {
        $pr = parent::may($u, $lvl);
        if(is_bool($pr)) return $pr;
        elseif($lvl & READ) return $this->isMember($u);
        else return $pr;
    }
}

?>
