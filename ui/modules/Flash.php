<?php

/**
 * @author Jonatan Olofsson [joolo]
 * @version 1.0
 * @package Content
 */

 /**
  * The flash class is created to view system messages and warnings
 * @author Jonatan Olofsson [joolo]
 * @package Content
  */
class Flash{
    static $__FLASHES = array();

    /**
     * When a flash is created, it is called with a message and an optional type.
     * The type is set as the class on the div (prepended by 'flash_type_')
     * that is later created and displayed to the user.
     * Messages with the same class are grouped and duplicates within a
     * class are not displayed.
     *
     * @param string $message The message to be shown
     * @param string $type The class of the flash
     * @return void
     */
    function create($message, $type=false){
        $type = ($type?$type:'notice');
        if(!isset(self::$__FLASHES[$type]) || !in_array($message, self::$__FLASHES[$type])){
            self::$__FLASHES[$type][] = $message;
        }
    }



    /**
     * When a flash is created, it is called with a message and an optional type.
     * The type is set as the class on the div (prepended by 'flash_type_')
     * that is later created and displayed to the user.
     * Messages with the same class are grouped and duplicates within a
     * class are not displayed.
     *
     * @param string $message The message to be shown
     * @param string $type The class of the flash
     * @return void
     */
    function queue($message, $type=false){
        $type = ($type?$type:'notice');
        if(!isset($_SESSION['__FLASHES']) || !is_array($_SESSION['__FLASHES']))
            $_SESSION['__FLASHES'] = array();
        if(!isset($_SESSION['__FLASHES'][$type]) || !in_array($message, $_SESSION['__FLASHES'][$type])){
            $_SESSION['__FLASHES'][$type][] = $message;
        }
    }


    /**
     * Display all flashes
     * @return HTML
     */
    static function display() {
        if(isset($_SESSION['__FLASHES']) && is_array($_SESSION['__FLASHES'])) {
            self::$__FLASHES = $_SESSION['__FLASHES'] + self::$__FLASHES;
            unset($_SESSION['__FLASHES']);
        }
        if(!empty(self::$__FLASHES)) {
            echo '<div class="flash_container">';
            foreach(self::$__FLASHES as $type => $flashes) {
                echo '<div class="flash flash_type_'.$type.'"><div class="icon"></div><ul>';
                foreach($flashes as $flash) {
                    echo '<li>'.$flash.'</li>';
                }
                echo '</ul></div>';
            }
            //FIXME: Add function for yes/no buttons
            echo '</div>';
            JS::loadjQuery(true);
            JS::raw('$(function(){'
                .'$(".flash_container").dialog({modal:true,buttons:{Ok:function(){$(this).dialog("close");}}});'
            .'});');
        }
    }
}
?>
