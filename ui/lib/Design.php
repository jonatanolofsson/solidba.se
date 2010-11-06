<?php
class Design {
    private static $stdColWidth = 50;
    private static $stdMargin = 10;
    
    function getPxWidth($cols) {
        return (int)($cols * self::$stdColWidth + ($cols-1)*self::$stdMargin);
    }
    
    /**
     * @param string $content
     * @param bool $spacer
     * @return string
     */
    function row($content, $spacer=false){
        if(!is_array($content)) $content = array($content);
        $r = '<div class="cols'.($spacer?' spacer':'').'">';
        foreach($content as $col){
            $r .= $col;
        }
        $r .= '</div>';
        return $r;
    }
    
    /**
     * @param string|array $content
     * @param int $size
     * @param bool $first
     * @param bool $border
     * @param bool $thinner
     * @param bool $lighter
     * @param bool $class
     * @return string
     */
    function column($content, $size, $first=false, $border=false, $thinner=false, $lighter=false, $class=false){
        return '<div class="col'.($first?' first':'').' '.numberToText($size).($border?' bordered':'').($thinner?' thinner':'').($lighter?' lighter':'').($class?' '.$class:'').'">'.$content.'</div>';
    }
    
    
    /**
     * 
     * @param string $module name(machine-readable) of the module
     * @param string $name name(Human-readable) of the module
     * @param array $size Valid size for the module
     * @param string $type Type of input. Valid input are autofill, text, image
     * @param array $classes Classes where the module can be used ?
     * @return void
     */
    function registerModule($module, $name, $icon, $size, $type, $classes, $editorcontent=false) {
        global $CONFIG;
        $m = $CONFIG->pagemodules->settings;
        if(!is_array($m)) $m = array();
        if(!is_array($size)) $size = array($size);
        if(!is_array($type)) $type = array($type);
        if(!is_array($classes)) $classes = array($classes);
        if(!array_key_exists($module,$m)) {
            $m[$module] = array('name' => $name,
                                'icon' => $icon,
                                'size' => (array)$size,
                                'type' => (array)$type,
                                'classes' => (array)$classes,
                                'editorcontent' => $editorcontent);
            $CONFIG->pagemodules->settings = $m;
        }
    }
    
    function editModule($module, $property, $value) {
        global $CONFIG;
        $m = $CONFIG->pagemodules->settings;
        if(array_key_exists($module,$m)) {
            $m[$module][$property] = $value;
            $CONFIG->pagemodules->settings = $m;
            return true;
        }
        return false;
    } 
    
    /**
     * 
     * @param string $module name(machine-readable) to be unreged
     * @return void
     */
    function unregisterModule($module) {
        global $CONFIG;
        $m = $CONFIG->pagemodules->settings;
        if(!is_array($m)) $m = array();
        unset($m[$module]);
        $CONFIG->pagemodules->settings = $m;
    }

}
?>