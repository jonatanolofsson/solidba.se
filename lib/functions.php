<?php

/**
 * functions.php
 * Various functions for solidba.se
 *
 * @author Jonatan Olofsson [joolo]
 * @version 1.0
 * @license http://creativecommons.org/licenses/by-nc/3.0/ Creative Commons Attribution-Noncommercial 3.0 Unported License
 * @package Base
 */

include "dntHash.php";
include "SafeEmails.php";

/**
 * Adds a path to the inclusion path
 * @param string $path Path to include from
 * @return void
 */
function addPath($path, $pre = true) {
    if($path) {
        if($pre)
            set_include_path($path . PATH_SEPARATOR . get_include_path());
        else
            set_include_path(get_include_path() . PATH_SEPARATOR . $path);
    }
}

function readDirFilesRecursive($dir, $ignorepath = false) {
    $d = dir($dir);
    $files = array();
    while(false !== ($f = $d->read()))
    {
        if($f[0] == '.') continue;
        $path = $dir.DIRECTORY_SEPARATOR.$f;
        if(is_dir($path))
        {
            $files = array_merge($files, readDirFilesRecursive($path, $ignorepath));
        }
        else
        {
            $files[] = ($ignorepath?$f:$path);
        }
    }

    return $files;
}

/**
 * retrieve the current subdomain
 * @return void
 */
function getSubdomain() {
    global $CONFIG;
    if(!$CONFIG->Subdomains->Top_domain) return false;

    $matches = array();
    preg_match('#^(www\.)?([^\.]+)\.'.str_replace('.', '\.', $CONFIG->Subdomains->Top_domain).'#', $_SERVER['HTTP_HOST'], $matches);
    if(count($matches)>0) {
        return $matches[2];
    } else return false;
}

/**
 * Associate the current subdomain with it's set association
 * @param string $sd Subdomain to associate (defaults to the current subdomain)
 * @return string Association
 */
function associateSubdomain($sd=false) {
    if(!$sd) $sd = SUBDOMAIN;
    if($sd) {
        global $DB;
        return $DB->subdomains->getCell(array('subdomain' => $sd), 'assoc');
    } else {
        return 'frontpage';
    }
}

function associateID($field = 'id', $aLevel = READ) {
    global $Controller;
    if($obj = $Controller->get($_REQUEST[$field], $aLevel)) return $obj;
    while(($pos = strrpos($_REQUEST[$field], '/')) !== false)
    {
        $_REQUEST[$field] = substr($_REQUEST[$field], 0, $pos);
        if($obj = $Controller->get($_REQUEST[$field], $aLevel)) return $obj;
    }

    return false;
}

function associateEditor() {
    $class = $_REQUEST['with'];
    if(!class_exists($class)) return false;

    if($obj = associateID('edit', OVERRIDE)) {
        $editors = $obj->editable;
        if(!in_array($class, array_keys($editors)) || !$obj)
            return false;

        if(!$class::canEdit($obj)
        || !$obj->mayI($editors[$class]))
            errorPage(401);

        return new $class($obj);
    } else return false;
}

/**
 * Generate a highly randomized salt
 * @return string
 */
function generateSalt() {
    return str_shuffle(sha1(uniqid(mt_rand(), true)).md5(uniqid(mt_rand(), true)));
}

/**
 * Arrange and flatten the incoming elements
 * @param array $args
 * @return array
 */
function flatten($args){
    $res = array();
    foreach($args as $a) {
        if(is_array($a)) $res = array_merge($res, flatten($a));
        else $res[] = $a;
    }
    return $res;
}

/**
 * Generate an URL from the arguments passed and existing request variables.
 * If a string is passed as first argument, it is checked to begin with http:// and returned.
 * @param array|string $array Array of values to build a GET query from
 * @param array|string $keep Array of request variables to keep. A single such may be passed as a string
 * @param bool $validate Validate the parameters according to $_REQUEST before including in URL
 * @param bool $SiteUrlOnEmptyParamSet Return the site's url if no parameters are passed
 * @param bool $getAliases Attempt to replace ID's with their respective aliases
 * @return string
 */
function url($array = false, $keep=false, $validate = true, $SiteUrlOnEmptyParamSet = false, $getAliases = true) {
    if(is_string($array) && !is_numeric($array) && strtolower(substr($array, 0, 4)) == 'www.') return 'http://'.$array;

    $pass = array();
    if($array == false) $array = array();
    if(!is_array($array)) $array = array('id' => $array);
    if($keep === true) {
        $keep = $_GET->keys();
    }
    elseif($keep !== false && !is_array($keep)) $keep = array($keep);
    if(!is_array($keep)) $keep = array();
    foreach($_REQUEST as $g => $v) {
        if(!in_array($g, $keep)) continue;
        elseif(is_array($v)) {
            foreach($v as $a => $b) {
                if(!empty($b)) $pass[$g][$a] = $b;
            }
        }
        else $pass[$g] = $v;
    }
    $parts = array_merge($pass,$array);
    $validParts = array();
    foreach($parts as $key => $val) {
        if($key == '#') continue;
        if(!$validate || ($_REQUEST->validate($key, $val) || $_GET->validate($key, $val))) {
            $validParts[$key] = $val;
        }
    }
    $url = '';
    //FIXME: Config
    // Short-URL
    if(true && isset($validParts['id'])) {
        global $Controller;
        if(is_numeric($validParts['id']) && $getAliases) {
            if($obj = $Controller->get($validParts['id'], OVERRIDE))
                if($alias = $obj->alias) $validParts['id'] = $alias;
        } elseif(is_object($validParts['id'])) {
            if($alias = $validParts['id']->alias) $validParts['id'] = $alias;
            else $validParts['id'] = $validParts['id']->ID;
        }
        $url = '/'.($validParts['id'] != 'frontpage'?$validParts['id']:'');
        unset($validParts['id']);
    }
    $query = http_build_query($validParts,'','&amp;');
    if(!empty($query)) $url .= '?'.$query;
    if(isset($array['#'])) $url.='#'.$array['#'];
    if((!empty($url) || $SiteUrlOnEmptyParamSet) && $SiteUrlOnEmptyParamSet != -1) {
        global $SITE;
        $url = $SITE->URL.$url;
    }
    return $url;
}

/**
 * Generate an icon from the 3rdParty library
 * @param string $which Which icon should be used
 * @param string $alt An alternative text
 * @param string $url Make the icon a link by using the $url argument, passing the URL on which to point.
 * @return string
 */
function icon($which, $alt='', $url=false, $print_alt = false, $class=false) {
    return new Icon($which, $alt, $url, $print_alt, $class);
}

/**
 * Modified version of serialize()
 * @param mixed $var The variable to serialize
 * @param bool $recur This argument is only used internally for recursive calls
 * @return unknown_type
 */
function serial ( $var = array(), $recur = FALSE ) {
    if ( $recur ) {
        foreach ( $var as $k => $v ) {
            if ( is_array($v) ) {
                $var[$k] = serial($v, 1);
            } else {
                $var[$k] = base64_encode($v);
            }
        }
        return $var;
    } else {
        return serialize(serial($var, 1));
    }
}

/**
 * A modified version of unserialize().
 * @param string $var Serialized (using serial()) variable
 * @param bool $recur This argument is only used internally for recursive calls
 * @return unknown_type
 */
function unserial ( $var = FALSE, $recur = FALSE ) {
    if ( $recur ) {
        foreach ( $var as $k => $v ) {
            if ( is_array($v) ) {
                $var[$k] = unserial($v, 1);
            } else {
                $var[$k] = base64_decode($v);
            }
        }
        return $var;
    } else {
        return unserial(unserialize($var), 1);
    }
}

/**
 * Halts execution and recirect the browser to a new location
 * @param string|array $where New location. An array will be interpreted with url()
 * @return void
 */
function redirect($where=-2, $delay=false){
    if(is_object($where)) $where = array('id' => $where->ID);
    elseif(is_numeric($where)){
        if($where <= 0) {
            $where = url(@$_SESSION['TRACE'][-$where]['_GET'], false, false, false, true);
        } else {
            $where = array('id' => $where);
        }
    }
    if(is_array($where)) $where = url($where);
    $where = str_replace('&amp;', '&', $where);
    if(!$where) die();
    while(ob_get_level()) @ob_end_clean();
    if($delay) {
        JS::raw('setTimeout("window.location.href=\''.$where.'\'",'. $delay*1000 .');');
    }
    elseif(headers_sent()) {
        echo "<script type=\"text/javascript\">
<!--
    window.location='$where';
-->
</script>";
    }
    else {
        header('Location: '.$where);
    }
    die();
}
/**
* Returns the multidimensional representation of the database storage
* of objects with parents and children.
* @access public
* @param array $list Associative list containing at least [id, parent] keys (the key [children] is also reserved)
* @return array
*/
function inflate($list){
    $r = array();
    $flat = array();
    foreach($list as $val)
    {
        $flat[$val['id']] = $val;
    }
    foreach($list as $val)
    {
        if(!isset($flat[$val['parent']])) {
            $r[$val['id']] = &$flat[$val['id']];
        }
        else {
            if(!isset($flat[$val['parent']]['children'])) $flat[$val['parent']]['children'] = array();
            $flat[$val['parent']]['children'][] = &$flat[$val['id']];
        }
    }
    return $r;
}

/**
 * Creates a HTML-list of an array
 * @param array $array The array to convert to HTML-list
 * @param string $class Defines which class the top-level UL should be assigned
 * @return string
 */
function listify($array, $class='list', $vary=true, $textfield = false, $childfield = false){
    if(!$array) return;
    static $i = -1;
    static $recursion = 0;
    ++$recursion;
    if($vary === true) $vary = array('odd', 'even');
    $vlen = count($vary);
    $result = '';
    $result = '<ul'.($class?' class="'.$class.'"':'').'>';
    foreach($array as $key => $li) {
        $result .= '<li'.($vary?' class="'.$vary[++$i%$vlen].'"':'').'>'
            .($textfield ? @$li[$textfield] : (is_array($li) ? $key : $li))
            .(is_array($li)
                ? ($childfield
                    ? (isset($li[$childfield])
                        ? listify($li[$childfield], false, $vary, $textfield, $childfield)
                        : ''
                      )
                    : listify($li, false, $vary, $textfield, $childfield)
                   )
                 : '');
    }
    $result .= '</ul>';
    --$recursion;
    if(!$recursion) $i = 0;
    return $result;
}

function arrayRepeat($repeat, $nr) {
    $res = array();
    $len = count($repeat);
    for($i=0;$i<$nr;$i++) {
        $res[] = $repeat[$i%$len];
    }
    return $res;
}

function arrayKeyMergeRecursive() {
    $arrays = func_get_args();
    $result = array();
    foreach($arrays as $array) {
        foreach($array as $key => $value) {
            if(is_array($value)) {
                if(isset($result[$key])) {
                    $result[$key] = arrayKeyMergeRecursive($result[$key], $value);
                } else $result[$key] = $value;
            } else $result[$key] = $value;
        }
    }
    return $result;
}

/**
 * Searches a string for any of supplied vaules. Returns false if no match is found, else
 * the position of the match (may be 0!)
 * @access public
 * @param string haystack The string to search
 * @param array needles Array of strings to match the subject against
 * @return int|bool
 */
function strapos($haystack,$needles,$offset=0) {
    $results = array();
    foreach($needles as $n) {
        $results[] = strpos($haystack, $n);
    }
    $results = array_filter($results);
    sort($results);
    if(isset($results[$offset])) return $results[$offset];
    else return false;
}

/**
 * This filter is run as the last thing before the output buffer is sent to the browser.
 * It inserts the string registered with Head::add() does some output compression if possible.
 * @param string $buffer
 * @param integer $mode OB mode
 * @return string
 */
function outputBufferFilter($buffer, $mode) {
    //FIXME: Uncomment?
    //$buffer = trim($buffer);
    /**
     * Mail replace
     */
    $buffer = SafeEmails::replace($buffer);

    $headadd = Head::finalize();
    if(is_array($headadd)) {
        $buffer = preg_replace('#</head>#i', $headadd[0]."\r\n</head>", $buffer, 1);
        $buffer = preg_replace('#</body>#i', $headadd[1]."\r\n</body>", $buffer, 1);
    }

    return $buffer;
}

/**
 * OB-function to add a content size header
 * @param $buffer
 * @param $mode
 * @return false
 */
function contentSize($buffer, $mode){
    global $PREVENT_CSIZE_HEADER;
    if(!$PREVENT_CSIZE_HEADER) header('Content-Length: ' . ob_get_length());
    return false;
}
global $PREVENT_CSIZE_HEADER;
$PREVENT_CSIZE_HEADER = false;

/**
 * Convert string to UTF-8
 * @param $str String to convert
 * @return string Converted string
 */
function utf8($str)
{
    if(mb_detect_encoding($str) == "UTF-8" && mb_check_encoding($str,"UTF-8")) return $str;
    else return utf8_encode($str);
}

/**
 * Convert string from UTF-8
 * @param $str String to convert
 * @return string Converted string
 */
function deutf8($str)
{
    if(mb_detect_encoding($str) == "UTF-8" && mb_check_encoding($str,"UTF-8")) return utf8_decode($str);
    else return $str;
}

/**
 * Get the real path to a file or folder. Returns false on failure, else the path to the file/folder
 * @param string $file Path
 * @param string $sub Subdirectory in which the file must lie
 * @param bool $must_be_file If true, the file must be a real file
 * @param bool $d Debug
 * @return string|bool
 */
function getPath($file, $sub="", $must_be_file = true, $d=false)
{
    if($file == false || empty($file)) return false;
    $path = realpath('.').DIRECTORY_SEPARATOR.$sub.DIRECTORY_SEPARATOR.$file;
    if($d) var_dump( $file, $sub, $path );
    if($path != false && strpos($path, realpath('.').DIRECTORY_SEPARATOR.$sub) !== false)
    {
        if($must_be_file && !is_file($path)) return false;
        return $path;
    }
    else
    {
        return false;
    }
}

/**
 * This function takes an error code and redirects it to an errorpage
 * @param integer|string $error The error code
 * @return void
 * @todo Finish the function
 */
function errorPage($error){
    switch($error){
        case 401:
            header('HTTP/1.1 401 Unauthorized');
            break;
        case 403:
            header('HTTP/1.1 403 Forbidden');;
            break;
        case 404:
            header('HTTP/1.1 404 Not Found');
            break;
    }
    global $Controller;
    if($p = $Controller->alias('error_'.$error)) {
        internalRedirect($p);
    } else {
        //@ob_end_clean();
        print($error);
    }
    die();
}

/**
 * Returns wether one or more keys mathes any of a number of given elements in an array.
 * This function is recursive, so the haystack menu may be multi-dimensional
 * @param string|array $needles The strings to search for
 * @param $haystack The array to search
 * @param bool $CASE_INSENSITIVE Sets wether a case insensitive search should be used or not
 * @return bool
 */
function somewhereInArray($needles, $haystack, $CASE_INSENSITIVE = false){
    if(!is_array($needles)) $needles = array($needles);
    if(is_array($haystack)) {
        foreach($haystack as $straw) {
            if(somewhereInArray($needles, $straw, $CASE_INSENSITIVE)) return true;
        }
        return false;
    }
    else {
        foreach($needles as $needle) {
            if($CASE_INSENSITIVE) {
                if(stripos($haystack, $needle) !== false) return true;
            }
            else {
                if(strpos($haystack, $needle) !== false) return true;
            }
        }
        return false;
    }
}

/**
 * Regenerate the session id for increased security
 * @param $reload
 * @return unknown_type
 */
function regenerateSession($reload = false)
{
    if(!isset($_SESSION['fingerprint']) || $reload) {
        $_SESSION['fingerprint'] = sha1(md5($_SERVER['REMOTE_ADDR'].'ahsh')
                                        .md5($_SERVER['HTTP_USER_AGENT'].'afke'));
    }

    /**
     * Create new session and destroy the old one
     */
    session_regenerate_id(true);
    global $CONFIG;
    if($CONFIG->Subdomains->Top_domain) {
        setcookie(session_name(), session_id(), 0, "/", ".".$CONFIG->Subdomains->Top_domain, true);
    }
}

/**
 * Make sure the session hasn't been hijacked
 * @return bool
 * @todo Salt?
 */
function checkSession()
{
    if(sha1(md5($_SERVER['REMOTE_ADDR'].'ahsh').md5($_SERVER['HTTP_USER_AGENT'].'afke'))
        != @$_SESSION['fingerprint'])
    {
        Flash::create('Session check failed');
        return false;
    }
    if(mt_rand(1, 20) == 1)
    {
        regenerateSession();
    }
    return true;
}

/**
 * Checks if the given path is a file, and if it's extension is a known image-extension
 * @param string $path The path to the file
 * @return bool
 */
function isImage($path) {
    global $CONFIG;
    return (is_file($path) && in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), $CONFIG->extensions->images));
}

/**
 * Checks the validity of an email by regex
 * @param $email Email address to check
 * @return bool
 */
function isEmail($email) {
    return (bool)preg_match('/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}$/i', $email);
}

/**
 * Stristr's younger cousin. Replacement for functionality appearing in 5.3.0
 * @param $haystack Where to search for the needle
 * @param $needle What to search for
 * @param $before_needle Return part of haystack before needle
 * @return string
 */
function stristrr($haystack, $needle, $before_needle=false) {
    if(!$before_needle) return stristr($haystack, $needle);

  $pos = stripos($haystack, $needle);
    if($pos === false) return false;
  return substr($haystack, 0, $pos);
}

/**
 * Checks if any of the strings in an array contains the needle and returns the position
 * Note: May return 0!
 * @param array $haystack Array to search
 * @param string $needles Needle to search for
 * @return int|bool String position in matching element. False if no string in array match
 */
function strposa($haystack, $needles){
    foreach($needles as $needle) {
        if(($r = strpos($haystack, $needle)) !== false) return $r;
    }
    return false;
}
/**
 * Tries to get mime data of the file.
 * @return {String} mime-type of the given file
 * @param $filename String
 */
function getMime($filename){
    $ext = pathinfo($filename, PATHINFO_EXTENSION);    # Get File extension for a better match
    switch(strtolower($ext)){
        case "js": return "application/javascript";
        case "json": return "application/json";
        case "jpg": case "jpeg": case "jpe": return "image/jpeg";
        case "png": case "gif": case "bmp": return "image/".strtolower($ext);
        case "css": return "text/css";
        case "xml": return "application/xml";
        case "html": case "htm": case "php": return "text/html";
        default:
            if(function_exists("mime_content_type")){ # if mime_content_type exists use it.
               $m = mime_content_type($filename);
            }else if(function_exists("")){    # if Pecl installed use it
               $finfo = finfo_open(FILEINFO_MIME);
               $m = finfo_file($finfo, $filename);
               finfo_close($finfo);
            }else{    # if nothing left try shell
               if(strstr($_SERVER[HTTP_USER_AGENT], "Windows")){ # Nothing to do on windows
                   return ""; # Blank mime display most files correctly especially images.
               }
               if(strstr($_SERVER[HTTP_USER_AGENT], "Macintosh")){ # Correct output on macs
                   $m = trim(exec('file -b --mime '.escapeshellarg($filename)));
               }else{    # Regular unix systems
                   $m = trim(exec('file -bi '.escapeshellarg($filename)));
               }
            }
            $m = split(";", $m);
            return trim($m[0]);
    }
}

/**
 * Removes an element with a given value from an array
 * @param array $array The array to remove from
 * @param mixed $value The value to remove
 * @return array
 */
function arrayRemove($array, $values, $reorder=false) {
    $values = (array)$values;
    foreach($values as $value) {
        while(false !== ($loc = array_search($value, $array))) unset($array[$loc]);
    }
    return ($reorder ? array_values($array) : $array);
}

/**
 * Reset the main variables and restart the page generation in order to make an internal redirect.
 * @param object|id $to The object (or object id) which to redirect to.
 * @return void
 */
function internalRedirect($to){
    global $ID, $PAGE, $CURRENT, $Controller, $OLD;

    if(!is_object($to)) $to = $Controller->{(string)$to};

    if($PAGE)
        $OLD = $PAGE->ID;
    else $OLD = false;
    $PAGE = $CURRENT = $to;
    $_REQUEST['id'] = $to->ID;

    if(!@method_exists($CURRENT, 'run')) errorPage('Controller unknown');

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

    exit;
}

/**
 * A safer version of PHP's gettext short version _
 * @param string $str The string to translate
 * @return string
 */
function __($str){
    if(!empty($str)) return _($str);
    else return '';
}
//runkit_function_redefine('_', '$str', 'return (!empty($str) ? gettext($str) : "";');

/**
 * Uses the pear Text_Diff library to generate a diff between two files.
 * The only HTML-tags allowed are <p> and <div>, all other will be stripped.
 * @param string $text1 The first text
 * @param string $text2 The text to compare
 * @return string
 */
function diff($text1, $text2) {
__autoload('TextDiff');
include_once 'Text/Diff.php';
include_once 'Text/Diff/Renderer.php';
include_once 'Text/Diff/Renderer/unified.php';


    $vtext1 = chunk_split(strip_tags($text1, '<p><div>'), 1, "\n");
    $vtext2 = chunk_split(strip_tags($text2, '<p><div>'), 1, "\n");

    $vlines1 = str_split($vtext1, 2);
    $vlines2 = str_split($vtext2, 2);
    $text1 = str_replace("\n"," \n",$text1);
    $text2 = str_replace("\n"," \n",$text2);

    $vlines1 = explode(" ", $text1);
    $vlines2 = explode(" ", $text2);
    $diff = new Text_Diff($vlines1, $vlines2);
    $renderer = new Text_Diff_Renderer_inline();
    $html = html_entity_decode($renderer->render($diff));

    return preg_replace(array('#(<ins>|<del>)(<[^\>]+>)#i', '#(</[^\>]+>)(</ins>|</del>)#i'), '$2$1', $html);
}

/**
 * Dumps the variable to the browser
 * @return mixed
 */
function dump() {
    $args = func_get_args();
    echo '<div style="background-color: pink; margin: 3px;">';
    foreach($args as $arg) {
        echo "<p><pre>";
        var_dump($arg);
        echo "</pre></p>";
    }
    echo '</div>';
    return @$args[0];
}

/**
 * Shows the trace from the calling function and back
 * @return string trace
 */
function trace(){
    $r = array_map(create_function('$a', 'return (isset($a["class"])?$a["class"].$a["type"]:"").$a["function"]."(".@join(",",$a["args"]).");, Row: ".@$a["line"];'), debug_backtrace(false));
    array_shift($r);
    return $r;
}

/**
 * Turns the given name to a browser-acceptable, reproducable, id
 * @param $name name to convert
 * @return string
 */
function idfy($name=false){
    if(!$name) $name = uniqid(false, true);
    return 'id'.substr(md5($name),20);
}

/**
 * Returns the specified keys from an array, in specified order
 * @param $array Array to take values from
 * @param $keys Which keys to return, in desired order
 * @return array Sorted array containing the specified (and existing) keys only
 */
function arrayKeySort(&$array, $keys) {
    $res = array();
    foreach($keys as $key) {
        if(isset($array[$key])) {
            $res[$key] = $array[$key];
        }
    }
    return $res;
}

/**
 * Prepend an array with a value and return the new array
 * @param $array Array to prepend
 * @param $value Value to prepend
 * @return array Prepended array
 */
function arrayPrepend($array, $value) {
    array_unshift($array, $value);
    return $array;
}

/**
 * Extracts data from an array. The array can be a multidimensional array or an array of objects, and
 * the property may thus be an array index, property name or even, with the function parameter set, a class method
 * to call for result
 * @param array $array Array to extract from
 * @param string $property Property, index och method name to extract from
 * @param bool $keep_keys Keeps the array index of the original array
 * @param bool $function Set to true if the property should be invoked as a method call when appliccable
 * @return array Array containing the extracted elements
 */
function arrayExtract($array, $property, $keep_keys = false, $function=false) {
    $r = array();
    foreach($array as $key => $a) {
        if(is_object($a)) {
            if($keep_keys) {
                if($function && method_exists($a, $property)) {
                    $r[$key] = $a->$property();
                } else {
                    $r[$key] = @$a->$property;
                }
            } else {
                if($function && method_exists($a, $property)) {
                    $r[] = $a->$property();
                } else {
                    $r[] = @$a->$property;
                }
            }
        }
        elseif(isset($a[$property])) {
            if($keep_keys) {
                $r[$key] = $a[$property];
            } else {
                $r[] = $a[$property];
            }
        }
    }
    return $r;
}

/**
 * Sort an array of objects according to a given property
 * @param array $array Array of objects
 * @param string $property Property used for sorting
 * @return array Sorted array
 */
function propsort(&$array, $property) {
    uasort($array, create_function('$a,$b','return strcmp($a->'.$property.', $b->'.$property.');'));
    return $array;
}

/**
 * Trim a sentence after a given number of chars and at the end of a word
 * @param string $line String to trim
 * @param int $length Length of output
 * @param string $trim_suffix String to append the shortened string
 * @return string Shortened string
 * @author Joakim Gebart
 */
function lineTrim($line, $length, $trim_suffix = "...", &$clipping_occured = false) {
    $tmpstr = wordwrap ( trim($line), $length , '%%%%!#!%%%%');
    $lines = explode("%%%%!#!%%%%", $tmpstr, 2);
    $ret = $lines[0];
    if ($clipping_occured = (count($lines) > 1)) {
        $ret .= $trim_suffix;
    }
    return $ret;
}

/**
 * Returns a textual representation of a file's permissions
 * @param string $file Filename of the file to check
 * @return string Textual unix permission representation
 */
function perms($file) {
$perms = fileperms($file);

if (($perms & 0xC000) == 0xC000) {
    // Socket
    $info = 's';
} elseif (($perms & 0xA000) == 0xA000) {
    // Symbolic Link
    $info = 'l';
} elseif (($perms & 0x8000) == 0x8000) {
    // Regular
    $info = '-';
} elseif (($perms & 0x6000) == 0x6000) {
    // Block special
    $info = 'b';
} elseif (($perms & 0x4000) == 0x4000) {
    // Directory
    $info = 'd';
} elseif (($perms & 0x2000) == 0x2000) {
    // Character special
    $info = 'c';
} elseif (($perms & 0x1000) == 0x1000) {
    // FIFO pipe
    $info = 'p';
} else {
    // Unknown
    $info = 'u';
}

// Owner
$info .= (($perms & 0x0100) ? 'r' : '-');
$info .= (($perms & 0x0080) ? 'w' : '-');
$info .= (($perms & 0x0040) ?
            (($perms & 0x0800) ? 's' : 'x' ) :
            (($perms & 0x0800) ? 'S' : '-'));

// Group
$info .= (($perms & 0x0020) ? 'r' : '-');
$info .= (($perms & 0x0010) ? 'w' : '-');
$info .= (($perms & 0x0008) ?
            (($perms & 0x0400) ? 's' : 'x' ) :
            (($perms & 0x0400) ? 'S' : '-'));

// World
$info .= (($perms & 0x0004) ? 'r' : '-');
$info .= (($perms & 0x0002) ? 'w' : '-');
$info .= (($perms & 0x0001) ?
            (($perms & 0x0200) ? 't' : 'x' ) :
            (($perms & 0x0200) ? 'T' : '-'));
            return $info;
}

/**
 * Convert a number to text
 * Works for numbers up to 20
 * @param int $num
 * @return string
 */
function numberToText($num) {
    if(!is_numeric($num)){
        $num = intval($num);
        if(!is_numeric($num)) return 'NaN';
    }
    $numbers = array('zero', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine', 'ten', 'eleven', 'twelve', 'thirteen', 'fourteen', 'fifteen', 'sixteen', 'seventeen', 'eighteen', 'nineteen', 'twenty');
    return $numbers[$num];
}


/**
 * Calculates the relative path between $path and $rel
 * @param $path Absolute path to convert
 * @param $rel Path which to relate to
 * @return string Relative path between $path and $rel
 */
function relativePath($path, $rel=false) {
    $relpath = '';

    if(!$rel) $rel = getcwd();
    $apath = explode(DIRECTORY_SEPARATOR, realpath($path));
    $arel = explode(DIRECTORY_SEPARATOR, realpath($rel));
    while(isset($apath[0]) && isset($arel[0]) && $apath[0] == $arel[0]) {
        array_shift($apath);
        array_shift($arel);
    }

    return str_repeat('..'.DIRECTORY_SEPARATOR, count($arel)).join(DIRECTORY_SEPARATOR, $apath);
}


/**
 * Find all directories with files that match a given pattern
 * @param string $path Path to directory to search
 * @param string $pattern Pattern that the files should match
 * @return array A list of all the paths of the directories that contain matching files
 */
function findDirectoriesWithFiles($path, $pattern = '*') {
    $res = array();
    if(substr($path, -1) != '/') $path = $path.'/';
    foreach(glob($path.$pattern, GLOB_BRACE) as $file) {
        $res[] = dirname($file);
    }
    foreach(glob($path.'*', GLOB_ONLYDIR|GLOB_BRACE) as $file) {
        $res = array_merge($res, findDirectoriesWithFiles($file, $pattern));
    }
    return $res;
}
?>
