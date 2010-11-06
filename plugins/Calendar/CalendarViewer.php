<?php

class CalendarViewer extends Page {
    static function installable(){return __CLASS__;}
    private $itemsAPage;
    private $months;
    
    function install(){
        global $Controller, $CONFIG;
        $Controller->newObj('CalendarViewer');
        $CONFIG->Calendar->setType('Items_a_Page', 'text');
        $CONFIG->Calendar->setDescription('Items_a_Page','Number of items displayed on a page');
        $CONFIG->Calendar->Items_a_Page = 10;
    }
    
    function __construct($id, $lang=false){
        global $CONFIG;
        parent::__construct($id, $lang);
        $this->suggestName('CalendarViewer','en');
        $this->alias = 'calendar';
        $this->itemsAPage = $CONFIG->Calendar->Items_a_Page;
    }
    
    function __get($property){
        return parent::__get($property);
    }
    
    function run(){
        global $Templates;
        
        $_REQUEST->setType('cal','string');
        $_REQUEST->setType('time','string');
        $_REQUEST->setType('view','string');
        $_REQUEST->setType('event','string');
        
        $this->setContent('menu',$this->submenu(urldecode($_REQUEST['cal']),$_REQUEST['time']));
    
        if($_REQUEST['event']) {
            $this->setContent('main', $this->displayEvent($_REQUEST['event']));
        } elseif($_REQUEST['view'] == 'cal') {
            $this->setContent('main', $this->displayCalendar($_REQUEST['cal'], $_REQUEST['time']));
        } elseif(!$_REQUEST['view'] || $_REQUEST['view'] == 'list') {
            $this->setContent('main', $this->displayList(urldecode($_REQUEST['cal']),$_REQUEST['time']));
        }
        $Templates->yweb('empty')->render();
    }
    
    /**
     *
     */
    function submenu($currentCal=false,$currentTime=false) {
        $cals = Calendar::getCalendars();
        $menu = '<div id="subnav"><ul class="menu"><li'.(($currentCal || $currentTime || $_REQUEST['event'])?'':' class="activeli"').'><a href="'.url(null,'id').'">'.__('All events').'</a></li></ul><h2>'.__('By category').'</h2><ul class="menu">';
        foreach($cals as $cal){
            $menu .= '<li'.($cal == $currentCal?' class="activeli"':'').'><a href="'.url(array('cal' => urlencode($cal)),'id').'">'.$cal.'</a></li>';
        }
        $menu .= '</ul><h2>'.__('By time').'</h2><ul>';
        foreach($this->getTimeList() as $month) {
            if($month<10) $month = '0'.$month;
            $menu .= '<li'.($month == substr($currentTime,5,2)?' class="activeli"':'').'><a href="'.url(array('time' => (strftime('%G',time()).'-'.$month)),'id').'">'.ucfirst(strftime('%B',mktime(0,0,0,$month))).'</a></li>';
        }
        $menu .= '</ul></div>';
        return $menu;
    }
    /**
     * Displays the rightmenu.
     * 
     * @return string
     */
    function rightmenu() {
        return '<div class="col four right bordered thinner"><h2>'.__('Display options').'</h2><ul class="options"><li><a href="'.url(array('view' => 'list'), array('id', 'cal', 'time')).'">'.icon('small/text_list_bullets').' List</a></li><li><a href="'.url(array('view' => 'cal'), array('id', 'cal', 'time')).'">'.icon('small/calendar').' Calendar</a></li></ul></div>';
    }

    /**
     *
     */
    function displayList($calendar=false, $time=false) {
        if($time){
            $time = explode('-',$time);
            $start = mktime(0,0,0,$time[1],1,$time[0]);
            $end = mktime(0,0,0,$time[1]+1,1,$time[0]);
        } else $start = $end = false;
        
        $eventIDs = Calendar::getEventIDs($calendar, $start, $end);
        $total = count($eventIDs);
        $pageInfo = Pagination::getRange($this->itemsAPage, $total);
        $start = $pageInfo['range']['start'];
        $stop = $pageInfo['range']['stop'];

        $r = '<h1>'.__('Calendar').'</h1><div class="cols spacer"><div class="col eight first"><ul class="events">';
        $i = 0;
        foreach($eventIDs as $eventID){
            if($start <= $i && $i <= $stop) {
                $event = Calendar::getEvent($eventID);
                $r .= '<li>'.$event->getShort(true).'</li>';
            }
            $i++;
        }
        if($total > $this->itemsAPage) $r .= '<li><div class="cols"><div class="col eight first bordered thinner">'.$pageInfo['links'].'</div></div></li>';
        $r .= '</ul></div>';
        $r .= $this->rightmenu();
        return $r;
    }
    
    
    /**
     * 
     * @param $time Which month to display
     * @return string Page content
     */
    function displayCalendar($calendar=false, $time = false) {
        if($time) {
            if(!is_numeric($time)) {
                $time = mktime(0,0,0,substr($time,5,2),1,substr($time,0,4));
            }
        } else $time = time();
        
        $DaysInMonth 		= date('t', $time);
        $StartingTime 		= mktime(0, 0, 0, date('m', $time), 1, date('Y', $time));
        $StopTime			= mktime(0, 0, 0, date('m', $time)+1, 0, date('Y', $time));
        $Year 				= date('Y', $StartingTime);
        $Month 				= date('m', $StartingTime);
        $StartingWeekday	= date('N', $StartingTime);
        $StartingWeek		= date('W', $StartingTime);
        $Today 				= date('Ymd');
        $WeekStartsAt		= 1;
        
        $events = Calendar::getEvents($calendar, $StartingTime, $StopTime);
/* 		dump($events); */
        
        $result = '<h1>'.__('Calendar').'</h1><div class="cols spacer"><div class="col first eight"><table class="calendar">';
        $result .= '<thead><tr><td></td>'
            .'<td>'.icon('small/resultset_first', __('Previous Year'), url(array('time' => ($Year-1).'-'.$Month), array('id', 'view'))).'</td>'
            .'<td>'.icon('small/resultset_previous', __('Previous Month'), url(array('time' => $Year.'-'.($Month<11?'0':'').($Month-1)), array('id', 'view'))).'</td>'
            .'<td colspan="2">'
                .'<a href="'.url(array('viewPeriod' => $Year.'-'.$Month.'-01|'.date('Y-m-d', mktime(0,0,0,$Month+1,0,$Year))), array('id')).'">'.strftime('%B', $StartingTime).'</a>, '
                .'<a href="'.url(array('viewPeriod' => $Year.'-01-01|'.$Year.'-12-31'), array('id')).'">'.strftime('%Y', $StartingTime).'</a>'
            .'</td>'
            .'<td>'.icon('small/resultset_next', __('Next Month'), url(array('time' => $Year.'-'.($Month<9?'0':'').($Month+1)), array('id', 'view'))).'</td>'
            .'<td>'.icon('small/resultset_last', __('Next Year'), url(array('time' => ($Year+1).'-'.$Month), array('id', 'view'))).'</td><td></td></tr>';
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
                $result .= '<tr><th><a href="'.url(array('viewPeriod' => $weekStarts.'|'.$weekEnds)).'">'.$Week++.'</th>';
            }
            if($Cell > $InitialEmptyCells AND $Cell <= $DaysInMonth + $InitialEmptyCells) {
                $result .= '<td class="';
                if($Year.$Month.$Day < $Today) 			$result .= 'cal_past';
                elseif($Year.$Month.$Day == $Today) 	$result .= 'cal_today';
                else 									$result .= 'cal_future';
                
                if(array_key_exists($YearMonth.$Day,$events)) { 
                    $result .= ' cal_wholeday" title="'.$events[$YearMonth.$Day]->Name.'"><a href="'.url(array('event' => $events[$YearMonth.$Day]->ID), array('id')).'">'.$Day.'</a>';
                } else {
                    $result .= '">'.$Day.'</td>';
                }
/* 				$result .= '"><a href="'.url(array('viewDate' => $YearMonth.$Day), array('id')).'">'.$Day.'</a></td>'; */
            } else {
                $result .= '<td></td>';
            }
            if(($Cell % 7) == 0) {
                $result .= '</tr>';
                if($Cell >= ($DaysInMonth + $InitialEmptyCells)) break;
            }
        }
        $result .= '</tbody></table></div>';
        $result .= $this->rightmenu();
        return $result;
    }
    
    
    /**
     *
     */
    function displayEvent($eventID){
        $event = Calendar::getEvent($eventID);
        return $event->getFull();
    }
    
    /**
     * @param int $start Unix-timestamp
     * @param int $end Unix-timestamp
     */
    function formatTime($start, $end){
        $start = array('time' => strftime('%H:%M',$start), 'day' => strftime('%e',$start), 'monthStr' => strftime('%B',$start), 'year' => strftime('%Y',$start));
        
        $end = array('time' => strftime('%H:%M',$end), 'day' => strftime('%e',$end), 'monthStr' => strftime('%B',$end), 'year' => strftime('%Y',$end));
        
        $startStr = $endStr = '';
        foreach($start as $key => $value){
            if($start[$key] != $end[$key] && $start[$key] != '00:00') $startStr .= $start[$key].' ';
            if($end[$key] != '00:00') $endStr .= $end[$key].' ';
        }

        return $startStr.($startStr != ''?'- ':'').$endStr;
    }
    
    /**
     *
     */
    function getTimeList(){
        global $DB;
        
        $start = time();
        $end = $start + strtotime('+1 year');
        //FIXME: Return list with year to avoid problem with diffrent years
        return $DB->asList($DB->query("SELECT MONTH( FROM_UNIXTIME(`start`) ) `month` FROM `events` WHERE 
                        (
                            (`start` BETWEEN ".$start." AND ".($end-1).")
                            OR
                            (`end` BETWEEN ".($start+1)." AND ".$end.")
                            OR
                            (`start` <= ".$start." AND `end` >= ".$end.")
                        ) GROUP BY `month` ORDER BY `month`"));

    }
    
}