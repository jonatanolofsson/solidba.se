<?php
/**
 * This class keeps track of which files are associated with solidba.se and makes sure all are properly installed
 * when first detected.
 * @author Jonatan Olofsson [joolo]
 * @license http://creativecommons.org/licenses/by-nc/3.0/ Creative Commons Attribution-Noncommercial 3.0 Unported License
 */

class Installer extends Page {
    private $installed = array();

    /**
     * Constructor
     * @param numeric $id ID from spine Database
     * @return void
     */
    function __construct($id){
        parent::__construct($id);
        $this->suggestName('Installer');
        $this->alias = 'Installer';
        $this->icon = 'small/computer';
    }

    /**
     * installs a new class by running the install method, if the installable method of the same class correctly returns
     * the name of the class (__CLASS__).
     *
     * The install method of the class should return the version number of the class for future
     * reference for the  upgrade function.
     * <code>
     * Installer::install('UserEditor');
     * </code>
     * @param strings $toinstall The files to install.
     * @return array|mixed Array of returns from the installations (or, if a single argument is passed, the return from this installation)
     */
    function install() {
        global $CONFIG;
        $toinstall = func_get_args();
        $installed = array();
        $return = array();

        $installed = $CONFIG->base->installed;
        if(!is_array($installed)) {
            $installed = array();
            $CONFIG->base->setType('installed', 'not_editable');
        }
        $installedVersions = $CONFIG->base->installedVersions;
        if(!is_array($installedVersions)) {
            $installedVersions = array();
            $CONFIG->base->setType('installedVersions', 'not_editable');
        }
        $iVersions = array();
        foreach($toinstall as $class) {
            if(strtolower(substr($class, -4)) == '.php') $class = substr($class, 0, -4);
            if(!in_array($class, $CONFIG->base->installed) && validInclude($class)) {
                __autoload($class);
                $methods = get_class_methods($class);
                if($methods && in_array('installable', $methods) && in_array('install', $methods) && call_user_func(array($class, 'installable')) == $class) {
                    $iVersions[$class] = $return[] = (string)call_user_func(array($class, 'install'));
                }
                $installed[] = $class;
            }
        }
        $CONFIG->base->installed = $installed;
        $CONFIG->base->installedVersions = array_merge($installedVersions, $iVersions);

        if(count($toinstall) == 1) return @$return[0];
        else return $return;
    }

    /**
     * Runs the upgrade method of a class, if the upgradable() method returns the correct
     * class name (__CLASS). The Update method should accept the previous version number
     * as argument and return the new (or current if unchanged)
     * @return void
     */
    function upgrade() {
        global $CONFIG;
        $toupgrade = func_get_args();

        $installed = $CONFIG->base->installed;
        if(!is_array($installed)) {
            $installed = array();
            $CONFIG->base->setType('installed', 'not_editable');
        }
        $installedVersions = $CONFIG->base->installedVersions;
        if(!is_array($installedVersions)) {
            $installedVersions = array();
            $CONFIG->base->setType('installedVersions', 'not_editable');
        }
        $iVersions = array();
        foreach($toupgrade as $class) {
            if(strtolower(substr($class, -4)) == '.php') $class = substr($class, 0, -4);
            if(!class_exists($class, false)) {
                __autoload($class);
                return;
            }
            $methods = get_class_methods($class);
            if(in_array('upgradable', $methods) && in_array('upgrade', $methods) && call_user_func(array($class, 'upgradable')) == $class) {
                $iVersions[$class] = (string)call_user_func(array($class, 'upgrade'), @$installedVersions[$class]);
            }
        }
        $CONFIG->base->installedVersions = array_merge($installedVersions, $iVersions);
    }

    /**
     * Run the unInstaller of a file and remove it
     * @param array|string $touninstall The files or classes to uninstall
     * @return array|mixed The returns from the unInstallers (an array if multiple, otherwise the output of the single file that was uninstalled)
     */
    function uninstall() {
        global $CONFIG;
        $touninstall = func_get_args();
        $uninstalled = array();
        $return = array();

        $installed = $CONFIG->base->installed;
        if(!is_array($installed)) {
            $installed = array();
            $CONFIG->base->setType('installed', 'not_editable');
        }
        foreach($touninstall as $class) {
            if(strtolower(substr($class, -4)) == '.php') $class = substr($class, 0, -4);
            if(in_array($class, $installed) && validInclude($class)) {
                __autoload($class);
                $methods = get_class_methods($class);
                if(in_array('uninstallable', $methods) && in_array('uninstall', $methods) && call_user_func(array($class, 'uninstallable')) == $class) {
                    $return[] = call_user_func(array($class, 'uninstall'));
                }
                $uninstalled[] = $class;
            }
        }
        $CONFIG->base->installed = array_diff($installed, $uninstalled);

        if(count($touninstall) == 1) return @$return[0];
        else return $return;
    }

    /**
     * See if any new files should be installed
     * @return void
     */
    function check() {
        return true; //FIXME
        global $CONFIG, $SITE;
        $installed = $CONFIG->base->installed;

        $folders = array(	ROOTDIR. '/lib',
                            ROOTDIR. "/admin_pages",
                            ROOTDIR. "/pages",
                            ROOTDIR. "/modules");
        foreach($folders as $path) {
            $dir = dir($path);

            while (false !== ($entry = $dir->read())) {
                $className = substr($entry, 0, -4);
                if(substr($entry, -4) == '.php' && !in_array($className, $installed))
                {
                    self::install($className);
                }
            }
        }
    }

    /**
     * Display the page for managing installations
     * @see lib/Page#run()
     */
    function run(){
        global $USER, $CONFIG, $Templates, $SITE, $Controller;
        if(!$this->may($USER, READ)) return;

        $_REQUEST->setType('place', 'numeric');
        $_REQUEST->setType('parent', 'numeric');
        $_REQUEST->setType('reinstall', 'string');
        $_REQUEST->setType('new', 'string');

        if($this->mayI(EDIT)) {
            if($_REQUEST['reinstall']) {
                $this->reinstall($_REQUEST['reinstall']);
                Flash::create($_REQUEST['reinstall'].' '.__('was reinstalled'));
            } elseif($_REQUEST['new']) {
                $class = $_REQUEST['new'];
                if(validInclude($class) && ($class == 'MenuItem' || @is_subclass_of($class, 'MenuItem')) && $Controller->menuEditor->mayI(EDIT)) {
                    $obj = $Controller->newObj($class);
                    $obj->move(($_REQUEST['place']?$_REQUEST['place']:'last'), $_REQUEST['parent']);
                    Flash::queue(__('New').' '.$class.' '.__('installed'));
                    redirect(url(array('id' => 'menuEditor')));
                }
                unset($class);
            }
        }
        $installed = $CONFIG->base->installed;
        $dir = 'plugins';

        $fullpath = ROOTDIR.DIRECTORY_SEPARATOR.$dir;
        $entries = readDirFilesRecursive($fullpath, true);
        natcasesort($entries);
        $i = 0; $c = array();
        foreach($entries as $entry) {
            if(substr($entry, -4) == '.php')
            {
                $class = substr($entry, false, -4);
                $methods = (class_exists($class)?get_class_methods($class):array());

                $c[] = '<span class="fixed-width">'
                    .$class
                    .'</span><div class="tools">'
                    .(($this->may($USER, EDIT) &&
                    (@in_array('installable', $methods) && @in_array('install', $methods) && call_user_func(array($class, 'installable')) == $class))
                        ?icon('small/arrow_refresh_small', __('Reinstall'), url(array('reinstall' => $class), array('id'))):'')
                    .((($class == 'MenuItem' || @is_subclass_of($class, 'MenuItem')) && $Controller->menuEditor->may($USER, EDIT))
                        ?icon('small/add', __('Add new instance to menu'), url(array('new' => $class), array('id'))):'')
                    .'</div>';
            }
        }

        $this->setContent('header', __('Installer'));
        $this->setContent('main', listify($c));

        $Templates->admin->render();
    }

    /**
     * First uninstalls, then installs anew, any given class.
     * @param strings $toReinstall The files to reinstall
     * @return void
     */
    function reinstall(){
        $toReinstall = func_get_args();
        call_user_func_array(array('Installer', 'uninstall'), $toReinstall);
        call_user_func_array(array('Installer', 'install'), $toReinstall);
    }
}
?>
