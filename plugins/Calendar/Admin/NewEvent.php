<?php

/**
 * @author Jonatan Olofsson [joolo]
 * @version 1.0
 * @package Calendar
 */
/**
 * Provides the default solidba.se interface for administrating calendar events
 * @package Content
 */
class NewEvent extends Page {
    static public $edit_icon = 'small/calendar_add';
    static public $edit_text = 'New event';
    private $that;

    function canEdit($obj) {
        return is_a($obj, 'Calendar') || is_a($obj, 'CalendarViewer');
    }

    function __construct($obj) {
        parent::__construct($obj->ID);
        $this->that = $obj;
    }

    /**
     * In this function, most actions of the module are carried out and the page generation is started, distibuted and rendered.
     * @return void
     * @see solidbase/lib/Page#run()
     */
    function run(){
        if($this->that->mayI(EDIT)) {
            global $Controller;
            $new = $Controller->newObj('Event');
            if(is_a($this->that, 'Calendar')) {
                $new->calendar = $this->that->ID;
            } else {
                $cals = $this->that->calendars;
                $new->calendar = $cals[0];
            }
            redirect(array('edit' => $new->ID, 'with' => 'EventEditor'));
        }
        redirect();
    }
}

?>
