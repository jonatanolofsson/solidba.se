<?php
/**
 * @author Jonatan Olofsson [joolo]
 * @version 1.0
 * @package Base
 * @license http://creativecommons.org/licenses/by-nc/3.0/ Creative Commons Attribution-Noncommercial 3.0 Unported License
 */

/**
 * The Site class gathers a few informations about the site itself
 * @author Jonatan Olofsson [joolo]
 * @package Base
 */
class Site {
    private $URL;
    private $fullURL;
    private $base_dir;
    private $settings;

    /**
     * Returns the property asked for
     * @param string $property The property asked for
     * @return mixed
     */
    function __get($property) {
        global $CONFIG;
        if($property == 'Name') return $CONFIG->Site->Name;
        return $this->$property;
    }

    /**
     * Gathers and generates a few handy properties about the site
     * @return void
     */
    function __construct(){
        global $CONFIG;
        $ssl = false;

        $this->settings = new Settings(0);

        //FIXME: Yweb-specific
        $this->base_dir = getcwd();
        $this->fullURL = 'http';
        if(isset($_SERVER['HTTPS']) && @$_SERVER['HTTPS']=='on') {
            $ssl = true;
            $this->fullURL .=  's';
        }
        $this->fullURL .=  '://';
        $this->fullURL .=  @$_SERVER['HTTP_HOST'];
        
        $this->fullURL .= substr(@$_SERVER['SCRIPT_NAME'], 0, -(max(strlen(strrchr(@$_SERVER['SCRIPT_NAME'], '/')), strlen(strrchr(@$_SERVER['SCRIPT_NAME'], '\\')))));
        $this->URL = $this->fullURL;
        if(@$_SERVER['QUERY_STRING']>' '){
            $this->fullURL .=  '?'.@$_SERVER['QUERY_STRING'];
        }

        $CONFIG->Site->setDescription('Name', 'The name of the site');
    }
    
    function url($force_www = false, $ssl=false, $include_gets = false, $old = false) {
        $fullURL ='http';
        if($ssl || (isset($_SERVER['HTTPS']) && @$_SERVER['HTTPS']=='on')) {
            $fullURL .=  's';
        }
        $fullURL .=  '://';
        if($force_www === -1 && strtolower(substr($_SERVER['HTTP_HOST'], 0, 4)) == 'www.') {
            $fullURL .= substr($_SERVER['HTTP_HOST'], 4);
        } elseif($force_www > 0 && strtolower(substr($_SERVER['HTTP_HOST'], 0, 4)) != 'www.') {
            $fullURL .= 'www.'.$_SERVER['HTTP_HOST'];
        } else $fullURL .=  @$_SERVER['HTTP_HOST'];
        
        if($include_gets) {
            global $OLD;
            $fullURL .= url(($old && isset($OLD)?array('id' => $OLD):null), true, true, -1);
        }
        return $fullURL;
    }
}
?>
