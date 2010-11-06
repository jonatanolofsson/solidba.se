<?php
class Booking_Object extends Page {
    private $bookings = array();
    private $loadedTimes = array();
    private $ParentBookObject = false;
    private $subBookables = array();
    public $privilegeGroup = 'Booking objects';
    

    
    /**
     * (non-PHPdoc)
     * @see lib/Page#run()
     */
    function run() {
        Settings::registerSetting('booking_timelimit', 'text', false, 3);
        Settings::registerSetting('booking_advance_limit', 'text', false, 3);
        Settings::registerSetting('booking_confirmation', 'select', array('Must be confirmed', 'Self-confirmed'), 3);
        global $Templates, $Controller, $DB, $USER;
        
        $_REQUEST->setType('viewPeriod', '#[0-9]{4}-[0-9]{2}-[0-9]{2}\|[0-9]{4}-[0-9]{2}-[0-9]{2}#');
        $_REQUEST->setType('viewDate', '#[0-9]{4}-[0-9]{2}-[0-9]{2}#');
        $_REQUEST->setType('when', '#[0-9]{4}-[0-9]{2}#');
        $_REQUEST->setType('viewDay', 'numeric');
        $_REQUEST->setType('viewBooking', 'string');
        $_REQUEST->setType('delbooking', 'string');
        $_REQUEST->setType('rembooking', 'string');
        $_REQUEST->setType('view', 'string');
        $_REQUEST->setType('startTime', 'numeric');
        $_REQUEST->setType('confirm', 'string');
        $_REQUEST->setType('js', 'any');
        
        $this->link(false, array('viewBooking' => 'adsfafasg'));
        $this->setContent('header', $this->Name);
        $popup = false;
        $c = '';
        
        if($_REQUEST['delbooking'] || $_REQUEST['rembooking']) {
            $booking = $DB->booking_bookings->getRow(array('b_id' => ($_REQUEST['delbooking']?$_REQUEST['delbooking']:$_REQUEST['rembooking'])));
            if($this->mayI(EDIT) || $booking['booked_by'] == $USER->ID || ($booking['booked_for'] && $Controller->{$booking['booked_for']}('Group') && $Controller->{$booking['booked_for']}->isMember($USER))) {
                $bstart = $booking['starttime'];
    
                $popup = true;
                if($_REQUEST['js']) {
                    $nav = '<div class="nav"><a href="javascript:window.close()">'.icon('small/cancel').__('Close').'</a></div>';
                    Head::add("window.opener.location = window.opener.location; setTimeout('window.close()', 8000);", 'js-raw');
                    Head::add("window.onblur=function(){window.close();}", 'js-raw');
                } else {
                    $nav = '<div class="nav"><a href="'.url(array('viewDate' => date('Y-m-d', $booking['starttime'])), array('id')).'">'.icon('small/arrow_up').__('Back').'</a></div>';
                }
                
                if($bstart<time()) {
                    $c = $nav.'<h3>'.__("Startingtime has passed. Unable to proceed.").'</h3>';
                } else {
                    if($_REQUEST['delbooking']) {
                        $c = $nav.'<h3>'.__('Booking deleted').'</h3>';
                        $DB->booking_bookings->delete(array('b_id' => $_REQUEST['delbooking']), false);
                    } else {
                        $c = $nav.'<h3>'.__('Object removed from booking').'</h3>';
                        $DB->booking_bookings->delete(array('b_id' => $_REQUEST['rembooking'], 'id' => $this->ID));
                    }
                }
                unset($booking);
            }
        }
        elseif($_REQUEST['confirm']) {
            if($this->confirm($_REQUEST['confirm'])) {
                Flash::create(__('Confirmed booking'), 'confirmation');
            } else {
                Flash::create(__('Could not confirm booking'), 'warning');
            }
        }
        if(!$c) {
            if($_REQUEST['view']) {
                if($_REQUEST['view'] == 'book') {
                    $c = $this->bookingPage();
                    $popup = true;
                }
            }
            elseif($_REQUEST['viewBooking']) {
                $c = $this->viewBooking($_REQUEST['viewBooking']);
                $popup = true;
            }
            elseif($_REQUEST['viewDate']) {
                $c = $this->viewDate($_REQUEST['viewDate']);
            }
            elseif($_REQUEST['viewPeriod']) {
                $t = explode('|', $_REQUEST['viewPeriod']);
                $c = $this->viewPeriod($t[0], $t[1]);
            } else {
                $c = $this->calendarView().'<div class="bcontent">'.@$this->content['main'].'</div>';
            }
        }
    
        $this->setContent('main', $c);
        if($_REQUEST['js'] && $popup) {
            $Templates->popup->render();
        } else {
            $Templates->render();
        }
    }
    
    /**
     * The calendar view
     * @return string Page Content
     */
    function calendarView() {
        return '<div class="nav"><a href="'.url(array('id' => 'book')).'">'.icon('small/arrow_left').__('List all objects').'</a></div>'
        .'<h3>'.__('Objects').'</h3>'
        .$this->listView()
        .'<h2>'.__('Book').'</h2>'
        .$this->displayCalendar($_REQUEST['when']);
    }
    
    /**
     * View the interface for booking the selected items
     * @return string Page content
     */
    function bookingPage() {
        global $DB, $USER, $CONFIG, $Controller;
        
        $_REQUEST->setType('startTime', 'numeric');
        $_REQUEST->setType('duration', 'numeric');
        $_REQUEST->setType('d', 'string');
        $_REQUEST->setType('refresh', 'any');
        $_POST->setType('bookObjs', 'numeric', true);
        $_POST->setType('who', array('numeric', '#^$#'));
        $_POST->setType('comment', 'string');
        
        $TimeStep			= 30;
                
        if($_REQUEST['startTime'] && $_REQUEST['startTime'] >= time()) $startTime = $_REQUEST['startTime'];
        else $startTime = mktime(date('H'),(date('i') + ($TimeStep - (date('i') % $TimeStep))),0);
        if($_REQUEST['duration'] && $_REQUEST['duration']>0) $duration = $_REQUEST['duration'];
        else $duration = 60*$TimeStep;
        if($_POST['bookObjs'] && !$_REQUEST['refresh']) {
            if($this->book($_POST['bookObjs'], $startTime, $duration, (int)$_POST['who'], $_POST['comment'])) {
                if($_REQUEST['js']) {
                    $nav = '<div class="nav"><a href="javascript:window.close();">'.icon('small/cancel').__('Close').'</a></div>';
                    Head::add("window.opener.location = window.opener.location; setTimeout('window.close()', 8000);", 'js-raw');
                    Head::add("window.onblur=function(){window.close();}", 'js-raw');
                } else {
                    $nav = '<div class="nav"><a href="'.url(null, array('id', 'viewDate')).'">'.icon('small/arrow_up').__('Back').'</a></div>';
                }
                return $nav.'<h1>'.__('Booking confirmed.').'</h1>';
            } else {
                $_REQUEST['refresh'] = 'true';
            }
        }
        
        $endTime = $startTime + $duration;
    
        if($_REQUEST['js']) {
            $nav = '<div class="nav"><a href="javascript:window.close();">'.icon('small/cancel').__('Close').'</a></div>';
        } else {
            $nav = '<div class="nav"><a href="'.url(null, array('id', 'viewDate')).'">'.icon('small/arrow_up').__('Back').'</a></div>';
        }
        
        $this->loadBookingObject();
        $result = '';
        $result .= $nav;
        $form = new Form('booking', url(null, 'viewDate'), __('Book'));
        $result .= $form->open();
        $result .= '<div class="cal_bookable"><ul>';
    
        $Objects = (array)$this->subBookables;
        array_unshift($Objects, $this);
        $no_free = true;
        foreach($Objects as $Obj) {
            if($Obj->isFree($startTime, $duration)) {
                $result .= '<li'.($Obj === $this ? ' class="cal_parent"':'').'>'
                    .new Minicheck($Obj, 'bookObjs[]', ($_REQUEST['bookObjs']?(in_array($Obj->ID, (array)$_POST['bookObjs'])):true), false, $Obj->ID, ($Obj === $this ? 'cal_parent': false))
                .'</li>';
                $no_free = false;
            }
        }
        $result .= '</ul></div>';
        
        $time_selectValues = array();
        $limit = false;
        $this->loadDay($endTime);
        foreach($this->bookings as $booking) {
            if($booking['starttime']>$startTime) {
                if(!$limit || $limit > $booking['starttime']) {
                    $limit = $booking['starttime'];
                }
            }
        }
        if(!$limit) $limit = mktime(0,0,0,date('m', $endTime),date('d', $endTime)+1,date('Y', $endTime));
        
        $time_selectValues = array();
        if(date('Ymd', $startTime)!=date('Ymd', $endTime)) {
            $t = mktime(0,0,0,date('m', $endTime),date('d', $endTime),date('Y', $endTime));
        } else {
            $t = $startTime+60*$TimeStep;
        }
        while($t<=$limit) {
            $time_selectValues[($t-$startTime)] = date('H:i', $t);
            $t += 60*$TimeStep;
        }
        
        if(empty($time_selectValues) || $no_free) {
            $current = $this->getOnGoingBooking(($no_free?$startTime:$t));
            if($_REQUEST['d']=='l') $_REQUEST['startTime'] = $current['starttime'] - $duration;
            else $_REQUEST['startTime'] = $current['starttime'] + $current['duration'];
            return $this->bookingPage();
        }
    
        $personal_timelimit = $USER->settings['booking_timelimit'];
        if($personal_timelimit == '') 
            $personal_timelimit = $Controller->{(string)MEMBER_GROUP}(OVERRIDE)->settings['booking_timelimit'];
        $canBookSelf = (bool)$personal_timelimit;
        $bookingGroups = array();
        foreach($USER->groups as $g) {
            if($g->ID != MEMBER_GROUP && $g->settings['booking_timelimit']) {
                $bookingGroups[] = $g;
            }
            elseif($g->ID == MEMBER_GROUP) {
                if(!$canBookSelf) $canBookSelf = (bool) $g->settings['booking_timelimit'];
            }
        }
        
        $result .= new Set(
            new Hidden('startTime', $startTime),
            new Hidden('endTime', $endTime),
            new Select(__('Who'), 'who', $bookingGroups, @$_POST['who'], false, ($canBookSelf?__('Yourself'):false)),
            strftime('%a, %e %b -%y', $startTime)
                .', <a href="'.url(array('startTime' => mktime(date('H', $startTime), date('i', $startTime) - $TimeStep, 0, date('m', $startTime), date('d', $startTime), date('Y', $startTime)), 'duration' => $duration, 'd' => 'l'), array('id', 'view', 'js')).'">&laquo;</a> '
                .date('H:i', $startTime)
                .' <a href="'.url(array('startTime' => mktime(date('H', $startTime), date('i', $startTime) + $TimeStep, 0, date('m', $startTime), date('d', $startTime), date('Y', $startTime)), 'duration' => $duration), array('id', 'view', 'js')).'">&raquo;</a>',
            (date('Ymd',$endTime)>date('Ymd', $startTime)?'<a href="'.url(array('startTime' => $startTime, 'duration' => $duration - 86400), array('id', 'view', 'js')).'">':'').'&laquo;'.(date('Ymd',$endTime)>date('Ymd', $startTime)?'</a> ':' ')
                .strftime('%a, %e %b -%y', $endTime)
                .' <a href="'.url(array('startTime' => $startTime, 'duration' => $duration + 86400), array('id', 'view', 'js')).'">&raquo;</a>'
                .(new select(false, 'duration', $time_selectValues, $duration)).' '
                .(new submit(__('Check availability'), 'refresh')),
            new textarea(__('Comment'), 'comment', '', 'mandatory')
        );
        
        $result .= $form->close();
        return $result;
    }
    
    /**
     * Confirm a booking
     * @param $booking_id Which booking to confirm 
     * @return bool Success
     */
    function confirm($booking_id) {
        global $DB, $USER, $Controller;
        if(!$this->mayI(EDIT) || !$booking_id) return false;
        $DB->booking_bookings->update(array('cleared_by' => $USER->ID), array('b_id' => $booking_id), false, false);
        $owner = $DB->booking_bookings->getCell(array('b_id' => $booking_id), 'booked_by');
        
        $_REQUEST->setType('viewBooking', 'string');
        new Notification(
            __('Booking confirmed'),
            __('Your booking has been confirmed').': '.$this->link(false, array('viewBooking' => $booking_id)),
            $owner
        );
        return true;
    }
    
    /**
     * Book the item and/or it's subobjects
     * @param $what Which objects to book
     * @param $from From which time
     * @param $duration How long should they be booked
     * @param $who In what name was the booking made (note: this is not equivalent to the user that did the booking)
     * @param $comment Comment for the booking
     * @return bool Success
     */
    function book($what, $from, $duration, $who, $comment) {
        global $Controller, $DB, $USER;
        $booking_id 	= uniqid();
        $book			= array();
        
        $clearance = $this->getClearance($from, $duration, $who);
        if($clearance === false) {
            return false;
        }
        foreach($what as $obj_id) {
            if(!is_numeric($obj_id)) continue;
            if($obj = $Controller->$obj_id('Booking_Object')) {
                if(!$obj->isFree($from, $duration)) {
                    Flash::create(__('Time is not free'), 'warning');
                    return false;
                }
                $book[] = $obj_id;
            }
        }
        if(!$book) return true;
        $DB->booking_bookings->insertMultiple(array('id' => $book, 'b_id' => $booking_id, 'starttime' => $from, 'duration' => $duration, 'booked_by' => $USER->ID, 'booked_for' => $who, 'cleared_by' => $clearance, 'comment' => $comment));
        foreach($book as $b) {
            $o = $Controller->$b;
            $o->cascadeBooking($booking_id, $from, $duration, $who, $comment);
        }
        $_REQUEST->setType('viewBooking', 'string');
        if(!$clearance) {
            new Notification(
                __('Booking registered'),
                __('A new booking has been registered').': '.$this->link(false, array('viewBooking' => $booking_id)),
                //FIXME: Notifier recipient; 1019 = Bilansvarig
                $Controller->{"1019"}(OVERRIDE)
            );
        }
        return true;
    }
    
    /**
     * Returns the ID which grants clearance to perform a booking
     * @param $from Starting time of booking
     * @param $duration How long the booking lasts
     * @param $who Who should be debited
     * @return int ID of the user, or 0 if not yet cleared and false if not allowed to book
     */
    function getClearance($from, $duration, $who=false) {
        global $USER, $Controller, $DB;
        if(!$who) $who = $USER;
        if(!is_object($who)) $who = $Controller->{(string)$who};
        if(!is_a($who, 'User') && !is_a($who, 'Group')) {
            return false;
        }
        
        if(is_a($who, 'User')) {
            $booking_timelimit = $who->settings['booking_timelimit'];
            if($booking_timelimit == '') 
                $booking_timelimit = $Controller->{(string)MEMBER_GROUP}(OVERRIDE)->settings['booking_timelimit']; 
        }
        
        if($booking_timelimit == 0) {
            Flash::create(__('Booking not allowed'), 'warning');
            return false;
        }
        
        /* Is the user allowed to book this far in the future? */
        if($who->settings['booking_advance_limit']) {
            if($from + $duration > time() + $who->settings['booking_advance_limit']*86400) {
                Flash::create(__('Booking to far'), 'warning');
                return false;
            }
        }
        
        /* Has the user got any time left to use? */
        if($booking_timelimit > 0) {
            $total_booked_time = $DB->getCell("SELECT SUM( `duration` ) as `total_time` FROM `booking_bookings` WHERE (`booked_by` = '".Database::escape($who->ID)."' OR `booked_for` == '".Database::escape($who->ID)."') AND `starttime`+`duration` > UNIX_TIMESTAMP() GROUP BY `b_id`", 'total_time');
            if($total_booked_time + $duration > $booking_timelimit*3600) {
                Flash::create(__('Not enough time left'), 'warning');
                return false;
            }
        }
        
        if($this->mayI(EDIT) || $who->settings['booking_confirmation']) {
            return $USER->ID;
        } else return 0;
    }
    
    /**
     * Continue the booking process for all children
     * @param $booking_id Original ID of the booking
     * @param $from Starting time
     * @param $duration Duration of the booking
     * @param $who In which name was the booking made
     * @param $comment Comment for the booking
     * @return bool Success
     */
    function cascadeBooking($booking_id, $from, $duration, $who, $comment) {
        global $DB, $USER;
        $this->loadBookingObject();
        $what = $this->subBookables;
        if(!$what) return true;
        $book			= array();
        
        foreach($what as $obj_id) {
            if(!is_numeric($obj_id)) continue;
            if($obj = $Controller->$obj_id('Booking_Object')) {
                if(!$obj->isFree($from, $duration)) {
                    return false;
                }
                $book[] = $obj_id;
            }
        }
        if(!$book) return true;
        $DB->booking_bookings->insertMultiple(array('id' => $book, 'b_id' => $booking_id, 'starttime' => $from, 'duration' => $duration, 'booked_by' => $USER->ID, 'booked_for' => $who));
        foreach($book as $b) {
            $o = $Controller->$b;
            $o->cascadeBooking($booking_id, $from, $duration, $who, $comment);
        }
        return true;
    }
    
    
    /**
     * Loads the parent object and all subobjects
     * @param $force Force reload
     * @return void
     */
    function loadBookingObject($force = false) {
        if($this->bLoaded && !$force) return;
        $this->bLoaded = true;
        global $DB, $Controller;
        $this->ParentBookObject = $Controller->retrieve($DB->booking_items->getCell(array('id' => $this->ID), 'parent'), OVERRIDE);
        $this->subBookables 	= $Controller->get($DB->booking_items->asList(array('parent' => $this->ID), 'id'));
    }
    private $bLoaded = false;
    
    
    
    /**
     * Load all bookings relevant to the object and it's children 
     * @param $StartingTime Starting time
     * @param $StopTime Stop time
     * @param $force Bypass cache
     * @return void
     */
    function loadBookings($StartingTime, $StopTime, $force = false) {
        if(!is_numeric($StartingTime) || !is_numeric($StopTime)) return false;
        global $DB;
        if(in_array($StartingTime.'|'.$StopTime, $this->loadedTimes) && !$force) return;
        $this->loadedTimes[] = $StartingTime.'|'.$StopTime;
        $this->bookings = array_merge($this->bookings, 
$DB->asArray("SELECT * FROM `booking_bookings` WHERE `id`='".$this->ID."' AND
 (
   (`starttime` BETWEEN ".$StartingTime." AND ".($StopTime-1).")
   OR
   ((`starttime` + `duration`) BETWEEN ".($StartingTime+1)." AND ".$StopTime.")
   OR
   (`starttime` <= ".$StartingTime." AND (`starttime` + `duration`) >= ".$StopTime.")
 )"));
        $booking_ids = array();
        $bookings = $this->bookings;
        foreach($bookings as $key => $booking) {
            if(in_array($booking['b_id'], $booking_ids)) unset($this->bookings[$key]);
            else $booking_ids[] = $booking['b_id'];
        }
    }
    
    /**
     * Load all events a given day
     * @param $from Timestamp from the day to load
     * @param $to If you want to load multiple days, this day is a timestamp from the last day to load
     * @return void
     */
    function loadDay($from, $to=false) {
        $to = max($to, $from);
        $this->loadBookings(mktime(0,0,0, date('m', $from), date('d', $from), date('Y', $from)), mktime(0,0,0, date('m', $to), date('d', $to)+1, date('Y', $to)));
    }
    
    /**
     * Returns wether the object is free the given time or not
     * @param $from Starttime
     * @param $to Endtime
     * @return bool Returns true if the object is free, false otherwise
     */
    function isFree($from, $duration=0) {
        $to = $from + $duration;
        $this->loadBookingObject();
        $this->loadDay($from);
        foreach($this->bookings as $booking) {
            $start 	= $booking['starttime'];
            $end 	= $booking['starttime'] + $booking['duration'];
            if(		($from >= $start AND $from <  $end)
                OR	($to   >  $start AND $to   <= $end)
                OR	($from <= $start AND $to   >= $end)
            ) {
                return false;
            }
        }
        foreach($this->subBookables as $sub) {
            if(!$sub->isFree($from, $duration)) {
                return false;
            }
        }
        return true;
    }
    
    
    /**
     * Returns true if the given booking is one of the object's
     * @param $booking Booking
     * @return bool
     */
    function bookingExists($booking) {
        $this->loadBookingObject();
        $this->loadBookings(mktime(0,0,0, date('m', $booking['starttime']), date('d', $booking['starttime']), date('Y', $booking['starttime'])), mktime(0,0,0, date('m', $booking['starttime']+$booking['duration']), date('d', $booking['starttime']+$booking['duration'])+1, date('Y', $booking['starttime']+$booking['duration'])));
        if(in_array($booking['b_id'], arrayExtract($this->bookings, 'b_id'))) return true;
        else return false;
    }
    
    /**
     * Get the booking going on at a given time
     * @param $time Timestamp
     * @return booking The ongoing booking
     */
    function getOnGoingBooking($time) {
        $this->loadBookingObject();
        $this->loadDay($time);
        foreach($this->bookings as $booking) {
            if($time >= $booking['starttime'] AND $time < ($booking['starttime']+$booking['duration'])) {
                return $booking;
            }
        }
        return false;
    }
    
    function parentBookID() {
        $this->loadBookingObject();
        if($this->ParentBookObject) {
            return $this->ParentBookObject->ID;
        } else return 0;
    }
    
     /**
      * Display a given booking
      * @param $booking Booking id
      * @return string Page content
      */
    function viewBooking($booking) {
        global $DB, $Controller, $USER;
        $res = $DB->booking_bookings->get(array('b_id' => $booking));
        $booking = false;
        $booked_items = array();
        $nr = 0;
        while(false !== ($r = Database::fetchAssoc($res))) {
            $booking = $r;
            $nr++;
            if($Controller->{$r['id']}) $booked_items[] = array('obj' => $Controller->{$r['id']}, 'id' => $r['id'], 'parent' => $Controller->{$r['id']}->parentBookID());
        }
        if(!$booking) return __('An error occured. Cannot find booking');
        $nav = '<div class="nav">';
        
        $nav .= ($_REQUEST['js']
                    ?'<a href="javascript:window.close();">'.icon('small/cancel').__('Close').'</a>'
                    :'<a href="'.url(null, array('viewDate', 'id')).'">'.icon('small/arrow_left').__('Back').'</a>'
                )
            .($this->mayI(EDIT) || $booking['booked_by'] == $USER->ID || ($booking['booked_for'] && $Controller->{$booking['booked_for']}('Group') && $Controller->{$booking['booked_for']}->isMember($USER))
                ?'<a href="'.url(array('delbooking' => $booking['b_id']), array('viewDate', 'id', 'js')).'">'.icon('small/delete').__('Delete booking').'</a>'
                    .($nr>1
                        ?'<a href="'.url(array('rembooking' => $booking['b_id']), array('viewDate', 'id', 'js')).'">'.icon('small/cross').__('Remove from booking').'</a>'
                        :'')
                :'')
            .(!$booking['cleared_by'] && $this->mayI(EDIT)
                ?'<a href="'.url(array('confirm' => $booking['b_id']), true).'">'.icon('small/tick').__('Confirm').'</a>'
                :'');
        $nav .= '</div>';
        return $nav.new Set(
            ($booked_items 
                ? new FormText(__('What'), listify(inflate($booked_items), false, true, 'obj', 'children'))
                : null
            ),
            ($Controller->{$booking['booked_by']}
                ?new FormText(__('Booked by'), $Controller->{$booking['booked_by']})
                :null
            ),
            ($booking['booked_for'] && $Controller->{$booking['booked_for']}
                ?new FormText(__('Booked for'), $Controller->{$booking['booked_for']})
                :null
            ),
            new FormText(__('Booked from'), date('Y-m-d, H:i', $booking['starttime'])),
            new FormText(__('Booked until'), date('Y-m-d, H:i', $booking['starttime'] + $booking['duration'])),
            ($booking['comment']
                ?new FormText(__('Comment'), $booking['comment'])
                :null
            )
        );
    }
    
    
    /**
     * View the object's availability for a given date
     * @param $Date
     * @return string Page content
     */
    function viewDate($Date) {
        JS::loadjQuery();
        Head::add('SB_Calendar', 'js-lib');
        Head::add('$(".cal_viewdate a").not(".cal_obj_links a").click(SB_Calendar.popup);', 'js-raw');
        
        global $DB, $Controller;
        $this->loadBookingObject();
        
        if(!is_numeric($Date)) $Date = strtotime($Date);
        
        $result = '';
        $result .= '<div class="nav"><a href="'.url(array('when' => date('Y-m', $Date)), array('id')).'">'.icon('small/arrow_left').__('Back').'</a></div>';
        
        $result .= $this->viewDatEmptyTable($Date);
        return $result;
    }
    
    function viewDatEmptyTable($Date, $allownew = true, $tableheader=true)
    {
        global $Controller;
        $StartingTime 		= mktime(0,0,0,date('m', $Date), date('d', $Date), date('Y', $Date));
        $StopTime			= mktime(0,0,0,date('m', $Date), date('d', $Date)+1, date('Y', $Date));
        $Now				= time();
        
        $TimeStep 			= 30; //minutes
        
    
        if($StopTime>=$Now) {
            Head::add("setTimeout('window.location=window.location;',60000)", 'js-raw');
        }
        
        $result = '';
        $result .= '<table class="cal_viewdate">';
        if($tableheader) 
        {
            $result .= '<tr class="cal_timeline"><td></td>';
            for($t=0;$t<24;$t+=($TimeStep / 60)) {
                $result .= '<td>';
                if(round($t,1) == round($t)) {
                    $result .= round($t);
                }
                $result .= '</td>';
            }
            $result .= '</tr>';
        }
        $Objects = (array)$this->subBookables;
        array_unshift($Objects, $this);
        $Objects = array_values($Objects);
        foreach($Objects as $row => $Obj) {
            if($Obj === $this) {
                $result .= '<tr class="cal_category"><th class="cal_obj_links">'.$Obj->link().'</th>';
            } else {
                $result .= '<tr><td class="cal_obj_links">'.$Obj->link().'</td>';
            }
            $onGoing = 0;
            $onGoingBooking = false;
            $oldOnGoingBooking = false;
            for($t=0;$t<60*24;$t+=$TimeStep) {
                
                $CurrentTime = $StartingTime + $t*60;
                
                if($onGoingBooking = $Obj->getOnGoingBooking($CurrentTime)) {
                    if($oldOnGoingBooking != $onGoingBooking && $onGoing) {
                        if($row == 0 || !$Objects[$row-1]->bookingExists($oldOnGoingBooking)) {
                            $objs = array_values(array_slice($Objects, $row+1, null, true));
                            $rowspan=0;
                            while(isset($objs[$rowspan]) && $objs[$rowspan]->bookingExists($oldOnGoingBooking)) $rowspan++;
                            $rowspan++;
                            $result .= '<td class="'.($oldOnGoingBooking['cleared_by']?'cal_booked':'cal_reserved').'" colspan="'.$onGoing.'" rowspan="'.$rowspan.'"><a href="'.url(array('id' => $oldOnGoingBooking['id'], 'viewBooking' => $oldOnGoingBooking['b_id']), 'viewDate').'">'.($oldOnGoingBooking['booked_for']?$Controller->{$oldOnGoingBooking['booked_for']}:$Controller->{$oldOnGoingBooking['booked_by']}).'</a></td>';
                        }
                        $onGoing = 0;
                    }
                    $oldOnGoingBooking = $onGoingBooking;
                    $onGoing++;
                } else {
                    if($oldOnGoingBooking && $onGoing) {
                        if($row == 0 || !$Objects[$row-1]->bookingExists($oldOnGoingBooking)) {
                            $objs = array_values(array_slice($Objects, $row+1, null, true));
                            $rowspan=0;
                            while(isset($objs[$rowspan]) && $objs[$rowspan]->bookingExists($oldOnGoingBooking)) $rowspan++;
                            $rowspan++;
                            $result .= '<td class="'.($oldOnGoingBooking['cleared_by']?'cal_booked':'cal_reserved').'" colspan="'.$onGoing.'" rowspan="'.$rowspan.'"><a href="'.url(array('id' => $oldOnGoingBooking['id'], 'viewBooking' => $oldOnGoingBooking['b_id']), 'viewDate').'">'.($oldOnGoingBooking['booked_for']?$Controller->{$oldOnGoingBooking['booked_for']}:$Controller->{$oldOnGoingBooking['booked_by']}).'</a></td>';
                        }
                    }
                    $onGoing = 0;
                    if($CurrentTime < $Now) {
                        $result .= '<td class="cal_past">&nbsp;</td>';
                    } elseif(!$allownew) {
                        $result .= '<td class="cal_free">&nbsp;</td>';
                    } else {
                        $result .= '<td class="cal_free"><a href="'.url(array('id' => $Obj->ID, 'view' => 'book', 'startTime' => $CurrentTime)).'">&nbsp;</a></td>';
                    }
                }
            }
            if($oldOnGoingBooking && $onGoing) {
                if($row == 0 || !$Objects[$row-1]->bookingExists($oldOnGoingBooking)) {
                    $objs = array_values(array_slice($Objects, $row+1, null, true));
                    $rowspan=0;
                    while(isset($objs[$rowspan]) && $objs[$rowspan]->bookingExists($oldOnGoingBooking)) $rowspan++;
                    $rowspan++;
                    $result .= '<td class="'.($oldOnGoingBooking['cleared_by']?'cal_booked':'cal_reserved').'" colspan="'.$onGoing.'" rowspan="'.$rowspan.'"><a href="'.url(array('id' => $oldOnGoingBooking['id'], 'viewBooking' => $oldOnGoingBooking['b_id']), 'viewDate').'">'.($oldOnGoingBooking['booked_for']?$Controller->{$oldOnGoingBooking['booked_for']}:$Controller->{$oldOnGoingBooking['booked_by']}).'</a></td>';
                }
            }
            $result .= '</tr>';
        }
        $result .= '</table>';
        return $result;
    }
    
    function viewPeriod($start, $stop)
    {
        JS::loadjQuery();
        Head::add('SB_Calendar', 'js-lib');
        Head::add('$(".cal_viewdate a").not(".cal_obj_links a").click(SB_Calendar.popup);', 'js-raw');

        if(!is_numeric($start)) $start = strtotime($start);
        if(!is_numeric($stop)) $stop = strtotime($stop);
        $result = '';
        $result .= '<div class="nav"><a href="'.url(array('when' => date('Y-m')), array('id')).'">'.icon('small/arrow_up').__('Up').'</a></div>';
        $result .= '<table cellpadding="0" cellspacing="0" border="0">';
        $this->loadBookings($start, $stop);
        $i = 0;
        while(true)
        {
            $dayStarts = mktime(0,0,0,date('m', $start),date('d', $start)+$i,date('Y', $start));
            $dayEnds = mktime(0,0,0,date('m', $start),date('d', $start)+(++$i),date('Y', $start));
            
            if($dayEnds >= $stop) break;
            
            if(!$this->isFree($dayStarts, 86400))
            {
                $result .= '<tr><th>'.date('Y-m-d', $dayStarts).'</th><td>';
                $result .= $this->viewDatEmptyTable($dayStarts);
                $result .= '</td></tr>';
            }
        }
        
        $result .= '</table>';
        return $result;
    }
    
    /**
     * Display the object's availability for a given month
     * @param $when Which month to display
     * @return string Page content
     */
    function displayCalendar($when = false) {
        global $DB;
        
        if($when) {
            if(!is_numeric($when)) {
                $when = mktime(0,0,0,substr($when,5,2),1,substr($when,0,4));
            }
        } else $when = time();
        
        $DaysInMonth 		= date('t', $when);
        $StartingTime 		= mktime(0, 0, 0, date('m', $when), 1, date('Y', $when));
        $StopTime			= mktime(0, 0, 0, date('m', $when)+1, 0, date('Y', $when));
        $Year 				= date('Y', $StartingTime);
        $Month 				= date('m', $StartingTime);
        $StartingWeekday	= date('N', $StartingTime);
        $StartingWeek		= date('W', $StartingTime);
        $Today 				= date('Ymd');
        
        $WeekStartsAt		= 1;
        $HalfDayLimit		= 0.3;
        
        
        $result = '<table class="calendar">';
        $result .= '<thead><tr><td></td>'
            .'<td>'.icon('small/resultset_first', __('Previous Year'), url(array('when' => ($Year-1).'-'.$Month), array('id'))).'</td>'
            .'<td>'.icon('small/resultset_previous', __('Previous Month'), url(array('when' => $Year.'-'.($Month<11?'0':'').($Month-1)), array('id'))).'</td>'
            .'<td colspan="2">'
                .'<a href="'.url(array('viewPeriod' => $Year.'-'.$Month.'-01|'.date('Y-m-d', mktime(0,0,0,$Month+1,0,$Year))), array('id')).'">'.strftime('%B', $StartingTime).'</a>, '
                .'<a href="'.url(array('viewPeriod' => $Year.'-01-01|'.$Year.'-12-31'), array('id')).'">'.strftime('%Y', $StartingTime).'</a>'
            .'</td>'
            .'<td>'.icon('small/resultset_next', __('Next Month'), url(array('when' => $Year.'-'.($Month<9?'0':'').($Month+1)), array('id'))).'</td>'
            .'<td>'.icon('small/resultset_last', __('Next Year'), url(array('when' => ($Year+1).'-'.$Month), array('id'))).'</td><td></td></tr>';
        $result .= '<tr><td><nobr><sub>'.__('Week').'</sub><span class="caldash">\</span><sup>'.__('Day').'</sup></nobr></td>';
        for($i=0;$i<=6;++$i) {
            $result .= '<th><a href="'.url(array('viewPeriod' => $Year.'-'.$Month.'-01|'.date('Y-m-d', mktime(0,0,0,$Month+1,0,$Year)), 'viewDay' => $i+1), array('id')).'">'.strftime('%a', 1251064800 + (86410 * $i)).'</a></th>';
        }
        $result .= '</tr></thead>';
        
        $result .= '<tbody>';
        
        $Cell 				= 0;
        $Week 				= $StartingWeek;
        $InitialEmptyCells 	= (($StartingWeekday - $WeekStartsAt + 7) % 7);
        $YearMonth 			= date('Y-m-', $StartingTime);
        while(++$Cell) {
            $Day = $Cell - $InitialEmptyCells;
            if($Day < 10 && $Day > 0) $Day = '0'.$Day;
            if(($Cell % 7) == 1) {
                $weekStarts = date('Y-m-d', mktime(0,0,0,$Month, $Day, $Year));
                $weekEnds = date('Y-m-d', strtotime('Next Sunday', mktime(0,0,0,$Month, $Day, $Year)));
                $result .= '<tr><th><a href="'.url(array('viewPeriod' => $weekStarts.'|'.$weekEnds), 'id').'">'.$Week++.'</th>';
            }
            if($Cell > $InitialEmptyCells AND $Cell <= $DaysInMonth + $InitialEmptyCells) {
                $result .= '<td class="';
                if($Year.$Month.$Day < $Today) 			$result .= 'cal_past';
                elseif($Year.$Month.$Day == $Today) 	$result .= 'cal_today';
                else 									$result .= 'cal_future';
                
                $duration = $this->percentageOfDayBooked($YearMonth.$Day);
                if($duration) {
                    if($duration <= $HalfDayLimit) {
                        $result .= ' cal_halfday';
                    } elseif($duration > $HalfDayLimit) {
                        $result .= ' cal_wholeday';
                    }
                }
                $result .= '" title="'.round($duration*100).'% '.__('booked').'"><a href="'.url(array('viewDate' => $YearMonth.$Day), array('id')).'">'.$Day.'</a></td>';
            } else {
                $result .= '<td></td>';
            }
            if(($Cell % 7) == 0) {
                $result .= '</tr>';
                if($Cell >= ($DaysInMonth + $InitialEmptyCells)) break;
            }
        }
        $result .= '</tbody></table>';
        
        return $result;
    }
    
    /**
     * View a list of the object and it's subobjects
     * @return string List
     */
    function listView() {
        global $DB, $Controller;
        $Objects = $Controller->get(arrayPrepend($DB->booking_items->asList(array('parent' => $this->ID), 'id', false, false, 'place ASC'), $this->ID));
        $booked_items = array();
        foreach($Objects as $obj) {
            $booked_items[] = array('link' => $obj->link(), 'id' => $obj->ID, 'parent' => $obj->parentBookID());
        }
        return listify(inflate($booked_items), false, true, 'link', 'children');
    }

    /**
     * Moves the object to a new place among the bookings.
     * Note that the place number is calculated among the siblings, so the place is relative to the parent
     * @param integer $newPlace The place to which the object should be moved to
     * @param integer|object $parent Parent ID or object. 0 if none.
     * @return bool
     */
    function sortBooking($newPlace, $parent=false) {
        global $DB, $Controller, $USER;
        $this->getSelf();
        if($this->ID===false || $newPlace < 0) return false;
        if($Controller->bookings(EDIT)) {
            if($parent === false) $parent = $this->_parent;
            if($parent == false) $parent = 0;
            if(is_string($parent) && !is_numeric($parent)) $parent = $Controller->{(string)$parent};

            $pid = (is_numeric($parent) ? $parent : @$parent->ID);
            if($parent===false) return false;
            $length = $DB->menu->count(array("parent" => $pid));
            if($newPlace === 'last' || $newPlace > $length) $newPlace = $length;
            if(!is_numeric($newPlace)) return false;
            if($this->place == $newPlace && $this->parentID == $pid) return true;
            $tonext = ($this->_place !== false && $newPlace == $this->_place + 1);

            $DB->query("UPDATE `booking_items` SET `booking_items`.`place`=(place+1) WHERE `booking_items`.`place`>".($tonext?'':'=')."'".($newPlace)."' AND `booking_items`.`parent`='".$pid."'");
            $a = $DB->booking_items->update(	array(	"parent" => $pid,
                                        "place"  => $newPlace+$tonext),
                                array('id' => $this->ID),
                                true);

            if($this->_place !== false) $DB->query("UPDATE `booking_items` SET `booking_items`.`place`=(place-1) WHERE `booking_items`.`place`>'".$this->place."' AND `booking_items`.`parent`='".$this->parentID."'");

            $this->_place = $newPlace;
            $this->_parent = $Controller->$pid;
            $this->_parentID = $pid;
            return true;
        }
    }
    
    /**
     * Returns how many percent a given day is booked
     * @param $when Timestamp from the given day
     * @return float Percentage booked [0,1]
     */
    function percentageOfDayBooked($when) {
        if(!is_numeric($when)) $when = strtotime($when);
        $StartingTime = mktime(0,0,0,date('m', $when), date('d', $when), date('Y', $when));
        $StopTime = mktime(0,0,0,date('m', $when), date('d', $when)+1, date('Y', $when));
        return $this->percentageBooked($StartingTime, $StopTime);
    }
    
    /**
     * Returns the percentage of the timespan the the object is considered booked
     * @param $StartingTime
     * @param $StopTime
     * @return float Percentage booked [0,1]
     * @todo May return >1 if several subobjects are booked simultaneously
     */
    function percentageBooked($StartingTime, $StopTime) {
        global $DB;
        if($StartingTime >= $StopTime || !is_numeric($StartingTime) || !is_numeric($StopTime)) return false;
        
        $Booked_Time = array_sum($DB->asList("SELECT SUM(`duration` - GREATEST( `starttime` + `duration` - ".$StopTime." , 0) - GREATEST( ".$StartingTime." - `starttime` , 0 )) FROM `booking_bookings` WHERE `id`".($this->subBookables?" IN ('".$this->ID."','".implode("','", $this->subBookables)."')":"='".$this->ID."'")." AND
 (
   (`starttime` BETWEEN ".$StartingTime." AND ".($StopTime-1).")
   OR
   ((`starttime` + `duration`) BETWEEN ".($StartingTime+1)." AND ".$StopTime.")
   OR
   (`starttime` <= ".$StartingTime." AND (`starttime` + `duration`) >= ".$StopTime.")
 )
GROUP BY `b_id`"));
        return ($Booked_Time / ($StopTime - $StartingTime));
    }
}
?>
