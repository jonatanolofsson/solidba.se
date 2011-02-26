<?php

class Event extends Page {
    private $_start,
            $_end,
            $_soft_deadline,
            $_hard_deadline,
            $_send_reminder,
            $_calendar;

    public $editable = array(
        'EventEditor' => EDIT,
        'PermissionEditor' => EDIT_PRIVILEGES
    );

    function __construct($id=false) {
        parent::__construct($id);
        Base::registerMetadata(array(
            'Image',
            'contact',
            'attendance',
            'attending_groups'
        ));
    }


    function __create() {
        parent::__create();
        $this->Name = __('New event');
    }



    /**
     * Function for getting a event property
     * @param string $property Reqested property
     * @return string
     */
    function __get($property) {
        switch($property) {
            case 'time': return $this->getTime();
            case 'text':
                $this->loadContent();
                return @$this->content['main'];
            case 'attendance_information':
                $this->loadContent();
                return @$this->content['attendance'];
            case 'start':
            case 'calendar':
            case 'end':
            case 'soft_deadline':
            case 'hard_deadline':
            case 'send_reminder':
                $this->loadEvent();
                return $this->{'_'.$property};
            default: return parent::__get($property);
        }
    }

    function __set($property, $value) {
        switch($property) {
            case 'start':
            case 'end':
            case 'soft_deadline':
            case 'hard_deadline':
            case 'send_reminder':
            case 'calendar';
                global $DB;
                $DB->events->update(array($property => $value), array('id' => $this->ID), true);
                return $this->{'_'.$property} = $value;
            default: return parent::__set($property, $value);
        }
    }

    function loadEvent() {
        if($this->_start) return;
        global $DB;
        $e = $DB->events->{(string)$this->ID};
        $this->_calendar = $e['calendar'];
        $this->_start = $e['start'];
        $this->_end = $e['end'];
        $this->_soft_deadline = $e['soft_deadline'];
        $this->_hard_deadline = $e['hard_deadline'];
        $this->_send_reminder = $e['send_reminder'];
    }

    /**
     *
     */
    function run() {
        global $Templates;
        $this->saveAttendance();
        $this->setContent('main',$this->getFull());
        $Templates->render();
    }

    /**
     *
     */
    function getShort($link=false) {
        $r = '<div class="cols"><div class="col first eight bordered thinner '.$this->active(true).'">';
        $r .= '<div class="cols"><div class="col first '.($this->Image?'four':'seven').'"><h2>'.$this->Name.$this->active().'</h2><p class="date">'.$this->getTime().'</p>'.($this->place?'<p><strong>'.__('Place').':</strong> '.$this->place.'</p>':'').'<p>'.$this->getPreamble(100).'</p>'.($link?'<p><a href="'.url(array('id' => $this->ID)).'">Read more</a></p>':'').'</div>'.($this->Image?'<div class="col two right alignright">'.$this->getImage(110).'</div>':'').'</div></div></div>';
        return $r;
    }

    /**
     *
     */
    function getFull() {
        global $Controller;
        $r = '<h1>'.$this->Name.'</h1><span class="whichcal">'.$Controller->{$this->calendar}.'</span><p class="date">'.$this->getTime().'</p>'.($this->place!=''?'<p><strong>'.__('Place').':</strong> '.$this->place.'</p>':'');
        $r .= '<div class="cols spacer"><div class="col first six">'.$this->text.'</div><div class="col six">'.$this->getImage(350).'</div></div>';
        $r .= $this->displayAttendance();
        return $r;
    }


    function getBox() {
        return '<div class="cols spacer"><div class="col first six bordered"><div class="cols"><div class="col first ">'.icon('large/1day-32').'</div><div class="col three"><h2>'.$this->Name.'</h2><p>'.$this->getTime().'</p></div></div></div>';
    }

    function getLink() {
        $_REQUEST->setType('event', 'string');
        return '<ul class="links"><li><p><a href="'.url(array('id' => 'calendar', 'event' => $this->ID)).'">'.__('Calendar Event').'</a></p></li></ul>';
    }


    function getImage($maxWidth=false, $maxHeight=false) {
        if($this->_Image) {
            global $Controller;
            if($img = $Controller->{(string)$this->_Image}(READ)) {
                $url = array('id' => $this->_Image);
                if($maxWidth) {
                    $_REQUEST->setType('mw', 'numeric');
                    $url['mw'] = (string)$maxWidth;
                }
                if($maxHeight) {
                    $_REQUEST->setType('mh', 'numeric');
                    $url['mh'] = (string)$maxHeight;
                }
                return '<img src="'.url($url).'" alt="" />';
            }
        }
        return '';
    }


    /**
     *
     */
    function getTime() {
        //FIXME: Fix output
        $start = array('time' => strftime('%H:%M',$this->start), 'day' => strftime('%e',$this->start), 'monthStr' => strftime('%B',$this->start), 'year' => strftime('%Y',$this->start));

        $end = array('time' => strftime('%H:%M',$this->end), 'day' => strftime('%e',$this->end), 'monthStr' => strftime('%B',$this->end), 'year' => strftime('%Y',$this->end));
/*      dump($start,$end); */
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
    function active($getClass=false) {
        $now = time();
        if($this->start < $now && $now < $this->end) return ($getClass?'ongoing':'<span class="active now">'.__('Ongoing').'</span>');
        $diff = round(($this->start - $now)/60);
        if($diff > 0 && $diff<(60*12)){
            if($getClass) return 'upcoming';
            else {
                if($diff>60) return '<span class="active later">'.__('Starts in').' '.round($diff/60).' '.__('hours').'</span>';
                else return '<span class="active later">'.__('Starts in').' '.$diff.' '.__('minutes').'</span>';
            }
        }
    }

    function saveAttendance() {
        $_REQUEST->setType('attending', '/yes|no/');
        $_REQUEST->setType('user', 'numeric');
        $_REQUEST->setType('comment', 'string');

        if($_REQUEST['attending']) {
            if($_REQUEST['user'] && $this->mayI(EDIT)) {
                $user = $_REQUEST['user'];
            } else {
                global $USER;
                $user = $USER->ID;
            }
            global $DB;

            $DB->attendance->update(array(
                'attending' => $_REQUEST['attending'],
                'comment' => $_REQUEST['comment']
            ), array(
                'event' => $this->ID,
                'attendee' => $user
            ), true);

            Flash::queue(__('Saved'));
            return true;
        }
        return false;
    }

    function getPreamble($limit=false) {
        global $CONFIG;
        if(!$limit)	$limit = 300;
        $text = strip_tags($this->text);
        $textlen = strlen($text);
        return lineTrim($text, $limit);
    }


    /**
     *
     */
    function may($u, $lvl) {
        if(is_bool($pr = parent::may($u, $lvl))) {
            return $pr;
        } else {
            global $Controller;
            $c = $this->calendars;
            foreach($c as $cal) {
                if($Controller->get($cal, $lvl)) return true;
            }
            if($lvl&READ) {
                return $this->isActive();
            } else return $pr;
        }
    }

    function attendanceForm() {
        global $USER;
        $att = $this->getAttendance();
        return Form::quick(false, null,
            /*($this->mayI(EDIT)
                ? new UserSelect(__('User'), 'user', $USER->ID)
                : null),*/
            new RadioSet(__('Attending'), 'attending',
                array('yes' => __('Yes'), 'no' => __('No')),
                @$att[$USER->ID]['attending']),
            new Input(__('Comment'), 'comment', @$att[$USER->ID]['comment'])
        );
    }

    function isAttending($u, $asString = false) {
        $a = $this->getAttendance();
        if(!isset($a[$u->ID])) return null;
        if($asString) return $a[$u->ID]['attending'];
        else return $a[$u->ID]['attending'] == 'yes';
    }

    function displayAttendance() {
        if(!$this->attendance) return false;
        global $USER;

        if(!$this->mayI(EDIT)) {
            if(!is_array($g = $this->attending_groups)) $g = array();
            $mayattend = false;
            foreach($g as $group) {
                if($USER->memberOf($group)) {
                    $mayattend = true;
                    break;
                }
            }
            if(!$mayattend) return false;
        }
        return $this->attendanceStatistics()
        .$this->attendanceForm()
        .$this->attendanceList();
    }

    function attendanceStatistics() {
        $attending = $this->getAttendance();
        global $CONFIG, $Controller;
        if(!is_array($ig = $this->attending_groups)) $ig = array();
        $counter = array('yes' => array(), 'no' => array());
        $tcounter = array('yes' => 0, 'no' => 0);
        foreach($attending as $id =>$a) ++$tcounter[$a['attending']];
        $unknown = array();
        foreach($ig as $gid) {
            $group = $Controller->get($gid, OVERRIDE);
            $counter['yes'][$group->Name] = 0;
            $counter['no'][$group->Name] = 0;
            $unknown[$group->Name] = count($group->memberUsers(true, true));
            if($group) {
                foreach($attending as $id =>$a) {
                    if($group->isMember($id)) {
                        $counter[$a['attending']][$group->ID] = @$counter[$group->Name] + 1;
                        --$unknown[$group->Name];
                    }
                }
            }
        }

        $colspan = 2*count($ig)+1;
        $r = '<table cellpadding="0" cellspacing="0" border="0">'
        .'<tr><th>'.__('Contact').'</th>'
            .'<td colspan="'.$colspan.'">'.$this->contact.'</td>'
        .'</tr>'
        .'<tr><th>'.__('When').'</th>'
            .'<td colspan="'.$colspan.'">'.Short::datespan($this->start, $this->end).'</td>'
        .'</tr>'
        .'<tr><th>'.__('Last registration date').'</th>'
            .'<td colspan="'.$colspan.'">'.date('Y-m-d', $this->soft_deadline).'</td>'
        .'</tr>'
        .'<tr><th>'.__('Attending').'</th>'
            .'<td>'.$tcounter['yes'].'</td>';
        ksort($counter['yes']);
        foreach($counter['yes'] as $g => $count) {
            $r .= '<td class="groupcol">'.$g.'</td><td>'.$count.'</td>';
        }
        $r .= '</tr><tr><th>'.__('Not attending').'</th>'
            .'<td>'.$tcounter['no'].'</td>';
        ksort($counter['no']);
        foreach($counter['no'] as $g => $count) {
            $r .= '<td class="groupcol">'.$g.'</td><td>'.$count.'</td>';
        }
        $r .= '</tr><tr><th>'.__('Unknown').'</th>'
            .'<td>'.array_sum($unknown).'</td>';
        ksort($unknown);
        foreach($unknown as $g => $count) {
            $r .= '<td class="groupcol">'.$g.'</td><td>'.$count.'</td>';
        }
        $r .= '</tr></table>';

        return $r;
    }

    function attendanceList() {
        global $Controller;
        $attending = $this->getAttendance();
        $objects = $Controller->get(array_keys($attending), OVERRIDE);

        $groupsort = array();
        $nogroup = $attending;

        global $CONFIG, $Controller;
        if(!is_array($ig = $CONFIG->matrikel->interesting_groups)) $ig = array();
        $groups = $Controller->get($ig);

        foreach($attending as $id => $a) {
            foreach($ig as $gid) {
                if($groups[$gid]->isMember($id)) {
                    $groupsort[$groups[$gid]->Name] = $a;
                    unset($nogroup[$id]);
                    break;
                }
            }
        }
        foreach($groupsort as &$group) {
            propsort($group, 'Name');
        }
        ksort($groupsort);
        propsort($nogroup, 'Name');

        $r = array(new Tableheader(__('Name'), __('Group'), __('Attending'), __('Comment'))); //FIXME: Stämma
        foreach($groupsort as $groupname => $group) {
            foreach($group as $user) {
                $u = $object[$user['attendee']];
                $r[] = new Tablerow($u->Name, $group, $user['attending'], $user['comment']);
            }
        }
        foreach($nogroup as $user) {
            $u = $objects[$user['attendee']];
            $r[] = new Tablerow($u->Name, '', $user['attending'], $user['comment']);
        }
        return new Table($r);
    }

    function getAttendance() {
        if($this->__attendance === false) {
            global $DB;
            $this->__attendance = $DB->attendance->asArray(array('event' => $this->ID), 'attendee,*', false, 2);
        }
        return $this->__attendance;
    }
    private $__attendance = false;
}
?>
