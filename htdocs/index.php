<?php
/**
 * index.php
 *
 * Main index file of solidba.se
 * Detects requested ID and executes the corresponding object.
 *
 * @author Jonatan Olofsson [joolo]
 * @version 1.0
 * @package Base
 * @license http://creativecommons.org/licenses/by-nc/3.0/ Creative Commons Attribution-Noncommercial 3.0 Unported License
 */
if(is_dir('INSTALL')) {
    header('Location: INSTALL/');
    die();
}

//FIXME: Remove?
ini_set('xdebug.var_display_max_depth', 5);

/**
 * Start execution timer and sessions
 */
$s = microtime(true);
define("solidbase", 1.0);
error_reporting(E_ALL &~E_DEPRECATED);

/**
 * Run initialization script and start outbut buffering
 */
include '../lib/init.php';
/* Output buffering */
if($_REQUEST->raw('disable_ob')!=1) {
    ob_start("contentSize");
    //ob_start('ob_gzhandler');
    ob_start("ob_iconv_handler");
    ob_start("outputBufferFilter");
}

/**
 * Detect requested object and load it
 */
$association_type = 'default';
$CURRENT = false;
$EDIT = false;
if($_REQUEST['id'] && $CURRENT = associateID()) $association_type = 'id';
elseif($_REQUEST['edit'] && $CURRENT = associateEditor()) {$association_type = 'edit';$EDIT=$CURRENT->ID;}
elseif(($domain = associateSubdomain()) && $CURRENT = @$Controller->{$domain}(READ)) $association_type = 'subdomain';
elseif(!$_REQUEST['id'] && $CURRENT = $Controller->frontpage(READ));
else errorPage(404);
$PAGE = $CURRENT;
$ID = $CURRENT->ID;

if(!$_REQUEST['id']) $_REQUEST['id'] = $ID;

/**
 * Trace the user's last pageviews
 */
if(is_a($CURRENT, 'Page') && !isset($_GET['mw']) && !isset($_GET['w']) && !isset($_GET['mh']) && !isset($_GET['h'])) {
    $_REQUEST->setType('history', 'string');
    if($_REQUEST['history'] == 'back')
    {
        array_shift($_SESSION['TRACE']);
    }
    else {
        if(!isset($_SESSION['TRACE']) || !is_array($_SESSION['TRACE'])) $_SESSION['TRACE'] = array();
        $gets = $_GET->extract();
        if(isset($_SESSION['TRACE'][0]) && $_SESSION['TRACE'][0]['_GET'] != $gets)
            array_unshift($_SESSION['TRACE'], array('id' => $ID, '_GET' => $gets));
        if(count($_SESSION['TRACE'])>25)
            $_SESSION['TRACE'] = array_slice($_SESSION['TRACE'], 0, 25);

        unset($gets);
    }
}
if($CURRENT == false) errorPage('404');
if(!$CURRENT->may($USER, ANYTHING)) errorPage('401');
if(!method_exists($CURRENT, 'run')) errorPage('Controller unknown');

/**
 * Execute the requested object
 */
$CURRENT->run();
/**
 * Send output to browser
 */
while (ob_get_level() > 0) {
    ob_end_flush();
}
?>
