<?php

/**
 * @author Jonatan Olofsson [joolo]
 * @version 1.0
 * @license http://creativecommons.org/licenses/by-nc/3.0/ Creative Commons Attribution-Noncommercial 3.0 Unported License
 * @package Content
 */

/**
 * The PageEditor provides an interface for editing pages
 * @package Content
 */
class PageEditor extends Page{
    public $privilegeGroup = 'Administrationpages';

    /**
     * Sets up the object and makes sure that it's present in both the menu->makers and the menu->editors configuration sections.
     * @param integer $id The ID of the object
     * @return void
     */
    function __construct($id = false){
        parent::__construct($id);

        /**
         * User input types
         */
        $_REQUEST->addType('edit', array('numeric','#^new$#'));
        global $CURRENT, $DB, $CONFIG;
        if(is_numeric($id)) {
            $this->deletable = false;
            $this->icon = 'small/page_edit';
            $this->alias = $alias = 'pageEditor';

            /**
             * User input types
             */
            $_REQUEST->setType('edit', 'string');

            //FIXME: Move to Installer
            MenuEditor::registerMaker($alias,'large/newfont-32','New static page', array('id' => $this->ID,'edit' => 'new'));
            MenuEditor::registerEditor('#(?!MenuSection)#',$alias,$this->icon,'Edit',array('id' => $this->ID),'edit');
            MenuEditor::registerEditor('#.#','addSubPage','small/add','Add static subpage',array('id' => $this->ID, 'edit' => 'new'),'parent');
        }

        if(!isset($CURRENT)) {
            $this->link  = url(array("id" => $this->ID,
                                     "edit" => $this->ID));
        }
        else {
            $this->link  = url(array("id" => $this->ID,
                                     "edit" => $CURRENT->ID));
        }
        $this->suggestName('Edit page');
    }

    /**
     * Overrides permission-test. If the user has privilege to EDIT the page, access is granted to the tool to do so.
     */
    function may($u, $a) {
        global $Controller, $ID, $CURRENT, $USER;
        if(($a & READ) &&
        (($ID == $this->ID && $_REQUEST->numeric('edit')
            && $Controller->{(string) $_REQUEST['edit']}(EDIT))
        || ($ID != $this->ID && is_a($CURRENT, 'Page') && $CURRENT->may($USER, EDIT)))) return true;
        else return parent::may($u, $a);
    }

    /**
     * Most actions of the module are here, along with the pageview logic
     * and template rendering
     */
    function run() {
        global $Templates;
        $this->content = $this->edit();
        $Templates->render();
    }

    function saveChanges(&$page) {
        global $Controller;
        $changes = false;
        /**
         * User input types
         */
        $_REQUEST->setType('lang', 'string');
        $_POST->addType('PageSettings', 'any');
        $_POST->addType('contentbody', 'any');
        $_REQUEST->addType('revisions', 'any');
        $_REQUEST->setType('rev1', 'numeric', true);
        $_POST->setType('revert1', 'numeric', true);
        $_REQUEST->setType('rev2', 'numeric', true);
        $_POST->setType('revert2', 'numeric', true);
        $_POST->setType('commentsEnabled', 'string');
        $_POST->setType('alias', 'string');
        $_POST->setType('template', 'string');
        $_POST->addType('content', 'any', true);
        $_POST->setType('trnslok', 'any', true);
        $_POST->setType('forceNewRevision', 'any');
        $_POST->setType('trnsl', 'string', true);

        if($page) {
            $p = is_a($page, 'Page');
            $class = get_class($page);
            //FIXME: Problem?
            if(!$page->mayI(EDIT)) return false;
            /**
             * Save changes
             */
            if($_POST['PageSettings']) {
                $page->resetAlias(explode(',', $_POST['alias']));
                $page->setActive(Short::parseDateAndTime('activate'), Short::parseDateAndTime('deactivate'));
                if($p) {
                    $page->settings['comments'] = isset($_POST['commentsEnabled']);
                    $page->template = $_POST['template'];
                }
                $userform = new UserForm($page);
                $userform->saveForm();
                $changes = true;
            }
            if($p && $_POST['contentbody'] && $_POST['content']) {
                foreach($_POST['content'] as $code => $t) {
                    $c = array();
                    foreach($t as $s => $tsc) {
                        if(isset($_POST['trnslok'][$code][$s])){
                            $c[$s] = $tsc;
                        }
                    }
                    $cpage = new $class($page->ID, $code);
                    $cpage->saveContent($c);
                    $changes = true;
                }
            }
            if($_POST['trnsl']) {
                foreach($_POST['trnsl'] as $code => $t) {
                    $cpage = new $class($page->ID, $code);
                    if(isset($t['titleok'])) {
                        if(!empty($t['title'])) {
                            $cpage->Name = $t['title'];
                            $changes = true;
                        }
                    }
                    if(isset($t['descok'])) {
                        $cpage->description = $t['desc'];
                        $changes = true;
                    }
                    if(isset($t['tags'])) {
                        $tags = explode(',',str_replace(' ', ',', $t['tags']));
                        $ttags = array();
                        foreach($tags as $tag) {
                            if($tag && !in_array($tag, $ttags)) {
                                $ttags[] = $tag;
                            }
                        }
                        $cpage->setTags($ttags);
                        $changes = true;
                    }
                }
            }

            $restore = false;
            if($_POST['revert1']) $restore = $_REQUEST['rev1'];
            if($_POST['revert2']) $restore = $_REQUEST['rev2'];
            if($restore) {
                $lPage = new Page($_REQUEST['edit'], $_REQUEST['lang']);
                if($lPage->mayI(EDIT)) {
                    foreach($restore as $section => $revision) {
                        $lPage->restoreRevision($section, $revision, isset($_POST['forceNewRevision']));
                    }
                    $changes = true;
                }
            }
            $Controller->forceReload($page);
        }
        $_REQUEST->setType('newFormField', 'any');
        if($changes && !$_REQUEST['newFormField']) {
            redirect(url(array('ok' => 1), array('id', 'edit')));
        }
    }

    function edit($edit = false) {
        global $USER;
        $_REQUEST->addType('edit', array('numeric','#^new$#'));
        $_REQUEST->setType('view', '#content|revisions#');
        $_REQUEST->setType('trnsl', 'string', true);
        $_REQUEST->setType('ret', 'string');
        $_REQUEST->setType('parent', 'numeric');
        $_REQUEST->setType('ok', 'numeric');
        $_REQUEST->setType('lang', 'string');
        
        if(!$edit) $edit = $_REQUEST['edit'];
        $new = ($edit == 'new');

        if($this->mayI(READ) && $edit) {
            global $Controller, $DB, $Templates;
            $page=false;
            if($edit === 'new') {
                if($_REQUEST->validNotEmpty('trnsl')) {
                    $page = $Controller->newObj('Page');
                    $page->move('last', $_REQUEST['parent']);
                    $_REQUEST['edit'] = $edit = (string)$page->ID;
                }
            } else {
                if(!is_object($edit))
                    $page = $Controller->{$edit}(EDIT);
                else $page = $edit;
                if(!is_a($page, 'MenuItem') || is_a($page, 'MenuSection') ||
                    ($_REQUEST['view'] && !$_REQUEST['lang'])) errorPage('Invalid input');
                    
                if($_REQUEST['ok']) Flash::create(__('Your changes were saved'), 'confirmation');
            }

            if($page) {
                PageEditor::saveChanges($page);
                if($new && $_REQUEST['trnsl']) redirect(url(array('edit' => $page->ID), array('id')));
            }

            /**
             * Pageview logic
             */
            if($_REQUEST['edit'] == 'new') {
                return array(	'header' => __('New page'),
                                'main' => $this->editor('new'));
            } else {
                if(!is_object($edit))
                    $page = $Controller->{$edit}(EDIT);
                else $page = $edit;
                
                return array(	'header' => __('Editing').": ".$page.($_REQUEST['lang']?' ['.google::languages($_REQUEST['lang']).']':''),
                                            'main' => ($_REQUEST['view'] == 'content'
                                                        ? PageEditor::contentEditor($page, $_REQUEST['lang'])
                                                        : PageEditor::editor($page)));
            }
        }
    }

    function contentEditor($page, $l, $sectionMap=false, $return=false) {
        if(!$return && $_REQUEST['ret']) $return = $_REQUEST['ret'];
        
        global $DB, $Templates;
        $lang = google::languages($l);
        $r = $DB->query("SELECT t1.* FROM content AS t1
            LEFT JOIN content t2 ON t1.section = t2.section
            AND t1.revision < t2.revision
            AND t1.language = t2.language
            AND t1.id = t2.id
            WHERE t2.section IS NULL
            AND t1.id='".Database::escape($page->ID)."'
            AND t1.language='".$l."'
            ORDER BY t1.revision DESC");

        $csections = array();
        while($row = Database::fetchAssoc($r)) {
            $csections[] = $row['section'];
            $content[$row['section']] = $row;
        }
        $csections = (array)@array_unique((array)@$csections);
        if(!$sectionMap) {
            $sections = $Templates->pageTemplate($page)->sections;
            $sections = array_combine($sections, $sections);
            asort($sections);
        }
        else {
            $sections = $sectionMap;
        }

        $newest = $DB->asArray("SELECT t1.section, t1.* FROM content AS t1
            LEFT JOIN content t2 ON t1.section = t2.section
            AND t1.language = t2.language
            AND t1.revision < t2.revision
            AND t1.id = t2.id
            WHERE t2.section IS NULL
            AND t1.id='".Database::escape($page->ID)."'
            ORDER BY t1.revision DESC", true);


        /**
        * Content-tabs
        */

        $diff = array_diff(array_keys($sections), $csections);
        $trFrom = array();
        $trText = array();
        $trSect = array();
        if($diff) {
            foreach($diff as $s) {
                if(isset($newest[$s])) {
                    $trFrom[] = $newest[$s]['language'];
                    $trText[] = $newest[$s]['content'];
                    $trSect[] = $s;
                }
            }
            $translation = @array_combine($trSect, google::translate($trText, $trFrom, $l));
        }
        foreach($sections as $sid => $s){
            $contentArray[] = new Tab($s,
                    (isset($translation[$sid])
                        ? new Checkbox(__('Sanction'), 'trnslok['.$l.']['.$sid.']', isset($_REQUEST['trnslok'][$l][$sid]))
                        : new Hidden('trnslok['.$l.']['.$sid.']','new')),
                    new htmlField(null, 'content['.$l.']['.$sid.']',
                        (isset($_REQUEST['content'][$l][$sid])
                            ? $_REQUEST['content'][$l][$sid]
                            : (isset($content[$sid])
                                ? $content[$sid]['content']
                                : @$translation[$sid]))));
        }

        $cForm = new Form('contentbody', url(null, array('id', 'edit', 'view', 'lang', 'ret')));
        return '<div class="nav">'.Short::backn()
                    .'<a href="'.url(array('id' => $page->ID)).'">'.icon('small/page_go').__('To page').'</a>'
                    .'<a href="'.url(null,array('id','edit')).'">'.icon('small/page_edit').__('Edit metadata').'</a>'
                .'</div>'
        .$cForm->collection(new Hidden('lang', $l),
            new Tabber('c'.$l, $contentArray));
    }

    function viewRevisions($page, $l, $sectionMap=false) {
        global $DB, $Controller;
        if(is_numeric($page)) $page = $Controller->{(string)$page}(EDIT);
        $lang = google::languages($l);
        $revisions = array();
        
        
        $_REQUEST->setType('rev1', 'numeric', true);
        $_REQUEST->setType('rev2', 'numeric', true);
        
        Head::add('ins {background: lightgreen;}
del {background: pink;}
.revlegend {text-align: right;display:inline;margin: 0 0 0 45px;}
.revlegend ins,.revlegend del {margin: 0 5px;}', 'css-raw');

        $r1 = false;
        $r2 = false;
        $r = $DB->content->get(array('id' => $page->ID, 'language' => $l), false, false, 'revision DESC');
        while($rev = Database::fetchAssoc($r)) {
            $revisions[$rev['section']][$rev['revision']] = strftime('%c',$rev['revision']);
            if($_REQUEST['rev1'][$rev['section']] === $rev['revision']) $r1[$rev['section']] = $rev;
            if($_REQUEST['rev2'][$rev['section']] === $rev['revision']) $r2[$rev['section']] = $rev;
        }
        $revArray = array();
        if($revisions) {
            foreach($revisions as $sectionName => $sectContent) {
                $revArray[] = new Tab(($sectionMap&&isset($sectionMap[$sectionName])?$sectionMap[$sectionName]:$sectionName),
                    new Li(
                        new Select('View revision', 'rev1['.$sectionName.']', $sectContent, $_REQUEST['rev1'][$sectionName], false, __('None')),
                        new Submit('Revert to this', 'revert1['.$sectionName.']')
                    ),
                    new Li(
                        new Select('Compare to', 'rev2['.$sectionName.']', $sectContent, $_REQUEST['rev2'][$sectionName], false, __('None')),
                        new Submit('Revert to this', 'revert2['.$sectionName.']')
                    ),
                    (isset($r1[$sectionName])
                        ?	'<div class="revlegend"><ins>'.strftime('%c',$r1[$sectionName]['revision']).'</ins>'
                                .(isset($r2[$sectionName])
                                    ?'<del>'.strftime('%c',$r2[$sectionName]['revision']).'</del></div>'
                                        .'<div id="revdiff">'.diff($r2[$sectionName]['content'], $r1[$sectionName]['content']).'</div>'
                                    :'<div id="revdiff">'.strip_tags($r1[$sectionName]['content'], '<p><div>').'</div>')
                        :	null)
                );
            }
            $rForm = new Form('revisionsForm', url(null, array('id', 'edit', 'view', 'lang'), false));
            return '<div class="nav">'.Short::backn() .'</div>'
            .$rForm->collection(new Hidden('lang', $l),
                new Tabber('r'.$l, $revArray));
        } else return '<div class="nav">'.Short::backn().'</div>'
        .__('There are no saved revisions for this page and language');
    }

    function editor($page, $extraTabs = null, $contentEditor = 'pageEditor'){
        if($_REQUEST['view'] == 'revisions') return self::viewRevisions($page, $_REQUEST['lang']);

        if(!$page) return false;
        global $DB, $Templates, $Controller, $USER, $CONFIG;
        $contentArray = array();
        $revisionArray = array();
        $comments = array();
        $mTabs = array();
        $revisions = array();
        $languages = google::languages((array)$CONFIG->Site->languages);
        ksort($languages);
        $langCList = '';
        $i=1;
        $p=true;

        if($page !== 'new') {
            if(!is_object($page)) $page = $Controller->{(string)$page}(EDIT);
            if(!$page) return false;
            if(!$page->mayI(EDIT)) return false;
            $p = is_a($page, 'Page');

            /**
            * Metadata
            */

            $sanctionedMeta = array('title' => $DB->metadata->asList(array('id' => $page->ID, 'field' => 'Name'), 'metameta,value', false, true),
                                    'desc' => $DB->metadata->asList(array('id' => $page->ID, 'field' => 'description'), 'metameta,value', false, true));

            $r = $DB->tags->get(array('id' => $page->ID));
            while(false !== ($tag = Database::fetchAssoc($r))) {
                $Tags[$tag['lang']][] = $tag['tag'];
            }

            $nst = array_diff($CONFIG->Site->languages, array_keys($sanctionedMeta['title']));
            $nsd = array_diff($CONFIG->Site->languages, array_keys($sanctionedMeta['desc']));

            if(count($nst)) {
                if($page->Name) {
                    $metaTranslation['title'] = @array_combine($nst, google::translate($page->Name, $page->loadedLanguage, $nst));
                } else {
                    if($info = $DB->metadata->getRow(array('id' => $page->ID, 'field' => 'Name'), 'value, metameta'))
                        $metaTranslation['title'] = @array_combine($nst, google::translate($info['value'], $info['metameta'], $nst));
                    else $metaTranslation['title'] = array();
                }
            }
            if(count($nsd)) {
                if($page->description)
                    $metaTranslation['desc'] = @array_combine($nsd, google::translate($page->description, $page->loadedLanguage, $nsd));
                else {
                    if($info = $DB->metadata->getRow(array('id' => $page->ID, 'field' => 'description'), 'value, metameta')) {
                        $metaTranslation['desc'] = @array_combine($nsd, google::translate($info['value'], $info['metameta'], $nsd));
                    }
                    else $metaTranslation['desc'] = array();
                }
            }

            $Template = $Templates->pageTemplate($page);
        } else {
            $Template = $Templates->default;
        }

        ksort($languages);
        foreach($languages as $l => $lang) {
            /* Metadata-tab */
            $mTabs[] = new Tab(__($lang),
                (isset($sanctionedMeta['title'][$l]) || $page === 'new' || !@$metaTranslation['title']
                    ?	new Li(
                            new Input(__('Title'), 'trnsl['.$l.'][title]', (isset($_REQUEST['trnsl'][$l]['title'])?$_REQUEST['trnsl'][$l]['title']:@$sanctionedMeta['title'][$l])),
                            new Hidden('trnsl['.$l.'][titleok]', 1)
                        )
                    :	new Li(
                            new Input(__('Title'), 'trnsl['.$l.'][title]', (isset($_REQUEST['trnsl'][$l]['title'])?$_REQUEST['trnsl'][$l]['title']:@$metaTranslation['title'][$l])),
                            new Minicheck(__('Sanction'), 'trnsl['.$l.'][titleok]')
                        )
                ),
                (isset($sanctionedMeta['desc'][$l]) || $page === 'new' || !@$metaTranslation['desc']
                ?	new Li(
                        new TextArea(__('Description'), 'trnsl['.$l.'][desc]', (isset($_REQUEST['trnsl'][$l]['desc'])?$_REQUEST['trnsl'][$l]['desc']:@$sanctionedMeta['desc'][$l])),
                        new Hidden('trnsl['.$l.'][descok]', 1)
                    )
                :	new Li(
                        new TextArea(__('Description'), 'trnsl['.$l.'][desc]', (isset($_REQUEST['trnsl'][$l]['desc'])?$_REQUEST['trnsl'][$l]['desc']:@$metaTranslation['desc'][$l])),
                        new Minicheck(__('Sanction'), 'trnsl['.$l.'][descok]')
                    )
                ),
                new input(__('Tags'), 'trnsl['.$l.'][tags]', (isset($_REQUEST['tags'])?$_REQUEST['tags']:@join(',',@$Tags[$l])))
            );


            /* Content-tab */
            if(is_a($page, 'Page')) {
                $langCList .= '<li class="'.($i++%2?'odd':'even').'"><span class="fixed-width"><a href="'.url(array('id' => $contentEditor, 'view' => 'content', 'lang' => $l), array('edit', 'ret'), false).'">'.__($lang).'</a></span><div class="tools">'.icon('small/disk_multiple', __('History'), url(array('id' => 'pageEditor', 'view' => 'revisions', 'lang' => $l), array('edit', 'ret'), false)).'</div></li>';
            }


            /* Comment-tab */
            if($page !== 'new') {
                $tmpC = Comments::edit($page->ID, $l);
                if($tmpC) {
                    $comments[] = new EmptyTab(__($lang),
                        $tmpC
                    );
                }
            }
        }
        /* Content-tab */

        if($p) {
            $cTab = new EmptyTab(__('Content'),
                                '<ul class="list">'.$langCList.'</ul>');
        } else $cTab = null;

        $form = new Form('PageSettings', url(array('edit' => ($page == 'new'?$page:$page->ID)), true));

        if(count($languages) == 1) {
            $mTabs[0]->name = __('Metadata');
            $mTab = $mTabs[0];
        }
        else {
            $mTab = new EmptyTab(	__('Metadata'),
                                    new Tabber('m', $mTabs));
        }

        if($page !== 'new') $userForm = new UserForm($page);
        
        $active = ($page === 'new' 
                        ? array('start' => false, 'stop' => false) 
                        : $page->getActive());

        return 	'<div class="nav">'.Short::backn()
                    .($page !== 'new'
                        ?'<a href="'.url(array('id' => $page->ID)).'">'.icon('small/page_go').__('To page').'</a>'
                        :'')
                    .'</div>'.
                        $form->collection(
                            new Tabber('pe',
                                $mTab,
                                ($page==='new'?null:$cTab),
                                new Tab(	__('Page settings'),
                                        Short::datetime(__('Activate'), 'activate', @$active['start']),
                                        Short::datetime(__('Deactivate'), 'deactivate', @$active['stop']),
                                        new Input(__('Alias'), 'alias', ($_REQUEST->valid('alias')?$_REQUEST['alias']:@join(',', $page->aliases))),
                                        ($p?new select(__('Template'), 'template', array_merge(array(__('System') => array(	'inherit' => __('Inherit'),
                                                                                                                        'default' => __('Default'),
                                                                                                                    'admin' => __('Admin default')
                                                                                                            )),
                                                                                                $Templates->listAll()), ($_REQUEST->valid('template')?$_REQUEST['template']:($page!=='new'?$page->template:'inherit'))):null)
                                ),
                                $extraTabs,
                                ($p&&$page!=='new'?new EmptyTab(__('Form'),
                                        $userForm->editForm(),
                                        $userForm->viewResult()):null),
                                ($p&&$page!=='new'?new Tab(__('Comments'),
                                        new Checkbox(__('Enabled'), 'commentsEnabled', (isset($_REQUEST['commentsEnabled'])?true:@$page->settings['comments'])),
                                        ($comments?
                                        new Tabber('cl',
                                            $comments
                                        ):null)
                                ):null)
                            )
                        );
    }
}

?>
