<?php

/**
 * @author Jonatan Olofsson [joolo]
 * @version 1.1
 * @license http://creativecommons.org/licenses/by-nc/3.0/ Creative Commons Attribution-Noncommercial 3.0 Unported License
 * @package Content
 */

/**
 * Provides the default solidba.se interface for administrating articles
 * @package Content
 */
class Articles extends Page {
    public $privilegeGroup = 'Administrationpages';
    static $VERSION = 1;
    static public function installable() {return __CLASS__;}
    //static public function uninstallable() {return __CLASS__;}
    static public function upgradable() {return __CLASS__;}

    function upgrade() {}

    function install() {
        global $Controller;
        $Controller->newObj('Articles')->move('last', 'adminMenu');
        return self::$VERSION;
    }

    /**
     * Setup the page
     * @param integer $id The object's ID
     * @return void
     */
    function __construct($id=false){
        parent::__construct($id);
        $this->alias = 'articles';
        $this->suggestName('Articles', 'en');
        $this->suggestName('Artiklar', 'sv');

        $this->icon = 'small/script';
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
        $_REQUEST->setType('asave', 'any');
        $_REQUEST->setType('view', 'string');
        $_REQUEST->setType('edit', array('numeric', '#new#'));
        $_REQUEST->setType('del', 'numeric');
        $_REQUEST->setType('lang', 'string');
        $_POST->setType('atitle', 'string');
        $_POST->setType('apubd', 'string');
        $_POST->setType('apubt', 'string');
        $_POST->setType('atxt', 'any');
        $_POST->setType('apre', 'any');
        
        if($_REQUEST['del']) {
            if($Controller->{$_REQUEST['del']} && $Controller->{$_REQUEST['del']}->delete()) {
                Flash::create(__('Article removed'), 'confirmation');
            }
        }
        
        /**
         * Save newsitem
         */
        do {
            $item = false;
            if($_REQUEST['edit'] && $_REQUEST['asave']) {
                if(is_numeric($_REQUEST['edit'])) {
                    $item = new Article($_REQUEST['edit'], $_REQUEST['lang']);
                    if(!$item || !is_a($item, 'Article') || !$item->mayI(EDIT)) {
                        Flash::create(__('Invalid article'), 'warning');
                        break;
                    }
                }
                if(!$_POST['atitle']) {
                    Flash::create(__('Please enter a title'));
                    break;
                }
                if(!$_POST['atxt']) {
                    Flash::create(__('Please enter a text'));
                    break;
                }
                if($_REQUEST['edit'] === 'new') {
                    $item = $Controller->newObj('Article', $_REQUEST['lang']);
                    $_REQUEST['edit'] = $item->ID;
                }
                if($item) {
                    $item->Name 	= $_POST['atitle'];
                    $item->Publish 	= strtotime($_POST['apubd'] . ', ' . $_POST['apubt']);
                    $item->saveContent(array('Preamble' => $_POST['apre'], 'Text' => $_POST['atxt']));

                    Flash::create(__('Your data was saved'), 'confirmation');
                    $_REQUEST->clear('edit');
                    $_POST->clear('atitle', 'apubd', 'apubt', 'atxt', 'apre');
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
            $this->content = array(	'header' => __('Articles'),
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
            if(!@$obj->content['Preamble'] && !$_POST['apre']) {
                $translate[] = 'Preamble';
            }
            if(!@$obj->content['Text'] && !$_POST['atxt']) {
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
            
            if(!$obj->Name && !$_POST['atitle']) {
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
            $this->setContent('main', 
                '<div class="nav"><a href="'.url(null, array('id', 'edit')).'">'.icon('small/arrow_left').__('Back').'</a></div>'
                .$form->collection(
                    new Hidden('asave', 1),
                    new Hidden('edit', $id),
                    new Set(
                        new Hidden('lang', $language),
                        new FormText(__('Language'), google::languages($language)),
                        (empty($translation)?null:'<span class="warning">'.__('Warning - Some of the text has been automatically translated').'</span>'),
                        new Input(__('Title'), 'atitle', ($_POST['atitle']?$_POST['atitle']:($obj->Name?$obj->Name:@$translation['Name']))),
                        new Li(
                        //FIXME: strftime
                            new Datepicker(__('Publish'), 'apubd', ($_POST['apubd']?$_POST['apubd']:($obj->Publish ? date('Y-m-d', $obj->Publish):''))),
                            new Timepickr(false, 'apubt', ($_POST['apubt']?$_POST['pubt']:($obj->Publish ? date('H:i', $obj->Publish) : '')))
                        ),
                        new htmlfield(__('Text'), 'atxt', ($_POST['atxt']?$_POST['atxt']:(@$obj->content['Text']?@$obj->content['Text']:@$translation['Text']))),
                        new htmlfield(__('Preamble'), 'apre', ($_POST['apre']?$_POST['apre']:(@$obj->content['Preamble']?@$obj->content['Preamble']:@$translation['Preamble'])))
                    )
                ));
        } else {
            PageEditor::saveChanges($obj);
            $this->setContent('main', PageEditor::editor($id));
        }
    }

    /**
     * @return string
     */
    private function mainView() {
        global $USER, $CONFIG, $DB, $Controller;
        
        $aList = array();
        
        $total = $DB->getCell("SELECT DISTINCT COUNT(*) FROM updates AS t1 
            LEFT JOIN spine sp ON sp.id = t1.id
            LEFT JOIN updates t2 ON t1.id = t2.id
            AND t1.edited < t2.edited
            WHERE t2.edited IS NULL
            AND sp.class = 'Article'
            ORDER BY t1.edited DESC");
        $perpage = 20;
        $pager = Pagination::getRange($perpage, $total);
        
        $r = $DB->query("SELECT DISTINCT sp.id FROM updates AS t1 
            LEFT JOIN spine sp ON sp.id = t1.id
            LEFT JOIN updates t2 ON t1.id = t2.id
            AND t1.edited < t2.edited
            WHERE t2.edited IS NULL
            AND sp.class = 'Article'
            ORDER BY t1.edited DESC
            LIMIT ".$pager['range']['start'].", ".$perpage);
        
        while(false !== ($article = $DB->fetchAssoc($r))) {
            $article = $Controller->{$article['id']};
            $aList[] = '<li><span class="fixed-width">'.$article->Name.'</span><div class="tools">'.icon('small/eye', __('View'), url(array('id' => $article->ID))).icon('small/pencil', __('Edit'), url(array('edit' => $article->ID), array('id'))).icon('small/delete', __('Delete'), url(array('del' => $article->ID), 'id')).'</div></li>';
        }
        $aList = listify($aList);
        if($total > $perpage) {
            $aList .= $pager['links'];
        }
        
        
        $form = new Form('newArticle');
        $calendarSettings->params = 'collapsible:true,active:false';
        return new Tabber('events',	__('Article manager'),$aList,
                            __('New article'),
                            $form->collection(
                                new Hidden('asave', 1),
                                new Hidden('edit', ($_REQUEST['edit'] ? $_REQUEST['edit'] : 'new')),
                                new Set(
                                    new Select(__('Language'), 'lang', google::languages($CONFIG->Site->languages), $USER->settings['language']),
                                    new Input(__('Title'), 'atitle'),
                                    new Li(
                                        new Datepicker(__('Publish'), 'apubd'),
                                        new Timepickr(false, 'apubt')
                                    ),
                                    new htmlfield(__('Text'), 'atxt'),
                                    new htmlfield(__('Preamble'), 'apre')
                                )
                            ));
    }
}

?>
