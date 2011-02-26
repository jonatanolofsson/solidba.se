<?php
/**
 *
 */

class Calendar extends Page {
    public $editable = array(
        'NewEvent' => EDIT,
        'PageSettings' => EDIT,
        'PermissionEditor' => EDIT_PRIVILEGES,
        'MenuEditor' => EDIT,
        'Delete' => DELETE
    );

    function __construct($id) {
        parent::__construct($id);
        Base::registerAssociation('events', 'calendars');
    }

    /**
     * @param int $start Startdate in Unix_timestamp format
     * @param int $end Enddate in Unix_timestamp format
     * @param int $limit Number of event to return
     * @param bool $forced_all Use this if you want to return <i>all</i> events
     * @return array
     */
    function getEventIDs($start=false, $end=false, $limit=false, $forced_all=false) {
        global $DB;
        if(!$start) $start = time();
        else $start = (int)$start;
        if(!$end) $end = strtotime('+1 year');
        else $end = (int)$end;

        if($forced_all){
            return $DB->events->asList(array('calendar' => $this->ID), 'id', $limit, false, 'start');
        } else {
            return $DB->asList($DB->query("SELECT `id` FROM `events` WHERE `calendar` = '".Database::escape($this->ID)."' AND
                                (
                                    (`start` BETWEEN ".(int)$start." AND ".(int)($end-1).")
                                    OR
                                    (`end` BETWEEN ".(int)($start+1)." AND ".(int)$end.")
                                    OR
                                    (`start` <= ".(int)$start." AND `end` >= ".(int)$end.")
                                ) ORDER BY `start`".($limit?" LIMIT ".(int)$limit:""))
                            );
        }
    }

    /**
     * getEvents function.
     *
     * @param bool $calendar. (default: false)
     * @param bool $start. (default: false)
     * @param bool $end. (default: false)
     * @param bool $limit. (default: false)
     * @param bool $forced_all. (default: false)
     */
    function getEvents($start=false, $end=false, $limit=false, $forced_all=false) {
        global $Controller;
        return $Controller->get(self::getEventIDs($start, $end, $limit, $forced_all), ANYTHING, false, true, 'Event');
    }

    /**
     *
     */
    function upcomingEvents($limit=1) {
        $eventIDs = self::getEventIDs(false, false, $limit);
        $r = '';
        if(count($eventIDs) > 1){
            $r .= '<ul>';
            foreach($eventIDs as $id){
            $event = self::getEvent($id);
                $r .= '<li><h2>'.$event->Name.'</h2><p><a href="'.url(array('id' => $event->ID)).'">'.__('Read more').'</a></p></li>';
            }
            $r .= '</ul>';

        } else if(count($eventIDs) == 1) {
            foreach($eventIDs as $id){
                $event = self::getEvent($id);
                $r .= '<h2>'.$event->Name.'</h2>'.$event->getImage(170).'<p>'.$event->text.'</p>';
            }
        } else $r .= '<p>No upcoming events</p>';
        return $r;
    }

    function run() {
        global $Templates;
        $_REQUEST->setType('when', 'numeric');
        $_REQUEST->setType('view', 'string');

        switch($_REQUEST['view']) {
            case 'month':
            case 'week':
            case 'list_month':
            case 'list_period':
            default:
                $r = self::viewMonth($_REQUEST['when']);
        }
        $this->setContent('main', $r);
        $Templates->render();
    }

    function viewMonth($when = false) {
        if(!$when) $when = time();
        return self::viewPeriod(mktime(0,0,0,date('n', $when),1), mktime(0,0,0,date('n', $when)+1,-1))
            .'<span class="navigation">'
                .icon('small/arrow_left', 'Previous month', url(array('when' => mktime(0,0,0,date('n', $when)-1,1))))
                .icon('small/arrow_right', 'Next month', url(array('when' => mktime(0,0,0,date('n', $when)+1,1))))
            .'</span>';
    }

    function viewPeriod($start, $end) {
        return listify(self::getEvents($start, $end));
    }
}
