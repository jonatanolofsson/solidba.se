<?php
/**
 * MenuEditor
 * @author Jonatan Olofsson [joolo]
 * @version 1.0
 * @license http://creativecommons.org/licenses/by-nc/3.0/ Creative Commons Attribution-Noncommercial 3.0 Unported License
 * @package Menu
 */
/**
 * The menueditor is a tool for editing the menu
 * @package Menu
 */
class MenuEditor extends Page {
    private $that;

    public $editable = array(
        'PageSettings' => EDIT,
        'PermissionEditor' => EDIT_PRIVILEGES,
        'MenuEditor' => EDIT,
    );

    private $DBTable = 'menu';
    static public function installable() {return __CLASS__;}
    static public function uninstallable() {return __CLASS__;}

    static public $edit_icon = 'small/book_next';
    static public $edit_text = 'Edit menu';

    function canEdit($obj) {
        return is_a($obj, 'MenuItem');
    }

    /**
     * Sets up the object
     * @param integer $id ID of the object
     * @return void
     */
    function __construct($obj){
        if(!is_object($obj)) {
            parent::__construct($obj);
            $this->that = false;
        }
        else {
            $obj = $this->getRuler($obj);
            parent::__construct($obj->ID);
        }
    }

    function getRuler($obj) {
        while($obj && !is_a($obj, 'MenuSection')) {
            $obj = $obj->parent;
        }
        $this->that = $obj;
        if(!$obj) {
            global $Controller;
            $obj = $Controller->alias('menuEditor');
        }
        return $obj;
    }

    function getParent($obj) {
        $obj = $obj->parent;
        if(!$obj) {
            global $Controller;
            $obj = $Controller->alias('menuEditor');
        }
        return $obj;
    }

    /**
     * Creates the nescesary table on installation
     * @return bool
     */
    function install() {
        global $DB, $USER, $Controller, $CONFIG;
        $DB->query('CREATE TABLE IF NOT EXISTS `'.$this->DBTable.'` (
  `id` int(11) NOT NULL,
  `parent` int(11) unsigned NOT NULL,
  `place` int(11) unsigned NOT NULL,
  KEY `id` (`id`,`parent`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;');

        return true;
    }

    /**
     * Drops the table on uninstall
     * @return bool
     */
    function uninstall() {
        global $DB, $USER;
        if(!$USER->may(INSTALL)) return false;
        $DB->dropTable($this->DBTable);
        return true;
    }

    /**
     * Overrides permission-test. If the user has privilege to EDIT the page, access is granted to the tool to do so.
     */
/*
    function may($u, $a) {
        global $Controller, $ID, $CURRENT, $USER;
        $_REQUEST->addType('section', 'numeric');
        if(($a & READ) &&
            (($ID == $this->ID && $_REQUEST->numeric('section')
            && $Controller->{(string) $_REQUEST['section']}(EDIT))
            || ($ID != $this->ID && $CURRENT->may($u, EDIT))))
            return true;
        else return parent::may($u, $a);
    }
*/

    /**
     * Contains actions and page view handling
     * @return void
     * @see solidbase/lib/Page#run()
     */
    function run() {
        global $Templates;

        if($this->mayI(ANYTHING)) {
            $this->saveChanges();
            $this->setContent('header', __('Edit the menu'));

            $this->menu = $this->getMenu($_REQUEST['section']);
            $this->setContent('main',
                $this->editMenu()
                .'<hr />'.$this->sectionForm()
                .'<div id="pagemoveform">'
                .$this->moveForm()
                .'</div>'
            );
            $Templates->admin->render();
        } else errorPage(401);
    }

    private function saveChanges() {
        global $Controller, $USER;

        $_REQUEST->setType('stpl', 'string');
        $_REQUEST->setType('newName', 'string');
        $_REQUEST->setType('page', 'numeric');
        $_REQUEST->setType('where', '/below|child/');
        $_REQUEST->setType('to', 'numeric');
        $_REQUEST->setType('action', 'string');

        /**
         * Delete menusection
         */
        if($_REQUEST['delete'] && $this->mayI(DELETE)) {
            $obj = $Controller->{$_REQUEST['delete']};
            if($DB->menu->exists(array('parent' => $_REQUEST['delete']))) {
                Flash::queue(__('Section not empty'), 'warning');
            }
            else {
                if($obj) $obj->deleteFromMenu();
                Flash::queue(__('Menu item removed'), 'warning');
            }
        }
        /**
         * Create a new section
         */
        if($_REQUEST['newName']) {
            if($DB->aliases->exists(array('alias' => $_REQUEST['newName']))) {
                Flash::queue(__('Alias already in use'));
            }
            else {
                $obj = $Controller->newObj('MenuSection');
                $obj->alias = $_REQUEST['newName'];
                $obj->template = $_REQUEST['stpl'];
                $obj->move('last', ($_REQUEST['section'] ? $_REQUEST['section'] : 0));
                Flash::create(__('New section created'), 'confirmation');
            }
        }

        /**
         * Create new page
         */
        if($_POST['action'] == 'newpage') {
            $newObj = $Controller->newObj('Page');
            $newObj->Name = __('New page');
            $_REQUEST['page'] = $newObj->ID;
        }

        /**
         * Move an item
         */
        if($_REQUEST['page'] && $_REQUEST['where'] && $_REQUEST['to']) {
            $obj = $Controller->{$_REQUEST['page']};
            if($obj) {
                $ruler = $this->getParent($obj);
                if($ruler->mayI(EDIT)) { // May edit source parent
                    $to = $Controller->{$_REQUEST['to']};
                    if($_REQUEST['where'] == 'below') {
                        $parent = $this->getParent($to);
                    } else {
                        $parent = $to;
                    }
                    if($parent->mayI(EDIT)) { // May edit target
                        if($_REQUEST['where'] == 'below') {
                            $obj->move($to->place()+1, $parent);
                        } else {
                            $obj->move(0, $parent);
                        }
                    }
                }
            }
        }
    }

    /**
     * Display the toolbar for section actions
     * @return string
     */
    private function sectionForm() {
        global $Templates;
        return Form::quick(false, __('Create'),
            new Formsection(__('New menusection'),
                new Hidden('action', 'newSection'),
                new Input(__('Section name'), 'newName'),
                Short::selectTemplate()
            )
        );
    }

    /**
     * Display the overview page for menu editing
     * @return string
     */
    private function editMenu() {
        $r = '<div class="menuedit">'. $this->makeMenu($this->getMenu($this->that, false)).'</div>';

        return $r;
    }

    function getMenu($obj=false, $limit = true, $hide_sections = true) {
    global $DB, $Controller, $CONFIG, $USER;
        $Menus = array();
        $iMenus = array();
        $MenuRows = array();

        if(!$obj) {
            $last_level = $DB->menu->asList(array('parent' => array('',0,null)), 'id');
            $MenuRows = $DB->{'spine,menu'}->asArray(array('spine.id' => $last_level), false, false, true, 'place');
        }
        else {
            $last_level = array($obj->ID);
        }
        $new_rows = 0;
        do{
            $newMenuRows = $DB->{'spine,menu'}->asArray(array('menu.parent' => $last_level), false, false, true, 'place');
            $last_level = array();
            $MenuRows = arrayKeyMergeRecursive($MenuRows, $newMenuRows);
            if($limit) {
                foreach($newMenuRows as $id => $row) {
                    if($row['class'] != 'MenuSection' || $id == $_REQUEST['expand']) //FIXME: Implement expand
                    {
                        $last_level[] = $id;
                    }
                }
            } else {
                $last_level = array_keys($newMenuRows);
            }
        } while($last_level);
        if($MenuRows) {
            MenuItem::preload(array_keys($MenuRows));
            foreach($MenuRows as $id => $row) {
                $obj = $Controller->{$id};
                if($obj) {
                    $Menus[] = array(   'id' => $id,
                                        'parent' => $row['parent'],
                                        'place' => $row['place'],
                                        'object' => $obj);
                }
            }
            $iMenus = inflate($Menus, true);
        }
        return $iMenus;
    }

    /**
     * Render the menu for editing
     * @param array $array
     * @return string
     */
    private function makeMenu($array) {
        static $i = 0;
        static $recursion = 0;

        if(count($array) == 0) return;
        ++$recursion;
        global $CONFIG,$USER;
        if($this->mayI(EDIT)) {
            JS::loadjQuery(false);
            JS::lib('menusort');
        }
        /* $r=''; */
        $r = '<ul class="menulist">';
        $save = array('edit', 'with', 'id');
        while(list(, $obj) = each($array)) {
            $r .= '<li id="m'.$obj['id'].'" class="'.(++$i%2?'odd':'even').(is_a($obj['object'], 'MenuSection')?' menusection':'').'"><span class="fixed-width">'.$obj['object'].'</span>'
                .Box::tools($obj['object'])
                .Box::dropdown('small/add', false,
                    array(
                        icon('small/arrow_right', __('New child page'), url(array('action' => 'newpage', 'where' => 'child', 'to' => $obj['id']), $save), true),
                        icon('small/arrow_down',  __('New page below'), url(array('action' => 'newpage', 'where' => 'below', 'to' => $obj['id']), $save), true)
                    )
                )
                .Box::dropdown('small/arrow_out', false,false, 'pagemove')
                .(isset($obj['children'])?$this->makeMenu($obj['children']):'')
                .'</li>';
        }
        $r .= '</ul>';

        --$recursion;
        if($recursion == 0) $i = 0;
        return $r;
    }

    function moveForm() {
        $menu = $this->getMenu(false, false, false);
        return Form::quick(false, null,
            '<h2>'.__('Move page').'</h2><span id="whichpagetomove">'.new Select(false, 'page', $menu, false, false, true).'</span>',
            new RadioSet('.. '.__('as '), 'where', array('child' => __('Child'),'below' => __('Below'))),
            new Select('.. '.__('to'), 'to', $menu, false, false, true)
        );
    }
}

?>
