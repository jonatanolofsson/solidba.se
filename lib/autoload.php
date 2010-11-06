<?php
/**
 * Autoload new classes when needed. This is a PHP feature that allows for
 * automatic loading of classes' files. Note that these folders should be synced manually between these two functions.
 *
 * @author Jonatan Olofsson
 * @param string $class_name name of the class that's beeing loaded
 */
function __autoload($class_name) {
    try {
        require_once $class_name . '.php';
    }
    catch(Exception $err) {
        dump('Class '.$class_name . 'not found');
    }


    //FIXME: Replace
    /*if(class_exists('Installer', false) && class_exists($class_name, false)){
        Installer::upgrade($class_name);
    }*/
}


function updateAutoloadCache() {
    $pDIR = realpath(dirname(__FILE__).'/..').'/';
    $folders = '{lib,modules,autoload,admin_plugins,plugins,ui}/';
    $paths = implode(PATH_SEPARATOR, array_unique(findDirectoriesWithFiles($pDIR.$folders, '*.php')));

    file_put_contents(CACHE_DIR . 'path.cache', $paths);
    addPath($paths);
}
//FIXME:
updateAutoloadCache();
addPath(@file_get_contents(CACHE_DIR . 'path.cache'), true);



/**
 * Checks if a classname exists in one of the include directories, and therefore could
 * be included with __autoload. Note that these folders should be synced manually between these two functions.
 * @param $class_name
 * @return bool
 * @see __autoinclude()
 */
function validInclude($class_name){
        return (file_exists($class_name . '.php'));
}
?>
