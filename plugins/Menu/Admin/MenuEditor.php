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
class MenuEditor extends Page{
    public $title;
    public $content;
    public $privilegeGroup = 'Administrationpages';
    private $DBTable = 'menu';
    static public function installable() {return __CLASS__;}
    static public function uninstallable() {return __CLASS__;}

    /**
     * Sets up the object
     * @param integer $id ID of the object
     * @return void
     */
    function __construct($id=false){

        parent::__construct($id);
        $this->suggestName('Edit menu');
        $this->icon = 'small/link';
        $this->deletable = false;

        $this->alias = 'menuEditor';

        global $CONFIG;
        $CONFIG->menu->setType('editors', 'not_editable');
        $CONFIG->menu->setType('makers', 'not_editable');
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

        $CONFIG->menu->setType('makers', 'not_editable');
        $CONFIG->menu->setType('editors', 'not_editable');

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

    /**
     * Contains actions and page view handling
     * @return void
     * @see solidbase/lib/Page#run()
     */
    function run(){
    global $USER, $Templates, $DB, $Controller;
        /**
         * User input types
         */
        $_REQUEST->setType('delete', 'numeric');
        $_REQUEST->setType('whichSection', 'any');
        $_REQUEST->setType('name', 'string');
        $_REQUEST->setType('stpl', 'string');
        $_REQUEST->setType('newName', 'string');
        $_REQUEST->setType('move', 'numeric');
        $_REQUEST->setType('how', 'string');
        $_REQUEST->setType('status', 'string');
        $_REQUEST->setType('action', 'string');
        $_REQUEST->setType('editSection', 'numeric');
        $_REQUEST->setType('moving', '#^menusort_([0-9]+)$#');
        $_REQUEST->setType('toParent', '#^menusort_([0-9]+)$#');
        $_REQUEST->setType('toPlace', 'numeric');
        $_REQUEST->setType('section', 'numeric');
        $_REQUEST->setType('parent', 'numeric');

        if($this->mayI(ANYTHING)) {
            /**
             * Delete menusection
             */
            if($_REQUEST['delete'] && $this->mayI(DELETE)) {
                $obj = $Controller->{$_REQUEST['delete']};
                if($DB->menu->exists(array('parent' => $_REQUEST['delete']))) {
                    Flash::create(__('Section not empty'), 'warning');
                }
                else {
                    if($obj) $obj->deleteFromMenu();
                    Flash::create(__('Menu item removed'), 'warning');
                }
            }
            if($this->mayI(READ)) { // FIXME: needs reworking
                global $DB;
                if($_REQUEST->valid('moving', 'toParent', 'toPlace')) {
                    $moving 	= substr($_REQUEST['moving'], 9);
                    $toParent 	= substr($_REQUEST['toParent'], 9);
                    $toPlace	= $_REQUEST['toPlace'];
                    if($obj = $Controller->$moving(OVERRIDE)) {
                        $obj->move($toPlace, $toParent);
                    }
                    exit;
                }

                /**
                 * Edit section
                 */
                if($_REQUEST['whichSection'] && $_REQUEST['name']) {
                    if($obj = $Controller->{$_REQUEST['whichSection']}('MenuSection')) {
                        if($DB->aliases->exists(array('alias' => $_REQUEST['name'], 'id!' => $obj->ID))) {
                            Flash::create(__('Alias already in use'));
                        }
                        else {
                            $obj->resetAlias($_REQUEST['name']);
                            $obj->template = $_REQUEST['stpl'];
                            Flash::create(__('Section edited'), 'confirmation');
                        }
                    }
                }
                /**
                 * Create a new section
                 */
                if($_REQUEST['action'] === 'newSection' && $_REQUEST->nonempty('newName')) {
                    if($DB->aliases->exists(array('alias' => $_REQUEST['newName']))) {
                        Flash::create(__('Alias already in use'));
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
                 * Move a menuitem
                 */
                if($_REQUEST->valid('move') && $_REQUEST['how']) {
                    if($this->mayI(READ) && ($mpost = $DB->menu->{$_REQUEST['move']}) && $obj = $Controller->{$_REQUEST['move']})
                    {
                        switch($_REQUEST['how']){
                            case 'up':
                                if($mpost['place']>0) {
                                    if($pre = @$obj->previous()->place) {
                                        $obj->move($pre,$mpost['parent']);
                                    }
                                }
                                break;
                            case 'left':
                                if($mpost['parent']>0) {
                                    if($parent = $DB->menu->{$mpost['parent']}) {
                                        $obj->move($parent['place']+1, $parent['parent']);
                                    }
                                }
                                break;
                            case 'right':
                                if($mpost['place']>0) {
                                    if($previous = $obj->previous(false)) {
                                        $obj->move('last', $previous);
                                    }
                                }
                                break;
                            case 'down':
                                if($nxt = @$obj->next()->place) {
                                    $obj->move($nxt+1, $mpost['parent']);
                                }
                                break;
                        } // switch
                        redirect(url(null, array('id','section')));
                    }
                }
                if($_REQUEST['status'] == 'ok') Flash::create(__('The new page was added successfully'), 'confirmation');
            }
            $this->content = array('header' => __('Edit the menu'), 'main' => $this->editMenu($_REQUEST['section']).($this->mayI(READ)?$this->sectionActions():'')); //FIXME: Dubious may
            $Templates->render();
        } else errorPage(401);
    }

    /**
     * Display the toolbar for section actions
     * @return string
     */
    private function sectionActions() {
        global $USER, $DB, $Controller, $Templates;
        $tabs = array();
        $i=0;
        if($_REQUEST->numeric('editSection') &&	$o = $Controller->{$_REQUEST['editSection']}(EDIT,'MenuSection')) {
            $form = new Form('sectionActions', url(null, array('id','section')), __('Save'));
            $tabs[$i] = new EmptyTab(__('Edit section'),
                                $form->collection(
                                    new Set(
                                        new Hidden('whichSection', $_REQUEST['editSection']),
                                        new Input(__('Section name'), 'name', $o->alias),
                                        new select(__('Section default template'), 'stpl', $Templates->listAll(), ($_REQUEST['stpl']?$_REQUEST['stpl']:@$o->template))
                                )));
            $i++;
        }
        $nform = new Form('newSection', url(null, array('id','section')), __('Create'));
        $tabs[$i] = new EmptyTab(__('New section'),
                            $nform->collection(
                                new Set(
                                    new Hidden('action', 'newSection'),
                                    new Input(__('Section name'), 'newName'),
                                    new select(__('Section default template'), 'nstpl', $Templates->listAll())
                                )
                            ));
        return new Tabber('msa', $tabs);
    }

    /**
     * Display the overview page for menu editing
     * @return string
     */
    private function editMenu($menu=false) {
    global $DB, $Controller, $CONFIG, $USER;
        $Menus = array();
        $iMenus = array();
        $MenuRows = array();
        $r='';

        if(!$menu) {
            $last_level = $DB->menu->asList(array('parent' => array('',0,null)), 'id');
            $MenuRows = $DB->{'spine,menu'}->asArray(array('spine.id' => $last_level), false, false, true, 'place');
        }
        else {
            if(!($mobj = $Controller->{(string)$menu}(EDIT))) errorPage(401);
            $last_level = array($mobj->ID);
        }
        $new_rows = 0;
        do{
            $newMenuRows = $DB->{'spine,menu'}->asArray(array('menu.parent' => $last_level), false, false, true, 'place');
            $last_level = array();
            $MenuRows = arrayKeyMergeRecursive($MenuRows, $newMenuRows);
            foreach($newMenuRows as $id => $row)
                if($row['class'] != 'MenuSection' || $id == $_REQUEST['expand']) //FIXME: Implement expand
                    $last_level[] = $id;
        } while($last_level);

        $r .= '<div class="nav"><a href="'.url(null, 'id').'">'.icon('small/arrow_left').__('Back to menu overview').'</a></div>';

        if($MenuRows) {
            MenuItem::preload(array_keys($MenuRows));
            foreach($MenuRows as $id => $row) {
                $obj = $Controller->{$id};
                if($obj) {
                    $Menus[] = array(	'id' => $id,
                                        'parent' => $row['parent'],
                                        'place' => $row['place'],
                                        'object' => $obj);
                }
            }
            $iMenus = inflate($Menus, true);
        }
        if($this->mayI(READ)) { // FIXME: Needs reworking
            $makers = $CONFIG->menu->makers;
            if(is_array($makers)) {
                $rt = '<div class="ui-helper-reset ui-helper-clearfix ui-widget-header ui-corner-all">';
                $rtt = '';
                foreach($makers as $maker) {
                    if(!isset($maker['lnarray'], $maker['title'])
                        /*|| !$Controller->{$maker['who']}(READ)*/) continue;//conflicts with special meaning of may(READ) on pageEditor

                    //FIXME: FULT J"VLA HACK, fixas b'st i lib/functions.php url()-funktionen.
                    $_REQUEST->addType('edit','string');
                    //FIXME: FULT J"VLA HACK, fixas b'st i lib/functions.php url()-funktionen.
                    $_REQUEST->addType('lnedit','string');

                    $rtt .= '<a href="'.url(array_merge($maker['lnarray'], array('parent' => ($_REQUEST['section'] ? $_REQUEST['section'] : 0))), array('section')).'">';
                    if(@$maker['icon']) $rtt .= icon($maker['icon']);
                    $rtt .= $maker['title'].'</a>';
                }
                if($rtt) {
                    $r .= $rt.$rtt.'</div>';
                }
            }
        }
        $r .= '<div class="menuedit menustop" id="menusort_'.($menu ? $mobj->ID : '0').'">'. $this->makeMenu($iMenus).'</div>';

        return $r;
    }

    /**
     * Render the menu for editing
     * @param array $array
     * @return string
     */
    private function makeMenu($array) {
        static $i = 0;
        static $recursion = 0;
        ++$recursion;

        if(count($array) == 0) return;
        global $CONFIG,$USER;
        if($this->mayI(READ)) {
            JS::loadjQuery(false);
            JS::lib('menusort');
        }
        /* $r=''; */
        $r = '<ul class="menulist">';

        $previous = false;
        while(list(, $obj) = each($array)) {
            $next = current($array);
            $editors = array();
            $class = get_class($obj['object']);
            foreach($CONFIG->menu->editors as $exp => $e) {
                if($exp == $class
                    || (!preg_match('/^[a-z0-9]/i', $exp )
                        && preg_match($exp, $class))) {
                            $editors += $e;
                        }
            }
            $r .= '<li id="menusort_'.$obj['id'].'" class="'.(++$i%2?'odd':'even').(is_a($obj['object'], 'MenuSection')?' menusection':'').'"><span class="fixed-width">'.$obj['object'].'</span>'
                .'<div class="tools">'

                .($this->mayI(READ)
                ?	icon('small/arrow_up', __('Move post up'), url(array('move' => $obj['id'], 'how' => 'up'), array('id','section')), 'menu_move_up')
                    .icon('small/arrow_turn_down_left', __('Move to after parent'), url(array('move' => $obj['id'], 'how' => 'left'), array('id','section')), 'menu_move_left')
                    .icon('small/arrow_right', __('Make child to predecessor'), url(array('move' => $obj['id'], 'how' => 'right'), array('id','section')), 'menu_move_right')
                    .icon('small/arrow_down', __('Move post down'), url(array('move' => $obj['id'], 'how' => 'down'), array('id','section')), 'menu_move_down')
                :'')

                .'</div><div class="tools2">'
                .(is_a($obj['object'], 'MenuSection') && $obj['object']->mayI(EDIT)
                    ?	icon('small/pencil', __('Edit'), url(array('editSection' => $obj['id']), array('id','section')))
                        .icon('small/book_next', __('View this section only'), url(array('section' => $obj['id']), array('id')))
                    :'')

                .(empty($editors)?'':
                            $this->predicatedJoin($editors, $obj['object'],$obj['id']))

                .($obj['object']->mayI(DELETE)?icon('small/delete', __('Delete'), url(array('delete' => $obj['id']), array('id','section'))):'')
                .'</div>'.(isset($obj['children'])?$this->makeMenu($obj['children']):'').'</li>';
            $previous = $obj;
        }
        $r .= '</ul>';

        --$recursion;
/* 		if(!$recursion) $i = 0; */
        return $r;
    }

    /**
     * @param array @pieces This is the pieces that are tested against a predicate included in the beginning of the string
     * @param object $obj The object the predicate should be tested on
     * @param numeric $oid The current object's id
     * @return string
     */
    private function predicatedJoin($pieces, $obj, $oid) {
        global $USER;
        $res = '';
        foreach($pieces as $identifier => $editors){
            foreach($editors as $priv => &$e) {
                if($priv == 0 || $obj->mayI($priv)) {
                    $e['lnarray'][$e['idvar']] = $oid;
                    $_REQUEST->addType($e['idvar'],'numeric');
                    $e = icon($e['icon'],__($e['title']),url($e['lnarray'],'section'));
                }
                else {
                    $e = '';
                }
            }
            $res .= join('', $editors);
        }

        return $res;
    }

    /**
     *
     * @param $identifier
     * @param $icon
     * @param $title
     * @param $lnarray
     * @param $that
     * @return unknown_type
     */
    function registerMaker($identifier,$icon,$title,$lnarray,$that=false) {
        global $CONFIG;
        $m=$CONFIG->menu->makers;
        if(!is_array($m)) $m = array();
        $m[$identifier] = array(	'icon' => $icon,
                                    'title' => $title,
                                    'lnarray' => (array)$lnarray,
                                    'who' => ($that?$that->ID:$this->ID));
        $CONFIG->menu->makers = $m;
    }

    function registerEditor($class,$identifier,$icon,$title,$lnarray,$idvar,$accessLevel=EDIT) {
        global $CONFIG;
        $m=$CONFIG->menu->editors;
        if(!is_array($m)) $m = array();
        $m[$class][$identifier][$accessLevel] = array(	'icon' => $icon,
                                                        'title' => $title,
                                                        'lnarray' => (array)$lnarray,
                                                        'idvar' => $idvar);
        $CONFIG->menu->editors = $m;
    }

    function unregisterEditor($class,$identifier) {
        global $CONFIG;
        $m=$CONFIG->menu->editors;
        if(!is_array($m)) $m = array();
        unset($m[$class][$identifier]);
        $CONFIG->menu->editors = $m;
    }
}

?>
