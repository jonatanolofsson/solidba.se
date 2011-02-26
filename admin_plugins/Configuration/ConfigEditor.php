<?php
/**
 * MenuEditor
 * @author Jonatan Olofsson [joolo]
 * @version 1.0
 * @license http://creativecommons.org/licenses/by-nc/3.0/ Creative Commons Attribution-Noncommercial 3.0 Unported License
 * @package Base
 */
/**
 * The menueditor is a tool for editing the menu
 * @package Base
 */
class ConfigEditor extends Page {
    public $privilegeGroup = 'Administrationpages';

    /**
     * Setup the page
     * @param integer $id The object's ID
     * @return void
     */
    function __construct($id=false){
        parent::__construct($id);
        $this->suggestName('Configuration');

        $this->icon = 'small/cog_edit';
        $this->deletable = false;
    }


    /**
     * Contains actions and page view handling
     * @return void
     * @see solidbase/lib/Page#run()
     */
    function run() {
        global $Templates, $USER, $DB, $CONFIG;

        /**
         * User input types
         */
        $_REQUEST->setType('conf', 'string', true);

        if(!$this->may($USER, ANYTHING)) errorPage(401);
        if($this->may($USER, EDIT)) {
            if($_REQUEST['conf']) {
                $r = $DB->config->get(null, null, null, 'section,property');
                while($c = Database::fetchAssoc($r)) {
                    $val = @$_REQUEST['conf'][$c['section']][$c['property']];
                    switch($c['type']) {
                        case 'CSV': $val = @explode(',', $val);
                        case 'password': if($c['type'] == 'password' && $val == '********') continue 2;
                        case 'select':
                        case 'set':
                        case 'text':
                            if($val === false) continue;
                            $CONFIG->{$c['section']}->{$c['property']} = $val;
                            break;
                        case 'check':
                            $CONFIG->{$c['section']}->{$c['property']} = (int)isset($val);
                            break;

                    }
                }
                Log::write('Configuration changed', 2);
                Flash::create(__('The configuration was updated'), 'confirmation');
            }
        }

        $this->setContent('header', 'Edit configuration');
        $this->setContent('main', $this->viewAll());

        $Templates->admin->render();
    }

    /**
     * Display all options
     * @return void
     */
    function viewAll() {
        global $DB, $USER;
        $r = $DB->config->get(array('type!' => 'not_editable'), false, false, 'section,property');

        $form = new Form();

        $e = $this->may($USER, EDIT);

        $lastSectionName = false;
        $lastSection = false;
        $sections = array();
        while($c = Database::fetchAssoc($r)) {
            if($lastSectionName != $c['section']) {
                $lastSectionName = $c['section'];
                if($lastSection != false && $lastSection->count() == 0) {
                    array_pop($sections);
                }
                $sections[] = $lastSection = new Fieldset(ucwords(str_replace('_', ' ',$c['section'])));
            }
            $mult=false;
            $a = false;
            switch($c['type']) {
                case 'CSV':
                    if(is_array($c['value']))
                        $c['value'] = @join(',', $c['value']);
                case 'text':
                    if($e) {
                        $a = new Input(ucwords(__(str_replace('_', ' ', $c['property']))), 'conf['.$c['section'].']['.$c['property'].']', $c['value'], null, __($c['description']));
                    }
                    else {
                        $a = '<span class="property">'.ucwords(__(str_replace('_', ' ', $c['property']))) .':</span> <span class="value">'.$c['value'].'</span><span class="description">'.__($c['description']).'</span>';
                    }
                    break;
                case 'password':
                    if($e) {
                        $a = new Password(ucwords(__(str_replace('_', ' ', $c['property']))), 'conf['.$c['section'].']['.$c['property'].']', '********', null, __($c['description']));
                    } else {
                        $a = '<span class="property">'.ucwords(__(str_replace('_', ' ', $c['property']))) .':</span> <span class="value">********</span><span class="description">'.__($c['description']).'</span>';
                    }
                    break;
                case 'set': $mult=true;
                case 'select':
                    if(is_array($c['set'])) {
                        if($e) {
                            $a = new Select(ucwords(__(str_replace('_', ' ', $c['property']))), 'conf['.$c['section'].']['.$c['property'].']', array_map('__', $c['set']), $c['value'], $mult, false, false, __($c['description']));
                        }
                        else {
                            $a = '<span class="property">'.ucwords(__(str_replace('_', ' ', $c['property']))) .':</span> <span class="value">'.@$c['set'][$c['value']].'</span><span class="description">'.__($c['description']).'</span>';
                        }
                    }
                    break;
                case 'check':
                    if($e) {
                        $a = new Checkbox(ucwords(__(str_replace('_', ' ', $c['property']))), 'conf['.$c['section'].']['.$c['property'].']', $c['value'], $c['value'], false, __($c['description']));
                    }
                    else {
                        $a = '<span class="property">'.ucwords(__(str_replace('_', ' ', $c['property']))) .':</span> <span class="value">'.$c['value'].'</span><span class="description">'.__($c['description']).'</span>';
                    }
                    break;
            }
            if($a) $lastSection->add($a);
        }
        if($lastSection != false && $lastSection->count() == 0) {
            array_pop($sections);
        }
        if($e)
            return $form->collection($sections);
        else
            return join('', $sections);
    }
}
?>
