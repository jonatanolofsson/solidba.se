<?php

/**
 * @author Jonatan Olofsson [joolo]
 * @version 1.0
 * @license http://creativecommons.org/licenses/by-nc/3.0/ Creative Commons Attribution-Noncommercial 3.0 Unported License
 * @package Content
 */

/**
 * The PageEditor provides an interface for editing pages
 * @package Content
 */
class PageSettings extends Page{
    static public $edit_icon = 'small/page_edit';
    static public $edit_text = 'General settings';

    private $that = false;

    /**
     * Sets up the object and makes sure that it's present in both the menu->makers and the menu->editors configuration sections.
     * @param integer $id The ID of the object
     * @return void
     */
    function __construct($obj){
        parent::__construct($obj->ID);
        $this->that = $obj;
    }

    function run() {
        __autoload('Form');
        if($this->saveChanges()) redirect(array('id' => $this->that->ID));
        global $Templates;
        $this->setContent('header', __('Edit page settings'));
        $this->setContent('main',
            Form::quick(false, null,
                $this->editTab()
            )
        );
        $Templates->admin->render();
    }

    function editTab() {
        return array(
            new Input(__('Title'), 'title', $this->that->Name),
            new Input(__('Aliases'), 'alias', implode(',',$this->that->aliases)),
            new Checkbox(__('Comments enabled'), 'commentsEnabled', (isset($_POST['commentsEnabled'])?true:@$this->that->settings['comments'])),
            Short::selectTemplate($this->that->template)
        );
    }

    function saveChanges() {
        $_POST->setType('title', 'string');
        $_POST->setType('template', 'string');
        $_POST->setType('alias', 'string');
        $_POST->setType('commentsEnabled', 'bool');

        if(!$_POST['title']) return false;
        $this->that->Name = $_POST['title'];
        $this->that->resetAlias(explode(',', $_POST['alias']));
        $this->that->setActive(
            Short::parseDateAndTime('activate'),
            Short::parseDateAndTime('deactivate')
        );
        $this->that->settings['comments'] = isset($_POST['commentsEnabled']);

        if($_POST['template']) {
            $this->that->template = $_POST['template'];
        }
        Flash::queue(__('Your changes were saved'), 'confirmation');
        return true;
    }


    function canEdit($obj)
    {
        return is_a($obj, 'Page');
    }
}

?>
