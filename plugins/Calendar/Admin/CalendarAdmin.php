 <?php

/**
 * @author Kalle Karlsson [kakar]
 * @version 1.0
 * @package Content
 */
/**
 * Provides the default solidba.se interface for administrating calendar events
 * @package Content
 */
class CalendarAdmin extends Page {
    public $editable = array(
        //'CalendarEditor' => EDIT,
        'PageSettings' => EDIT,
        'NewEvent' => EDIT,
        'PermissionEditor' => EDIT_PRIVILEGES,
        'MenuEditor' => EDIT,
        'Delete' => DELETE
    );

    /**
     * Setup the page
     * @param integer $id The object's ID
     * @return void
     */
    function __construct($id=false){
        parent::__construct($id);
        $this->alias = 'calendarAdmin';
        $this->suggestName('Calendar administration','en');
        $this->suggestName('Kalenderadministration','sv');

        $this->icon = 'small/calendar';
    }

    /**
     * In this function, most actions of the module are carried out and the page generation is started, distibuted and rendered.
     * @return void
     * @see solidbase/lib/Page#run()
     */
    function run(){
        global $Templates;
        $this->saveChanges();
        $this->setContent('main', $this->listCalendars()
        .'<hr /><h2>'.__('New calendar').'</h2>'.$this->calendarForm());
        $Templates->admin->render();
    }

    function saveChanges() {
        $_POST->setType('calendarname', 'string');
        if(!$_POST['calendarname']) return false;

        global $Controller;
        $new = $Controller->newObj('Calendar');
        $new->Name = $_POST['calendarname'];

        Flash::queue(__('The new calendar'). ' `' . $_POST['calendarname'] . '`' . __('was created'), 'confirmation');

        return true;
    }

    function listCalendars() {
        global $Controller;
        return Short::toolList($Controller->getClass('Calendar'));
    }

    function calendarForm() {
        return Form::quick(null, null,
            new Input(__('Calendar name'), 'calendarname')
        );
    }
}

?>
