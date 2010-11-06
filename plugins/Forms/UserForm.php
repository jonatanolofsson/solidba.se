<?php
class UserForm extends Base {
    private $loadedLanguage;
    private $_Form_Title=false;
    private $_Limit=false;
    private $_PostCount=null;
    private $_Public_Form=null;
    private $page = false;

    function __construct($page, $lang=false) {
        if(is_object($page)) $page = $page->ID;
        $this->page = $page;
        if($page == 'new') return;
        parent::__construct($page);
        global $DB;

        global $USER;

        if(!$lang) $this->loadedLanguage = $USER->settings['language'];
        else $this->loadedLanguage = $lang;

        Metadata::injectAll('Limit', 'Form_Title', 'Public_Form');
    }

    function __set($property, $value) {
        if(in_array($property, array('Limit', 'Form_Title', 'Public_Form'))) {
            $this->{'_'.$property} = $value;
        }
    }

    function __get($property) {
        if($property == 'PostCount') {
            if(is_null($this->_PostCount)) {
                global $DB;
                $this->_PostCount = $DB->formdata->count(array('id' => $this->ID, 'field_id' => 'poster'), 'value');
            }
            return $this->_PostCount;
        } else return parent::__get($property);
    }

    function render($force=false) {
        global $DB, $Controller, $USER;
        if((!$force && !$this->isActive('form')) || $USER->ID == NOBODY) return '';

        if($this->_Limit>0 && $this->PostCount >= $this->_Limit) {
            return '<span class="forminfo">'.__('Form posts has reached the limit').'</span>';
        }


        $this->saveFormData();

        $r = $DB->formfields->get(array('id' => $this->ID, 'language' => $this->loadedLanguage), '*', false, 'sort');

        if(!Database::numRows($r)) return '';

        $uForm = new Form('uform');

        $form = array();
        while(false !== ($field = Database::fetchAssoc($r))) {
            $fieldName = 'uform['.$field['field_id'].']';
            $fieldLabel = self::fieldlabel($field['label'], $this->loadedLanguage);
            switch($field['type']) {
                case 'Checkbox':
                case 'pCheckbox':
                    $Values = array_map('trim', explode(',', $field['value']));
                    $Names = array_map('md5', $Values);
                    if(count($Values) > 1) {
                        $form[] = new checkset($fieldLabel, $fieldName, array_combine($Names, $Values), ($field['type'] == 'pCheckbox'));
                    } else {
                        $form[] = new Checkbox($fieldLabel, $fieldName, ($field['type'] == 'pCheckbox'));
                    }
                    break;
                case 'select':
                case 'mselect':
                    $Values = array_map('trim', explode(',', $field['value']));
                    $Names = array_map('md5', $Values);
                    $form[] = new select($fieldLabel, $fieldName, array_combine($Names, $Values), false, ($field['type'] == 'mselect'));
                    break;
                case 'Radio':
                    $Values = array_map('trim', explode(',', $field['value']));
                    $Names = array_map('md5', $Values);
                    $form[] = new Radioset($fieldLabel, $fieldName, array_combine($Names, $Values));
                    break;
                case 'input':
                case 'textarea':
                case 'htmlfield':
                    $form[] = new $field['type']($fieldLabel, $fieldName, $field['value']);
                    break;
                case 'hidden':
                    $form[] = new Hidden($fieldName, $field['value']);
                    break;
            }
        }

        if(empty($form)) return '';
        $form[] = new Hidden('uform[userformsubmittrigger]', 1);

        $a = $this->getActive('form');
        $forminfo = '';
        if(@$a['stop']) {
            $forminfo .= __('Open for submission until').' '.date('Y-m-d H:i', $a['stop']).'. ';
        }
        if($this->_Limit>0) {
            $forminfo .= $this->PostCount . ' ' . __('of') . ' ' . $this->_Limit . ' ' . __('submissions received').'.';
        }
        if($forminfo) {
            $forminfo = '<span class="forminfo">'.$forminfo.'</span>';
        }
        if($this->_Form_Title) $forminfo = '<h2>'.$this->_Form_Title.'</h2>'.$forminfo;
        return $forminfo.$uForm->set($form);
    }

    function may($u, $lvl) {
        global $Controller;
        if(!($myself = $Controller->{(string)$this->ID}(OVERRIDE))) return 0;
        return $myself->may($u, $lvl);
    }

    function saveFormData() {
        global $DB, $Controller, $USER;
        if(!$this->mayI(READ) || !$this->isActive('form')) return false;

        $_POST->setType('uform', 'string', true);
        if(!$_POST['uform']) return false;


        /*
         * Is there a limit to consider?
         */
        if($this->_Limit>0) {
            if($this->PostCount >= $this->_Limit) {
                Flash::create(__('Submissions has reached the limit'), 'warning');
                return false;
            }
        }

        $r = $DB->formfields->get(array('id' => $this->ID));

        $okay = array();
        while(false !==($field = Database::fetchAssoc($r))) {
            if(isset($_POST['uform'][$field['field_id']]) || in_array($field['type'], array('Checkbox', 'pCheckbox'))) {
                $value = '';
                $Possible_Values = array_map('trim', explode(',', $field['value']));
                if(in_array($field['type'], array('select', 'mselect', 'Radio')) || (in_array($field['type'], array('Checkbox', 'pCheckbox')) && count($Possible_Values)>1)) {
                    $key_hash = array_map('md5', $Possible_Values);
                    $Possible_Values = array_combine($key_hash, $Possible_Values);

                    $Legitimate_Values = array();
                    $fv = (array)$_POST['uform'][$field['field_id']];
                    foreach($fv as $fd) {
                        if(isset($Possible_Values[$fd])) {
                            $Legitimate_Values[] = $Possible_Values[$fd];
                        }
                    }
                    $value = join(', ', $Legitimate_Values);
                } elseif(in_array($field['type'], array('pCheckbox', 'Checkbox'))) {
                    $value = isset($_POST['uform'][$field['field_id']]);
                } elseif(!is_array($_POST['uform'][$field['field_id']])) {
                    $value =  $_POST['uform'][$field['field_id']];
                } else continue;


                $okay['field_id'][] = $field['field_id'];
                $okay['value'][] = $value;
            }
        }
        if(!empty($okay)) {
            $duplicate = array();
            foreach($okay['field_id'] as $i => $fieldname) {
                $duplicate[] = "`field_id`='".$fieldname."' AND `value`='".$okay['value'][$i]."'";
            }
            $okay['id'] = $this->ID;
            $okay['post_id'] = uniqid();

            $okay['field_id'][] = 'poster';
            $okay['value'][] = $USER->ID;

            if($DB->getCell("SELECT MAX(`c`) FROM (SELECT COUNT(*) as `c` FROM (
    (SELECT `post_id` FROM `formdata` WHERE `id`='".$this->ID."' AND (`field_id`='poster' AND `value`='".$DB->escape($USER->ID)."')) as `t1`)
    LEFT JOIN formdata USING(`post_id`) WHERE ((".implode(") OR (", $duplicate)."))
GROUP BY `post_id`) as `t2`") == count($duplicate)) {
                Flash::create(__('Duplicate submission'));
            } else {
                $okay['field_id'][] = 'posted';
                $okay['value'][] = time();
                $okay['field_id'][] = 'poster:ip';
                $okay['value'][] = $_SERVER['REMOTE_ADDR'];
                $okay['field_id'][] = 'language';
                $okay['value'][] = $this->loadedLanguage;

                $DB->formdata->insertMultiple($okay);
                Flash::create(__('We have received your submission'), 'confirmation');
                ++$this->_PostCount;
            }
        }
    }

    function fieldlabel($label, $language) {
        if(!strstr($label, ',')) return $label;
        $langs = array_filter(array_map('trim', explode(',', $label)));

        $translation = $langs[0];

        foreach($langs as $i => $lnlbl) {
            $parts = array_map('trim', explode(':',$lnlbl));
            if(count($parts) == 2) {
                if($parts[0] == $language) return $parts[1];
                if(!$i) $translation = $parts[1];
            }
        }
        return $translation;
    }

    function saveForm() {
        $_POST->setType('form', 'string', true);
        $_REQUEST->setType('newFormField', 'any');
        $_REQUEST->setType('delfield', 'string');
        $_POST->setType('form', 'string', true);
        $_POST->setType('ftitle', 'string');
        $_POST->setType('formactivate', 'string', true);
        $_POST->setType('formdeactivate', 'string', true);
        $_POST->setType('formlimit', 'numeric');
        $_POST->setType('formpublic', 'any');

        if(($_POST['form'] || $_POST['nform']) && is_numeric($this->page)) {
            global $DB;
            $order=0;
            if($_POST['form']) {
                foreach($_POST['form'] as $field_id => $field) {
                    if(isset($field['new'])) $field_id = uniqid();
                    $DB->formfields->update(array('type' => $field['type'], 'label' => $field['lbl'], 'value' => $field['val'], 'sort' => $order++), array('id' => $this->ID, 'field_id' => $field_id), true);
                }
            }
            $_POST->clear('nform', 'form');
            Metadata::set(array('Limit' => $_POST['formlimit'], 'Form_Title' => $_POST['ftitle'], 'Public_Form' => $_POST['formpublic'] ));
            $this->_Limit = $_POST['formlimit'];
            $this->_Public_Form = (isset($_POST['formpublic']));
            $this->_Form_Title = $_POST['ftitle'];
            $this->setActive(strtotime($_POST['formactivate']['date'].', '.$_POST['formactivate']['time']),
                    strtotime($_POST['formdeactivate']['date'].', '.$_POST['formdeactivate']['time']),
                    'form');
        }
    }

    function editForm() {
        global $DB, $Controller;

        if(!$this->mayI(EDIT)) return false;

        $this->saveForm();

        if($_REQUEST['delfield']) {
            $DB->formfields->delete(array('id' => $this->ID, 'field_id' => $_REQUEST['delfield']));
            $DB->formdata->delete(array('id' => $this->ID, 'field_id' => $_REQUEST['delfield']));
            $_REQUEST->clear('delfield');
        }


        $fieldTypes = array('input' => __('Text input'),
                            'Checkbox' => __('Checkbox'),
                            'pCheckbox' => __('Checkbox, preselected'),
                            'select' => __('Select'),
                            'mselect' => __('Select multiple'),
                            'textarea' => __('Textarea'),
                            'htmlfield' => __('HTML'),
                            'Radio' => __('Radio button'),
                            'hidden' => __('Hidden'));

        $formFields = array();
        if($this->page !== 'new') {
            $r = $DB->formfields->get(array('id' => $this->ID), false, false, 'sort');


            while(false !== ($field = Database::fetchAssoc($r))) {
                $formFields[] = new tablerow(
                                    icon('small/arrow_switch', __('Move'), '#', 'fieldhandle'),
                                    new select(false, 'form['.$field['field_id'].'][type]', $fieldTypes, $field['type'], false, false, false, false, 'medium'),
                                    new Input(false, 'form['.$field['field_id'].'][lbl]', $field['label'], false, false, 'medium'),
                                    new Input(false, 'form['.$field['field_id'].'][val]', $field['value'], false, false, 'medium'),
                                    icon('small/delete', __('Delete field'), url(array('delfield' => $field['field_id']), true))
                                );
            }
        }
        if($_REQUEST['newFormField']) {
            $nid = uniqid();
            $formFields[] = new tablerow(
                                icon('small/arrow_switch', __('Move'), '#', 'fieldhandle'),
                                new Hidden('form['.$nid.'][new]',1).
                                new select(false, 'form['.$nid.'][type]', $fieldTypes, false, false, false, false, false, 'medium'),
                                new Input(false, 'form['.$nid.'][lbl]', false, false, false, 'medium'),
                                new Input(false, 'form['.$nid.'][val]', false, false, false, 'medium')
                            );
        }
        if(!empty($formFields)) {
            $ff = new Table(
                new tableheader('',__('Type'), __('Label'), __('Value(s)')),
                $formFields
            );
            $ff->id = 'formfields';

            JS::loadjQuery();
            Head::add('$(function(){$("#formfields tr").parent().sortable({handle:".fieldhandle"});$(".fieldhandle").click(function(){return false;}).css("cursor","move");});', 'js-raw');
        } else $ff = null;

        $active = $this->getActive('form');
        return new Set(
            new input(__('Form title'), 'ftitle', $this->_Form_Title),
            new Li(new Datepicker(__('Activate'), 'formactivate[date]', (isset($active['start'])?date('Y-m-d', $active['start']):'')), new Timepickr(false, 'formactivate[time]', (isset($active['start'])?date('H:i', $active['start']):''))),
            new Li(new Datepicker(__('Deactivate'), 'formdeactivate[date]', (isset($active['stop'])?date('Y-m-d', $active['stop']):'')), new Timepickr(false, 'formdeactivate[time]', (isset($active['stop'])?date('H:i', $active['stop']):''))),
            new select(__('Public form'), 'formpublic', array(__('Closed'), __('Names disclosed'), __('Full disclosure')), $this->_Public_Form),
            new input(__('Limit'), 'formlimit', $this->_Limit),
            $ff,
            new submit(__('New field'), 'newFormField')
        );
    }

    /**
     * Returns an array with all the emails of the posters
     * @return array
     */
    function getEmails() {
        return array_map(create_function('$a', 'return $a->getEmail();'), $this->getPosters());
    }

    /**
     * Returns an array with all the emails of the posters
     * @return array
     */
    function getContacts() {
        return join(",\r\n", array_map(create_function('$a', 'return "\"".$a->Name()."\" <".$a->getEmail().">";'), $this->getPosters()));
    }

    function getSortedTable() {
        $posters = $this->getPosters();
        $rows = array();
        foreach($posters as $p) {
            $rows[] = array(@$p->userinfo['sn'].', '.@$p->userinfo['givenName'], $p->getEmail());
        }
        usort($rows, create_function('$a,$b', 'return ($a[0]>$b[0]);'));
        return new Table(new tableheader(__('Name'), __('Email'), __('Notes')),
            array_map(create_function('$a', 'return new tablerow($a[0], $a[1], "&nbsp;");'), $rows));
    }

    /**
     * Get all the users that has posted
     * @return array Array with all the objects of the posters
     */
    function getPosters() {
        global $Controller, $DB;
        return $Controller->get(self::getPosterIDs(), OVERRIDE);
    }

    /**
     * Get the ID's of all the users that has posted
     * @return array Array with all the objects of the posters
     */
    function getPosterIDs() {
        global $DB;
        return $DB->formdata->asList(array('id' => $this->ID, 'field_id' => 'poster'), 'value');
    }

    function sendFile($OP_TYPE = 'email') {
            while(ob_get_level()) ob_end_clean();
            header("Expires: ".gmdate("D, d M Y H:i:s", time()+172801)." GMT");
            header('Cache-Control: max-age=172801, must-revalidate');
            switch($OP_TYPE) {
                case 'email':
                    header('Content-Type: text/plain');
                    header('Content-Description: File Transfer');
                    header("Content-Type: application/force-download", false);
                    header("Content-Type: application/download", false);
                    header('Content-Disposition: attachment; filename="contacts.txt"');
                    header('Content-Transfer-Encoding: binary');
                    print($this->getContacts());
                    break;
                case 'sortedtable':
                    global $Controller;
                    $tbl = $this->getSortedTable();
                    print('<?xml version="1.0" encoding="utf-8"?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"><html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en"><head><title></title>
    <style type="text/css">
        tr.odd {background: #eee;}
        table {width: 100%; border: 1px solid black;}
        tr.odd td {border: solid black; border-width: 1px 0 1px 0;}
        td {padding: 5px 0;}
        th {border: solid black; border-width: 0px 0 3px 0; text-align: left;}
    </style></head><body>'
                    .'<h1>'.$Controller->{$this->page}->Name.': '.$this->_Form_Title.'</h1>'
                    .$tbl.'</body></html>');
                    break;
            }
            die();
    }

    function viewResult($echo=false, $no_stats = false) {
        $_REQUEST->setType('cout', '#^(email|sortedtable)$#');
        global $DB, $Controller, $USER;
        if($USER->ID == NOBODY) return false;

        if($_REQUEST['cout']) {
            $this->sendFile($_REQUEST['cout']);
        }

        $_REQUEST->setType('delpost', 'any');
        $myPosts = array();
        $where = array('id' => $this->ID);
        if(!$this->mayI(EDIT)) {
            $myPosts = $DB->formdata->asList(array('id' => $this->ID, 'field_id' => 'poster', 'value' => $USER->ID), 'post_id');
            if(!$this->_Public_Form) {
                if(!$myPosts) return '';
                $where['post_id'] = $myPosts;
            }
            $no_stats = true;
        }
        if($_REQUEST['delpost']) {
            if($this->mayI(EDIT) || in_array($_REQUEST['delpost'], $myPosts)) {
                $DB->formdata->delete(array('id' => $this->ID, 'post_id' => $_REQUEST['delpost']), false);
                if(!is_null($this->_PostCount)) --$this->_PostCount;
            }
        }

        $sort = array();
        $r = $DB->formfields->get(array('id' => $this->ID), false, false, 'sort');
        while(false !== ($field = Database::fetchAssoc($r))) {
            $sort[$field['field_id']] = $field['sort'];
            $fields[$field['field_id']] = $field;
            $labels[$field['field_id']] = self::fieldlabel($field['label'], $this->loadedLanguage);
        }
        asort($sort);
        $sort = array_flip(array_keys($sort));


        $r = $DB->formdata->get($where);
        $data = array();
        $postSort = array();
        $u=0;
        $stats = array();
        while(false !== ($res = Database::fetchAssoc($r))) {
            if(in_array($res['field_id'], array('poster', 'posted', 'poster:ip', 'language'))) {
                $postMeta[$res['post_id']][$res['field_id']] = $res['value'];
                if($res['field_id'] == 'posted') $postSort[$res['value']] = $res['post_id'];
            } else {
                if($this->_Public_Form < 2 && !$this->mayI(EDIT) && !in_array($res['post_id'], $myPosts)) continue;
                if(@in_array($fields[$res['field_id']]['type'], array('Checkbox', 'pCheckbox')) && substr_count($fields[$res['field_id']]['value'], ',') == 0) {
                    if($res['value']) $res['value'] = __('Yes');
                    else $res['value'] = __('No');
                }
                if(!$no_stats) {
                    if(@!in_array($fields[$res['field_id']]['type'], array('htmlfield', 'textarea', 'hidden', 'mselect'))) {
                        if(isset($stats[$res['field_id']][$res['value']]))
                            $stats[$res['field_id']][$res['value']]++;
                        else $stats[$res['field_id']][$res['value']] = 1;
                    } elseif($fields[$res['field_id']]['type'] == 'mselect') {
                        if(!is_array($res['value'])) $res['value'] = array($res['value']);
                        foreach($res['value'] as $sel) {
                            if(isset($stats[$res['field_id']][$sel]))
                                $stats[$res['field_id']][$sel]++;
                            else $stats[$res['field_id']][$sel] = 1;
                        }
                    }
                }

                $data[$res['post_id']][(isset($sort[$res['field_id']])?$sort[$res['field_id']]:count($sort)+$u++)] = new tablerow(@$labels[$res['field_id']], $res['value']);
            }
        }
        if(empty($postSort)) return '';

        $sortData = array();
        ksort($postSort);
        foreach($postSort as $post_id) {
            if(isset($data[$post_id])) {
                ksort($data[$post_id]);
                $sortedData[$post_id] = $data[$post_id];
            } else
                $sortedData[$post_id] = null;
        }

        $oa = array();
        foreach($sortedData as $post_id => $rows) {
            $tbl = new Table(new tableheader(__('Posted by').': '.@$Controller->{$postMeta[$post_id]['poster']}, ($this->mayI(EDIT)||in_array($post_id, $myPosts)?icon('small/delete', __('Delete post'), url(array('delpost' => $post_id), true)):'')),
                                $rows);
            $tbl->class = 'form_posterdata';
            $oa[] = $tbl;
        }

        $output = listify($oa);
        $_REQUEST->setType('to', 'numeric');
        if($this->mayI(EDIT))
            $output .= '<span class="forminfo">'.$this->PostCount . ' ' . __('posters').($this->_Limit?' (of '.$this->_Limit.')':'').'</span>'.
                        '<span class="uform_posterdata"><a href="'.url(array('cout' => 'email'), 'id').'">'.__('Contact data').'</a>|<a href="'.url(array('cout' => 'sortedtable'), 'id').'">'.__('Sorted table').'</a>'
                        .($Controller->mailer?'|<a href="'.url(array('id' => 'mailer', 'to' => $this->ID)).'">'.__('Email posters').'</a>':'')
                        .'</span>';

        if(!$no_stats) {
            $s = new Table(new tableheader(__('Field'), __('Data'), __('Occurrances')));
            $s->class = 'form_stats_table';
            foreach($stats as $field_id => $values) {
                $st_rows = array();
                $i=0;
                foreach($values as $value => $count) {
                    $st_rows[] = new tablerow((!($i++)?@$labels[$field_id]:''), $value, $count);
                }
                $s->append($st_rows);
            }
            $output .= $s;
        }


        if($echo) echo $output;
        return $output;
    }

    function delete() {
        global $DB;
        if(!$this->mayI(DELETE)) return false;
        $DB->formdata->delete(array('id' => $this->ID), false);
        $DB->formfields->delete(array('id' => $this->ID), false);
        return true;
    }
}
?>
