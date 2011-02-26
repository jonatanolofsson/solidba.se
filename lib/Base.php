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
    public $ID = false;
    private $_Name=false;
    private $_ACTIVE=false;
    static private $PRIVILEGES = array();
    static private $BENEFICIARIES = array();
    private $__UPDATE_PRIVILEGES = false;
    protected $installable=false;
    protected $uninstallable=false;
    protected $translateName = true;
    public $privilegeGroup = 'Other';
    public $settings;
    protected $metadata_separator;
    private $metadata = array();

    public $editable = false;
    /**
     * Construct the class, setting the ID to it's (from the Controller) given value
     * @param $id
     * @return void
     */
    function __construct($id=false){
        $this->ID = $id;
        $this->settings = new Settings($id);
        self::registerMetadata('Name');
        self::registerMetadata('Activated', '1');
    }

    /**
     * First initiation of a new object
     * @return void
     */
    function __create() {
        global $USER;

        self::$PRIVILEGES[$this->ID][$USER->ID] = EVERYTHING;
        unset($this->_PRIVILEGECACHE[$USER->ID]);

        self::$PRIVILEGES[$this->ID][EVERYBODY_GROUP] = 0;
        unset($this->_PRIVILEGECACHE[EVERYBODY_GROUP]);

        $this->__registerUpdatePrivileges();
        $this->Name = __('New').' '.get_class($this);
    }

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
        if(!$reload && isset(self::$ALIASES[$this->ID])) return;
        global $Controller;
        $cloaded = $Controller->loadedIds(true);
        $load = array_diff($cloaded, self::$ALOADED);
        if($reload) $load += array($this->ID);
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
        $this->_ACTIVE = $DB->active->asArray(array('id' => $this->ID), 'identifier,start,stop', false, true);
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
            $str = $this->getMetadata('Name');
            if(!$this->translateName || empty($str)) return $str;
            else return __($str);
        }
        elseif($property == 'aliases') {
            $this->loadAliases();
            if(isset(self::$ALIASES[$this->ID])) {
                return self::$ALIASES[$this->ID];
            } else return array();
        }
        elseif($property === 'alias') {
            $this->loadAliases();
            if(isset(self::$ALIASES[$this->ID])) {
                reset(self::$ALIASES[$this->ID]);
                return current(self::$ALIASES[$this->ID]);
            } else return false;
        }
        elseif($property == 'tags') {
            return $this->getTags();
        }
        elseif(isset($this->metadata[$property])) {
            return $this->getMetadata($property);
        }
        elseif(isset($this->ASSOCIATIONS[$property])) {
            return $this->getAssociations($property);
        }
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
            if($property == 'alias') return $this->setAlias($value);
            elseif($property == 'tags' && is_array($value)) return call_user_func_array(array($this, 'setTags'), $value);
            elseif(isset($this->metadata[$property])) {
                return($this->setMetadata($property, $value));
            }
            elseif(isset($this->ASSOCIATIONS[$property])) {
                return($this->reassociate($property, $value));
            }
        }
    }

    /**
     * Set which tags are associated with the object
     * @param array|strings Variable number of tags
     * @return void
     */
    function setTags() {
        $tags = func_get_args();
        if(count($tags)==1 && is_array($tags[0])) $tags = $tags[0];
        $Oldtags = $this->getTags();
        $remove = array_diff($Oldtags, $tags);
        $add    = array_diff($tags, $Oldtags);

        global $DB;
        if(!empty($remove)) {
            $DB->tags->delete(array('id' => $this->ID, 'tag' => $remove));
        }
        if(!empty($add)) {
            foreach($add as $a) {
                $DB->tags->insert(array('id' => $this->ID, 'tag' => $a), false, true);
            }
        }
        $this->_tags = $tags;
    }

    /**
     * Get all the tags from the database
     * @return array
     */
    function getTags($force=false) {
        if(!$force && $this->tagged) return $this->_tags;
        $this->tagged = true;
        global $DB;
        $this->_tags = $DB->tags->asList(array('id' => $this->ID), 'tag');
        return $this->_tags;
    }
    private $tagged=false;


    /**
     * See if the object, or any defined identifier associated in the object, is active
     * @param string $identifier Identifier to check if active
     * @param $NOW When to check if the identifier is active
     * @return bool
     */
    function isActive($identifier=false, $NOW = false) {
        if(!$this->Activated) return false;
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
                $DB->active->delete(array('id' => $this->ID, 'identifier' => $identifier));
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
        if($this->already_updated) return;
        global $DB, $USER;
        $DB->updates->insert(array('id' => $this->ID, '#!edited' => 'UNIX_TIMESTAMP()', 'editor' => $USER->ID));
        $this->already_updated = true;
    }
    private $already_updated;

    /**
     * Associate an alias with the object. Returns a list of all the aliases which failed to set
     * @param string $aliases The alias(es) to be added
     */
    function setAlias($aliases) {
        global $DB;
        if(!is_array($aliases)) $aliases = array($aliases);
        $failed = array();
        $this->loadAliases();
        $newAliases = array_unique(array_filter(array_diff($aliases, (array)@self::$ALIASES[$this->ID])));
        if($newAliases && $DB->aliases->insertMultiple(array('id' => $this->ID, 'alias' => $newAliases), false, 2, true)) {
            foreach($newAliases as $new)
                self::$ALIASES[$this->ID][$new] = $new;
            Log::write('Changed aliases of (id=' . $this->ID . ')', 2);
        }
    }

    /**
     * Removes all old alias associations and sets up new ones
     * @param string|array $aliases The alias(es) to set
     * @return array Failed associations
     */
    function resetAlias($aliases) {
        global $DB;
        $DB->aliases->delete(array('id' => $this->ID), false);
        self::$ALIASES[$this->ID] = array();
        Log::write('Removed all aliases for (id=' . $this->ID . ')', 2);
        return $this->setAlias($aliases);
    }

    /**
     * Checks wether a given alias is associated with the object
     * @param string $alias The alias to check for association with
     * @return bool
     */
    function isMe($alias) {
        $this->loadAliases();
        return (in_array($alias, (array)@self::$ALIASES[$this->ID]));
    }

    /**
     * Removes an alias association
     * @param string $alias The alias to remove
     * @return void
     */
    function unalias($alias) {
        $DB->aliases->delete(array('id' => $this->ID, 'alias' => $alias));
        if(($pos = array_search($alias, self::$ALIASES[$this->ID])) !== false)
            unset(self::$ALIASES[$this->ID][$pos]);
        Log::write('Removed alias \'' . $alias . '\' from (id=' . $this->ID . ')', 2);
    }

    /**
     * Register a default metadata value
     * @param string $name The metadata that should be registered
     * @param mixed $default_value Default value of metadata
     * @return void
     */
    function registerMetadata($name, $default_value='') {
        $names = (array)$name;
        foreach($names as $n)
            if(!isset($this->metadata[$n]))
                $this->metadata[$n] = $default_value;
    }

    function getMetadata($property)
    {
        if(!$this->metadata_loaded) $this->loadMetadata();
        return @$this->metadata[$property];
    }

    function setMetadata($property, $value)
    {
        global $DB;
        $val['id'] = $this->ID;
        if($this->metadata_separator)
            $val['metameta'] = $this->metadata_separator;
        $val['field'] = $property;
        $val['value'] = $value;
        $DB->metadata->insert($val, false, 2);
        return ($this->metadata[$property] = $value);
    }

    function loadMetadata()
    {
        if(in_array($this->metadata_separator, $this->metadata_loaded)) return;
        global $DB;
        $where['id'] = $this->ID;
        if($this->metadata_separator)
            $where['metameta'] = $this->metadata_separator;
        $this->metadata = array_merge(
            $this->metadata,
            $DB->metadata->asList($where, 'field,value', false, true)
        );
        $this->metadata_loaded[$this->metadata_separator] = true;
    }
    protected $metadata_loaded = array();

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
            (get_class($beneficiary) == 'Group' && $beneficiary->ID == ADMIN_GROUP)) return true;

        $this->__loadPrivileges($beneficiary);
        if(!isset(self::$PRIVILEGES[$this->ID])) return 0;

        if(isset(self::$PRIVILEGES[$this->ID][$id])) return ((self::$PRIVILEGES[$this->ID][$id] & $accessLevel) > 0);

        if(isset($this->_PRIVILEGECACHE[$id]))
            return ($this->_PRIVILEGECACHE[$id] === false
                ?0
                :(($this->_PRIVILEGECACHE[$id] & $accessLevel) > 0));

        if($accessLevel & READ>0 && !$this->isActive()) return $this->may($beneficiary, EDIT);

        if(!$this->ID) return;
        $appliccable_permissions = array_intersect($beneficiary->groupIds, array_keys(self::$PRIVILEGES[$this->ID]));
        if(empty($appliccable_permissions)) $this->_PRIVILEGECACHE[$id] = false;
        else {
            $permissionset = 0;
            foreach($appliccable_permissions as $a) $permissionset |= (int)self::$PRIVILEGES[$this->ID][$a];
            $this->_PRIVILEGECACHE[$id] = $permissionset;
        }
        return ($this->_PRIVILEGECACHE[$id] === false
            ?0
            :(($this->_PRIVILEGECACHE[$id] & $accessLevel) > 0));
    }
    private $_PRIVILEGECACHE = array();

    /**
     * Equivalent to may($USER, ...), but with some improved caching
     * @param integer $accessLevel Accesslevel for the user to be tested against
     * @return bool
     */
    final function mayI($accessLevel){
        global $USER;
        if(isset($this->_PRIVILEGECACHE[$this->ID]) //EXPERIMENTAL
        && ($this->_PRIVILEGECACHE[$USER->ID] & $accessLevel))
            return true;
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
        if(!$this->ID) return;
        $this->__loadPrivileges($beneficiary);
        global $USER;
        if($this->mayI(EDIT_PRIVILEGES)) {
            self::$PRIVILEGES[$this->ID][$beneficiary->ID] = (self::$PRIVILEGES[$this->ID][$beneficiary->ID] | $accessLevel);
            unset($this->_PRIVILEGECACHE[$beneficiary->ID]);
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
    if(!$this->ID) return;
        $this->__loadPrivileges($beneficiary);
        global $USER, $Controller;
        if(!is_object($beneficiary)) $beneficiary = $Controller->get($beneficiary);
        if($this->mayI(EDIT_PRIVILEGES)) {
            self::$PRIVILEGES[$this->ID][$beneficiary->ID] = (self::$PRIVILEGES[$this->ID][$beneficiary->ID] &~ $accessLevel);
            unset($this->_PRIVILEGECACHE[$beneficiary->ID]);
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
        $arr['id'] = $this->ID;
        $r = '<a href="'.url($arr).'">'.$this.'</a>';
        if($echo) echo $r;
        return $r;
    }

    /**
     * Register a privilege update as a shutdown function.
     * @return void
     */
    private function __registerUpdatePrivileges(){
    if(!$this->ID) return;
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
    if(!$this->ID) return;
    global $DB;
        $DB->privileges->delete(array('id' => $this->ID), false);
        foreach(self::$PRIVILEGES[$this->ID] as $p => $v) {
            $DB->privileges->insert(array(  "id" => $this->ID,
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
        if(!$this->ID || in_array($beneficiary->ID, self::$BENEFICIARIES)) return;
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
        if($this->ID) {
            if(in_array($this->ID, array(ADMIN_GROUP, EVERYBODY_GROUP))) return false;
            global $DB;
            Log::write('Deleted object (id=' . $this->ID . ')', 2);
            $DB->spine->delete($this->ID);
            $DB->aliases->delete($this->ID, false);
            $DB->privileges->delete($this->ID, false);
            $DB->metadata->delete(array('id' => $this->ID), false);
            $DB->flow->delete(array('id' => $this->ID), false);
            $DB->subdomains->delete(array('assoc' => array_merge(array($this->ID), $this->aliases)));
            unset($Controller->{$this->ID});
            $this->ID = false;
        }
    }

    function sweepPrivileges($u = false) {
        $aLevel = 0;
        for($which = 0; $which <= 5; ++$which)
        {
            $lvl = (1 << $which);
            if(($u?$this->may($u, $lvl):$this->mayI($lvl)))
                $aLevel |= $lvl;
        }

        return $aLevel;
    }

    private $ASSOCIATIONS = array();
    private $A_INWARDS = array();

    function registerAssociation($name, $inwards = false) {
        $this->ASSOCIATIONS[$name] = array();
        $this->A_INWARDS[$name] = $inwards;
    }

    function getAssociations($name) {
        if($this->ASSOCIATIONS[$name]) return $this->ASSOCIATIONS[$name];
        global $DB;

        $this->ASSOCIATIONS[$name] = $DB->associations->asList(
            array(
                ($this->A_INWARDS[$name] ? 'to'  : 'from') => $this->ID,
                'name' => ($this->A_INWARDS[$name] ? $this->A_INWARDS[$name] : $name)
            ),
            ($this->A_INWARDS[$name] ? 'from' : 'to'  ));
        return $this->ASSOCIATIONS[$name];
    }

    function associateWith($name, $ids) {
        $this->getAssociations($name);
        $ids = array_map(function($id){return (is_object($id) ? $id->ID : $id);}, (array)$ids);
        $new = array_diff($ids, $this->ASSOCIATIONS[$name]);
        $this->ASSOCIATIONS[$name] = array_merge($this->ASSOCIATIONS[$name], $ids);
        if($new) {
            global $DB;
            $DB->associations->insertMultiple(
                array(
                    ($this->A_INWARDS[$name] ? 'from' : 'to') => $new,
                    ($this->A_INWARDS[$name] ? 'to' : 'from') => $this->ID,
                    'name' => ($this->A_INWARDS[$name] ? $this->A_INWARDS[$name] : $name)
                )
            );
        }
    }

    function diassociateFrom($name, $ids) {
        $this->getAssociations($name);
        $ids = array_map(function($id){return (is_object($id) ? $id->ID : $id);}, (array)$ids);

        $this->ASSOCIATIONS[$name] = arrayRemove($this->ASSOCIATIONS[$name], $ids);
        $DB->associations->delete(
            array(
                ($this->A_INWARDS[$name] ? 'from' : 'to') => $ids,
                ($this->A_INWARDS[$name] ? 'to' : 'from') => $this->ID,
                'name' => ($this->A_INWARDS[$name] ? $this->A_INWARDS[$name]  : $name)
            ),
            false
        );
    }

    function reassociate($name, $ids) {
        $ids = array_unique(array_filter(array_map(
            function($id){return (is_object($id) ? $id->ID : $id);}, (array)$ids
        )));

        global $DB;

        $DB->associations->delete(
            array(
                ($this->A_INWARDS[$name] ? 'to' : 'from') => $this->ID,
                'name' => ($this->A_INWARDS[$name] ? $this->A_INWARDS[$name]  : $name)
            ),
            false
        );
        if($ids) {
            $DB->associations->insertMultiple(
                array(
                    ($this->A_INWARDS[$name] ? 'from' : 'to') => $ids,
                    ($this->A_INWARDS[$name] ? 'to' : 'from') => $this->ID,
                    'name' => ($this->A_INWARDS[$name] ? $this->A_INWARDS[$name]  : $name)
                )
            );
        }
        $this->ASSOCIATIONS[$name] = array();
    }

}

?>
