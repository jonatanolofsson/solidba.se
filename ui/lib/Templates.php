<?php

/**
 * @author Jonatan Olofsson [joolo]
 * @version 1.0
 * @license http://creativecommons.org/licenses/by-nc/3.0/ Creative Commons Attribution-Noncommercial 3.0 Unported License
 * @package Base
 */
/**
 * This class handles all the available templates handles the references to those
 * @package Base
 */
class Templates{
    private $templates;
    private $current;

    /**
     * Returns the template object asked for. Magical keywords are [admin,current,default,inherit,popup] which
     * dynamically points to a corresponding template set up in the configuration or corresponding
     * @param string $template The template asked for. If the template is not found, the next fallback template
     * is returned (first by inheritance, then system default template).
     * @return template
     */
    function __get($template) {
        global $PAGE;
        return $this->get($template, $PAGE);
    }

    /**
     * Returns the template object asked for. Magical keywords are [admin,current,default,inherit,popup] which
     * dynamically points to a corresponding template set up in the configuration or corresponding
     * If 'popup' is asked for, the subtemplate 'popup' of the current
     * @param string $template The template asked for. If the template is not found, the next fallback template
     * is returned (first by inheritance, then system default template).
     * @param Page $page Page that 'inherit'
     * @return template
     */
    function get($template, $page=false){
        global $CONFIG, $PAGE;
        if(!$page) $page = $PAGE;

        if($template == 'popup') {
            $t = $this->current('popup');
            if($t->subtpl == 'popup') {
                return $t;
            } else $template = 'popup';
        }

        if($template === 'current') {
            if(isset($this->current)) return $this->current;
            else return $this->current = $this->pageTemplate($page);
        }
        if($template === 'inherit') {
            $p = array_reverse($page->parents);
            foreach($p as $pa) {
                if($pa->template == 'inherit') continue;
                return $this->pageTemplate($pa);
            }
            $template = 'default';
        }

        $default = ($template == 'default');
        switch($template){
            case 'admin':
                $template = $CONFIG->Template->default .":admin";//$CONFIG->Template->admin
                if($template) break;
            case 'default':
                $template = $CONFIG->Template->default;
                $default = true;
                break;
            case 'page':
                $template = $this->pageTemplate($page);
        } // switch

        if(!$template) return;
        if(!isset($this->templates[$template])) $this->__load($template);
        if(isset($this->templates[$template]))
            return $this->templates[$template];
        elseif($default) return false;
        else return $this->__get('default');
    }

    /**
     * The magic method __call is used to attain a subtemplate. If the subtemplate is not found,
     * the default template file is returned.
     * <code>
     * $Templates->myTpl('subTpl')->render();
     * </code>
     * The above code will render subTpl, subtemplate to myTpl, if the file subTpl.php exists in the
     * myTpl template directory.
     * @param string $template This is the function name, i.e. the template that is asked for
     * @param array $args This should be a single element array with the subtemplate name
     * @return Template
     */
    function __call($template, $args) {
    global $PAGE, $CONFIG;
        $subtpl = $args[0];
        if($template == 'current') {
            if(isset($this->current)) $template = $this->current;
            else $template = $PAGE->template;
        }
        if($template == 'inherit') {
            $p = array_reverse($PAGE->parents);
            foreach($p as $pa) {
                if($pa->template == 'inherit') continue;
                else {
                    $template = $pa->template;
                    break;
                }
            }
            $template == 'default';
        }

        switch($template){
            case 'admin':
                $template = @$CONFIG->Template->default .":admin";//$CONFIG->Template->admin
                if($template) break;
            case 'default':
                $template = @$CONFIG->Template->default;
                break;
            case 'page':
                $template = $PAGE->template;
        } // switch
        if(!$template) return;
        if(!isset($this->templates[$template.':'.$subtpl])) $this->__load($template, $subtpl);
        if(isset($this->templates[$template.':'.$subtpl]))
            return $this->templates[$template.':'.$subtpl];
        else return $this->__get('default');
    }

    /**
     * retrieves the template set for a given page
     * @param Page $page The page
     * @return template
     */
    function pageTemplate($page){
        if(@!$page->template) $template = 'inherit';
        elseif(in_array($page->template, array('inherit', 'default', 'admin'))) {
            $template = $page->template;
        } else {
            if(isset($this->templates[$page->template]))
                return $this->templates[$page->template];
            elseif($this->__load($page->template)) {
                return $this->templates[$page->template];
            }
            else $template = 'inherit';
        }
        return $this->get($template, $page);
    }

    /**
     * Set the current template, or other property
     * @param string $property Property name
     * @param string $value The property's value
     * @return void
     */
    function __set($property, $value){
        if($property == 'current') {
            $a = $this->__get($value);
            $a->vars = array_join($a->vars, $this->current->vars);
            $this->current = $a;
        }
    }

    /**
     * Load a template file
     * @param $template
     * @return bool
     */
    private function __load($template, $subtpl=false){
        if(strstr($template, ':')) list($template, $subtpl) = explode(':', $template,2);
        if(!$subtpl) $subtpl = 'default';
        if(!isset($this->templates[$template.($subtpl=='default'?'':':'.$subtpl)])) {
                if(file_exists(TEMPLATE_DIR.DIRECTORY_SEPARATOR.$template.DIRECTORY_SEPARATOR.$subtpl.'.php')) {
                    $this->templates[$template.($subtpl=='default'?'':':'.$subtpl)] = new Template($template, $subtpl);
                    return true;
                } else return false;
        } else return true;
    }

    /**
     * Load all templates in the templates directory
     * @return void
     */
    private function __loadAll($force=false) {
        if($this->__allLoaded && !$force) return;
        $dir = dir(TEMPLATE_DIR);

        while(false !== ($file = $dir->read())) {
            if($file == '.' || $file == '..') continue;
            if(is_dir(TEMPLATE_DIR.DIRECTORY_SEPARATOR.$file)) {
                $subs = glob(TEMPLATE_DIR.DIRECTORY_SEPARATOR.$file.DIRECTORY_SEPARATOR.'*.php');
                foreach($subs as $sub) {
                    $this->__load($file, pathinfo($sub, PATHINFO_FILENAME));
                }
            }
        }
        $this->__allLoaded = true;
    }
    var $__allLoaded = false;

    /**
     * Returns a list with all the templates' names and (if available) thumbnail image
     * @return array Associative array with template name as key
     */
    public function ThumbnailImgList(){
        $this->__loadAll();
        $list = array();
        asort($this->templates);
        foreach($this->templates as $n => $t) {
            if(isset($t->info['thumbnail'])) $file = $t->info['thumbnail'];
            elseif(is_file($t->dir.'screen.png')) $file = 'screen'.(strpos($n,':')?'_'.substr(strstr($n,':'),1):'').'.png';
            elseif(is_file($t->dir.'screen.jpg')) $file = 'screen'.(strpos($n,':')?'_'.substr(strstr($n,':'),1):'').'.jpg';
            else $file = false;
            $list[$n] = $t->name;
            if($file) {
                if(strpos($n, ':') === false) $p = $n;
                else $p = substr($n, 0, strpos($n, ':'));
                $list[$n] .= '<img src="templates'.DIRECTORY_SEPARATOR.$p.DIRECTORY_SEPARATOR.$file.'" alt="'.$t->name.'" title="'.$t->name.'" />';
            }
        }
        return $list;
    }

    /**
     * List all templates (sorted)
     * @param $group Group in optgroups by main template
     * @return array
     */
    public function listAll($group=true, $system_templates=true) {
        if($system_templates) return array_merge(array(__('System') => array(	'inherit' => __('Inherit'),
                                                    'default' => __('Default'),
                                                    'admin' => __('Admin default')
                                                    )),
                                                $this->listAll($group, false));

        $this->__loadAll();
        $list = array();
        if(!$group) {
            foreach($this->templates as $n => $t) $list[$n] = $t->name;
        }
        else {
            foreach($this->templates as $n => $t) {
                $mtpl = explode(':', $n);
                $mtpl = $mtpl[0];
                $list[$mtpl][$n] = $t->name;
            }
            $list2 = $list;
            foreach($list2 as $m => $subtemplates) {
                if(count($subtemplates) == 1) {
                    unset($list[$m]);
                    reset($subtemplates);
                    $list[key($subtemplates)] = current($subtemplates);
                }
            }
        }
        ksort($list);
        return $list;
    }

    /**
     * Outputs the current page's template
     * @return void
     */
    function render() {
        global $PAGE;
        $this->pageTemplate($PAGE)->render();
    }
}

/**
 * Contains information about a template
 * @author Jonatan Olofsson [joolo]
 * @package Base
 */
class Template {
    public $vars = array();
    private $sections = array();
    private $dir;
    private $path;
    public $info;
    private $name;
    public $subtpl;

    /**
     * Set up the internal variables and read some information from the template
     * @param $name
     * @return unknown_type
     */
    function __construct($name, $subtpl=false) {
        if(!$subtpl) $subtpl = 'default';
        $this->subtpl = $subtpl;
        $this->dir = TEMPLATE_DIR.DIRECTORY_SEPARATOR.$name.DIRECTORY_SEPARATOR;
        $this->webdir = '/templates/'.$name.'/';
        $this->path = $this->dir.$subtpl.'.php';
        $this->rname = $name.($subtpl==='default'?'':'_'.$subtpl);
        $this->name = $name . ($subtpl == 'default' ? '':' ('.$subtpl.')');
        $this->__getSections();
        $this->__loadInfo();
    }

    /**
     * Returns a property value
     * @param string $property The property asked for
     * @return mixed
     */
    function __get($property) {
        if(in_array($property, array('dir', 'path', 'name')))
            return $this->$property;
        elseif($property == 'sections') {
            if(!$this->sections) $this->__getSections();
            return $this->sections;
        }
    }

    /**
     * Gets all sections from the template and stores them in the sections property
     * @return void
     */
    private function __getSections(){
        $matches = array();
        preg_match_all('#new Section\([\'"](?<name>[^\'"]+)#', file_get_contents($this->path), $matches, PREG_PATTERN_ORDER);
        $this->sections = $matches['name'];
    }

    /**
     * If available, load additional information about the template into the info property
     * @return void
     */
    private function __loadInfo() {
        if(is_file($this->dir.'info.xml'))
            $this->info = @simplexml_load_file($this->dir.'info.xml');
    }

    /**
     * Starts the rendering of the template by defining the global variables and including the template's default file
     * @return void
     */
    public function render() {
        global $ID, $SITE, $PAGE, $CURRENT, $USER, $DB, $CONFIG, $Controller;
        foreach($this->vars as $name => $value) $$name = $value;
        require $this->path;
    }

    /**
     * Register a variable for direct inclusion in the template's namespace
     * @param string $name name of the variable
     * @param mixed $value Value of the variable
     * @return void
     */
    public function set($name, $value) {
        $this->vars[$name] = $value;
    }

    /**
     * Register a variable by reference for direct inclusion in the template's namespace
     * @param string $name name of the variable
     * @param mixed $value Value of the variable
     * @return void
     */
    public function ref($name, &$value) {
        $this->vars[$name] &= $value;
    }
}

?>
