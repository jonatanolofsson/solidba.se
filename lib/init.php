<?php
/**
 * In this file, all objects vital to the others are loaded.
 *
 * @author Jonatan Olofsson [joolo]
 * @version 1.0
 * @license http://creativecommons.org/licenses/by-nc/3.0/ Creative Commons Attribution-Noncommercial 3.0 Unported License
 * @package Base
 */

define('ROOTDIR', dirname(dirname(__FILE__)));
define('WWW_ROOT', ROOTDIR . '/htdocs/');
define('CACHE_DIR', ROOTDIR . '/cache/');
define('LIB_DIR', ROOTDIR . '/lib/');
define('UI_DIR', ROOTDIR . '/ui/');
define('TEMPLATE_DIR', UI_DIR . '/templates/');
define('CONFIG_DIR', ROOTDIR . '/config/');
define('PRIV_PATH', ROOTDIR . '/private/');

session_start();
require_once 'functions.php';
require_once 'REQUEST.php';
require_once 'autoload.php';

$_REQUEST->setType('id', array('numeric', '#[a-z_0-9]+#'));

/**
 * Load and initiate the configuration class, loading database configuration
 */
$CONFIG = new Config();
$CONFIG->loadFile(CONFIG_DIR . "/config.php");

require_once CONFIG_DIR . '/pwdEncode.php';
/**
 * Fire up the database
 * @var Database $DB The database object
 */
$DB = new Database(		$CONFIG->DB->host,
                    $CONFIG->DB->username,
                    $CONFIG->DB->password,
                    $CONFIG->DB->db,
                    $CONFIG->DB->prefix,
                    $CONFIG->DB->charset
                );
/**
 * Load the configuration stored in the database
 */
$CONFIG->loadFromDatabase();

//FIXME: Clean, automate and move
/* Language and encoding */
$CONFIG->Site->setDescription('charset', 'Default text-encoding');
$CONFIG->Site->setDescription('locale', 'Default locale');
if(empty($CONFIG->Site->charset)) $CONFIG->Site->charset = 'UTF-8';
header('Content-Type: text/html; charset='.$CONFIG->Site->charset);
iconv_set_encoding("internal_encoding", "UTF-8");
iconv_set_encoding("output_encoding", $CONFIG->Site->charset);
setlocale(LC_ALL, (@$_COOKIE['locale']
                        ?@$_COOKIE['locale']
                        :(@$USER->settings->locale
                            ?@$USER->settings->locale
                            :$CONFIG->Site->locale)));
bindtextdomain('yweb', './languages');
textdomain('yweb');

/**
 * Load site data
 */
$SITE = new Site();
define('BASE_DIR', $SITE->base_dir);

//FIXME: (Re)move
//$SITE->settings['language'] = 'en';
//Settings::changeSetting('language', 'select', google::languages($CONFIG->Site->languages));

/*
 * Initialize controller
 */
$Controller = new Controller();

/*
 * Load current user
 */
__autoload('User');
$USER = $Controller->{(string) NOBODY}(OVERRIDE);
$USER = $Controller->currentUser;

/**
 * Fire up template engine and ask the requested object to do it's thing
 *
 * @var Templates: Object handling the connection with the templates
 */
$Templates = new Templates();

Installer::check();

/**
 * Find current subdomain
 */
//FIXME: move: !!
define('SUBDOMAIN', getSubdomain());
$CONFIG->Subdomains->setType('Top_domain', 'text');
?>
