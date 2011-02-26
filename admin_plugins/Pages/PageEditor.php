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
    static public $edit_icon = 'small/page_edit';
    static public $edit_text = 'Edit page';

    private $that = false;

    /**
     * Sets up the object and makes sure that it's present in both the menu->makers and the menu->editors configuration sections.
     * @param integer $id The ID of the object
     * @return void
     */
    function __construct($obj){
        parent::__construct($obj->ID);
        $this->that = $obj;
    }

    function run() {
        __autoload('Form');
        if($this->saveChanges()) redirect(array('id' => $this->that->ID));
        global $Templates;
        $this->setContent('main',
            Form::quick(false, null,
                $this->editTab()
            )
        );
        $Templates->render();
    }

    function editTab() {
        global $Templates;
        $_POST->setType('commentsEnabled', 'bool');
        return array(
            new Formsection('Content',
                new Input(__('Title'), 'title', $this->that->Name),
                new HTMLField(false, 'content', $this->that->getContent('main'))
            ),
            new Formsection('Other',
                new Input(__('Aliases'), 'alias', implode(',',$this->that->aliases)),
                new Checkbox(__('Comments enabled'), 'commentsEnabled', (isset($_POST['commentsEnabled'])?true:@$this->that->settings['comments'])),
                Short::selectTemplate($this->that->template)
            )
        );
    }

    function saveChanges() {
        $_POST->setType('title', 'string');
        $_POST->setType('content', 'any');
        $_POST->setType('template', 'string');
        $_POST->setType('alias', 'string');

        if(!$_POST['title']) return false;
        $this->that->Name = $_POST['title'];
        $this->that->saveContent(array('main' => $_POST['content']));
        $this->that->resetAlias(explode(',', $_POST['alias']));
        $this->that->setActive(
            Short::parseDateAndTime('activate'),
            Short::parseDateAndTime('deactivate')
        );
        $this->that->settings['comments'] = isset($_POST['commentsEnabled']);

        if($_POST['template']) {
            $this->that->template = $_POST['template'];
        }
        Flash::queue(__('Your changes were saved'), 'confirmation');
        return true;
    }

    function viewRevisions($page, $l, $sectionMap=false) {
        global $DB, $Controller;
        if(is_numeric($page)) $page = $Controller->{(string)$page}(EDIT);
        $lang = google::languages($l);
        $revisions = array();


        $_REQUEST->setType('rev1', 'numeric', true);
        $_REQUEST->setType('rev2', 'numeric', true);
//FIXME: Move to CSS
        Head::add('ins {background: lightgreen;}
del {background: pink;}
.revlegend {text-align: right;display:inline;margin: 0 0 0 45px;}
.revlegend ins,.revlegend del {margin: 0 5px;}', 'css-raw');

        $r1 = false;
        $r2 = false;
        $r = $DB->content->get(array('id' => $this->that->ID, 'language' => $l), false, false, 'revision DESC');
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


    function canEdit($obj)
    {
        return is_a($obj, 'Page');
    }
}

?>
