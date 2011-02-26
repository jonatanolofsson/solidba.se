<?php

class MenuSectionEditor extends Page {
    private $that = false;
    private $ignore = array('Userimages', 'Userfiles', 'UserDirectory');

    static public $edit_icon = 'small/tab_edit';
    static public $edit_text = 'Edit section';

    function canEdit($obj) {
        return is_a($obj, 'MenuSection');
    }

    function __construct($obj){
        parent::__construct($obj->ID);
        $this->that = $obj;
    }

    /**
     * (non-PHPdoc)
     * @see lib/Page#run()
     */
    function run() {
        if($this->saveChanges()) redirect(-1);

        $this->setContent('main',
            Form::quick(false, __('Save'),
                $this->medit()
            )
        );

        global $Templates;
        $Templates->render();
    }

    function saveChanges() {
        $_REQUEST->setType('oldname', 'string');
        $_REQUEST->setType('newname', 'string');
        if($_REQUEST['oldname'] && $_REQUEST['newname']) {
            if($obj = $Controller->{$_REQUEST['oldname']}('MenuSection')) {
                if($DB->aliases->exists(array('alias' => $_REQUEST['newname'], 'id!' => $obj->ID))) {
                    Flash::create(__('Alias already in use'));
                }
                else {
                    $obj->resetAlias($_REQUEST['newname']);
                    $obj->template = $_REQUEST['template'];
                    Flash::create(__('Section edited'), 'confirmation');
                }
            }
        }
    }

    function medit() {
        global $Templates;
        __autoload('Form');
        return new Formsection(__('Edit section'),
            new Hidden('oldname', $_REQUEST['editSection']),
            new Input(__('Section name'), 'newname', $this->that->alias),
            Short::selectTemplate(($_REQUEST['template']?$_REQUEST['template']:@$this->that->template))
        );
    }
}
?>
