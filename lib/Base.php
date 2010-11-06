<?php

/**
 * Base.php
 *This file contains the base class
 *
 * @author Jonatan Olofsson [joolo]
 * @version 1.1
 * @license http://creativecommons.org/licenses/by-nc/3.0/ Creative Commons Attribution-Noncommercial 3.0 Unported License
 * @package Base
 */


/**
 * The Base class is the main (Base) class which handles mainly permissions and
 * ID/alias info for the classes that extends it
 * @package Base
 */
class Base{
    private $_ID = false;
    private $_Name=false;
    private $_ACTIVE=false;
    static private $PRIVILEGES = array();
    static private $BENEFICIARIES = array();
    private $__UPDATE_PRIVILEGES = false;
    protected $installable=false;
    protected $uninstallable=false;
    protected $translateName = true;
    public $privilegeGroup = 'Other';
    private $_settings;
    protected $_Tags;
    /**
     * Construct the class, setting the ID to it's (from the Controller) given value
     * @param $id
     * @return void
     */
    function __construct($id=false){
        $this->_ID = $id;
        $this->_settings = new Settings($id);
    }

    /**
     * First initiation of a new object
     * @return void
     */
    function __create() {}

    /**
     * Perform preload of multiple objects
     * @param array $ids This is the id's that are beeing loaded by the controller
     * @return void
     */
    function preload($ids, $aLEVEL) {}

    /**
     * Load the object's associated aliases from the database
     * @param $reload Force reload from database
     * @return void
     */
    function loadAliases($reload=false) {
        if(!$this->ID) return false;
        global $DB;
        if(!$reload && isset(self::$ALIASES[$this->_ID])) return;
        global $Controller;
        $cloaded = $Controller->loadedIds(true);
        $load = array_diff($cloaded, self::$ALOADED);
        if($reload) $load += array($this->_ID);
        if($load) {
            self::$ALOADED = array_merge($cloaded, $load);
            $r = $DB->aliases->get(array('id' => $load), 'id,alias');
            while(false !== ($row = Database::fetchAssoc($r)))
            {
                self::$ALIASES[$row['id']][$row['alias']] = $row['alias'];
            }
        }
    }
    private static $ALOADED = array();
    private static $ALIASES = array();

    /**
     * Load the activation settings for the object
     * @param $reload Force reload from database
     * @return void
     */
    function loadActive($reload=false) {
        global $DB;
        if(!$reload && $this->_ACTIVE!==false) return;
        $this->_ACTIVE = $DB->active->asArray(array('id' => $this->_ID), 'identifier,start,stop', false, true);
    }

    /**
     * Sets the variable's values an updates the database if nescessary
     * Usage:
     * <code>
     * $obj->alias = 'myAlias';
     * </code>
     * @param string $property The property that is affected
     * @param mixed $value The affected variable's new value
     * @return void
     */
    function __set($property, $value){
        if($this->ID) {
            global $USER, $DB;
            if($property == 'Name') {
                $this->{'_'.$property} = $value;
            }
            elseif($property == 'alias') $this->setAlias($value);
            elseif($property == 'Tags' && is_array($value)) call_user_func_array(array($this, 'setTags'), $value);
        }
        elseif($property == 'ID' && is_numeric($value) && $this->ID === false) {
            $this->_ID = $value;
        }
    }

    /**
     * Returns the value of a property
     * Usage:
     * <code>
     * $id = $obj->ID;
     * </code>
     * @param string $property The wanted property
     * @return mixed
     */
    function __get($property){
        if($property == 'Name') {
            $str = $this->_Name;
            if(!$this->translateName || empty($str)) return $str;
            else return __($str);
        }
        elseif(in_array($property, array('ID', 'settings'))) {
            return $this->{'_'.$property};
        }
        elseif($property == 'aliases') {
            $this->loadAliases();
            if(isset(self::$ALIASES[$this->_ID])) {
                return self::$ALIASES[$this->_ID];
            } else return array();
        }
        elseif($property === 'alias') {
            $this->loadAliases();
            if(isset(self::$ALIASES[$this->_ID])) {
                reset(self::$ALIASES[$this->_ID]);
                return current(self::$ALIASES[$this->_ID]);
            } else return false;
        }
        elseif($property == 'Tags') {
            return $this->getTags();
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
            $DB->tags->delete(array('id' => $this->ID, 'tag' => $remove));
        }
        if(!empty($add)) {
            foreach($add as $a) {
                $DB->tags->insert(array('id' => $this->ID, 'tag' => $a), false, true);
            }
        }
        $this->_Tags = $Tags;
    }

    /**
     * Get all the tags from the database
     * @return array
     */
    function getTags($force=false) {
        if(!$force && $this->tagged) return $this->_Tags;
        $this->tagged = true;
        global $DB;
        $this->_Tags = $DB->Tags->asList(array('id' => $this->ID), 'tag');
        return $this->_Tags;
    }
    private $tagged=false;


    /**
     * See if the object, or any defined identifier associated in the object, is active
     * @param string $identifier Identifier to check if active
     * @param $NOW When to check if the identifier is active
     * @return bool
     */
    function isActive($identifier=false, $NOW = false) {
        $this->loadActive();
        if(!$NOW) $NOW = time();
        if(!$identifier) $identifier = 'object';

        if(!isset($this->_ACTIVE[$identifier])) return true;

        return ($this->_ACTIVE[$identifier]['start']<=$NOW && ($this->_ACTIVE[$identifier]['stop'] <= $this->_ACTIVE[$identifier]['start'] || $this->_ACTIVE[$identifier]['stop']>=$NOW));
    }

    /**
     * Set the time when the object, or any with the object associated identifier, should be active
     * @param $start Starttime for activation
     * @param $stop Time when object/identifier stops beeing active
     * @param $identifier Optional identifier for object related properties that should be marked as active
     * @return void
     */
    function setActive($start=false, $stop=false, $identifier='object') {
        $this->loadActive();
        global $DB;
        if(($start === false && $stop === false) || $start === $stop) {
            if(isset($this->_ACTIVE[$identifier])) {
                $DB->active->delete(array('id' => $this->_ID, 'identifier' => $identifier));
                unset($this->_ACTIVE[$identifier]);
            }
            return;
        } elseif(isset($this->_ACTIVE[$identifier])) {
            $upd = array();
            if($start !== false) $upd['start'] = $start;
            if($stop !== false) $upd['stop'] = $stop;
            if($upd)
                $DB->active->update($upd, array('id' => $this->ID, 'identifier' => $identifier),true);
        } else {
            $DB->active->insert(array('id' => $this->ID, 'identifier' => $identifier, 'start' => $start, 'stop' => $stop),true);
        }
        $this->_ACTIVE[$identifier] = array('start' => $start, 'stop' => $stop);
    }

    /**
     * Get the activation settings for the object or given identifier
     * @param $identifier Optional activity identifier
     * @return array Array containing start/stop data
     */
    function getActive($identifier='object') {
        $this->loadActive();
        if(isset($this->_ACTIVE[$identifier])) return $this->_ACTIVE[$identifier];
        else return false;
    }

    /**
     * notify that changes have been made
     * @return void
     */
    function registerUpdate() {
        global $DB, $USER;
        $DB->updates->insert(array('id' => $this->_ID, '!!edited' => 'UNIX_TIMESTAMP()', 'editor' => $USER->ID));
    }

    /**
     * Associate an alias with the object. Returns a list of all the aliases which failed to set
     * @param string $aliases The alias(es) to be added
     * @return array Failed associations
     */
    function setAlias($aliases) {
        global $DB;
        if(!is_array($aliases)) $aliases = array($aliases);
        $failed = array();
        $this->loadAliases();
        $newAliases = array_unique(array_filter(array_diff($aliases, (array)@self::$ALIASES[$this->_ID])));
        if($newAliases && $DB->aliases->insertMultiple(array('id' => $this->_ID, 'alias' => $newAliases), false, 2, true)) {
            foreach($newAliases as $new)
                self::$ALIASES[$this->_ID][$new] = $new;
            Log::write('Changed aliases of (id=' . $this->_ID . ')', 2);
        }
    }

    /**
     * Removes all old alias associations and sets up new ones
     * @param string|array $aliases The alias(es) to set
     * @return array Failed associations
     */
    function resetAlias($aliases) {
        global $DB;
        $DB->aliases->delete(array('id' => $this->_ID), false);
        self::$ALIASES[$this->_ID] = array();
        Log::write('Removed all aliases for (id=' . $this->_ID . ')', 2);
        return $this->setAlias($aliases);
    }

    /**
     * Checks wether a given alias is associated with the object
     * @param string $alias The alias to check for association with
     * @return bool
     */
    function isMe($alias) {
        $this->loadAliases();
        return (in_array($alias, (array)@self::$ALIASES[$this->_ID]));
    }

    /**
     * Removes an alias association
     * @param string $alias The alias to remove
     * @return void
     */
    function unalias($alias) {
        $DB->aliases->delete(array('id' => $this->_ID, 'alias' => $alias));
        if(($pos = array_search($alias, self::$ALIASES[$this->_ID])) !== false)
            unset(self::$ALIASES[$this->_ID][$pos]);
        Log::write('Removed alias \'' . $alias . '\' from (id=' . $this->_ID . ')', 2);
    }

    /**
     * retrieve all metadata of the object
     * @param string $metameta Metameta
     * @param bool $force Force update
     * @return void
     */
    function getMetadata($metameta = '', $force=false, $autoset=false) {
        if(!$force) {
            $metameta = array_diff((array)$metameta, $this->metaget);
        }

        if(!empty($metameta)) {
            $this->metaget = array_merge($this->metaget, (array)$metameta);
            Metadata::$metameta = $metameta;
            Metadata::injectMe();
        }
        if($autoset) {
            $autoset = (array)$autoset;
            foreach($autoset as $var) {
                if(!$this->metaget($var)) {
                    $this->__set($var, '');
                }
            }
        }
    }
    private $metaget = array();

    function metaget($what) {
        return $this->__get($what);
    }

    /**
     * Converts the object to it's string representation (name)
     * @return string
     */
    function __toString(){
        return (string)$this->Name;
    }

    /**
     * Performs a permissions check and returns wether a given user or group
     * may perform the action in question. Returns true if allowed, false if explicitly not, 0 if no applying privilege has been set.
     * @param User $beneficiary User to be tested for permission
     * @param integer $accessLevel Accesslevel for the user to be tested against
     * @return bool
     */
    public function may($beneficiary, $accessLevel) {
        global $Controller;
        if(is_numeric($beneficiary)) $id = $beneficiary;
        else $id = $beneficiary->ID;

        if(!is_object($beneficiary)) $beneficiary = $Controller->{(string)$beneficiary}(OVERRIDE);

        if($Controller->{ADMIN_GROUP}(OVERRIDE)->isMember($beneficiary) ||
            (is_a($beneficiary, 'Group') && $beneficiary->ID == ADMIN_GROUP)) return true;

        $this->__loadPrivileges($beneficiary);
        if(!isset(self::$PRIVILEGES[$this->_ID])) return 0;

        if(isset(self::$PRIVILEGES[$this->_ID][$id])) return ((self::$PRIVILEGES[$this->_ID][$id] & $accessLevel) > 0);

        if(isset($this->_PRIVILEGECACHE[$id]))
            return ($this->_PRIVILEGECACHE[$id] === false
                ?0
                :(($this->_PRIVILEGECACHE[$id] & $accessLevel) > 0));

        if($accessLevel & READ>0 && !$this->isActive()) return $this->may($beneficiary, EDIT);

        if(!$this->_ID) return;
        $appliccable_permissions = array_intersect($beneficiary->groupIds, array_keys(self::$PRIVILEGES[$this->_ID]));
        if(empty($appliccable_permissions)) $this->_PRIVILEGECACHE[$id] = false;
        else {
            $permissionset = 0;
            foreach($appliccable_permissions as $a) $permissionset |= (int)self::$PRIVILEGES[$this->_ID][$a];
            $this->_PRIVILEGECACHE[$id] = $permissionset;
        }
        return ($this->_PRIVILEGECACHE[$id] === false
            ?0
            :(($this->_PRIVILEGECACHE[$id] & $accessLevel) > 0));
    }
    private $_PRIVILEGECACHE = array();

    /**
     * Equivalent to may($USER, ...);
     * @param integer $accessLevel Accesslevel for the user to be tested against
     * @return bool
     */
    function mayI($accessLevel){
        global $USER;
        $response = $this->may($USER, $accessLevel);
        $this->setPrivilegeCache($USER, $accessLevel, $response);
        return $response;
    }

    protected function setPrivilegeCache($beneficiary, $accessLevel, $granted=true) {
        global $Controller;
        if(is_numeric($beneficiary)) $id = $beneficiary;
        else $id = $beneficiary->ID;
        if(!isset($this->_PRIVILEGECACHE[$id])) $this->_PRIVILEGECACHE[$id] = 0;
        if(in_array($accessLevel, array(READ, EDIT, DELETE, EDIT_PRIVILEGES, INSTALL, PUBLISH))) {
            if($granted)
                $this->_PRIVILEGECACHE[$id] |= $accessLevel;
            else $this->_PRIVILEGECACHE[$id] &= ~$accessLevel;
        }
    }

    /**
     * Give a user or group privileges to perform a given action
     * @param User|Group $beneficiary The group that should be given the privilege
     * @param integer $accessLevel The type of permission that should be given
     * @return bool
    */
    public function allow($beneficiary, $accessLevel){
        if(!$this->_ID) return;
        $this->__loadPrivileges($beneficiary);
        global $USER;
        if($this->mayI(EDIT_PRIVILEGES)) {
            self::$PRIVILEGES[$this->_ID][$beneficiary->ID] = (self::$PRIVILEGES[$this->_ID][$beneficiary] | $accessLevel);
            unset($this->_PRIVILEGECACHE[$beneficiary->ID], $this->_PRIVILEGECACHE2[$beneficiary->ID]);
            $this->__registerUpdatePrivileges();
            return true;
        } else return false;
    }

    /**
     * Deny a user or group privileges to perform a given action
     * @param User|Group $beneficiary The group whose privileges should be restricted
     * @param integer $accessLevel The type of permission that should be denied
     * @return bool
    */
    public function deny($beneficiary, $accessLevel){
    if(!$this->_ID) return;
        $this->__loadPrivileges($beneficiary);
        global $USER;
        if($this->mayI(EDIT_PRIVILEGES)) {
            self::$PRIVILEGES[$this->_ID][$beneficiary->ID] = (self::$PRIVILEGES[$this->_ID][$beneficiary] &~ $accessLevel);
            unset($this->PRIVILEGECACHE[$beneficiary->ID], $this->PRIVILEGECACHE2[$beneficiary->ID]);
            $this->__registerUpdatePrivileges();
            return true;
        } else return false;
    }

    /**
     * outputs a link to the current object
     * @param $echo Send the link to output
     * @return string
     */
    public function link($echo = false, $arr=false) {
        if(!$arr) $arr = array();
        $arr['id'] = $this->_ID;
        $r = '<a href="'.url($arr).'">'.$this.'</a>';
        if($echo) echo $r;
        return $r;
    }

    /**
     * Register a privilege update as a shutdown function.
     * @return void
     */
    private function __registerUpdatePrivileges(){
    if(!$this->_ID) return;
        if(!$this->__UPDATE_PRIVILEGES) {
            register_shutdown_function(array($this, "__updatePrivileges"));
            $this->__UPDATE_PRIVILEGES = true;
        }
    }

    /**
     * Write the privileges to the database
     * @return void
     */
    public function __updatePrivileges(){
    if(!$this->_ID) return;
    global $DB;
        $DB->privileges->delete(array('id' => $this->_ID), false);
        foreach(self::$PRIVILEGES[$this->_ID] as $p => $v) {
            if($v != 0) $DB->privileges->insert(array(	"id" => $this->ID,
                                                        "beneficiary" => $p,
                                                        "privileges" => $v));
        }
    }

    /**
     * Load the privileges for the object
     * @return void
     */
    private function __loadPrivileges($beneficiary) {
        if(!is_object($beneficiary)) {
            global $Controller;
            $beneficiary = $Controller->get($beneficiary, OVERRIDE);
        }
        if(!$this->_ID || in_array($beneficiary->ID, self::$BENEFICIARIES)) return;
        global $DB;
        $new = array_merge(array($beneficiary->ID), $beneficiary->groupIds);
        self::$PRIVILEGES = arrayKeyMergeRecursive(self::$PRIVILEGES, $DB->privileges->asMDList(array('beneficiary' => $new, 'beneficiary!' => self::$BENEFICIARIES), 'id,beneficiary,privileges'));
        self::$BENEFICIARIES = array_merge(self::$BENEFICIARIES, $new);
    }

    /**
     * Delete self completely
     * @return void
     */
    function delete(){
        global $DB, $Controller;
        if($this->_ID) {
            if(in_array($this->_ID, array(ADMIN_GROUP, EVERYBODY_GROUP))) return false;
            global $DB;
            Log::write('Deleted object (id=' . $this->_ID . ')', 2);
            $DB->spine->delete($this->_ID);
            $DB->aliases->delete($this->_ID, false);
            $DB->privileges->delete($this->_ID, false);
            $DB->metadata->delete(array('id' => $this->_ID), false);
            $DB->flow->delete(array('id' => $this->_ID), false);
            $DB->subdomains->delete(array('assoc' => array_merge(array($this->_ID), $this->aliases)));
            unset($Controller->{$this->_ID});
            $this->_ID = false;
        }
    }
}

?>
