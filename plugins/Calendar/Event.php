<?php

class Event extends Page {
    private $type;
    private $_News;
    private $text;
    private $_Image = false;
    private $start;
    private $end;
    private $calendar;
    private $place;
    public $privilegeGroup = 'hidden';
    
    function __construct($id=false) {
        global $DB;
        parent::__construct($id);
        //FIXME: Don't load whole event unless needed
        $info = $DB->events->{$id};
        $this->type = $info['type'];
        $this->text = $info['text'];
        $this->start = $info['start'];
        $this->end = $info['end'];
        $this->calendar = $info['calendar'];
        $this->getMetadata('', false, array('Image'));
        $this->place = $info['place'];
    }
    
    /**
     * Function for getting a event property
     * @param string $property Reqested property
     * @return string
     */
    function __get($property) {
        if(in_array($property, array('text', 'start', 'end', 'calendar', 'place', 'type')))
            return $this->$property;
        elseif(in_array($property, array('Image', 'News')))
            return $this->{'_'.$property};
        elseif($property == 'time')
            return $this->getTime();
        else return parent::__get($property);
    }

    /**
     * Function for setting a event property
     * @param string $property Property to be set
     * @param string|number $value The value of the property
     * @return void
     */
    function __set($property, $value) {
        global $DB;
        $ipn = '_'.$property;
        if(in_array($property, array('text', 'start', 'end', 'calendar', 'place', 'type'))){
            if($this->$property != $value) {
                $this->$property = $value;
                $DB->events->{$this->ID} = array($property => $value);
            }
        } else if(in_array($property, array('Image', 'News'))) {
            if($value != @$this->$ipn && ($value || @$this->$ipn)) {
                if(@$this->$ipn !== false && $this->mayI(EDIT)) {
                    Metadata::set($property, $value);
                }
            }
            $this->$ipn = $value;
        } else parent::__set($property, $value);
    }
    
    function delete() {
        global $DB,$Controller;
        $DB->events->delete(array('id' => $this->ID));
        parent::delete();
    }
    
    /**
     *
     */
    function run() {
        global $Templates;
        $this->setContent('main',$this->getFull());
        $Templates->yweb('empty')->render();
    }
    
    /**
     *
     */
    function getShort($link=false) {
        $r = '<div class="cols"><div class="col first eight bordered thinner '.$this->active(true).'">';
        $r .= '<div class="cols"><div class="col first '.($this->Image?'four':'seven').'"><h2>'.$this->Name.$this->active().'</h2><p class="date">'.$this->getTime().'</p>'.($this->place?'<p><strong>'.__('Place').':</strong> '.$this->place.'</p>':'').'<p>'.$this->getPreamble(100).'</p>'.($link?'<p><a href="'.url(array('event' => $this->ID),'id').'">Read more</a></p>':'').'</div>'.($this->Image?'<div class="col two right alignright">'.$this->getImage(110).'</div>':'').'</div></div></div>';
        return $r;
    }
    
    /**
     *
     */
    function getFull() {
        $r = '<h1>'.$this->Name.'</h1><p class="date">'.$this->getTime().'</p>'.($this->place!=''?'<p><strong>'.__('Place').':</strong> '.$this->place.'</p>':'');
        $r .= '<div class="cols spacer"><p class="author">'.__('Author').$this->author->getLink().'</p><hr /><div class="col first six">'.$this->text.'</div><div class="col six">'.$this->getImage(350).'</div></div>';
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
/* 		dump($start,$end); */
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
            return $this->isActive();
        }
    }

}
?>