<?php
class NewsItem extends Page {
    private $_Cal = false;
    private $_Image = false;
    private $_Activated = false;
    private $_LockedPos = false;
    private $Publish = false;
    public $privilegeGroup = 'hidden';

    function __construct($id, $language=false) {
        parent::__construct($id, $language);
        $this->_edit_link = url(array('id' => 'eventAdmin', 'edit' => $id), null, false);

        $this->getMetadata('', false, array('Cal', 'Image', 'Activated', 'LockedPos'));
    }

    function __get($property) {
        if(in_array($property, array('Cal', 'Image', 'Activated', 'LockedPos'))) {
            return $this->{'_'.$property};
        } elseif($property == 'Publish') {
            $r = $this->getActive();
            return $r['start'];
        } else return parent::__get($property);
    }

    function __set($property, $value) {
        $ipn = '_'.$property;
        if(in_array($property, array('Cal', 'Image', 'Activated', 'LockedPos'))) {
            if($property == 'Activated' || $property == 'LockedPos')
            {
                 $value2 = $value;
                 $value = (int)(bool)$value;
            }
            if($value != @$this->$ipn && ($value || @$this->$ipn)) {
                if(@$this->$ipn !== false && $this->mayI(EDIT)) {
                    if(!$this->mayI(EDIT)) return false;
                    if($property == 'Activated')
                    {
                        if(!$this->mayI(PUBLISH)) return false;
                    }
                    elseif($property == 'Cal') {
                        $this->registerUpdate();
                        if(is_object($value)) {
                            $this->_Cal = $value;
                            $value = $value->ID;
                        }
                    }
                    Metadata::set($property, $value);
                }
            }
            $this->$ipn = (($property=='Activated'||$property=='LockedPos')&&$value2==''?$value2:$value);
        } elseif($property == 'Publish') {
            $this->setActive($value);
        } else parent::__set($property, $value);
    }

    /**
     * (non-PHPdoc)
     * @see lib/Page#run()
     */
    function run() {
        global $Controller, $Template;

        // Ajax-suport
        $_REQUEST->setType('action','string');
        if($_REQUEST->valid('action') && $_REQUEST['action']=='lockpos'){
            echo $this->lockPos();
        } else {
            if($this->_Cal) $event = $Controller->{$this->_Cal};
            $this->header = $this->getTitle();
            $this->setContent('main',
                    '<div class="articleHolder">'.
                        $this->getInfo().
                        '<div class="articleImg">'.$this->getImage(Design::getPxWidth(10),false, true).'</div>'.
                        @$this->content['Text'].
                    '</div>');
            parent::run();
        }
    }

    /**
     * Generate preview
     * @param $class Add class to preview div
     * @return HTML
     */
    function preview($class=false) {
        return '<div class="news'.($class?' '.$class:'').'"><h1>'.$this->Name.'</h1>'.$this->getImage().@$this->getPreamble(false, $clipping_occured).'<span class="more"><a href="'.url(array('id' => $this->ID)).'">'.($clipping_occured?_('Read more'):__('Go to page')).'</a></span>'.'</div>';
    }

    /**
     * Returns a preview text, with a link to the NewsItem
     * @param int $limit Max number of words in the text
     * @return HTML
     */
    function getText($limit=false, $viewer=true) {
        if(!$limit) return @$this->content['Text'];
        $r = '<p>'.@$this->getPreamble($limit).'</p>';
        $r .= '<p class="read_more"><a href="'.url(array('id' => $this->ID)).'">'.__('Read more').'</a></p>';
        /*
        '.($this->mayI(EDIT)?'<a href="javascript:;" class="edit">Edit</a>':'').'

    $_REQUEST->setType('item', 'numeric');
            $r .= '<p><a href="'.url(array('id' => 'flowView', 'item' => $this->ID)).'">'.__('More').'...</a></p>';
*/

        return $r;
    }

    /**
     * Returns the title of the NewsItem
     * @return HTML
     */
    function getTitle($small=false) {
        $h = pow(2, (int)(bool)$small);
        return '<h'.$h.'>'.$this->Name.'</h'.$h.'>';
    }

    /**
     * Returns the HTML-string for viewing an image
     * @param int $maxWidth Max windth off the requested image
     * @param int $maxHeight Max height off the requested image
     * @return HTML
     */
    function getImage($maxWidth=false, $maxHeight=false, $link=false) {
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
                return ($link?'<p><a href="'.url(array('id' => $this->ID)).'">':'').'<img src="'.url($url).'" alt="" />'.($link?'</a></p>':'');
            }
        }
        return '';
    }

    /**
     * Returns a formatted timestamp with the time the event was published
     * @return HTML
     */
    function getDate($raw=false, $box=false) {
        $active = $this->getActive();
        if($box) {
            return '<small class="date_box"><span class="date_day">'.date('j', $active['start']).'</span><span class="date_month">'.strtoupper(date('M', $active['start'])).'</span><span class="date_year">'.date('Y', $active['start']).'</span></small>';
        } elseif($raw) {
            return strftime('%e %B %Y', $active['start']);
        } else {
            return '<p class="date">'.strftime('%e %B %Y', $active['start']).'</p>';
        }
    }

    function may($u, $lvl) {
        if(is_bool($pr = parent::may($u, $lvl))) {
            return $pr;
        } else {
            global $Controller;
            if($lvl & READ) {
                if($r = $this->isActive()) return $r;
                $a = $this->getAuthor(true);
                if(is_string($u) && !is_numeric($u)) $u = $Controller->{(string)$u}(OVERRIDE);
                if(is_object($u)) $u = $u->ID;
                return ($a == $u);
            } else {
                $a = $this->getAuthor(true);
                if(is_string($u) && !is_numeric($u)) $u = $Controller->{(string)$u}(OVERRIDE);
                if(is_object($u)) $u = $u->ID;
                if($a == $u && $Controller->newsAdmin(READ)) return true;
                else return $Controller->newsAdmin(OVERRIDE)->may($u, $lvl);
            }
        }
    }

    function isActive($what=false, $when=false)
    {
        return ($this->_Activated && parent::isActive($what, $when));
    }

    function getPreamble($limit=false, &$clipping_occured=false) {
        global $CONFIG;
/* 		if(!$limit)	$limit = ($CONFIG->News->Preamble_size ? $CONFIG->News->Preamble_size : 300); */
        return dntHash::deHash(lineTrim(
                    strip_tags(preg_replace_callback(array('#<(a|b)[^>]*?>.*?</\1>#i', '#<br[^>]*?>#i'), array('dntHash', 'hash'), $this->content['Text'])),
                    $limit,
                    '...',
                    $clipping_occured));
    }

    /**
    * Info function
    */
    function getInfo() {
        return '<div class="articleInfo"><span class="author">'.$this->getDate(true).' '.__('by').' '.$this->author->getLink().'</span>'.($this->mayI(EDIT)?'<span class="edit">|<a href="javascript:;" id="'.$this->ID.'"'.($this->LockedPos?' class="locked"':'').'>Edit</a></span>':'').'<span class="category"><a href="/flowView?q=News">'.__('News').'</a></span></div>';
    }
/* 	'.($this->mayI(EDIT)?'<span class="edit">|<a href="javascript:;">Edit</a></span>':'').' */


    function display($size=false, $first=false, $viewer=false, $fulltext=false) {
        global $DB, $Controller;
        switch($size) {
            case 12:
                return Design::column( Design::row(array(
                            Design::column($this->getImage(Design::getPxWidth(6),Design::getPxWidth(6), true), 6, true, false, false, false, 'aligncenter'),
                            Design::column($this->getTitle().$this->getDate().$this->getText(700,false), 6)
                        )
                    ),12, true, true, true);
                break;
            case 6:
                return Design::column($this->getTitle(true)
                            .Design::row(array(
                                    Design::column($this->getImage(Design::getPxWidth(2),Design::getPxWidth(2), true).$this->getDate(), 2,  true),
                                    Design::column($this->getText(200,false), 4)
                                )
                            ), 6, $first, true);
                break;
            case 3:
                return Design::column($this->getTitle(true).$this->getImage(Design::getPxWidth(3), false, true).$this->getDate().$this->getText(100,false), 3, $first, true);
                break;

            /* <<< New flow design >>> */
            case 'new':
            default:
                return '<div class="articleHolder">'.
                    $this->getTitle().
                    $this->getInfo().
                    '<div class="articleImg">'.$this->getImage(Design::getPxWidth(10),false, true).'</div>'.
                    $this->getText(300,false,false).
                    '</div>';
                break;
        }
    }

    function lockPos(){
        if($this->LockedPos == false){
            $this->LockedPos = true;
            return 'Object position locked';
        } else {
            $this->LockedPos = false;
            return 'Object position unlocked';
        }
    }
}
?>
