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
    public $privilegeGroup = 'Administrationpages';
    static $VERSION = 1;
    static public function installable() {return __CLASS__;}
    //static public function uninstallable() {return __CLASS__;}
    static public function upgradable() {return __CLASS__;}

    function upgrade() {}

    function install() {
        global $Controller;
        $Controller->newObj('CalendarAdmin')->move('last', 'adminMenu');
        return self::$VERSION;
    }

    /**
     * Setup the page
     * @param integer $id The object's ID
     * @return void
     */
    function __construct($id=false){
        parent::__construct($id);
        $this->alias = 'calendarAdmin';
        $this->suggestName('Calendar','en');
        $this->suggestName('Kalender','sv');

        $this->icon = 'small/calendar';
        $this->deletable = false;
    }

    /**
     * In this function, most actions of the module are carried out and the page generation is started, distibuted and rendered.
     * @return void
     * @see solidbase/lib/Page#run()
     */
    function run(){
        global $Templates, $USER, $CONFIG, $Controller, $DB;
        if(!$this->may($USER, READ|EDIT)) { errorPage('401'); return false; }


        /**
         * User input types
         */
        $_REQUEST->setType('esave', 'any');
        $_REQUEST->setType('view', 'string');
        $_REQUEST->setType('edit', array('numeric', '#new#'));
        $_REQUEST->setType('del', 'numeric');
        $_REQUEST->setType('lang', 'string');
        $_POST->setType('estartd', 'string');
        $_POST->setType('estartt', 'string');
        $_POST->setType('eendd', 'string');
        $_POST->setType('eendt', 'string');
        $_POST->setType('etitle', 'string');
        $_POST->setType('etxt', 'any');
        $_POST->setType('eimg','string');
        $_POST->setType('ecal','string');
        $_POST->setType('eplace', 'string');
        
        /**
         * Delete event
         */	
        if($_REQUEST['del']) {
            if($Controller->{$_REQUEST['del']} && $Controller->{$_REQUEST['del']}->delete()) {
                Flash::create(__('Event removed'), 'confirmation');
            }
        }
        
        /**
         * Save event
         */
        do {
            $start = $end = 0;
            $event = false;
            if($_REQUEST['edit'] && $_REQUEST['esave']) {
                if(is_numeric($_REQUEST['edit'])) {
                    $event = $Controller->{$_REQUEST['edit']};
                    if(!$event || !is_a($event, 'Event') || !$event->mayI(EDIT)) {
                        Flash::create(__('Invalid event'), 'warning');
                        break;
                    }
                }
                if(($start = strtotime($_POST['estartd'] . ', ' . $_POST['estartt'])) === false) {
                //FIXME: Only startdate possibility
                    Flash::create(__('Invalid starttime'), 'warning');
                    break;
                }
                
                if(($end = strtotime($_POST['eendd'] . ', ' . $_POST['eendt'])) === false) {
                    $end = $start + 3600; //FIXME: Check if correct time
                }
                
                if(!$_POST['etitle']) {
                    Flash::create(__('Please enter a title'));
                    break;
                }
                if(!$_POST['etxt']) {
                    Flash::create(__('Please enter a text'));
                    break;
                }
                if(!$_POST['ecal']) {
                    Flash::create(__('Please enter a calendar name'));
                    break;
                }
                if($_REQUEST['edit'] === 'new') {
                    Calendar::newEvent($_POST['etitle'], $_POST['etxt'], $_POST['eimg'], $start, $end, $_POST['ecal'], $_POST['eplace']);
                    Flash::create(__('New event created'), 'confirmation');
                    $_REQUEST->clear('edit');
                    $_POST->clear('estartd', 'estartt', 'eendd', 'eendt', 'etitle', 'etxt', 'eimg', 'ecal', 'eplace');
                    break;
                } else if($event) {
                    Calendar::editEvent($event->ID, $_POST['etitle'], $_POST['etxt'], $_POST['eimg'], $start, $end, $_POST['ecal'], $_POST['eplace']);
                    Flash::create(__('Your data was saved'), 'confirmation');
                    $_REQUEST->clear('edit');
                    $_POST->clear('estartd', 'estartt', 'eendd', 'eendt', 'etitle', 'etxt', 'eimg', 'ecal', 'eplace');
                } else {
                    Flash::create(__('Unexpected error'), 'warning');
                    break;
                }
            }
        } while(false);
        

        /**
         * Here, the page request and permissions decide what should be shown to the user
         */
        if(is_numeric($_REQUEST['edit'])) {
            $this->editView($_REQUEST['edit'], $_REQUEST['lang']);
        } else {
            $this->header = __('Calendar');
            $this->setContent('main', $this->mainView());
        }

        $Templates->admin->render();
    }
    
    function editView($id, $language) {
        global $Controller, $DB;
        $obj = Calendar::getEvent($id);
        if(!$obj) return false;
        if(!$obj->mayI(EDIT)) errorPage(401);
        $this->setContent('header', __('Editing').' <i>"'.$obj->Name.'"</i>');
        $form = new Form('editE');
        $this->setContent('main',
            '<div class="nav"><a href="'.url(null, array('id')).'">'.icon('small/arrow_left').__('Back').'</a></div>'
            .$form->collection(
                new Set(
                    new Li(
                        new Datepicker(__('Starts'), 'estartd', ($_POST['estartd']?$_POST['estartd']:date('Y-m-d',$obj->start))),
                        new Timepickr(false, 'estartt', ($_POST['estartt']?$_POST['estartt']:date('h:i',$obj->start)))
                    ),
                    new Li(
                        new Datepicker(__('Ends'), 'eendd', ($_POST['eendd']?$_POST['eendd']:date('Y-m-d',$obj->end))),
                        new Timepickr(false, 'eendt', ($_POST['eendt']?$_POST['eendt']:date('H:i',$obj->end)))
                    )
                ),
                new Hidden('esave', 1),
                new Hidden('edit', $id),
                new Set(
                    new Hidden('lang', $language),
                    new Input(__('Title'), 'etitle', ($_POST['etitle']?$_POST['etitle']:$obj->Name)),
                    new Input(__('Place'), 'eplace', ($_POST['eplace']?$_POST['eplace']:$obj->place)),
                    new ImagePicker(__('Image'), 'eimg',($_POST['eimg']?$_POST['eimg']:$obj->Image)),
                    new htmlfield(__('Text'), 'etxt', ($_POST['etxt']?$_POST['etxt']:$obj->text)),
                    new Input(__('Calendar'), 'ecal', ($_POST['ecal']?$_POST['ecal']:$obj->calendar))
            )));
    }

    /**
     * @return string
     */
    private function mainView() {
        global $USER, $CONFIG, $DB, $Controller;
        
        $eventList = array();
        $eventIDs = Calendar::getEventIDs(false,false,false,false,true);
        $total = count($eventIDs);
        $perpage = 20;
        $pager = Pagination::getRange($perpage, $total);
        //FIXME: Move limitation to getEventIDs() Database limit
        $eventIDs = array_slice($eventIDs,$pager['range']['start'],$perpage);
        if($total>0) {
            foreach($eventIDs as $eventID) {
                $event = $Controller->{$eventID};
                $eventList[] = '<span class="fixed-width">'.$event->Name.'</span><div class="tools">'.icon('small/eye', __('View'), url(array('id' => $event->ID))).icon('small/pencil', __('Edit'), url(array('edit' => $event->ID), 'id')).icon('small/delete', __('Delete'), url(array('del' => $event->ID), 'id')).'</div>';
            }
            $eventList = listify($eventList);
            if($total > $perpage) {
                $eventList .= $pager['links'];
            }
        } else $eventList = __('No events');
        
        $form = new Form('newEvent');
        return new Tabber('events',	__('Event manager'),$eventList,
                                                __('New Event'),
                                $form->collection(new Set(
                                    new Li(
                                        new Datepicker(__('Starts'), 'estartd'),
                                        new Timepickr(false, 'estartt')
                                    ),
                                    new Li(
                                        new Datepicker(__('Ends'), 'eendd'),
                                        new Timepickr(false, 'eendt')
                                    )
                                ),
                                new Hidden('esave', 1),
                                new Hidden('edit', ($_REQUEST['edit'] ? $_REQUEST['edit'] : 'new')),
                                new Set(
                                    new Input(__('Title'), 'etitle'),
                                    new Input(__('Place'), 'eplace'),
                                    new ImagePicker(__('Image'), 'eimg'),
                                    new htmlfield(__('Text'), 'etxt'),
                                    new Input(__('Calendar'), 'ecal')
                                )
                            )
                        );
    }
}

?>
