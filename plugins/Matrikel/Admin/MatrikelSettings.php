<?php

class MatrikelSettings extends Page {
    private $that = false;

    static public $edit_icon = 'small/cog';
    static public $edit_text = 'Settings';

    function canEdit($obj) {
        return is_a($obj, 'Matrikel');
    }

    function __construct($obj){
        parent::__construct($obj->ID);
        $this->that = $obj;
        $this->alias = 'matrikel';
        global $CONFIG;
        $CONFIG->matrikel->setType('interesting_groups', 'not_editable');
    }

    function run() {
        global $Templates, $CONFIG;
        if($this->saveChanges()) {
            Flash::queue(__('Your changes were saved'), 'confirmation');
            redirect($this->that);
        }
        $this->setContent('main',
            Form::quick(false, __('Save'),
                new Input(__('Page name'), 'name', $this->Name),
                new Select(__('Group'), 'interesting_groups', $this->that->groups(),
                $CONFIG->matrikel->interesting_groups, true)
            )
        );
        $Templates->render();
    }

    function saveChanges() {
        $_POST->setType('interesting_groups', 'numeric', true);
        $_POST->setType('name', 'string');

        $changes = false;
        if($_POST['name']) {
            $this->Name = $_POST['name'];
            $changes = true;
        }
        if($_POST['interesting_groups']) {
            global $CONFIG;
            $CONFIG->matrikel->interesting_groups = $_POST['interesting_groups'];
            $changes = true;
        }
        return $changes;
    }
}
?>
