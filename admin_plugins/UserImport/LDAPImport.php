<?php

/**
 * @author Joakim Gebart
 * @version 1.0
 * @license http://creativecommons.org/licenses/by-nc/3.0/ Creative Commons Attribution-Noncommercial 3.0 Unported License
 * @package Privileges
 */

/**
 * The LDAPImport page helps when importing a large number of users from an LDAP directory
 */

class LDAPImport extends Page
{
    static private $DBTable = 'userimport';
    static private $smallSearchCount = 10;
    static private $largeSearchCount = 500;
    static public function installable() {return __CLASS__;}
    static public function uninstallable() {return __CLASS__;}
    public $privilegeGroup = 'Administrationpages';
    protected $Comment = '';
    protected $ldapMaxResults;

    /**
     * install the database and add an entry in the admin menu
     */
    function install() {
        global $Controller,$DB,$CONFIG;
        //$Controller->newObj(__CLASS__)->move('last', 'adminMenu');

        $DB->query("CREATE TABLE IF NOT EXISTS `".self::$DBTable."` (
  `rowid` int(11) NOT NULL auto_increment COMMENT 'internt radnummer',
  `dn` varchar(255) collate utf8_swedish_ci NOT NULL COMMENT 'DN som attributet tillhör',
  `attribute` varchar(255) collate utf8_swedish_ci NOT NULL COMMENT 'Attributets namn',
  `value` varchar(255) collate utf8_swedish_ci NOT NULL COMMENT 'Attributets värde',
  PRIMARY KEY  (`rowid`),
  KEY `dn` (`dn`),
  KEY `attribute` (`attribute`),
  KEY `value` (`value`),
  KEY `dn_2` (`dn`,`attribute`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci COMMENT='Innehåller sökresultat för tillfällig lagring innan import a' AUTO_INCREMENT=1 ;");

        $CONFIG->LDAP->setType('bindurl', 'text');
        $CONFIG->LDAP->setDescription('bindurl', 'URL to use when connecting');
        $CONFIG->LDAP->setType('binddn', 'text');
        $CONFIG->LDAP->setDescription('binddn', 'Distinguished name of the system account used for searching (needs to have read access to the uid attribute)');
        $CONFIG->LDAP->setType('bindpw', 'password');
        $CONFIG->LDAP->setDescription('bindpw', 'Password of system account');
        $CONFIG->LDAP->setType('basedn', 'text');
        $CONFIG->LDAP->setDescription('basedn', 'Base DN of searches');
        $CONFIG->LDAP->setType('searchattrs', 'CSV');
        $CONFIG->LDAP->setDescription('searchattrs', 'Comma separated list of valid attributes to search when importing users through LDAP');
        $CONFIG->LDAP->setType('storeattrs', 'CSV');
        $CONFIG->LDAP->setDescription('storeattrs', 'Comma separated list of attributes to retrieve and store when importing users through LDAP');
        $CONFIG->LDAP->setType('listattrs', 'CSV');
        $CONFIG->LDAP->setDescription('listattrs', 'Comma separated list of attributes to show in the user summary');
        $CONFIG->LDAP->setType('unameattr', 'text');
        $CONFIG->LDAP->setDescription('unameattr', 'The attribute to use as the login name when searching for a user in the LDAP directory');

    }

    /**
     * Drops the database table on uninstall
     * @return bool
     */
    function uninstall() {
        global $DB, $USER, $Controller;
        //if(!$USER->may(INSTALL)) return false;
        if (is_object($Controller->ldapImport))
        {
            $Controller->ldapImport->delete();
        }
        $DB->dropTable(self::$DBTable);
    }

    // Stole some code from the CompanyEditor admin_page - JGebart
    /**
     * Sets up the object
     * @param integer $id The ID of the object
     * @return void
     */
    function __construct($id=false){
        parent::__construct($id);
        $this->suggestName('Import users', 'en');
        $this->suggestName('Importera användare', 'sv');
        $this->setAlias('ldapImport');
        $this->ldapMaxResults = self::$smallSearchCount;

        $this->icon = 'small/database_go';
        $this->deletable = false;
    }

    /// Truncate userimport table
    protected function removeAllUsers() {
        global $DB;

        if ($this->mayI(EDIT)) {
            $DB->query("TrunCATE TABLE `".self::$DBTable."`;");
        }
    }

    /// Removes all rows related to the DNs matching the row ids in the $rowids array from the userimport table
    protected function removeUsers ($rowids) {
        global $DB;
        $table = $DB->{self::$DBTable};
        // Remove selected users
        if($this->mayI(EDIT))
        {
            if (@is_array($rowids))
            {
                foreach ($rowids as $rowid) {
                    if ($dn = $table->getCell(array('rowid' => $rowid), 'dn')) {
                        $table->delete(array('dn' => $dn), 0);
                    }
                }
            }
        }
        return true;
    }

    /// Import users from the userimport table into solidbase
    protected function importAllUsers ($groupId) {
        global $CONFIG, $USER, $Controller, $DB;
        $table = $DB->{self::$DBTable};

        $groupObj = $Controller->$groupId(EDIT, 'Group');
        if (!$groupObj) {
            Flash::create(__('You are not authorized to alter the selected group!'), 'warning');
            return false;
        }

        $unameattr = $CONFIG->LDAP->unameattr;
        if (@empty($unameattr)) {
            Flash::create(__('Configure your LDAP settings, unameattr is currently empty, using \'cn\''), 'warning');
            $unameattr = 'cn';
        }

        $users = $this->userImportList($CONFIG->LDAP->storeattrs);

        if (@count($users) > 0) {
            $importeduserrowids = array();
            foreach ($users as $dn => $userdata) {
                if (!is_array($userdata)) {
                    Flash::create('BUG: '. __FILE__ .':'. __LINE__ .':'. __METHOD__ .': userdata is not an array! dn: ' . $dn, 'warning');
                    continue;
                }
                if (!array_key_exists($unameattr, $userdata) || !($userdata[$unameattr])) {
                    //dump($userdata);
                    Flash::create(__('No username attribute value for: ') . $dn . ' unameattr: ' . $unameattr, 'warning');
                    continue;
                }
                $username = $userdata[$unameattr];
                if ($DB->users->exists(array('username' => $username))) {
                    Flash::create(__('Username is already in use: ') . $username, 'warning');
                    continue;
                }
                Flash::create(__('Adding user: ') . $username, 'confirmation');
                if ($user = $Controller->newObj('User')) {
                    $user->username = $username;
                    $user->passwordhash = 'LDAP';
                    Log::write('Imported user \'' . $username . '\' (id=' . $user->ID . ') from LDAP', 20);
                    foreach ($userdata as $attr => $value) {
                        if ($attr == $unameattr || $attr == 'userPassword') {
                            continue;
                        }
                        $user->userinfo = array($attr => $value);
                    }
                    $user->userinfo = array('dn' => $dn);
                    $user->addToGroup($groupObj);
                    $importeduserrowids[] = $userdata[0];
                }
                else {
                    Flash::create(__('Solidbase is broken! (unable to instantiate class User)'), 'warning');
                    return false;
                }
            }
            $this->removeUsers($importeduserrowids);
        }
    }

    /**
     * Contains actions and page view logic
     * @return void
     */
    function run(){
        global $Templates, $USER, $CONFIG, $Controller, $DB;
        $table = $DB->{self::$DBTable};
        if(!$this->may($USER, READ)) errorPage('401');

        $_REQUEST->setType('searchldapattr', 'string');
        $_REQUEST->setType('searchldapstring', 'string');
        $_REQUEST->setType('removeimport', 'numeric', true);
        $_REQUEST->setType('searchldaplarge', 'string');
        $_REQUEST->setType('importtogroup', 'numeric');
        $_REQUEST->setType('removeselected', 'string');
        $_REQUEST->setType('clearlist', 'string');

        if($this->may($USER, EDIT))
        {
            // Remove selected users
            if (isset($_REQUEST['removeselected'])) {
                $this->removeUsers($_REQUEST['removeimport']);
            }
            if (isset($_REQUEST['clearlist'])) {
                $this->removeAllUsers();
            }

            // Import users if there is a group selected
            if (isset($_REQUEST['importusers'])) {
                if ($_REQUEST['importtogroup']) {
                    $this->importAllUsers($_REQUEST['importtogroup']);
                }
                else {
                    Flash::create(__('Must select a target group for importing.'), 'warning');
                }
            }

            $searchform = new Form('searchLDAP', false, false);
            $editform = new Form('alterUserImport', false, false);
            $importform = new Form('importUsers', false, false);

            $groups = array();
            $groupIds = $DB->spine->asList(array('class' => 'Group'), 'id');
            if(count($groupIds)>0) {
                $groupObjs = $Controller->get($groupIds, EDIT);
                uasort($groupObjs, create_function('$a,$b', 'return strcmp($a->Name, $b->Name);'));
                foreach($groupObjs as $id => $group) {
                    $groups[$id] = $group->Name;
                }
            }

            $searchattrs = $CONFIG->LDAP->searchattrs;
            if (@is_array($searchattrs)) {
                $searchattrs = array_combine($searchattrs, array_map('__', $searchattrs));
            }
            else {
                $searchattrs = array();
            }
            if (!(@empty($_REQUEST['searchldaplarge']))) {
                $this->ldapMaxResults = self::$largeSearchCount;
            }
            if (!(@empty($_REQUEST['searchldapattr'])) && !(@empty($_REQUEST['searchldapstring']))) {
                $this->searchLDAP($_REQUEST['searchldapattr'], $_REQUEST['searchldapstring']);
            }
            $userlist = new Table(array_map(create_function('$a', '$a[0] = new Checkbox(false,\'removeimport[]\',false,false,false,false,htmlentities($a[0])); return new Tablerow($a);'), $this->userImportList($CONFIG->LDAP->listattrs)));

            $this->setContent('main',
                $editform->set($userlist,
                    new Li(
                        new Submit(
                            __('Remove selected'),
                            'removeselected'),
                        new Submit(
                            __('Clear list'),
                            'clearlist')
                    )
                ) .
                $searchform->set(
                    $this->Comment,
                    new Select(
                        __('Attribute'),
                        'searchldapattr',
                        $searchattrs,
                        @$_REQUEST['searchldapattr']
                    ),
                    new Input(
                        __('Search for'),
                        'searchldapstring',
                        @$_REQUEST['searchldapstring']
                    ),
                    new Checkbox(
                        __('Large search result'),
                        'searchldaplarge',
                        /*isset($_REQUEST['searchldaplarge']) - always disable large searches to avoid mistakes*/ false,
                        false,
                        __('maximum number of results increased from 10 to 500'),
                        '1'
                    ),
                    new Submit(
                        __('Search'),
                        'searchldap')
                ) .
                $importform->set(
                    new Select(
                        __('Target group'),
                        'importtogroup',
                        $groups,
                        @$_REQUEST['importtogroup'],
                        false,
                        __('Select a group'),
                        false,
                        __('Imported users will be made members of this group')
                    ),
                    new Submit(
                        __('Import users'),
                        'importusers')
                )
            );
        }
        else
        {
            $this->setContent('main', 'unauthorized');
        }

        $this->setContent('header', $this->title);
        $Templates->render();
    }

    /// Get a list of all users in the userimport table
    protected function userImportList($listattrs) {
        global $DB,$CONFIG;

        $table = $DB->{self::$DBTable};

        $results = $table->asArray(array('attribute' => $listattrs), array('rowid','dn','attribute','value'), false, false);

        // array index 0 is used for storing an arbitrary rowid belonging to the dn
        // The rowids are only used with the removeUsers() method to delete one or more users from the import list.
        // IMPORTANT: The rowid has nothing to do with solidbase's id numbering.
        array_unshift($listattrs, 0);
        $key_map = array_flip($listattrs);
        $userdata = array();
        foreach ($results as $row) {
            if (!(@is_array($userdata[$row['dn']]))) {
                $userdata[$row['dn']] = array($row['rowid']);
            }
            if (isset($userdata[$row['dn']][$key_map[$row['attribute']]])) {
                if ($this->compareLDAP($row['attribute'], $userdata[$row['dn']][$key_map[$row['attribute']]], $row['value']) < 0) {
                    $userdata[$row['dn']][$key_map[$row['attribute']]] = $row['value'];
                }
            }
            else {
                $userdata[$row['dn']][$key_map[$row['attribute']]] = $row['value'];
            }
        }
        // sort array in the same order as the array in the function argument, then change it into an associative array.
        // This is the simplest solution I've managed to come up with so far to do this, feel free to correct the following lines if you know any better way to do this..

        // Had to modify this from a simple array_map(array_combine()) to handle
        // missing attributes on some DN's
        // FIXME: Ugly and inefficient.
        foreach ($userdata as $dn => $data) {
            ksort($userdata[$dn]);
            foreach ($userdata[$dn] as $key => $value) {
                unset ($userdata[$dn][$key]);
                $userdata[$dn][$listattrs[$key]] = $value;
            }
        }

        // Sort by dn
        ksort($userdata);

        return $userdata;
    }

    /**
     * Searches the LDAP directory for users matching the attr value pair and
     * inserts the results into the userimport table.
     */
    protected function searchLDAP($attr, $value) {
        global $DB,$CONFIG;
        $ldapconn = ldap_connect($CONFIG->LDAP->bindurl);
        $storeattrs = $CONFIG->LDAP->storeattrs;
        $table = $DB->{self::$DBTable};

        if ($ldapconn)
        {
            // Bind (log in) to LDAP server
            if (ldap_bind($ldapconn, $CONFIG->LDAP->binddn, $CONFIG->LDAP->bindpw)) {
                $filter = '('.$attr.'=' . $value . ')';
                //echo $filter;
                $search = ldap_search($ldapconn, $CONFIG->LDAP->basedn, $filter, $storeattrs, 0, $this->ldapMaxResults);
                if ($search)
                {
                    $searchcount = ldap_count_entries($ldapconn, $search);
                    //$result = '';
                    if ($searchcount > 0) {
                        Flash::create('Found ' . $searchcount . ' results', 'confirmation');
                        // Found users
                        $entry = ldap_first_entry($ldapconn,$search);
                        do {
                            // Get DN from search result
                            $dn = ldap_get_dn($ldapconn,$entry);
                            //$result .= $dn.":\n";
                            if (!($table->exists(array('dn~' => $dn)))) {
                                $insertvalues = array();
                                $attrs = ldap_get_attributes($ldapconn,$entry);
                                for ($i=0; $i < $attrs['count']; $i++) {
                                    $attr_name = $attrs[$i];
                                    for ($j=0; $j < $attrs[$attr_name]['count']; $j++) {
                                        //$result .= " $attr_name: ".$attrs[$attr_name][$j]."\n";
                                        $insertvalues[] = array('dn' => $dn, 'attribute' => $attr_name, 'value' => $attrs[$attr_name][$j]);
                                    }
                                }

                                if ($table->insertMultipleRows($insertvalues,false,false,true)) {
                                    //Flash::create("Found: $dn\n", 'confirmation');
                                }
                                else {
                                    Flash::create("Error inserting into userimport!!!\n dn: $dn\n", 'warning');
                                }
                            }
                        } while (($entry = ldap_next_entry($ldapconn,$entry)) !== false);
                        //echo 'Found ' . $dn . "\n";
                        //Flash::create($result, 'confirmation');
                    }
                    else {
                        Flash::create('No results', 'warning');
                    }
                }
                else {
                    Flash::create('Search error', 'warning');
                }
            }
            else {
                //echo "LDAP bind failed...";
                Flash::create('Bind failed', 'warning');
                return false;
            }
        }
        else
        {
            // This will only happen if the ldap extension is broken
            // because OpenLDAP-2.x.x doesn't connect until the ldap_bind() call
                Flash::create('LDAP is broken, fix your PHP!', 'warning');
            return false;
        }
    }

    /// Compare two different values of the same attribute to find out which is most relevant for the summary list.
    /// @return ==0 if value1 is of equal relevance value2, >0 if value1 is more relevant, <0 if value2 is more relevant
    protected function compareLDAP($attr,$value1,$value2) {
        return 0;
    }
}

?>
