<?php
/**
 * @author Jonatan Olofsson [joolo]
 * @version 1.0
 * @license http://creativecommons.org/licenses/by-nc/3.0/ Creative Commons Attribution-Noncommercial 3.0 Unported License
 * @package Base
*/

/**
 * configuration class
 * @author Jonatan Olofsson [joolo]
 * @package Base
*/
class Config {
    private $sections = array();
    private $DBTable = 'config';

    /**
     * Sets up the database table
     * @return unknown_type
     */
    private function install() {
        global $DB, $USER;
        $DB->query("CREATE TABLE IF NOT EXISTS `".$this->DBTable."` (
  `section` varchar(255) character set utf8 NOT NULL,
  `property` varchar(255) character set utf8 NOT NULL default '',
  `value` text character set utf8 NOT NULL,
  `type` enum('text','CSV','not_editable','select','set','check','password') character set utf8 NOT NULL default 'text',
  `description` text character set utf8 NOT NULL,
  `set` blob NOT NULL,
  KEY `section` (`section`),
  KEY `property` (`property`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;");
    }

    /**
     * Drops the database table
     * @return unknown_type
     */
    function uninstall() {
        global $DB, $USER;
        if(!$USER->may(INSTALL)) return false;
        $DB->dropTable($this->DBTable);
    }

    /**
    * Returns a section or value from the configuration
    * @access public
    * @param string $property The name of the variable or Configuration section
    * @return mixed
    */
    function __get($property){
        global $DB;
        if(!isset($this->sections[$property])) $this->sections[$property] = new ConfigSection($property);
        return $this->sections[$property];
    }

    /**
    * Checks if a property is set
    * @access public
    * @param string $property The name of the property
    * @return mixed
    */
    function __isset($property){
        return isset($this->sections[$property]);
    }

    /**
    * Loads configuration from a file source
    * @access public
    * @param string $src The path to the file to be loaded
    * @return void
    */
    function loadFile($src){
        if(file_exists($src) && is_readable($src))
        {
            include $src;
            if(isset($CONFIG)) $this->add($CONFIG);
        }
    }

    /**
    * Loads configuration from the database
    * @access public
    * @return null
    */
    function loadFromDatabase(){
        global $DB;
        $resource = $DB->config->get();
        $list = array();
        while($row = $DB->fetchAssoc($resource))
        {
            if(!isset($this->sections[$row['section']])) $this->sections[$row['section']] = new ConfigSection($row['section']);
            $this->sections[$row['section']]->registerFromDatabase($row);
        }
    }

    /**
    * Adds an array of values to the configuration
    * @access public
    * @param array add Array of values
    * @return void
    */
    function add($add){
        if(is_array($add))
        {
            foreach($add as $p => $value){
                if(!isset($this->sections[$p])) $this->sections[$p] = new ConfigSection($p);
                $this->sections[$p]->register($value);
            }
        }
    }
}
/**
 * The ConfigSection class represents a separate area of the configuration, in turn representing f.e.
 * a certain module, an area of the site etc. It works basically as the first level of a multidimensional array
 * and is mainly used to tell configuration variables apart
 * @author Jonatan Olofsson
 * @package Base
 */
class ConfigSection {
    private $DATA = array();
    private $NAME;
    private $DESC;
    private $TYPE;

    /**
     * Sets up the class
     * @param $name
     * @return unknown_type
     */
    function __construct($name) {
        $this->NAME = $name;
    }

    /**
     * Returns a property asked for
     * Usage:
     * <code>
     * echo $CONFIG->site->name;
     * </code>
     * @param string $property
     * @return mixed
     */
    function __get($property) {
        if(isset($this->DATA[$property])) {
            if(@$this->TYPE[$property] == 'CSV' && @is_string($this->DATA[$property]))
                $val = explode(',', @$this->DATA[$property]);
            else $val = @$this->DATA[$property];

            return $val;
        }
    }

    /**
     * Updates the type of a parameter shown in the configuration editor.
     * @param string $property Which property to edit
     * @param string $to The type. Any of 'text','CSV','not_editable','select','set','check','password'
     * @return void
     */
    function setType($property, $to, $set=false) {
        global $DB;
        if(@$this->TYPE[$property] != $to) {
            $upd = array('type' => $to, 'set' => $set);
            if($to == 'CSV' && is_array(@$this->DATA[$property])) {
                $this->DATA[$property] = $upd['value'] = join(',',$this->DATA[$property]);
            } elseif(@$this->TYPE[$property] == 'CSV') {
                $this->DATA[$property] = $upd['value'] = explode(',',$this->DATA[$property]);
            }
            $this->TYPE[$property] = $to;
            $DB->config->update($upd, array('section' => $this->NAME, 'property' => $property), true);
        }
    }

    /**
     * Updates the description of a parameter shown in the configuration editor
     * @param string $property Which property to edit
     * @param string $to The description
     * @return void
     */
    function setDescription($property, $to) {
        global $DB;
        if(@$this->DESC[$property] != $to)
            $DB->config->update(array('description' => $to), array('section' => $this->NAME, 'property' => $property), true);
    }

    /**
     * Sets a configuration parameter
     * Usage:
     * <code>
     * $CONFIG->site->name = "solidba.se";
     * </code>
     * @param string $property The property to be set
     * @param mixed $value The value which to set the property with
     * @return void
     */
    function __set($property, $value) {
    global $DB;
        if(is_array($value) && @$this->TYPE[$property] === 'CSV') $value = join(',',$value);
        if(!isset($this->DATA[$property])
        || $this->DATA[$property] !== $value) {
            $DB->config->update(array('value' => $value), array('property' => $property, 'section' => $this->NAME), true);
            $this->DATA[$property] = $value;
        }
    }

    /**
    * Checks if a section is set
    * @access public
    * @param string $property The name of the variable or Configuration section
    * @return mixed
    */
    function __isset($property){
        return isset($this->DATA[$property]);
    }

    /**
     * Deletes a configuration parameter
     * @param string $property Property name
     * @return void
     */
    function remove($property) {
        global $DB;
        unset($this->DATA[$property]);
        $DB->config->delete(array('section' => $this->NAME, 'property' => $property));
    }

    /**
     * Register an array of configuration parameters into the cache
     * @param array $array Array to be registered
     * @return void
     */
    function register($array) {
        if(is_array($array)) $this->DATA += $array;
    }

    /**
     * Register a row from the database in the cache
     * @param array $arr The row to be registered
     * @return void
     */
    function registerFromDatabase($arr) {
        $this->DATA[$arr['property']] = $arr['value'];
        $this->DESC[$arr['property']] = $arr['description'];
        $this->TYPE[$arr['property']] = $arr['type'];
    }
}

?>
