<?php

class SubDir extends Page {
    private $that = false;

    static public $edit_icon = 'small/folder_add';
    static public $edit_text = 'Create subfolder';

    function canEdit($obj) {
        return is_a($obj, 'Folder');
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
            $this->newFolder()
        );

        global $Templates;
        $Templates->render();
    }

    function saveChanges() {
        $_REQUEST->setType('fname', 'string');
        if($_REQUEST['fname']
                && strposa($_REQUEST['fname'], array('..', '/', '\\')) === false
                && $this->that->mayI(EDIT)) {
            if(@mkdir($this->path.'/'.$_REQUEST['fname'], 0700)) {
                Flash::queue(__('The folder was created successfully'));
            } else {
                Flash::queue(__('There was a problem creating the directory. Check permissions and the name'));
            }
        }
    }


    /**
     * Display the page for creation of a new subfolder
     * @return void
     */
    function newFolder() {
        return Form::quick(false, __('Create'),
            new Formsection(__('New folder'),
                new Input(__('Folder name'), 'fname')
            )
        );
    }
}
?>
