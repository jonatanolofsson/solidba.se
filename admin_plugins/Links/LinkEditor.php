<?php

/**
 * @author Jonatan Olofsson [joolo]
 * @version 1.0
 * @license http://creativecommons.org/licenses/by-nc/3.0/ Creative Commons Attribution-Noncommercial 3.0 Unported License
 * @package Pages
 */

/**
 * The PageEditor provides an interface for editing pages
 * @package Pages
 */
class LinkEditor extends Page{
    public $privilegeGroup = 'Administrationpages';
    static public function installable() {return __CLASS__;}
    static public function uninstallable() {return __CLASS__;}

    function install() {
        global $Controller;
        $o = $Controller->newObj('LinkEditor');//->move('last', 'adminMenu');
            MenuEditor::registerMaker('linkEditor','large/outbox1-32','New static link', array('id' => $o->ID,'lnedit' => 'new'), $o);
            MenuEditor::registerEditor('Link','linkEditor','small/link_edit','Edit link',array('id' => $o->ID),'lnedit');
    }
    function uninstall() {

    }

    /**
     * Sets up the object and makes sure that it's present in both the menu->makers and the menu->editors configuration sections.
     * @param integer $id The ID of the object
     * @return void
     */
    function __construct($id = false){
        parent::__construct($id);

        /**
         * User input types
         */
        $_REQUEST->addType('edit', array('numeric','#^new$#'));

        global $CURRENT, $DB, $CONFIG;
        if(is_numeric($id)) {
            $this->deletable = false;
            $this->icon = 'small/link';
            $this->alias = $alias = 'linkEditor';

            /**
             * User input types
             */
            $_REQUEST->setType('lnedit', array('numeric','#^new$#'));

        }

        $this->suggestName('Edit link');
    }

    /**
     * Overrides permission-test. If the user has privilege to EDIT the link, access is granted to the tool to do so.
     */
    function may($u, $a) {
        global $Controller, $ID, $CURRENT, $USER;
        if(($a & READ) &&
        (($ID == $this->ID && $_REQUEST->numeric('edit')
            && $Controller->{$_REQUEST['lnedit']}(EDIT))
        || ($ID != $this->ID && get_class($CURRENT) == 'Link' && $CURRENT->may($USER, EDIT)))) return true;
        else return parent::may($u, $a);
    }

    /**
     * Most actions of the module are here, along with the pageview logic
     * and template rendering
     */
    function run(){
    global $Controller, $USER, $DB;

        /**
         * User input types
         */
        $_REQUEST->setType('LinkEditorForm', 'any');
        $_REQUEST->setType('save', 'any');
        $_REQUEST->setType('status', 'string');
        $_REQUEST->setType('target', 'string');
        $_REQUEST->setType('title', 'string');
        $_REQUEST->setType('desc', 'string');
        $_REQUEST->setType('alias', 'string');
        $_REQUEST->addType('lnedit', array('numeric','#^new$#'));
        $_REQUEST->setType('parent', 'numeric');

        if($this->may($USER, READ)) {
            if(!$_REQUEST->valid('lnedit')) {
                $this->content = array('header' => __('An error has occurred'), 'main' => __('An error has occurred'));
            }
            else {
                if($_REQUEST['lnedit'] !== 'new') {
                    $link = $Controller->{$_REQUEST['lnedit']}(EDIT);
                    if(get_class($link) !== 'Link') return false;
                }
                if(($_REQUEST['lnedit'] == 'new' && $Controller->menuEditor->mayI(EDIT)) || $link->may($USER, EDIT)) {
                    /**
                     * Save changes
                     */
                    if($_REQUEST['save'] && $_REQUEST['LinkEditorForm']){
                        if($_REQUEST->nonempty('title')) {
                            if($_REQUEST['lnedit'] === 'new') $link = $Controller->newObj('Link');
                            $link->Name = $_REQUEST['title'];
                            $link->link = $_REQUEST['target'];
                            $link->description = $_REQUEST['desc'];

                            $link->resetAlias(array_map('trim', explode(',', $_REQUEST['alias'])));

                            if($_REQUEST['lnedit'] == 'new' || ($_REQUEST['parent'] && $_REQUEST['place'])) {
                                $link->move(($_REQUEST['place']?$_REQUEST['place']:'last'), $_REQUEST['parent']);
                            }
                            Flash::create(__('Your changes have been saved'), 'confirmation');
                            if($_REQUEST['lnedit'] == 'new') {
                                redirect(url(array('id' => 'menuEditor', 'status' => 'ok', 'section' => $_REQUEST['parent'])));
                            }
                        } else {
                            Flash::create(__('Title must not be empty'), 'warning');
                        }
                    }
                    /**
                     * Pageview logic
                     */
                    if($_REQUEST['lnedit'] == 'new') {
                        $this->content = array(	'header' => __('New link'),
                                                    'main' => $this->editor('new'));
                    } else {
                        $this->content = array(	'header' => __('Editing link').": ".$link,
                                                    'main' => $this->editor($link));
                    }
                }
                else errorPage('401');
            }
            global $Templates;
            $Templates->admin->render();
        }
    }

    /**
     * Outputs the pageeditor page
     * @param object $link The page that should be edited
     * @return string
     */
function editor($link){

    global $DB, $Templates, $Controller;

        $Form = new Form('LinkEditorForm');
        if($link != 'new') {
            if(!is_object($link)) $link = $Controller->$link(EDIT);
            elseif(!$link->mayI(EDIT)) return false;
        }

        return 	'<div class="nav">'.($Controller->menuEditor(READ)?'<a href="'.url(array('id' => 'menuEditor')).'">'.icon('small/arrow_up').__('To menu manager').'</a>':'').'</div>'
                .$Form->collection(
                    new Fieldset(__('Link properties'),
                            ($_REQUEST['parent']?new Hidden('parent', $_REQUEST['parent']):null),
                            new Input(__('Title'), 'title', @$link->Name, 'required', __('The name of the link')),
                            new Input(__('Target'), 'target', @$link->rawLink, 'required', __('The URL where to point the link')),
                            new Input(__('Alias'), 'alias', @join(',', $link->aliases), false, __('Any alias to associate with the link')),
                            new TextArea(__('Description'), 'desc', @$link->description, false, __('A description of the link'))
                        )
                );
    }
}

?>