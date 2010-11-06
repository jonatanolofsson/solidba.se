<?php
/**
 *
 */
 
class Calendar {
    /**
     *
     */
    function getCalendars() {
        global $DB;
        return $DB->asList($DB->events->get(null, 'calendar', false, false, 'calendar'));
    }
    
    /**
     * @param string $calendar name of the wanted calendar
     * @param int $start Startdate in Unix_timestamp format
     * @param int $end Enddate in Unix_timestamp format
     * @param int $limit Number of event to return
     * @param bool $forced_all Use this if you want to return <i>all</i> events
     * @return array
     */
    function getEventIDs($calendar=false, $start=false, $end=false, $limit=false, $forced_all=false) {
        global $DB;
        if(!$start) $start = time();
        else $start = (int)$start;
        if(!$end) $end = strtotime('+1 year');
        else $end = (int)$end;
        
        if($forced_all){
            return $DB->asList($DB->query("SELECT `id` FROM `events` ORDER BY `start`".($limit?" LIMIT 0, ".$limit:"")));
        } else {
            return $DB->asList($DB->query("SELECT `id` FROM `events` WHERE ".($calendar?"`calendar` = '".Database::escape($calendar)."' AND ":"")."
                                (
                                    (`start` BETWEEN ".$start." AND ".($end-1).")
                                OR
                                    (`end` BETWEEN ".($start+1)." AND ".$end.")
                                OR
                                    (`start` <= ".$start." AND `end` >= ".$end.")
                                ) ORDER BY `start`".($limit?" LIMIT 0, ".$limit:""))
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
     * @return array Returns an array with sturcture [startdate] => (object)event
     */
    function getEvents($calendar=false, $start=false, $end=false, $limit=false, $forced_all=false) {
        $eventIDs = self::getEventIDs($calendar, $start, $end, $limit, $forced_all);
        $events = array();
        foreach($eventIDs as $id){
            $event = self::getEvent($id);
            $events[date('Y-m-d',$event->start)] = $event;
        }
        return $events;
    }
    
    /**
     *
     */
    function getEvent($id) {
        global $Controller;
        return $Controller->{$id}('Event');
    }
    
    /**
     *
     */
    function newEvent($title, $text, $img, $start, $end, $calendar, $place=false, $type=false) {
        global $Controller, $USER, $DB;
        $newEvent = $Controller->newObj('Event');
        $DB->events->insert(array('id' => $newEvent->ID));
        $newEvent->Name = $title;
        $newEvent->text = $text;
        $newEvent->Image = $img;
        $newEvent->start = $start;
        $newEvent->end = $end;
        $newEvent->calendar = $calendar;
        $newEvent->place = $place;
        $newEvent->type = ($type?$type:'global');
        $newEvent->registerUpdate();
        return $newEvent->ID;
    }
    
    /**
     *
     */
    function editEvent($eventID, $title=false, $text=false, $img=false, $start=false, $end=false, $calendar=false, $place=false, $type=false) {
        global $Controller;
        if(!$eventID) return false;
        $event = self::getEvent($eventID);
        if($title != false && $event->Name != $title) $event->Name = $title;
        if($text != false && $event->text != $text) $event->text = $text;
        if($img != false && $event->Image != $img) $event->Image = $img;
        if($start != false && $event->start != $start){
            $oldStart = $event->start;
            $event->start = $start;
            if($end == false){
                $duration = $event->end - $oldStart;
                $event->start = $start + $duration;
            }
        }
        if($end != false && $event->end != $end) $event->end = $end;
        if($calendar != false && $event->calendar != $calendar) $event->calendar = $calendar;
        if($place != false && $event->place != $place) $event->place = $place;
        if($type != false && $event->type != $type) $event->type = $type;
    } 
    
    /**
     *
     */
    function upcomingEvents($calendar=false, $limit=1) {
        $eventIDs = self::getEventIDs($calendar, false, false, $limit);
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

    
}