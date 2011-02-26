<?php

class Delete extends Page {
    private $that = false;

    static public $edit_icon = 'small/delete';
    static public $edit_text = 'Delete';

    function canEdit($obj) {
        return is_a($obj, 'Base');
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
        if($this->doDelete()) redirect();

        $this->setContent('main', $this->confirmationForm());
        global $Templates;
        $Templates->render();
    }

    function confirmationForm() {
        return Form::quick(null, __('Confirm'),
            new Hidden('confirm', '1')
        );
    }

    function doDelete() {
        $_POST->setType('confirm', 'any');
        if(!$_POST['confirm']) return false;
        if($this->that->mayI(DELETE)
            && $this->that->delete())
        {
            Flash::queue(__('The object was deleted'), 'confirmation');
        }
        else
        {
            Flash::queue(__('The object could not be deleted'), 'warning');
        }
        return true;
    }
}
?>
