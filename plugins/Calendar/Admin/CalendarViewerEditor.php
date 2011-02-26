<?php

/**
 * @author Jonatan Olofsson [joolo]
 * @version 1.0
 * @license http://creativecommons.org/licenses/by-nc/3.0/ Creative Commons Attribution-Noncommercial 3.0 Unported License
 * @package Content
 */

/**
 * The CalendarViewerEditor provides an interface for editing CalendarViewers
 * @package Content
 */
class CalendarViewerEditor extends Page{
    static public $edit_icon = 'small/calendar';
    static public $edit_text = 'Edit viewer';

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
        $this->setContent('header', __('Edit calendar-viewer settings'));
        $this->setContent('main',
            Form::quick(false, null,
                $this->editTab()
            )
        );
        $Templates->admin->render();
    }

    function editTab() {
        global $Controller;
        return array(
            new Select(__('Calendars'), 'calendars', $Controller->getClass('Calendar'), $this->that->calendars, true),
            new Input(__('Items per page'), 'itemsPerPage', $this->that->itemsPerPage)
        );
    }

    function saveChanges() {
        $_POST->setType('calendars', 'string', true);
        $_POST->setType('itemsPerPage', 'numeric');

        if(!$_POST['calendars']) return false;

        $this->that->calendars = $_POST['calendars'];
        if($_POST['itemsPerPage']) $this->that->itemsPerPage = $_POST['itemsPerPage'];
        Flash::queue(__('Your changes were saved'), 'confirmation');
        return true;
    }


    function canEdit($obj)
    {
        return is_a($obj, 'CalendarViewer');
    }
}

?>
