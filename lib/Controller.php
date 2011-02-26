<?php

/**
 *This file defines the Controller class, which keeps track of all the other objects in solidba.se
 *
 * @author Jonatan Olofsson [joolo]
 * @version 1.1
 * @license http://creativecommons.org/licenses/by-nc/3.0/ Creative Commons Attribution-Noncommercial 3.0 Unported License
 * @package Base
 */


/**
 *  Define access levels. These numbers should all be unique and divisible by two
 *  to allow for simple, fast binary control.
*/
define('READ', 1);
define('EDIT', 2);
define('DELETE', 4);
define('EDIT_PRIVILEGES',8);
define('INSTALL', 16);
define('PUBLISH', 32);
define('ANYTHING', READ|EDIT|DELETE|EDIT_PRIVILEGES|INSTALL|PUBLISH);
define('EVERYTHING', ANYTHING);
define('OVERRIDE', 'OVERRIDE');

 /**
  * The controller class is the backbone of solidba.se. It keeps track of all
  * objects, loads, caches and creates them accordingly. This class works with the
  * database table 'spine' to keep track of id, alias and type
  * @author Jonatan Olofsson [joolo]
 * @package Base
  */
class Controller{
    private $OBJECTS = array();
    private $aliases = array();
    private $DBTable = 'spine';
    private $aliasTable = 'aliases';
    private $currentUser = false;
    private $INSTANTIATING = array();
    private $ALL = array();


    static public function installable() {return __CLASS__;}

    /**
     * The install function creates the spine table on installation
     * @return void
     */
    function install() {
        global $DB, $USER;
        //FIXME: Hardcoded name in lib/{Base,Benefittor,Companies,Controller,File,Folder,Group,Page,Search}.php
        $DB->query("CREATE TABLE IF NOT EXISTS `".$this->DBTable."` (
  `id` int(11) NOT NULL auto_increment,
  `class` varchar(100) character set utf8 NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;");

        //FIXME: Hardcoded name in lib/{Base,Controller}.php
        $DB->query("CREATE TABLE IF NOT EXISTS `".$this->aliasTable."` (
  `id` int(11) NOT NULL,
  `alias` varchar(255) character set utf8 NOT NULL,
  PRIMARY KEY  (`alias`),
  KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;");

        //FIXME: Hardcoded name in lib/Log.php
        $DB->query("CREATE TABLE IF NOT EXISTS `log` (
  `time` datetime NOT NULL,
  `remote_addr` varchar(45) character set utf8 NOT NULL,
  `user` int(11) NOT NULL,
  `source` varchar(50) character set utf8 NOT NULL,
  `level` tinyint(4) NOT NULL,
  `message` varchar(255) character set utf8 NOT NULL,
  KEY `source` (`source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;");

        //FIXME: Hardcoded name in lib/{Base,Benefittor,Companies,DB,Metadata,Page,Search}.php
        $DB->query("CREATE TABLE IF NOT EXISTS `metadata` (
  `id` int(11) NOT NULL,
  `field` varchar(100) character set utf8 NOT NULL,
  `value` varchar(255) character set utf8 NOT NULL,
  `metameta` varchar(50) collate utf8_general_ci NOT NULL,
  KEY `id` (`id`),
  KEY `field` (`field`),
  KEY `metameta` (`metameta`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;");

        //FIXME: Hardcoded name in lib/{Base,Group}.php
        $DB->query("CREATE TABLE IF NOT EXISTS `privileges` (
  `id` int(11) NOT NULL,
  `beneficiary` int(11) NOT NULL,
  `privileges` mediumint(9) NOT NULL,
  KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;");

        //FIXME: Hardcoded name in lib/Settings.php
        $DB->query("CREATE TABLE IF NOT EXISTS `settings` (
  `id` int(11) NOT NULL,
  `property` varchar(255) character set utf8 NOT NULL,
  `value` text collate utf8_general_ci NOT NULL,
  KEY `property` (`property`),
  KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;");

        //FIXME: Hardcoded name in lib/Settings.php
        $DB->query("CREATE TABLE IF NOT EXISTS `setset` (
  `property` varchar(255) collate utf8_general_ci NOT NULL,
  `type` enum('text','CSV','select','set','check','password') collate utf8_general_ci NOT NULL,
  `visible` tinyint(4) NOT NULL,
  `set` blob NOT NULL,
  `description` text collate utf8_general_ci NOT NULL,
  PRIMARY KEY  (`property`),
  KEY `visible` (`visible`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;");
    }


    /**
     * Counts the number of loaded objects
     * @return int
     */
    function count() {
        return count($this->OBJECTS);
    }

    /**
     * Returns the ID's currently in cache
     * @return array
     */
    function loadedIds($all=false) {
        if($all) return $this->ALL;
        return array_keys($this->OBJECTS);
    }

    /**
     * This is the quick way to access a object.
     * <code>
     * $object = $Controller->$id;
     * </code>
     * @param $ID ID or alias
     * @return object The object asked for, if permissions allow
     */
    function __get($id){
        if($id == 'currentUser') {
            if(!$this->currentUser) {
                $this->currentUser = new User('current');
                $id = $this->currentUser->ID;
                if(!isset($this->OBJECTS[$id])) $this->OBJECTS[$id] = $this->currentUser;
            }
            return $this->currentUser;
        }
        return $this->retrieve($id, ANYTHING);
    }

    /**
     * This is the function that retrieves a single object
     * @param integer $ID Spine ID (or alias) of the object
     * @param integer $aLevel Accesslevel that should be tested against
     * @param User $u User object that the permissions should be tested for
     * @param bool $keep Set to false to disable caching
     * @return object Returns false if the object is not found or permissions deny access
     */
    function retrieve($id, $aLevel=ANYTHING, $u=false, $keep = true) {
    global $USER, $DB;
        if(is_numeric($id)) {
            if(isset($this->OBJECTS[$id])) {
                if($aLevel != OVERRIDE && method_exists($this->OBJECTS[$id], 'may')
                && !($u?$this->OBJECTS[$id]->may($u, $aLevel):$this->OBJECTS[$id]->mayI($aLevel))) {
                    return false;
                }
                return $this->OBJECTS[$id];
            }
            else {
                return $this->fetch($id, $aLevel, $u, $keep);
            }
        } else return $this->alias($id, $aLevel, $u, $keep);
    }

    /**
     * retrieve an object based on it's database alias. Returns false if alias is not found
     * or if the permissions test fail.
     * <code>
     * $PAGE = $Controller->alias('frontpage');
     * </code>
     * @param string $alias Alias that should be looked for
     * @param integer $aLevel Accesslevel that should be tested against
     * @param User $u User object that the permissions should be tested for
     * @param bool $keep Set to false to disable caching
     * @return bool|object
     */
    function alias($alias, $aLevel=ANYTHING, $u=false, $keep = true) {
        global $DB;
        if (@empty($alias)) {
            return false;
        }
        if(isset($this->aliases[$alias])) $id = $this->aliases[$alias];
        else {
            $id = $DB->aliases->getCell(array('alias' => $alias), 'id');
            if($id) $this->aliases[$alias] = $id;
        }

        if($id){
            return $this->retrieve($id, $aLevel, $u, $keep);
        }
        else return false;
    }

    /**
     * Fetch an object from the database and initialize it. Returns false if the classname is unknown
     * @param integer $ID Spine ID of the object(s)
     * @param integer $aLevel Accesslevel that should be tested against
     * @param User $u User object that the permissions should be tested for
     * @param bool $keep Set to false to disable caching
     * @return object
     */
    private function fetch($what, $aLevel=ANYTHING, $u=false, $keep = true, $class = false) {
    global $DB, $USER;
        $return = array();

        $where = array('id' => $what);
        if($class) $where['class'] = $class;
        $res = $DB->spine->asMDList($where, 'class,id', false, false, false, true);
        foreach($res as $class => $ids) {
            if(class_exists($class)) {
                call_user_func(array($class, 'preload'), $ids, $aLevel);
                foreach($ids as $id) {
                    if(!isset($this->INSTANTIATING[$id]))
                    {
                        $this->INSTANTIATING[$id] = true;
                        $this->ALL[$id] = $id;
                        $New_Object = new $class($id);
                        unset($this->INSTANTIATING[$id]);

                        if($keep) $this->OBJECTS[$id] = $New_Object;
                        if($aLevel != OVERRIDE && method_exists($New_Object, 'may')
                        && !($u?$New_Object->may($u, $aLevel):$New_Object->mayI($aLevel)))
                        {
                            continue;
                        }

                        if(!is_array($what)) return $New_Object;
                        $return[$New_Object->ID] = $New_Object;
                    }
                }
            }
        }
        if(!is_array($what)) return false;
        return $return;
    }

    /**
     * Create a new object of a class and register it. Returns false if the class name is unknown.
     * <code>
     * $newUser = $Controller->newObj('User);
     * </code>
     * @param string $class name of the class that should be instatiated
     * @param mixed $arg Argument to pass as a second argument (after the id) to the new object
     * @param bool $keep Set to false to disable caching
     * @return bool|object
     */
    function newObj($class, $arg=false, $keep=true){
        if(class_exists($class)) {
            global $DB;
            $id = $DB->spine->insert(array('class' => $class));
            Log::write('Created new object of class ' . $class . ' (id=' . $id . ')', 5);

            if($arg) $New_Object = new $class($id, $arg);
            else $New_Object = new $class($id);

            $New_Object->ID = $id;
            $New_Object->__create();

            if($keep) $this->OBJECTS[$id] = $New_Object;
            return $New_Object;
        } else return false;
    }

    /**
     * Unsets an object from the cache
     * @param integer $id Id to be removed from cache
     * @return void
     */
    function __unset($id) {
        unset($this->OBJECTS[$id]);
    }

    /**
     * The __call() function is here used to provide a tool to do a simple-syntax on-the-fly
     * access control, or assuring class belonging.
     * @param string $id Alias or (string)$id to identify an object
     * @param $args Here goes the arguments with wich the function was called. Contains (in any order)
     *  the accesslevel with wich the object will be tried, user to try, or class to enforce.
     * @return bool|object
     */
    function __call($id, $args){
        global $USER;
        if($id == 'newObj') return $this->$id(@$args[0]);

        $aLevel = ANYTHING;
        $obj = $this->retrieve($id, OVERRIDE, false, !(in_array(false, $args, true)));
        if(!$obj) return false;
        $u = $USER;
        foreach($args as $con) {
            if(is_string($con) && $con !== OVERRIDE && get_class($obj) != $con) return false;
            elseif(is_a($con, 'User')) $u = $con;
            elseif(is_int($con) || $con === OVERRIDE) $aLevel = $con;
        }
        if($aLevel !== OVERRIDE && !$obj->may($u, $aLevel)) return false;
        else return $obj;
    }

    /**
     * retrieve a set (array) of objects
     * @param array|integer $set The id's of the objects that are requested
     * @param integer $aLevel The accesslevel which all objects will be tried against.
     * @param User $u The user which the accesslevel will be tried for
     * @param bool $keep Set to false to disable caching
     * @return array Array of objects
     */
    function get($set, $aLevel = ANYTHING, $u = false, $keep = true, $class = false){
        if(is_string($aLevel) && $aLevel !== OVERRIDE) {
            $class = $aLevel;
            $aLevel = ANYTHING;
        }

        if($set == false) return array();
        $objects = array();
        if(!is_array($set)) {
            return $this->retrieve($set, $aLevel, $u, $keep);
        }
        $set = flatten($set);
        $loadedObjects = array_keys($this->OBJECTS);
        $avail = array_intersect($set, $loadedObjects);
        $fetch = array_diff($set, $loadedObjects);
        $result = array();
        if($avail) $result = $this->filter($this->OBJECTS, $aLevel, $u, $class);
        if($fetch) $fetch = $this->fetch($fetch, $aLevel, $u, $keep, $class);
        if($fetch) $result += $fetch;
        return arrayKeySort($result, $set);
    }

    function filter($set, $aLevel = ANYTHING, $u = false, $class = false) {
        if($aLevel === OVERRIDE) return $set;
        global $USER;
        $newset = array();
        foreach($set as $id => $obj) {
            if(($u?$obj->may($u, $aLevel):$obj->mayI($aLevel))
            && ($class?get_class($obj)==$class:true)) $newset[$id] = $obj;
        }
        return $newset;
    }

    /**
     * retrieve first allowed from set
     * @param array|integer $set The id's of the objects that are requested
     * @param integer $aLevel The accesslevel which all objects will be tried against.
     * @param User $u The user which the accesslevel will be tried for
     * @param bool $keep Set to false to disable caching
     * @return object First object to match permissions
     */
    function any($set, $aLevel = ANYTHING, $u = false, $keep = true){
        if($set == false) return array();
        $objects = array();
        if(!is_array($set)) $set = array($set);
        foreach($set as $id) {
            if($o = $this->retrieve($id, $aLevel, $u, $keep)) return $o;
        }
        return false;
    }

    /**
     * Returns at most $nr of objects
     * @param array|resource $source Array or MySQL resource with an 'id' field
     * @param integer $nr Maximum number of returned
     * @param integer $aLevel The accesslevel which all objects will be tried against.
     * @param User $u The user which the accesslevel will be tried for
     * @return object First object to match permissions
     * @return array Array of objects
     */
    function max($source, $nr, $aLevel=ANYTHING, $u=false, $keep = true) {
        $result = array();
        for($i=0;$i<$nr;) {
            if(is_resource($source)) {
                $currentID = Database::fetchAssoc($source);
                if($currentID === false || !isset($currentID['id'])) return $result;
                $currentID = $currentID['id'];
            } elseif(is_array($source)) {
                $currentID = next($source);
                if($currentID === false) return $result;
            } else return false;
            if($current = $this->retrieve($currentID, $aLevel, $u, $keep)) {
                $result[$currentID] = $current;
                $i++;
            }
        }
        return $result;
    }

    /**
     * Releases an object from memory. The passed parameter will be set to the ID
     * of the released object
     * @param object|string|int $id Object to release
     * @return void
     */
    function release(&$id) {
        if(is_array($id)) {
            foreach($id as $obj) {
                $this->release($obj);
            }
            return;
        }
        if(is_string($id) && !is_numeric($id)) {
            if(isset($this->aliases[$id])) {
                $id = $this->aliases[$id];
            } else return;
        }
        elseif(is_object($id)) $id = $id->ID;
        $this->__unset($id);
    }

    /**
     * Forces the controller to reload an object by bypassing the cache.
     * @param mixed $obj The object which should be reloaded
     * @return void
     */
    function forceReload(&$obj) {
        if(is_object($obj)) $id = $obj->ID;
        else $id = $obj;
        if(is_numeric($id) && !isset($this->OBJECTS[$id])) return;
        if(is_numeric($id)) {
            $this->OBJECTS[$id] = $this->fetch($id, OVERRIDE);
            if(is_object($obj)) $obj = $this->OBJECTS[$id];
        }
    }

    /**
     * Fetch all objects of a specified class
     * @param $class Which class to fetch
     * @param $aLevel Which accesslevel should be applied
     * @param $u Which user should be tested for permission
     * @param $keep Wether to keep object in cache
     * @return array Array och objects
     */
    function getClass($class, $aLevel = ANYTHING, $u = false, $keep = true) {
        global $DB;
        return $this->get($DB->spine->asList(array('class' => $class), 'id'), $aLevel, $u, $keep);
    }
}
?>
