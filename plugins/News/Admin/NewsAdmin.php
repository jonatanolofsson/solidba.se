<?php

/**
 * @author Jonatan Olofsson [joolo]
 * @version 1.1
 * @license http://creativecommons.org/licenses/by-nc/3.0/ Creative Commons Attribution-Noncommercial 3.0 Unported License
 * @package Content
 */
/**
 * Provides the default solidba.se interface for administrating news
 * @package Content
 */
class NewsAdmin extends Page {
    public $privilegeGroup = 'Administrationpages';
    static $VERSION = 1;
    static public function installable() {return __CLASS__;}
    //static public function uninstallable() {return __CLASS__;}
    static public function upgradable() {return __CLASS__;}

    function upgrade() {}

    function install() {
        global $Controller, $CONFIG;
        $CONFIG->News->setDescription('Preamble_size', 'Size of preamble');
        $Controller->newObj('NewsAdmin')->move('last', 'adminMenu');
        return self::$VERSION;
    }

    /**
     * Setup the page
     * @param integer $id The object's ID
     * @return void
     */
    function __construct($id=false){
        parent::__construct($id);
        $this->alias = 'newsAdmin';
        $this->suggestName('News');

        $this->icon = 'small/newspaper';
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
        $_POST->setType('einscal', 'any');
        $_POST->setType('etitle', 'string');
        $_POST->setType('activated', 'any');
        $_POST->setType('elocked', 'any');
        $_POST->setType('eimg', 'numeric');
        $_POST->setType('etxt', 'any');
        $_POST->setType('eupdate', 'any');
    
        if($_REQUEST['del']) {
            if($Controller->{$_REQUEST['del']} && $Controller->{$_REQUEST['del']}->delete()) {
                Flash::create(__('Newsitem removed'), 'confirmation');
            }
        }
        
        /**
         * Save newsitem
         */
        do {
            $start = $stop = 0;
            $item = false;
            if($_REQUEST['edit'] && $_REQUEST['esave']) {
                if(is_numeric($_REQUEST['edit'])) {
                    $item = new NewsItem($_REQUEST['edit'], $_REQUEST['lang']);
                    if(!$item || !is_a($item, 'NewsItem') || !$item->mayI(EDIT)) {
                        Flash::create(__('Invalid newsitem'), 'warning');
                        break;
                    }
                }
                //FIXME: Further validation?
                if($_POST['einscal']) {
                    if(($start=Short::parseDateAndTime('cstart')) === false) {
                        Flash::create(__('Invalid starttime'), 'warning');
                        break;
                    }
                    if(($stop=Short::parseDateAndTime('cend')) === false)
                    {
                        $stop = $start += 3600;
                    }
                }
                if(!$_POST['etitle']) {
                    Flash::create(__('Please enter a title'));
                    break;
                }
                if(!$_POST['etxt']) {
                    Flash::create(__('Please enter a text'));
                    break;
                }
                if($_REQUEST['edit'] === 'new') {
                    $item = $Controller->newObj('NewsItem', $_REQUEST['lang']);
                    $_REQUEST['edit'] = $item->ID;
                }
                if($item) {
                    $item->Name 	= $_POST['etitle'];
                    $item->Image	= $_POST['eimg'];
                    $item->setActive(Short::parseDateAndTime('estart'), Short::parseDateAndTime('eend', false));
                    $_REQUEST->clear('estart', 'eend');
                    $item->Activated = isset($_POST['activated']);
                    $item->LockedPos = isset($_POST['elocked']);
                    $item->saveContent(array('Text' => $_POST['etxt']));
                    if($_POST['einscal']) {
                        if($item->Cal) {
                            Calendar::editEvent($item->Cal, $_POST['etitle'], $_POST['etxt'], false, $start, $stop);
                        } else {
                            $item->Cal	= Calendar::newEvent($_POST['etitle'], $_POST['etxt'], false, $start, $stop, 'News');
                        }
                    }
                    if(!$_POST['eupdate']) Flow::touch($item->ID, 'News');
                    $Controller->forceReload($item);
                    Flash::create(__('Your data was saved'), 'confirmation');
                    $_REQUEST->clear('edit');
                    $_POST->clear('einscal', 'etitle', 'etxt', 'cstart', 'cend', 'estart', 'eend');
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
            $this->content = array(	'header' => __('News'),
                                    'main' => $this->mainView());
        }

        $Templates->admin->render();
    }
    
    function editView($id, $language) {
        global $Controller, $DB;
        $obj = new NewsItem($id, $language);
        if(!$obj) return false;
        if(!$obj->mayI(EDIT)) errorPage(401);
            
        $this->setContent('header', __('Editing').' <i>"'.$obj.'"</i>');
        
        if($_REQUEST['view'] == 'content') {
            $form = new Form('editN');
                
            $translate = array();
            if(!@$obj->content['Text'] && !$_POST['etxt']) {
                $translate[] = 'Text';
            }
            $trFrom = $trSect = $trText = array();
            if(!empty($translate)) {
                $newest = $DB->asArray("SELECT t1.section, t1.* FROM content AS t1
                    LEFT JOIN content t2 ON t1.section = t2.section
                    AND t1.language = t2.language
                    AND t1.revision < t2.revision
                    WHERE t2.section IS NULL
                    AND t1.id='".Database::escape($id)."'
                    AND (t1.section='".implode("' OR t1.section='", Database::escape($translate, true))."')
                    ORDER BY t1.revision DESC", true);
                
                foreach($newest as $s => $translation) {
                    $trFrom[] = $translation['language'];
                    $trText[] = $translation['content'];
                    $trSect[] = $s;
                }
            }
            
            if(!$obj->Name && !$_POST['etitle']) {
                if($info = $DB->metadata->getRow(array('id' => $obj->ID, 'field' => 'Name'), 'value, metameta')) {
                    $trFrom[] = $info['metameta'];
                    $trText[] = $info['value'];
                    $trSect[] = 'Name';
                }
            }
            $translation = array();
            if(!empty($trText)) {
                $translation = @array_combine($trSect, google::translate($trText, $trFrom, $language));
            }
            $cal = false;
            if($obj->Cal) $cal = Calendar::getEvent($obj->Cal);
            $calendarSettings = new Accordion(__('Calendar settings'),
                new Set(
                    new Checkbox(__('Insert into calendar'), 'einscal', ($_POST['einscal']?true:$cal)),
                    Short::datetime(__('Starts'), 'cstart', @$cal->start),
                    Short::datetime(__('Ends'), 'cend', @$cal->end)
                )
            );
            $calendarSettings->params = 'collapsible:true'.($cal||$_POST['einscal']?'':',active:false');
            $active = $obj->getActive();
            $locked = $obj->LockedPos;
            $this->setContent('main', 
                '<div class="nav"><a href="'.url(null, array('id', 'edit')).'">'.icon('small/arrow_left').__('Back').'</a></div>'
                .$form->collection($calendarSettings,
                    new Hidden('esave', 1),
                    new Hidden('edit', $id),
                    new Set(
                        new Hidden('lang', $language),
                        new FormText(__('Language'), google::languages($language)),
                        (empty($translation)?null:'<span class="warning">'.__('Warning - Some of the text has been automatically translated').'</span>'),
                        new Input(__('Title'), 'etitle', ($_POST['etitle']?$_POST['etitle']:($obj->Name?$obj->Name:@$translation['Name']))),
                        new Li(
                            Short::datetime(__('Publish'), 'estart', $active['start']),
                            ($obj->mayI(PUBLISH)?new Minicheck(__('Activate post'), 'activated', ($obj->Activated || $obj->Activated === '' || isset($_POST['activated']))):null)
                        ),
                        Short::datetime(__('Hide'), 'eend', $active['stop']),
                        new Checkbox(__('Locked position'), 'elocked', $locked),
                        new ImagePicker(__('Image'), 'eimg',($_POST['eimg']?$_POST['eimg']:$obj->Image)),
                        new htmlfield(_('Text'), 'etxt', ($_POST['etxt']?$_POST['etxt']:(@$obj->content['Text']?@$obj->content['Text']:@$translation['Text']))),
                        new Checkbox(__('Avoid updating time'), 'eupdate')
                    )
                ));
        } else {
            PageEditor::saveChanges($obj);
            $this->setContent('main', PageEditor::editor($id, null, $this->ID));
        }
    }

    /**
     * @return string
     */
    private function mainView() {
        global $USER, $CONFIG, $DB, $Controller;
        
        $newsList = array();
        
        $total = $DB->getCell("SELECT DISTINCT count(*) FROM updates AS t1 
            LEFT JOIN spine sp ON sp.id = t1.id
            LEFT JOIN updates t2 ON t1.id = t2.id
            AND t1.edited < t2.edited
            WHERE t2.edited IS NULL
            AND sp.class = 'NewsItem'
            ORDER BY t1.edited DESC");
        
        $perpage = 20;
        $pager = Pagination::getRange($perpage, $total);
        
        $r = $DB->query("SELECT DISTINCT sp.id FROM updates AS t1 
            LEFT JOIN spine sp ON sp.id = t1.id
            LEFT JOIN updates t2 ON t1.id = t2.id
            AND t1.edited < t2.edited
            WHERE t2.edited IS NULL
            AND sp.class = 'NewsItem'
            ORDER BY t1.edited DESC
            LIMIT ".$pager['range']['start'].", ".$perpage);
        
        while(false !== ($newsItem = $DB->fetchAssoc($r))) {
            if($newsItem = $Controller->{$newsItem['id']}(EDIT))
                $newsList[] = '<span class="fixed-width">'.$newsItem->Name.'</span>'
                .'<div class="tools">'
                    .icon('small/eye', __('View'), url(array('id' => $newsItem->ID)))
                    .icon('small/pencil', __('Edit'), url(array('edit' => $newsItem->ID), array('id')))
                    .icon('small/delete', __('Delete'), url(array('del' => $newsItem->ID), 'id'))
                    .($newsItem->LockedPos?icon('small/lock', __('Locked position')):'')
                    .(!$newsItem->Activated?icon('small/flag_red', __('Not activated')):'')
                .'</div>';
        }
        $newsList = listify($newsList);
        if($total > $perpage) {
            $newsList .= $pager['links'];
        }
        
        
        $form = new Form('newEvent');

        $calendarSettings = new Accordion(__('Calendar settings'),
            new Set(
                new Checkbox(__('Insert into calendar'), 'einscal'),
                Short::datetime(__('Starts'), 'cstart'),
                Short::datetime(__('Ends'), 'cend')
            )
        );
        $calendarSettings->params = 'collapsible:true,active:false';
        return new Tabber('events',	__('News manager'),$newsList,
                            __('New newsitem'),
                            $form->collection($calendarSettings,
                                new Hidden('esave', 1),
                                new Hidden('edit', ($_REQUEST['edit'] ? $_REQUEST['edit'] : 'new')),
                                new Set(
                                    new Select(__('Language'), 'lang', google::languages($CONFIG->Site->languages), $USER->settings['language']),
                                    new Input(__('Title'), 'etitle'),
                                    new ImagePicker(__('Image'), 'eimg'),
                                    new Li(
                                        Short::datetime(__('Publish'), 'estart'),
                                        ($this->mayI(PUBLISH)?new Minicheck(__('Activate post'), 'activated', true):null)
                                    ),
                                    Short::datetime(__('Hide'), 'eend'),
                                    new htmlfield(_('Text'), 'etxt')
                                )
                            ));
    }
}

?>
