<?php
/**
 * Database.php
 * The library for creating and executing database queries.
 *
 * @author Jonatan Olofsson [joolo]
 * @version 1.0
 * @license http://creativecommons.org/licenses/by-nc/3.0/ Creative Commons Attribution-Noncommercial 3.0 Unported License
 * @package Base
 */

/**
 * database
 * Handles all calls to the database
 * @package Base
 * @todo Fix the use of tblPrefix
 */
class Database{
    private $Connection;
    private $lastQuery;
    private $resource;
    private $tables = array();
    private $tableNames = array();

    private $host;
    private $username;
    private $password;
    private $db;
    private $tblPrefix;
    private $charset;

    private $queries;
    private $rawQueries=array();
    private $queryTime;

    /**
     * Returns the value of the requested property, if allowed.
     * <code>
     * $DB->pages; // Pages is a table in the database
     * </code>
     * @param string $property The requested variable or database table
     * @return mixed|DBTable
     */
    public function __get($property){
        if(in_array($property, array('rawQueries', 'lastQuery','resource', 'queries', 'queryTime', 'Connection', 'tableNames'))) return $this->$property;
        elseif($property == 'numRows') return $this->numRows();
        elseif(isset($this->tables[$property])) return $this->tables[$property];
        elseif(strpos($property, ',')) {
            $tables = array_map('trim', explode(',', $property));
            foreach($tables as $tbl) {
                if(!isset($this->tables[$tbl])) return null;
            }
            $this->tables[$property] = new JointDatabaseTable($tables, $this);
            return $this->tables[$property];
        }
        else return null;
    }

    /**
     * Initiates the database class and sets up the connection
     * @access protected
     * @param string $host
     * @param string $username
     * @param string $password
     * @param string $db
     * @param string $tblPrefix
     */
    function __construct($host, $username, $password, $db, $tblPrefix, $charset){
        $this->host = $host;
        $this->username = $username;
        $this->password = $password;
        $this->db = $db;
        $this->tblPrefix = $tblPrefix;
        $this->charset = $charset;

        if($this->Connection = mysql_connect($host, $username, $password))
        {
            mysql_set_charset($charset, $this->Connection);
            if(mysql_select_db($db, $this->Connection))
            {
                $this->loadTables();
            }
        }
    }

    /**
     * Load the tables that the Database user has access to into memory
     * @return void
     */
    function loadTables() {
        $r = $this->query("SHOW TABLES FROM `".mysql_escape_string($this->db)."`");
        while($row = Database::fetchRow($r)) {
            if(empty($this->tblPrefix) || strstr($row[0], $this->tblPrefix) === 0) {
                $rName = substr($row[0], strlen($this->tblPrefix));
                if(!in_array($rName, $this->tableNames)) {
                    $this->tables[$rName] = new DatabaseTable($row[0], $this);
                    $this->tableNames[] = $rName;
                }
            }
        }
    }

    /**
     * Returns the number of rows that were selected of affected by the previous query. Returns false on failure
     * @param resource $r Resource from Database query to check for number of selected rows instead of the previous Database-query
     * @return integer
     */
    function numRows($r=false) {
        if($r) $resource = $r;
        else $resource = $this->resource;

        if(false !== ($res = mysql_num_rows($resource))) {
            return $res;
        } elseif(!$r) {
            return mysql_affected_rows($this->Connection);
        }
        else return false;
    }

    /**
     * Reset the resource for a new query
     * @return unknown_type
     */
    function reset() {
        $this->resource = false;
    }

    /**
    * query()
    * @desc Executes the given query and returns the resource
    * @access public
    * @param string $sql SQL-query
    * @return resource
    */
    function query($sql){
        $this->reset();
        $this->queries++;
        $start = microtime(true);
        $this->resource = mysql_query($sql, $this->Connection);
        $stop = microtime(true);
        $qt = $stop-$start;
        $this->queryTime += $qt;
        $this->lastQuery = $sql;
        //$this->rawQueries[] = array('Query' => $sql, 'QueryTime' => $qt);
        //$this->rawQueries[] = array('Query' => $sql, 'Time' => microtime());
        $this->rawQueries[] = $sql;

        if(strtoupper(substr($sql, 0, 6)) == 'CREATE') $this->loadTables();
        // Trace database updates for debugging purposes:
        //if(strtoupper(substr($sql, 0, 6)) == 'UPDATE') dump(trace());

        //FIXME: Remove?
        $DEBUG = false;
        if($DEBUG && !$this->resource) {
            dump(mysql_error(), $sql);
            echo '<pre>';
            debug_print_backtrace();
            echo '</pre>';
        }
        return $this->resource;
    }

    /**
     * Returns Whether a certain database table is available
     * @param string $table The name of the table
     * @return bool
     */
    function tableExist($table) {
        return in_array($this->tblPrefix.$table,$this->tableNames);
    }

    /**
    * Returns the query result as an array
    * @access public
    * @param string $sql SQL-query
    * @param bool $fck If true, use the first selected column as array key
    * @return array
    */
    function asArray($sql = false, $fck = false){
        if(is_resource($sql)) $this->resource = $sql;
        elseif(is_string($sql)) $this->query($sql);

        if($this->resource == false) return false;
        $a = array();
        while($row = $this->fetchAssoc($this->resource)) {
            if(count($row)>1 && $fck) $a[array_shift($row)] = $row;
            else $a[] = $row;
        }
        return $a;
    }

    /**
    * Returns the query result as a list
    * @access public
    * @param string $sql SQL-query
    * @param bool $fck If true, use the first selected column as array key
    * @return array
    */
    function asList($sql = false, $fck = false){
        if(is_resource($sql)) $this->resource = $sql;
        elseif(is_string($sql)) $this->query($sql);
        if($this->resource == false) return false;
        $a = array();
        $cols = false;
        while($row = $this->fetchRow($this->resource)) {
            $cols = ($cols===false?count($row):$cols);
            if($fck) {
                if($cols == 1) {
                    $a[$row[0]] = $row[0];
                } elseif($cols == 2) {
                    $a[$row[0]] = $row[1];
                } else {
                    $a[array_shift($row)] = $row;
                }
            } else {
                $a[] = $row[0];
            }
        }
        return $a;
    }

    /**
     * Returns a multidimensional array from a result with at least 2 columns
     * @param $sql
     * @param $mRES Return the last level as an array instead of a single value
     * @return unknown_type
     */
    function asMDList($sql = false, $mRES = false) {
        if(is_resource($sql)) $this->resource = $sql;
        elseif(is_string($sql)) $this->query($sql);
        if($this->resource == false) return false;
        $a = array();
        $cols = false;
        while($row = $this->fetchRow($this->resource)) {
            Database::MDHelp($row, $a, $mRES);
        }
        return $a;
    }

    /**
     * Recursive helper function to buil multidimensional array
     * @param $cols
     * @return unknown_type
     */
    function MDHelp($cols, &$a, $mRES=false) {
        $col = array_shift($cols);
        if($cols) {
            if(!isset($a[$col])) $a[$col] = array();
            Database::MDHelp($cols, $a[$col], $mRES);
        } else {
            if($mRES) $a[] = $col;
            else $a = $col;
        }
    }

    /**
     * Returns a single cell from the result
     * @param $sql
     * @param $row
     * @param $col
     * @return string
     */
    function getCell($sql, $row=0, $col=0) {
        if(is_resource($sql)) $this->resource = $sql;
        elseif(is_string($sql)) $this->query($sql);
        if($this->resource == false) return false;
        return @mysql_result($this->resource, $row, $col);
    }

    /**
     * Encode an array for database insertion
     * @param array|object $val
     * @return string
     */
    function arrayEncode($val){
        return 'b64arrenc:'.base64_encode(serial($val));
    }

    /**
     * Decode an array or object encoded with arrayEncode
     * @see arrayEncode()
     * @param string $val Encoded array
     * @return array|object
     */
    function arrayDecode($val){
        return unserial(base64_decode(substr($val, 10)));
    }

    /**
     * Fetch an associative array from the next row in the database resource and decode if nescessary
     * @param $resource
     * @return array
     */
    static function fetchAssoc($resource = -1){
        global $DB;
        $vals = @mysql_fetch_assoc(($resource == -1?$DB->$resource:$resource));
        if(!$vals) return false;
        foreach($vals as $key => $val) {
            if(substr($val, 0, 10) == 'b64arrenc:') $vals[$key] = self::arrayDecode($val);
            if(substr($val, 0, 11) == 'nb64arrenc:') $vals[$key] = substr($val, 1);
        }
        return $vals;
    }

    /**
     * Fetch an array from the next row in the database resource and decode if nescessary
     * @param $resource
     * @return array
     */
    static function fetchArray($resource = -1, $type = MYSQL_BOTH){
        global $DB;
        $vals = mysql_fetch_array(($resource == -1?$DB->$resource:$resource));
        if(!$vals) return false;
        foreach($vals as $key => $val) {
            if(substr($val, 0, 10) == 'b64arrenc:') $vals[$key] = self::arrayDecode($val);
            if(substr($val, 0, 11) == 'nb64arrenc:') $vals[$key] = substr($val, 1);
        }
        return $vals;
    }

    /**
     * Fetch a numeric array from the next row in the database resource and decode if nescessary
     * @param $resource
     * @return array
     */
    static function fetchRow($resource = -1){
        global $DB;
        $vals = mysql_fetch_row(($resource == -1?$DB->$resource:$resource));
        if(!$vals) return false;
        foreach($vals as $key => $val) {
            if(substr($val, 0, 10) == 'b64arrenc:') $vals[$key] = self::arrayDecode($val);
            if(substr($val, 0, 11) == 'nb64arrenc:') $vals[$key] = substr($val, 1);
        }
        return $vals;
    }

    /**
     * Fetch a single cell from a database resource
     * @param $resource
     * @param integer $row Which row the wanted result is in
     * @param integer|string $field Which column name or number that should be returned
     * @return mixed
     */
    static function result($resource=-1, $row=0, $field=0){
        global $DB;
        $val = @mysql_result(($resource == -1?$DB->$resource:$resource), $row, $field);
        if(substr($val, 0, 10) == 'b64arrenc:') $val = self::arrayDecode($val);
        if(substr($val, 0, 11) == 'nb64arrenc:') $val = substr($val, 1);
        return $val;
    }

    /**
    * Escapes a string for database insertion
    * @access public
    * @param string $val The value that should be escaped
    * @param bool $mult Whether an incoming array should be treated as such or as several separate calls
    * @param bool $exec Whether the SQL is supposed to contain a MYSQL function
    * @param bool $enquote Wrap the result with "'"
    * @return string|array
    */
    static function escape($val, $mult=false, $exec=false, $enquote=false){
    global $DB;
        if(is_array($val) || is_object($val)) {
            if($mult) {
                foreach($val as &$v) $v = Database::escape($v, false, $exec, $enquote);
                return $val;
            }
            else $val = Database::arrayEncode($val);
        } elseif(is_string($val) && substr($val, 0, 10) == 'b64arrenc:') $val = 'n'.$val;
        if($exec && preg_match('#^([^\(]+)\([^\(]*\)$#', $val)) {
            return $val;
        } elseif($enquote) {
            return "'".mysql_real_escape_string($val, $DB->Connection)."'";
        } else {
            return mysql_real_escape_string($val, $DB->Connection);
        }
    }

    /**
     * Returns Whether a search gives any results (at all, i.e. if a row with the criteria exists in the table)
     * @access public
     * @param array|string $cond An associative array with conditions for the SQL-query (alternatively a string with the same)
     * @return bool
    */
    function exists($r){
        if(!is_resource($r)) $r = $this->query($r);
        return (bool)mysql_num_rows($r);
    }

    /**
    * Creates a new database table
    * @access public
    * @param string tableName The name of the table
    * @param array $columns Array of SQL descriptions of each column
    * @return void
    */
    function newTable($tableName, $columns){
        if($this->query("CREATE TABLE IF NOT EXISTS " . $this->tblPrefix.$tableName . " (" .
                (is_array($columns) ? implode(", ", $columns) : $columns) . ")"))
            $this->tables[$tableName] = new DatabaseTable;
    }

    /**
    * Drops a table from the database
    * @access public
    * @return void
    */
    function dropTable() {
        $tbls = func_get_args();
        foreach($tbls as $tbl) {
            $this->query("DROP TABLE IF EXISTS " . $tbl. "");
            unset($this->tables[$tbl]);
            unset($this->tableNames[array_search($tbl, $this->tableNames)]);
        }
    }

    /**
    * Closes the database connection
    * @access public
    * @return void
    */
    function close(){
        if(mysql_close($this->Connection)) $this->Connection = false;
    }
}
/**
 * Each table in the database is assigned a DatabaseTable object which
 * 	controls most interaction with that table
 * @author Jonatan Olofsson [joolo]
 * @package Base
 */
class DatabaseTable{
    protected $parent;
    private $name;
    private $columns;
    private $isLoaded = false;

    /**
     * Sets up the internal relations between the table and it's database
     * @access protected
     * @param string $tableName The name of the table that is beeing loaded
     * @param database $parent Parent database object
     */
    function __construct($tableName, $parent){
        $this->parent = $parent;
        $this->name = $tableName;
    }

    private $__loaded=false;
    function loadDatabaseStructure() {
        if($this->__loaded) return;
        $this->__loaded = true;
        $r = $this->parent->query("SHOW COLUMNS FROM ".$this->name);
        while($res = Database::fetchAssoc($r)) $this->columns[] = $res['Field'];
    }

    /**
    * Returns the row with the id asked for
    * @access public
    * @param numeric $property
    * @return array
    */
    function __get($property){
        if(is_numeric($property)) return $this->getRow($property);
        elseif(isset($this->$property)) return $this->$property;
    }

    /**
     * Updates the database row corresponding to the id, with the given value
     * @access public
     * @param numeric $property The id of the database post that is beeing changed
     * @param array $value An associative array with the values that should be inserted into the database
     * @return void
    */
    function __set($property, $value){
        if(is_numeric($property) && is_array($value)) return $this->update($value, array('id' => $property), true);
    }

    /**
     * Returns the resource with the rows selected by the condition. This function ties many of the other functions
     * toghether, as they all more or less depends on this one to build the SELECT query.
     * The following syntax examples thereby applies to most other DatabaseTable methods as well
     * <code>
     * $DB->pages->get(array('title' => 'Start')); //Get the page with the title 'Start'
     * $DB->pages->get(array('title!' => 'Start')); // Get all pages <i>except</i> the one with the title 'Start'.
     * $DB->pages->get(array('#!time' => 'NOW()')); // A shebang in front of the field name enables MySQL function exectution
     * $DB->pages->get(array('!!pwd' => 'PASSWORD("data")')); // A double exclamation mark in front of the field name marks that the data should not be escaped at all. Use with care!
     * $DB->pages->get(array('title!' => 'Start')); // Get all pages <i>except</i> the one with the title 'Start'.
     * $DB->pages->get(null, 'id, title', '2,4'); // Get the id and title from the pages table, starting from row 2, returning 4 rows
     * $DB->pages->get(4); //Equals $DB->pages-get(array('id' => "4"));
     * </code>
     * @access public
     * @param array|string $cond An associative array with conditions for the SQL-query (alternatively a string with the same)
     * @param string $cols Comma-separated list of requested columns
     * @param numeric|string $limit The numeric limitations of the SELECT query
     * @param string $order Which column to order the results by
     * @param string $group Which column(s) to group the result by
     * @param bool $OR Whether the conditions should be treated as alternatives (true) or multiple resraints (false)
     * @param bool $distinct Return distinct results
     * @return resource
    */
    function get($cond=false, $cols = false, $limit=false, $order=false, $group=false, $OR = false, $distinct=false) {
        $cond = $this->collapse($cond);
        return $this->parent->query("SELECT ".($distinct?'DISTINCT ':'').
                ($cols == false ? '*' : $this->prepareCols($cols)).
                " FROM `" . $this->name . "`" .
            (count($cond)>0 ? " WHERE " . implode(" ".($OR?'OR':'AND')." ", $cond) : "").
            ($group != false ? " GROUP BY " . $group : "").
            ($order != false ? " ORDER BY " . $order : "").
            ($limit != false ? " LIMIT " . $limit : ""));
    }

    /**
     * Prepare a set of columns for querying
     * @param strng|array $cols The columns to be escaped
     * @return string
     */
    function prepareCols($cols) {
        $this->loadDatabaseStructure();
        if($cols == '*') return $cols;
        if(!is_array($cols))
            $cols = explode(',', $cols);

        $cols = array_map('trim', $cols);

        $r = '';
        foreach($cols as $col) {
            if($col == '*') {
                $r .= ',`'.$this->name.'`.'.$col;
            }
            elseif(preg_match('#^[\'"].+[\'"]$#', $col)) {
                $r .= ',' . Database::escape(substr($col,1,-1), false, false, true);
            }
            elseif(preg_match('#^(?P<func>[^\(]+)\((?P<param>[^\(]*)\)$#', $col)) {
                $r .= ',' . $col;
            } elseif(in_array($col, $this->columns)) {
                $r .= ',`'.$this->name.'`.`'.$col.'`';
            }
        }
        return substr($r, 1);
    }

    /**
     * Returns the selected values as an array
     * @access public
     * @param array|string $cond An associative array with conditions for the SQL-query (alternatively a string with the same)
     * @param string $cols Comma-separated list of requested columns
     * @param numeric|string $limit The numeric limitations of the SELECT query
     * @param string $order Which column to order the results by
     * @param string $group Which column(s) to group the result by
     * @param bool $OR Whether the conditions should be treated as alternatives (true) or multiple resraints (false)
     * @param bool $distinct Return distinct results
     * @return resource
     */
    function asArray($cond=false, $cols = false, $limit=false, $fck=false, $order=false, $group=false, $OR=false, $distinct=false){
        return $this->parent->asArray($this->get($cond, $cols, $limit, $order, $group, $OR, $distinct), $fck);
    }

    /**
     * Returns the selected values as a list
     * @access public
     * @param array|string $cond An associative array with conditions for the SQL-query (alternatively a string with the same)
     * @param string $cols Comma-separated list of requested columns
     * @param numeric|string $limit The numeric limitations of the SELECT query
     * @param bool $fck If true, use the first selected column as array key
     * @param string $order Which column to order the results by
     * @param string $group Which column(s) to group the result by
     * @param bool $OR Whether the conditions should be treated as alternatives (true) or multiple resraints (false)
     * @param bool $distinct Return distinct results
     * @return resource
     */
    function asList($cond=false, $cols = false, $limit=false, $fck = false, $order=false, $group=false, $OR=false, $distinct=false){
        return $this->parent->asList($this->get($cond, $cols, $limit, $order, $group, $OR, $distinct), $fck);
    }

    /**
     * Returns the selected values as a multidimensional list
     * @access public
     * @param array|string $cond An associative array with conditions for the SQL-query (alternatively a string with the same)
     * @param string $cols Comma-separated list of requested columns
     * @param numeric|string $limit The numeric limitations of the SELECT query
     * @param string $group Which column(s) to group the result by
     * @param bool $OR Whether the conditions should be treated as alternatives (true) or multiple resraints (false)
     * @return resource
     */
    function asMDList($cond=false, $cols = false, $limit=false, $group=false, $OR=false, $multiple_results=false){
        return $this->parent->asMDList($this->get($cond, $cols, $limit, false, $group, $OR), $multiple_results);
    }

    /**
     * Returns the number of hits a certain condition gets in a database query
     * @param array|string $cond An associative array with conditions for the SQL-query (alternatively a string with the same)
     * @return integer
     */
    function count($cond=false, $distinct=false){
        return (int)$this->getCell($cond, 'COUNT('.($distinct ? 'DISTINCT'.($distinct !== true ? ' '.$distinct : '') : '*').')');
    }

    /**
     * Inserts a new row in the table and returns the id of the inserted row (if such exist)
     * Returns false if the insertion failed and 0 if the insertion did not generate an AUTO_INCREMENT value
     * @param array $values an associtive array of values to insert. The syntax rules are the same as for $cond in Database::get()
     * @param bool $replace Whether to use the SQL REPLACE syntax instead. This replaces on duplicate key.
     * @param bool $weak If true, perform a check to see if a row with the same values already exist. If so, return without action. Sending (int) 2 to this argument will create a INSERT IGNORE query, a quicker way if the columns are unique
     * @param bool $return_success If set to true, the function will return a boolean true if the insertion was successful, false otherwise
     * @return integer|bool
     */
    function insert($values=false, $replace=false, $weak=false, $return_success=false) {
        if($values == false) $values = array();
        if(count($values)>0 && $weak == 1) {
            if($this->exists($values, false, true)) return ($return_success?false:null);
        }
        $vals = $this->collapse($values, false);
        $this->parent->query(	($replace == false ? "INSERT".($weak == 2 ? ' IGNORE':'')." INTO " : "REPLACE ").
                        "`".$this->name."`".
                        (count($values) > 0 ? " SET " . implode(", ", $vals) : " () VALUES ()"));

        if($return_success) return (mysql_affected_rows($this->parent->Connection)>0);

        if($this->parent->resource) return mysql_insert_id($this->parent->Connection);
        else return false;
    }

    /**
     * Insert multiple rows at the same time.
     * @param array $values An associtive array of values to insert. The values should be arrays of same lengths. Non-arrays or arrays of shorter length will be repeated to the length of the longest value array.
     * @param bool $replace Whether to use the SQL REPLACE syntax instead. This replaces on duplicate key.
     * @param bool $weak If true, perform a check to see if a row with the same values already exist. If so, return without action. Sending (int) 2 to this argument will create a INSERT IGNORE query, a quicker way if the columns are unique
     * @param bool $return_success If set to true, the function will return a boolean true if the insertion was successful, false otherwise
     * @return integer|bool
     */
    function insertMultiple($values=false, $replace=false, $weak=false, $return_success=false) {
        if($values == false) $values = array();
        if(count($values)>0 && $weak == 1) {
            if($this->exists($values)) return ($return_success?false:null);
        }
        $vals = $this->collapseMultipleRows($values);
        if(empty($vals) || $vals[0].$vals[1]=='') return false;

        $this->parent->query(	($replace == false ? "INSERT".($weak == 2 ? ' IGNORE':'')." INTO " : "REPLACE ").
                        "`".$this->name."` (".$vals[0].") VALUES (".join('),(', $vals[1]).")");

        if($return_success) return (mysql_affected_rows($this->parent->Connection)>0);

        if($this->parent->resource) return mysql_insert_id($this->parent->Connection);
        else return false;
    }

    /**
     * Inserts multiple rows into the database. The values parameter should be an array of arrays, containing the data for each row.
     * If a row is short of an attribute, the previous used value is used. The first row must contain all columns
     * @param $values Values to insert
     * @param $replace Replace on key instead of insert
     * @param $weak Do not insert duplicates
     * @param $return_success Return success instead of inserted id
     * @return bool|int Last inserted id or success
     */
    function insertMultipleRows($values=false, $replace=false, $weak=false, $return_success=false) {
        if($values == false) $values = array();
        if(count($values)>0 && $weak == 1) {
            if($this->exists($values)) return ($return_success?false:null);
        }
        $vals = $this->collapseMultipleRows2($values);
        if(empty($vals) || $vals[0].$vals[1]=='') return false;

        $this->parent->query(	($replace == false
                                    ? "INSERT" .($weak == 2 ? ' IGNORE':'')." INTO "
                                    : "REPLACE ").
                        "`".$this->name."` (`".join('`,`',$vals[0])."`)"
                        ." VALUES (".join('),(', $vals[1]).")");

        if($return_success) return (mysql_affected_rows($this->parent->Connection)>0);

        if($this->parent->resource) return mysql_insert_id($this->parent->Connection);
        else return false;
    }

    /**
     * Takes an associative array and turns it into an array of SQL parts
     * 	to be used to insert multiple rows in the db at once
     * @param array $values The data that should be turned into SQL
     * @return array
     */
    function collapseMultipleRows($values){
        $this->loadDatabaseStructure();
        if(is_numeric($values)) $values = array('id' => $values);
        if($values === false || $values === null) return array();
        if(!is_array($values)) return array($values);

        $vals = array();
        $cols = array();
        $lengths = array();
        $res[0] = '';
        $res[1] = array();
        foreach($values as $col => $val) {
            if(is_int($col)) {
                continue;
            }
            if(in_array($col, $this->columns)) {
                if(is_array($val)) {
                    if(empty($val)) continue;
                    $lengths[$col] = count($val);
                } else {
                    $lengths[$col] = 1;
                    $val = array($val);
                }
                $vals[$col] = $val;
                $res[0] .= ",`".$col."`";
            }
        }
        $res[0] = substr($res[0], 1);

        $maxlen = max($lengths);
        for($i=1;$i<=$maxlen;$i++) {
            $tmp=array();
            foreach($vals as $col => $val){
                $tmp[] = Database::escape($val[$i%$lengths[$col]], false, false, true);
            }
            $res[1][] = join(",", $tmp);
        }
        return $res;
    }

    /**
     * Takes an associative array and turns it into an array of SQL parts
     * 	to be used to insert multiple rows in the db at once
     * @param array $valueArray Array of insertion sets. The first must
     * 	define all columns that should be inserted. Missing values in
     * 	following sets will be replaced with the previous value
     * @return array
     */
    function collapseMultipleRows2($valueArray){
        $this->loadDatabaseStructure();
        if(is_numeric($valueArray)) $valueArray = array(array('id' => $valueArray));
        if($valueArray === false || $valueArray === null) return array();
        if(!is_array($valueArray)) return array($valueArray);

        $vals = array();
        $cols = array();
        $lengths = array();
        $res[0] = array();
        $res[1] = array();
        $res[0] = array_keys($valueArray[0]);
        foreach($valueArray as $values) {
            if(is_numeric($values)) $values = array('id' => $values);
            foreach($values as $col => $val) {
                if(is_int($col)) {
                    continue;
                }
                if(in_array($col, $this->columns) && in_array($col, $res[0])) {
                    $vals[$col] = $val;
                }
            }

            $tmp = Database::escape($vals, true, false, true);
            $res[1][] = join(",", $tmp);
        }
        return $res;
    }

    /**
     * Takes an associative array and turns it into an array of SQL parts.
     * 	This also recognizes and handles the syntax documented for
     * 	$cond in Database::get().
     * @param array $values The data that should be turned into SQL
     * @param bool $select If the data should be used in a SELECT query,
     * 	use slightly different rules
     * @return array
     */
    function collapse($values, $select=true){
        $this->loadDatabaseStructure();
        if(is_numeric($values)) $values = array('id' => $values);
        if($values === false || $values === null) return array();
        if(!is_array($values)) return array($values);

        $vals = array();
        foreach($values as $col => $val){
            if(is_int($col)) {
                $vals[] = $val;
                continue;
            }
            $neg = false;
            $exec = false;
            $noescape = false;
            $lt = false;
            $gt = false;
            $e = true;
            $like = false;

            switch(substr($col, -1, 1)) {
                case '!': 	$neg = true;
                            $col = substr($col, 0, -1);
                        break;
                case '=':	$e = true;
                            $col = substr($col, 0, -1);
                        break;
                case '<':	$e = false;
                            $lt = true;
                            $col = substr($col, 0, -1);
                        break;
                case '>':	$e = false;
                            $gt = true;
                            $col = substr($col, 0, -1);
                        break;
                case '~':	$e = false;
                            $gt = false;
                            $like = true;
                            $col = substr($col, 0, -1);
                        break;
            }

            switch(substr($col, -1, 1)) {
                case '!': 	$neg = true;
                            $col = substr($col, 0, -1);
                        break;
                case '<':	$lt = true;
                            $col = substr($col, 0, -1);
                        break;
                case '>':	$gt = true;
                            $col = substr($col, 0, -1);
                        break;
            }

            switch(substr($col, 0,2)) {
                case '#!':	$exec = true;
                            $col = substr($col,2);
                        break;
                case '!!':	$noescape = true;
                            $col = substr($col,2);
                        break;
            }

            if(in_array($col, $this->columns)) {
                if(is_array($val) && $select){
                    if($val)
                        $vals[] = "`".$this->name."`.`".$col."`" . "".($neg?' NOT':'')." IN (".join(',', ($noescape?$val:Database::escape($val, true, $exec, true))).')';
                }
                else {
                    $vals[] = "`".$this->name."`.`".$col."`" . ($neg?($like?' NOT':'!'):'').($like?' LIKE ':(($lt?'<':'').($gt?'>':'').($e?'=':''))).($noescape?$val:Database::escape($val, false, $exec, true));
                }
            }
        }
        return $vals;
    }

    /**
     * Updates the database with the values given and returns the number of affected rows
     * @access public
     * @param array values Associative array of new values to be inserted into the database. The syntax rules are the same as for $cond in Database::get()
     * @param array|string $condition The conditions to select the database post(s) to update. The syntax rules are the same as for $cond in Database::get()
     * @param bool $insert If set, and if affected rows are zero, this function will combine the $values
     * and $condition arrays ($condition must be given as array) and insert a new row with this data
     * @param bool $OR Whether the conditions should be treated as alternatives (true) or multiple resraints (false)
     * @return integer
    */
    function update($values, $condition, $insert=false, $limit=1, $OR = false){
        if(!is_array($values)) return;
        $vals = array();
        $vals = $this->collapse($values, false);
        if(count($vals) == 0) return 0;
        $cond = $this->collapse($condition);
        $response = $this->parent->query(	"UPDATE `".
                        $this->name . "` SET " . implode(", ", $vals)
                        .($cond != false ? " WHERE " . implode(" ".($OR?'OR':'AND')." ", $cond) : "")
                        .($limit?' LIMIT '.$limit:''));
        $arows = mysql_affected_rows($this->parent->Connection);
        if($insert == false || $arows>0) return $arows;
        if($arows == 0 && is_array($values) && is_array($condition))
            return (int)(bool)$this->insert(array_merge($values, $condition), false, true, true);
        else return 0;
    }

    /**
     * Deletes the rows matching the condition
     * @access public
     * @param array|string cond The conditions to identify the rows that should be deleted. The syntax rules are the same as for $cond in Database::get()
     * @param bool $limit Limit the number of deleted rows
     * @param bool $OR Whether the conditions should be treated as alternatives (true) or multiple resraints (false)
     * @return int Number of rows deleted
    */
    function delete($cond,$limit=1, $OR=false){
        $cond = $this->collapse($cond);
        $this->parent->query("DELETE FROM `" . $this->name . "` WHERE " . implode(" ".($OR?'OR':'AND')." ", $cond) . ($limit?' LIMIT '.$limit:''));
        return mysql_affected_rows($this->parent->Connection);
    }

    /**
     * Get a single column from a single row in the database
     * @access public
     * @param array|string $cond An associative array with conditions for the SQL-query (alternatively a string with the same). The syntax rules are the same as for $cond in Database::get()
     * @param string $col Which column that should be selected
     * @param string $order Which column to order the results by
     * @param string $group Which column(s) to group the result by
     * @param bool $OR Whether the conditions should be treated as alternatives (true) or multiple resraints (false)
     * @return mixed
    */
    function getCell($cond=false, $col=false, $order=false, $group=false, $OR=false){
        $r = $this->get($cond, $col, 1, $order, $group, $OR);
        if($r) return Database::result($r, 0, 0);
        else return false;
    }

    /**
     * Returns a single row as an array of it's column's values.
     * @access public
     * @param array|string $cond An associative array with conditions for the SQL-query (alternatively a string with the same). The syntax rules are the same as for $cond in Database::get()
     * @param string $cols Which columns that should be selected
     * @param string $order Which column to order the results by
     * @param string $group Which column(s) to group the result by
     * @param bool $OR Whether the conditions should be treated as alternatives (true) or multiple resraints (false)
     * @return array
    */
    function getRow($cond=false, $cols=false, $order=false, $group=false, $OR=false){
        $r = $this->get($cond, $cols, 1, $order, $group, $OR);
        if($r && mysql_num_rows($r) > 0)
            return $this->parent->fetchAssoc($r);
        else return false;
    }

    /**
     * Searches the database for matching values in specified columns
     * @access public
     * @param array|string Search criteria
     * @param array|string string Comma-separated list or array of columns to search
     * @param string Which columns to return from the search
     * @param bool $BINARY Perform a binary search
     * @return resource
     */
    function search($needles, $columns="*", $returnCols="*", $BINARY = false){
        $this->loadDatabaseStructure();
        if(!is_array($needles)) $needles = array($needles);
        $cols = ($columns == "*"
            ? $this->columns
            : array_intersect($this->columns,
                    (is_array($columns)
                        ? $columns
                        : explode(',', str_replace(' ','',$columns)))));
        $s = "MATCH(".implode(', ',$cols).") AGAINST ('".implode(' ', $needles)."')";
        return $this->get($s, $returnCols);
    }

    /**
     * Searches the database for matching values in specified columns using LIKE
     * @access public
     * @param array|string $needles Search criteria
     * @param string $columns string Comma-separated list of columns to search
     * @param string $returnCols Which columns to return from the search
     * @return resource
     */
    function like($needles, $columns="*", $returnCols="*", $omitWildcard=false){
        $this->loadDatabaseStructure();
        if(!is_array($needles)) $needles = array($needles);
        $cols = ($columns == "*" ? $this->columns : array_intersect($this->columns, explode(',', str_replace(' ','',$columns))));
        $s = "";
        foreach($needles as $needle) {
            foreach($cols as $col) {
                $s .= " || " . $col . " LIKE '". ($omitWildcard?'':'%') . Database::escape($needle, $this->parent->Connection) . ($omitWildcard?'':'%') . "'";
            }
        }
        $s = substr($s, 4);
        return $this->get($s, $returnCols);
    }

    /**
    * Searches the database for matching column=>value pairs from array
    * @access public
    * @param array $searchArray Search criteria. Array with column=>value pairs
    * 	that should be matched as column LIKE '%value%' in the database
    * @param string $returnCols Which columns to return from the search
    * @return resource
    */
    function searchFromArray($searchArray, $returnCols="*"){
        if(!is_array($needles)) return false;
        $s = "";
        foreach($searchArray as $col => $needle) {
            $s .= " || " . $col . " LIKE '%" . Database::escape($needle, $this->parent->Connection) . "%'";
        }
        $s = substr($s, 4);
        return $this->get($s, $returnCols);
    }

    /**
     * Returns Whether a search gives any results (at all, i.e. if a row with the criteria exists in the table)
     * @access public
     * @param array|string $cond An associative array with conditions for the SQL-query (alternatively a string with the same)
     * @return bool
    */
    function exists($cond, $OR=false, $encodeArrays=false){
        $cond = $this->collapse($cond,!$encodeArrays);
        return (mysql_num_rows($this->parent->query("SELECT * "
                ." FROM `" . $this->name . "`" .
                (count($cond)>0 ? " WHERE " . implode(" ".($OR?'OR':'AND')." ", $cond) : "").
                " LIMIT 1")) == 1);
    }

    /**
     * Returns true if the specified column is part of the table
     * @param string $col Column to check
     * @return bool Wether the column is part of the table or not
     */
    function hasCol($col) {
        $this->loadDatabaseStructure();
        return in_array($col, $this->columns);
    }
}

/**
 * When a joint table is requested, an object is created of this class to
 * 	take care of the actions related to the joint table.
 * @author Jonatan Olofsson [joolo]
 * @package Base
 */
class JointDatabaseTable extends DatabaseTable {
    private $tables;
    private $tblString;

    function __construct($tables, $parent) {
        $this->tables = $tables;
        $this->parent = $parent;
        $this->tblString = '';
        $tmpTblArray = array();
        $tmpTables = array_filter($tables, create_function('$a', 'global $DB; return $DB->$a->hasCol("id");'));
        $i=0;
        foreach($tmpTables as $tbl) {
            if($i++ == 0) continue;
            $tmpTblArray[] = '`'.$tbl.'`.`id`=`'.$tmpTables[0].'`.`id`';
        }
        $this->tblString = ($tmpTblArray?'('.join(' AND ', $tmpTblArray).')':'');
    }


    /**
     * Returns the resource with the rows selected by the condition. This function ties many of the other functions
     * toghether, as they all more or less depends on this one to build the SELECT query.
     * The following syntax examples thereby applies to most other DatabaseTable methods as well
     * <code>
     * $DB->{'metadata,pages'}->get(array('title' => 'Start')); //Get the page with the title 'Start'
     * $DB->{'metadata,pages'}->get(array('title!' => 'Start')); // Get all pages <i>except</i> the one with the title 'Start'.
     * $DB->{'metadata,pages'}->get(array('#!time' => 'NOW()')); // A shebang in front of the field name enables MySQL function exectution
     * $DB->{'metadata,pages'}->get(array('!!pwd' => 'PASSWORD("data")')); // A double exclamation mark in front of the field name marks that the data should not be escaped at all. Use with care!
     * $DB->{'metadata,pages'}->get(array('title!' => 'Start')); // Get all pages <i>except</i> the one with the title 'Start'.
     * $DB->{'metadata,pages'}->get(null, 'id, title', '2,4'); // Get the id and title from the pages table, starting from row 2, returning 4 rows
     * $DB->{'metadata,pages'}->get(4); //Equals $DB->pages-get(array('id' => "4"));
     * </code>
     * @access public
     * @param array|string $cond An associative array with conditions for the SQL-query (alternatively a string with the same)
     * @param string $cols Comma-separated list of requested columns
     * @param numeric|string $limit The numeric limitations of the SELECT query
     * @param string $order Which column to order the results by
     * @param string $group Which column(s) to group the result by
     * @param bool $OR Whether the conditions should be treated as alternatives (true) or multiple resraints (false)
     * @param bool $distinct Return distinct results
     * @return resource
    */
    function get($cond=false, $cols = false, $limit=false, $order=false, $group=false, $OR = false, $distinct=false) {
        $cond = $this->collapse($cond);
        return $this->parent->query("SELECT ".($distinct?'DISTINCT ':'').
                ($cols == false ? '*' : $this->prepareCols($cols)).
                " FROM `" . join('`,`', $this->tables) . "`"
                . " WHERE " . $this->tblString .
            (count($cond)>0 ? ($this->tblString?" AND ":'') . implode(" ".($OR?'OR':'AND')." ", $cond) : "").
            ($group != false ? " GROUP BY " . $group : "").
            ($order != false ? " ORDER BY " . $order : "").
            ($limit != false ? " LIMIT " . $limit : ""));
    }

    /**
     * (non-PHPdoc)
     * @see lib/DatabaseTable#collapse($values, $select)
     */
    function collapse($values) {
        if(!is_array($values)) $values = array($values);
        $sortedConds = array();
        $collapsed = array();
        foreach($values as $col => $val) {
            if(is_numeric($col)) {
                $collapsed[] = $val;
                continue;
            }
            $d = explode('.', $col);
            if(count($d) !== 2) continue;
            $sortedConds[$d[0]][$d[1]] = $val;
        }
        foreach($sortedConds as $table => $cond) {
            $collapsed = array_merge($collapsed, (array)$this->parent->{$table}->collapse($cond));
        }
        return $collapsed;
    }

    /**
     * (non-PHPdoc)
     * @see lib/DatabaseTable#prepareCols($cols)
     */
    function prepareCols($cols) {
        if($cols == '*') return $cols;
        if(!is_array($cols))
            $cols = array_map('trim', explode(',', $cols));

        $sortedCols = array();
        foreach($cols as $col) {
            $d = explode('.', $col);
            if(count($d)<2) continue;
            $sortedCols[$d[0]][] = $d[1];
        }
        $prepared = array();
        foreach($sortedCols as $table => $cols) {
            $prepared = array_merge($prepared, (array)$this->parent->{$table}->prepareCols($cols));
        }
        return join(',', $prepared);
    }

    /* Disable functions we inherit from DatabaseTable but do not want */

    /**
     * It is not possible to insert into multiple tables at teh same time
     */
    function insert() {
        func_get_args();
        return false;
    }

    /**
     * Updating multiple tables is not possible
     */
    function update() {
        func_get_args();
        return false;
    }

    /**
     * It is not possible to delete data from multiple tables
     * @see lib/DatabaseTable#delete($cond, $limit, $OR)
     */
    function delete() {
        func_get_args();
        return false;
    }
}
?>
