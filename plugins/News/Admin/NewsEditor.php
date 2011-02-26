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
class NewsEditor extends Page {
    public $privilegeGroup = 'Administrationpages';


    function canEdit($obj) {
        return is_a($obj, 'NewsItem');
    }

    function editTab() {
        return array(
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
        );
    }



    function translate() {
        $translate = array();
        if(!$this->saved_content['Text']) {
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
        return $translation;
    }

    function saveChanges() {
        $_POST->setType('etitle', 'string');
        $_POST->setType('etxt', 'string');
        $_POST->setType('estart', 'any');
        $_POST->setType('eend', 'any');
        if(!$_POST['etitle']) {
            Flash::create(__('Please enter a title'));
            return;
        }
        if(!$_POST['etxt']) {
            Flash::create(__('Please enter a text'));
            return;
        }
        $this->that->Name     = $_POST['etitle'];
        $this->that->Image    = $_POST['eimg'];
        $this->that->setActive(Short::parseDateAndTime('estart'), Short::parseDateAndTime('eend', false));

        $this->that->saveContent(array('Text' => $_POST['etxt']));
        $Controller->forceReload($this->that);
        Flash::create(__('Your data was saved'), 'confirmation');
    }
}
