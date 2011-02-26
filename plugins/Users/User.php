<?php
/**
 * @author Jonatan Olofsson [joolo]
 * @version 1.0
 * @license http://creativecommons.org/licenses/by-nc/3.0/ Creative Commons Attribution-Noncommercial 3.0 Unported License
 * @package Privileges
 */

require_once 'Group.php';

/**
 * Defines the ID of the user NOBODY
 * @var integer
 */
global $CONFIG;
define('NOBODY', @$CONFIG->base->NOBODY);

/**
 * Set the expected datatypes of user input
 */
$_POST->setType('username', 'string');
$_POST->setType('password', 'any');
$_REQUEST->setType('logout', 'any');

/**
 * Each user is loaded as a user object, containing all information about the user
 * @package Privileges
 */
class User extends Beneficiary {
    private $_username;
    private $_password;
    private $_userinfo = array();
    protected $translateName = false;
    public $privilegeGroup = 'Users';
    private $DBTable = 'users';

    static public function installable() {return __CLASS__;}
    static public function uninstallable() {return __CLASS__;}

    public $editable = array('UserEditor' => EDIT);

    /**
     * Initialization of the user class
     * @param integer $id Id of the user that's beeing loaded
     * @param string $alias User's alias
     * @return void
     */
    function __construct($id=false){
        global $DB, $CONFIG, $Controller, $ID;
        $cur = false;
        if($id == 'current') {
            $id = $this->currentUserID();
            $cur = true;
            $DB->users->{(string)$id} = array('#!last_active' => 'NOW()');
        }
        parent::__construct($id);
        Base::registerMetadata('AcceptedTerms');
        $this->loadUser($id);

        if($cur && $id != NOBODY && $_REQUEST['id'] != 'Terms') {
            if(!$this->isActive()) {
                $_REQUEST->setType('return', 'numeric');
                redirect(url(array('id' => 'Terms', 'return' => $ID)));
            }
        }
    }

    function run() {
        global $Templates;
        $this->setContent('main',
            $this->infoBox().$this->presentation());
        $Templates->render();
    }

    function getInfo(){
        global $CONFIG;
        $r = array();
        $uifields = @$CONFIG->userinfo->Fields;
        foreach($uifields as $name => $uf) {
            switch($uf['type']) {
                case 'image':
                    if(@$this->userinfo[$name]) {
/* 						$r[] .= '<image src="'.url(array('id' => $this->userinfo[$name])).'" />'; */
                    }
                    break;
                default:
                    if(@$this->userinfo[$name]) {
                        $r[] = '<span class="type">'.$uf['label'].'</span>'.$this->userinfo[$name];
                    }
                    break;
            }
        }
        return $r;
    }

    function infoBox() {
        $r = '<div class="infobox">'.($this->getImage()?$this->getImage(32,32):icon('large/identity-32')).'<span class="info"><a href="'.url(array('id' => $this->ID)).'"><h3>'.$this->__toString().'</h3></a></span></div>';
/* 		if(empty($r[0]) && empty($r[1])) return ''; */
/* 		else return '<div class="infobox">'.$r[0].listify($r[1]).'</div>'; */
        return $r;
    }

    function getImage($maxWidth=false, $maxHeight=false) {
        global $CONFIG;
        $_REQUEST->setType('mv','numeric');
        $_REQUEST->setType('mh','numeric');
        $uifields = @$CONFIG->userinfo->Fields;
        foreach($uifields as $name => $uf) {
            if($uf['type'] == 'image' && @$this->userinfo[$name]) return '<img src="'.url(array('id' => $this->userinfo[$name], 'mw' => $maxWidth, 'mh' => $maxHeight)).'" class="userimg" alt="'.$this->Name.'"/>';
            else return false;
        }
    }

    function getLink() {
        return icon('small/user').'<a href="'.url(array('id' => $this->ID)).'">'.$this.'</a>';
    }

    /**
     * Creates the database table on installation
     * @return bool
     */
    function install() {
        global $DB, $USER, $Controller, $CONFIG;
        $DB->query("CREATE TABLE IF NOT EXISTS `".self::$DBTable."` (
  `id` int(11) NOT NULL,
  `username` varchar(255) character set utf8 NOT NULL default '',
  `password` varchar(255) character set utf8 NOT NULL,
  `last_active` datetime NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;");
        $nobody = $Controller->newObj('User');
        $DB->users->{$nobody->ID} = array('username' => 'nobody');
        $CONFIG->base->NOBODY = $nobody->ID;
        $CONFIG->base->setType('NOBODY', 'not_editable');
        $CONFIG->security->passwordSaltLength = 10;
        $CONFIG->security->setType('passwordSaltLength', 'not_editable');
        $CONFIG->security->setDescription('loginTimeout', 'Minutes of inactivity before user is logged out, 0 meaning no limit.');
        $CONFIG->Site->setDescription('TermsTimeOut', 'How often (in months) should the user agree to the terms? 0 means never, -1 means once on registration');
    }

    /**
     * Drops the database table on uninstall
     * @return bool
     */
    function uninstall() {
        global $DB, $USER;
        if(!$USER->may(INSTALL)) return false;
        $DB->dropTable($this->DBTable);
    }

    /**
     * Override text representation of User class. Several different outputs depending on what info is available.
     * @see solidbase/lib/Base#__toString()
     */
    function __toString() {
        return $this->Name(true);
    }

    /**
     * Return the user's name
     * @param $aUNIA Append Username If Admin
     * @return string
     */
    function name($aUNIA = false) {
        global $USER,$Controller;

        // Generic attributes
        if(isset($this->_userinfo['firstname']) && isset($this->_userinfo['surname'])
            && !empty($this->_userinfo['firstname']) && !empty($this->_userinfo['surname'])) {
            $ret = $this->_userinfo['firstname'] . ' ' . $this->_userinfo['surname'];
        }
        // LDAP attributes
        // Given name and Surname
        elseif(isset($this->_userinfo['givenName']) && isset($this->_userinfo['sn'])
            && !empty($this->_userinfo['givenName']) && !empty($this->_userinfo['sn'])) {
            $ret = $this->_userinfo['givenName'].' '.$this->_userinfo['sn'];
        }
        // LDAP attributes
        // Common name (most likely 'Firstname Surname')
        elseif(isset($this->_userinfo['cn']) && !empty($this->_userinfo['cn'])) {
            $ret = $this->_userinfo['cn'];
        }
        // Generic attributes
        elseif(isset($this->_userinfo['firstname']) && !empty($this->_userinfo['firstname'])) {
            $ret = $this->_userinfo['firstname'];
        }
        // Generic attributes
        elseif(isset($this->_userinfo['surname']) && !empty($this->_userinfo['surname'])) {
            $ret = $this->_userinfo['surname'];
        }
        else {
            $ret = (string)$this->_username;
        }

        if($aUNIA) {
            // Check if the user is an admin and append the username to the name.
            $admingroup = $Controller->{(string) ADMIN_GROUP}(OVERRIDE);
            if ($admingroup->isMember($USER)) {
                $ret .= ' (' . $this->_username .')';
            }
        }
        return $ret;
    }

    /**
    * Returns the value of the variable asked for. If the property is unknown, question is passed to parent class.
    * @access public
    * @param string $property The property asked for
    * @return mixed
    */
    function __get($property){
        if(in_array($property, array('username', 'userinfo', 'password')))
            return $this->{'_'.$property};
        return parent::__get($property);
    }

    /**
    * Sets the variable to the given value and updates the database, if allowed. If the property is unknown, the call is passed to parent class.
    * @access public
    * @param string $property The property to edit
    * @param mixed $value The value to set the property with
    */
    function __set($property, $value){
        global $DB, $USER;
        $ipn = '_'.$property;
        switch($property) {
            case 'password':
                if($this->password == 'LDAP') break;
                if(empty($value)) return false;
                $value = pwdEncode($value);
                //NOTE: No break here
            case 'username':
                if(empty($value)) return false;
                Base::__set('Name', $value);
            case 'passwordhash': // passwordhash bypasses pwdEncode and sets the raw password hash.
                if(empty($value)) return false;
                if ($property == 'passwordhash') {
                    $ipn = '_password';
                    $property = 'password';
                }
                if($this->$ipn === $value) break;
                $this->$ipn = $value;
                $DB->users->{$this->ID} = array($property => $value);
                break;
            case 'userinfo':
                if(!is_array($value)) return false;
                foreach($value as $prop => $val) {
                    $DB->userinfo->update(array('val' => $val), array('prop' => $prop, 'id' => $this->ID), true);
                }
                $this->_userinfo = array_merge($this->_userinfo, $value);
                break;
            default:
                parent::__set($property, $value);
        }
    }

    function presentation($what=false) {
        if($what === false) $what = 'g'.MEMBER_GROUP;
        elseif(is_numeric($what)) $what = 'g'.$what;
        elseif(is_object($what)) $what = 'g'.$what->ID;

        return $this->getContent($what);
    }

    /**
    * Compares if a gived password is equal to the users
    * @access public
    * @param string $comp
    * @return bool
    */
    function passwordsEqual($comp,$passhash = null){
        if ($passhash === null) {
            $passhash = $this->password;
        }
        return (pwdEncode($comp,$passhash) === $passhash);
    }

    /**
     * Register that the user has agreed to the terms
     * @return void
     */
    function acceptTerms() {
        global $USER;
        if($USER->ID == $this->ID) {
            $this->AcceptedTerms = time();
        }
    }

    function isActive() {
        return ($this->ID === NOBODY || (parent::isActive() && $this->termsAccepted()));
    }

    /**
     * Returns true if the latest terms are accepted and not expired
     * @return bool
     */
    function termsAccepted() {
        global $CONFIG, $DB, $Controller;
        $terms = $Controller->alias('Terms', OVERRIDE);
        if( $this->AcceptedTerms < $DB->{"aliases,content"}->single(array(
                'aliases.alias' => 'Terms',
                'content.language' => $this->settings['language']
            ),
            'content.revision'
        )) {
            return false;
        }
        $TO = $CONFIG->Site->TermsTimeOut;
        if($TO == -1) {
            return $this->AcceptedTerms>0;
        }

        return !($TO > 0 && ($this->AcceptedTerms<(time() - $TO*60*60*24*30)));
    }

    /**
    * Deletes the user and propagates the call to parent class.
    * @access public
    * @return void
    */
    function delete() {
    global $DB, $Controller, $USER;
        if($Controller->alias('userEditor')->may($USER, DELETE)
            && $this->ID != NOBODY
            && !$this->memberOf(ADMIN_GROUP)) {
            Log::write('Deleted user \'' . $this->username . '\' (id=' . $this->ID . ')', 20);
            $DB->users->delete(array('id' => $this->ID));
            $DB->group_members->delete(array('user' => $this->ID), false);
            $DB->userinfo->delete(array('id' => $this->ID), false);
            parent::delete();
        }
    }

    /**
     * Loads the user from the database
     * @param $who
     * @return void
     */
    function loadUser($who){
        if($this->user_loaded) return;
    global $DB, $Controller;
        $user = $DB->users->{(string)$who};
        if(!$user) return;
        $this->ID = $user['id'];
        $this->__set('Name', $this->_username = $user['username']);
        $this->_password = $user['password'];
        $this->_userinfo = $DB->userinfo->asList(array('id' => $this->ID), 'prop, val', false, true);
        if(!is_array($this->_userinfo)) $this->_userinfo = array();
    }
    private $user_loaded = false;

    function may($u, $lvl) {
        global $USER;
        if($u === $USER && $USER->ID !== NOBODY) {
            return true;
        } else {
            $pr = parent::may($u, $lvl);
            if(is_bool($pr) || $USER->ID == NOBODY) {
                return $pr;
            } else {
                if($lvl & READ) return true;
                else return $pr;
            }
        }
    }


    /**
    * Ends the user session
    * @access public
    * @return void
    */
    function logout(){
        $_SESSION = array();
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time()-42000, '/');
        }
        session_destroy();
    }

    /**
     * Returns the ID of the user currently logged in. Also handles the logging in/out of the user
     * @return integer
     */
    private function currentUserID(){
        global $DB, $CONFIG;
        if(isset($_SESSION['uid']) && $_REQUEST['logout'])
        {
            $this->logout();
            return NOBODY;
        }
        elseif($_POST['username'] && $_POST['password'])
        {
            $user = $DB->users->get(array('username' => $_POST['username']), false, 1);
            if(Database::numRows($user)==1)
            {
                $row = Database::fetchAssoc($user);

                // LDAP-users har password hash satt till 'LDAP'
                if($row['password'] == 'LDAP') {
                    $ldapconn = ldap_connect($CONFIG->LDAP->bindurl);

                    if ($ldapconn)
                    {
                        // Bind (log in) to LDAP server
                        if (@ldap_bind($ldapconn, $CONFIG->LDAP->binddn, $CONFIG->LDAP->bindpw)) {
                            //echo "LDAP bind successful...<br />\n";
                            $unameattr = $CONFIG->LDAP->unameattr;
                            if (@empty($unameattr)) {
                                $unameattr = 'cn';
                            }

                            $filter = '('.$unameattr.'=' . $_POST['username'] . ')';
                            $search = ldap_search($ldapconn, $CONFIG->LDAP->basedn, $filter, array('dn'), 0, 1); // The last parameter is to limit search to 1 result returned
                            if ($search)
                            {
                                // Found user
                                $entry = ldap_first_entry($ldapconn,$search);
                                // Get DN from search result
                                $dn = ldap_get_dn($ldapconn,$entry);
                                //echo 'Found ' . $dn . "\n";
                                // Don't unbind.
                                /* http://php.net/manual/en/function.ldap-unbind.php
                                 *  kmenard at wpi dot edu
                                 * 29-Nov-2001 07:47
                                 * ldap_unbind kills the link descriptor.  So, if you want to rebind
                                 * as another user, just bind again; don't unbind.
                                 * Otherwise, you'll have to open up a new connection.
                                 */
                                // Try to bind as the user account
                                // @ to not print a big error message if the user entered the wrong password
                                if (@ldap_bind($ldapconn, $dn, $_POST['password'])) {
                                    regenerateSession(true);
                                    //echo 'Login successful';

                                    $_SESSION['uid'] = $row['id'];
                                    $_SESSION['username'] = $row['username'];
                                    $_SESSION['upwd'] = 'LDAP';
                                    $_SESSION['loggedIn'] = time();
                                    $_SESSION['lastLogin'] = $row['last_active'];

                                    unset($_COOKIE['user_settings::language']);

                                    return $_SESSION['uid'];
                                }
                                else
                                {
                                    //echo 'Login failed';
                                    Flash::create(__('Wrong username or password'), 'warning');
                                    return NOBODY;
                                }
                            }
                        } else {
                            //echo "LDAP bind failed...";
                            return NOBODY;
                        }
                    }
                    else
                    {
                        // This will only happen if the ldap extension is broken
                        // because OpenLDAP-2.x.x doesn't connect until the ldap_bind() call
                        return NOBODY;
                    }
                }
                elseif($this->passwordsEqual($_POST['password'], $row['password'])) {
                    regenerateSession(true);

                    $_SESSION['uid'] = $row['id'];
                    $_SESSION['username'] = $row['username'];
                    $_SESSION['upwd'] = pwdEncode($_POST['password'], $row['password']);
                    $_SESSION['loggedIn'] = time();
                    $_SESSION['lastLogin'] = $row['last_active'];

                    return $_SESSION['uid'];
                }
            }
            else {
                // User not found in solidbase

                // Try to search in ldap database
                $ldapuid = $this->tryImportLDAP($_POST['username'], $_POST['password']);
                if ($ldapuid !== false) {
                    // Successfully imported user
                    return $ldapuid;
                }
            }
            Flash::create(__('Wrong username or password'), 'warning');
            return NOBODY;
        }
        elseif(isset($_SESSION['uid']) && checkSession())
        {
            $user = $DB->users->getRow(array('id' => $_SESSION['uid']), 'id, username, password, last_active');
            if($user != false
                && ($CONFIG->security->loginTimeout < 1
                    || strtotime($user['last_active']) >= (time() - 60*$CONFIG->security->loginTimeout))
                && isset($_SESSION['upwd'])
                && $_SESSION['upwd'] == $user['password']
                && isset($_SESSION['username'])
                && $user['username'] == $_SESSION['username'])
            {
                return $_SESSION['uid'];
            }
            return NOBODY;
        }
        return NOBODY;
    }

    function mail($html,$headers=false, $text=false) {
        if(!$this->getEmail()) return false;
        global $CONFIG;
        require_once 'Mail.php';
        require_once 'Mail/mime.php';

        $param['text_charset'] = 'utf-8';
        $param['html_charset'] = 'utf-8';
        $param['head_charset'] = 'utf-8';

        $hdrs = array(
            "From"    => $CONFIG->Mail->Sender . " <".$CONFIG->Mail->Sender_email.">",
            "Subject" => 'No subject'
        );
        if($headers) {
            if(!is_array($headers)) $headers = array($headers);
            $hdrs = array_merge($hdrs, $headers);
        }

        $mime = new Mail_mime();

        if($text) $mime->setTXTBody($text);
        $mime->setHTMLBody($html);

        $body = $mime->get($param);
        $hdrs = $mime->headers($hdrs);

        $mail = Mail::factory("mail");
        $mail->send($this->getEmail(), $hdrs, $body);
    }

    function getEmail() {
        return @$this->userinfo['mail'];
    }

    //FIXME: Flyttas till egen autentiseringsklass
    private function tryImportLDAP($username, $password) {
        global $CONFIG,$DB,$Controller;
        $ldapconn = ldap_connect($CONFIG->LDAP->bindurl);

        if (!(strstr($username,'*') === false)) {
            //Don't search for wildcards
            Flash::create(__('Ajabaja!'),'warning');
            return false;
        }

        if ($ldapconn)
        {
            // Bind (log in) to LDAP server
            if (ldap_bind($ldapconn, $CONFIG->LDAP->binddn, $CONFIG->LDAP->bindpw)) {
                //echo "LDAP bind successful...<br />\n";
                $unameattr = $CONFIG->LDAP->unameattr;
                if (@empty($unameattr)) {
                    $unameattr = 'cn';
                }
                $storeattrs = $CONFIG->LDAP->storeattrs;
                if (@empty($storeattrs)) {
                    // Not configured properly
                    return false;
                }


                $filter = '('.$unameattr.'=' . $username . ')';
                $search = ldap_search($ldapconn, $CONFIG->LDAP->basedn, $filter, $storeattrs, 0, 1); // The last parameter is to limit search to 1 result returned
                if ($search)
                {
                    // Found user
                    $entry = @ldap_first_entry($ldapconn,$search);
                    // Get DN from search result
                    $dn = @ldap_get_dn($ldapconn,$entry);
                    if(!$dn) return false;
                    //echo 'Found ' . $dn . "\n";

                    // LiU programregistrering
                    // FIXME: $CONFIG
                    $filterattr = 'liuStudentProgramCode';
                    // Y-programregistrering
                    // FIXME: $CONFIG
                    $filterregexp = '/^[6t]cyy[yi]-[1-9]-[vh]t20[01][0-9]$/';

                    $attrs = @ldap_get_attributes($ldapconn,$entry);
                    $user_ok = false;
                    $userdata = array();
                    for ($i=0; $i < $attrs['count']; $i++) {
                        $attr_name = $attrs[$i];
                        for ($j=0; $j < $attrs[$attr_name]['count']; $j++) {
                            if ($attr_name == $filterattr) {
                                if (preg_match($filterregexp,$attrs[$attr_name][$j])) {
                                    // User is okay to log in even though admin hasn't imported them from LDAP
                                    $user_ok = true;
                                }
                            }
                            if (isset($userdata[$attr_name])) {
                                if ($this->compareLDAP($attr_name, $userdata[$attr_name], $attrs[$attr_name][$j]) < 0) {
                                    $userdata[$attr_name] = $attrs[$attr_name][$j];
                                }
                            }
                            else {
                                $userdata[$attr_name] = $attrs[$attr_name][$j];
                            }
                        }
                    }
                    if (!$user_ok) {
                        // User does not match the regexp, won't be allowed to log in.
                        return false;
                    }
                    if (!array_key_exists($unameattr, $userdata) || !($userdata[$unameattr])) {
                        dump($userdata);
                        Flash::create(__('No username attribute value for: ') . $dn . ' unameattr: ' . $unameattr, 'warning');
                        return false;
                    }
                    // Don't unbind.
                    /* http://php.net/manual/en/function.ldap-unbind.php
                     *  kmenard at wpi dot edu
                     * 29-Nov-2001 07:47
                     * ldap_unbind kills the link descriptor.  So, if you want to rebind
                     * as another user, just bind again; don't unbind.
                     * Otherwise, you'll have to open up a new connection.
                     */
                    // Try to bind as the user account
                    // @ to not print a big error message if the user entered the wrong password
                    if (@ldap_bind($ldapconn, $dn, $password)) {
                        regenerateSession(true);
                        //echo 'Login successful';

                        $username = $userdata[$unameattr];
                        if ($DB->users->exists(array('username' => $username))) {
                            // This can actually happen through a race condition if the same user tries to log in twice in parallel.
                            Flash::create(__('BUG: Username already in use, try logging in again: ') . $username, 'warning');
                            return false;
                        }
                        Flash::create(__('Adding user: ') . $username, 'confirmation');

                        if ($user = $Controller->newObj('User')) {
                            $user->username = $username;
                            $user->passwordhash = 'LDAP';
                            Log::write('Imported user \'' . $username . '\' (id=' . $user->ID . ') from LDAP through autoimport', 20);
                            foreach ($userdata as $attr => $value) {
                                if ($attr == $unameattr || $attr == 'userPassword') {
                                    continue;
                                }
                                $user->userinfo = array($attr => $value);
                            }
                            $user->userinfo = array('dn' => $dn);
                        }
                        else {
                            Flash::create(__('Solidbase is broken! (unable to instantiate class User)'), 'warning');
                            return false;
                        }

                        $_SESSION['uid'] = $user->ID;
                        $_SESSION['username'] = $username;
                        $_SESSION['upwd'] = 'LDAP';
                        $_SESSION['loggedIn'] = time();
                        $_SESSION['lastLogin'] = time();

                        return $_SESSION['uid'];
                    }
                    else
                    {
                        //echo 'Login failed';
                        Flash::create(__('Wrong username or password'), 'warning');
                        return false;
                    }
                }
            } else {
                //echo "LDAP bind failed...";
                return false;
            }
        }
        else
        {
            // This will only happen if the ldap extension is broken
            // because OpenLDAP-2.x.x doesn't connect until the ldap_bind() call
            return false;
        }
    }
    protected function compareLDAP($attr,$value1,$value2) {
        $ret = 0;
        switch($attr) {
            case 'liuStudentProgramCode':
                // liuProgramCode is defined as [program]-[termin]-[date]
                // Where program == 6cyyy or tcyyy for Teknisk Fysik och Elektroteknik
                // 6cyyi, tcyyi == Yi
                //
                // date is vt2009, ht2009, vt2010 etc.
                //
                // We want the latest registration, therefore we need to grab the dates and compare them
                $parts = explode('-', $value1);
                $date1 = ((int) substr($parts[2],2,4)) * 10; //extract year and shift left
                if ($parts[2][0] == 'h' || $parts[2][0] == 'H') {
                    $date1 += 5;
                }

                $parts = explode('-', $value2);
                $date2 = ((int) substr($parts[2],2,4)) * 10; //extract year and shift left
                if ($parts[2][0] == 'h' || $parts[2][0] == 'H') {
                    $date2 += 5;
                }

                $ret = $date1 - $date2;
                break;
            default:
                break;
        }
        return $ret;
    }
}
?>
