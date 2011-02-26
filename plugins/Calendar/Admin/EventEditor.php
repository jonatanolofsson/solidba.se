<?php

class EventEditor extends Page {
    private $that = false;

    static public $edit_icon = 'small/calendar_edit';
    static public $edit_text = 'Edit event';

    function canEdit($obj) {
        return is_a($obj, 'Event');
    }

    function __construct($obj){
        parent::__construct($obj->ID);
        $this->that = $obj;
    }

    function run() {
        global $Templates;
        if($this->saveChanges());// redirect($_REQUEST['edit']);
        $this->setContent('main',
            Form::quick(false, __('Save'),
                $this->edit()
            )
        );
        $Templates->render();
    }

    function saveChanges() {
        $_POST->setType('etitle', 'string');
        $_POST->setType('eimg', 'string');
        $_POST->setType('etxt', 'string');
        $_POST->setType('active', 'string');
        $_POST->setType('regstop', 'string');
        $_POST->setType('attendance', 'string');
        $_POST->setType('reminder', 'bool');
        $_POST->setType('contact', 'numeric');
        $_POST->setType('attending_groups', 'numeric', true);
        $_POST->setType('visibility', 'numeric', true);
        if($_POST['etitle']) {
            $this->that->Name = $_POST['etitle'];
            $this->that->start  = Short::parseDateAndTime('estart', false);
            $this->that->end    = Short::parseDateAndTime('eend', false);
            $this->that->Image  = $_POST['eimg'];
            $this->that->registration_ends = strtotime($_POST['regstop']);
            $this->that->setActive(Short::parseDateAndTime('active'), $this->that->end);
            $this->that->contact = $_POST['contact'];
            $this->that->attendance = isset($_POST['attendance']);
            $this->that->attending_groups = $_POST['attending_groups'];
            $this->that->reminder = $_REQUEST['reminder'];
            $this->updateVisibility($_POST['visibility']);

            Flash::queue(__('Event updated'), 'confirmation');
        }
    }

    function updateVisibility($to) {
        if(!is_array($to)) $to = array();
        if($this->that->mayI(EDIT_PRIVILEGES)) {
            global $DB, $Controller;
            $b = $Controller->get($DB->privileges->asList(array('id' => $this->that->ID), 'beneficiary'));
            foreach($b as $id => $obj) {
                if(is_a($obj, 'Group') && $this->that->may($obj, READ)) {
                    if(!in_array($id, $to)) $del[] = $id;
                    else arrayRemove($to, $id);
                }
            }
            foreach($to as $group) {
                $this->that->allow($group, READ);
            }
            $Controller->forceReload($this->that);
        }
    }

    function getGroups($objs = false) {
        global $DB, $Controller;
        $r = array();
        $b = $Controller->get($DB->privileges->asList(array('id' => $this->that->ID), 'beneficiary'));
        foreach($b as $id => $obj) {
            if(is_a($obj, 'Group') && $this->that->may($obj, READ)) {
                $r[] = ($objs?$obj:$id);
            }
        }
        return $r;
    }


    function edit() {
        global $Controller;
        $groups = $Controller->getClass('Group');
        $startshow = $this->that->getActive();
        $startshow = @$startshow['start'];
        return array(
            new Formsection(__('Event details'),
                new Input(__('Title'), 'etitle', ($_POST['etitle']?$_POST['etitle']:$this->that->Name)),
                new Select(__('Calendar'), 'calendar', $Controller->getClass('Calendar'), $this->that->calendar),
                Short::datetime(__('Starts'), 'estart', $this->that->start),
                Short::datetime(__('Ends'), 'eend', $this->that->end)
            ),
            new Formsection(__('Description'),
                new ImagePicker(__('Image'), 'eimg',($_POST['eimg']?$_POST['eimg']:$this->that->Image)),
                new HTMLField(__('Text'), 'etxt', ($_POST['etxt']?$_POST['etxt']:$this->that->text))
            ),
            new Formsection(__('Visibility'),
                Short::datetime(__('Show from'), 'active', $startshow),
                new Select(__('Visibility'), 'visibility', $groups, $this->getGroups(), true)
            ),
            new Formsection(__('Attendance'),
                new Checkbox(__('Request registration'), 'attendance', $this->that->attendance),
                new Datepicker(__('Soft deadline'), 'sdeadline', $this->that->soft_deadline, false, __('Blank field disables')),
                new Datepicker(__('Hard deadline'), 'hdeadline', $this->that->hard_deadline, false, __('Blank field disables')),
                new Select(__('Contact group'), 'contact', $groups, $_POST['contact'], false, true),
                new TextArea(__('Attendance information'), 'attinfo', $this->that->attendance_info),
                new Select(__('Expected attendance'), 'attending_groups', $groups, $this->that->attending_groups, true),
                new Checkbox(__('Reminder'), 'reminder', $this->that->reminder)
            )
        );
    }
}
?>
