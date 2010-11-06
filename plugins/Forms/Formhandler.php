<?php
class Formhandler {
    function render($page=false) {
        global $DB, $Controller;
        
        
        if(!$page && isset($this)) $page = $this;
        elseif(!is_object($page)) $page = $Controller->retrieve($page);
        $r = $DB->formfields->get(array('id' => $page->ID, 'language' => $page->loadedLanguage), '*', false, 'sort');
        
        if(!Database::numRows($r)) return '';
        
        $uForm = new Form('uform');
        
        $form = array();
        while(false !== ($field = Database::fetchAssoc($r))) {
            $fieldName = 'uform['.$field['field_id'].']';
            switch($field['type']) {
                case 'Checkbox':
                case 'pCheckbox':
                    $Values = array_filter(array_map('trim', explode(',', $field['value'])));
                    $Names = array_map('idfy', $Values);
                    if(count($Values) > 1) {
                        $form[] = new checkset($field['label'], $fieldName, array_combine($Names, $Values), ($field['type'] == 'pCheckbox'));
                    } else {
                        $form[] = new Checkbox($field['label'], $fieldName, ($field['type'] == 'pCheckbox'));
                    }
                    break;
                case 'select':
                case 'mselect':
                    $Values = array_map('trim', explode(',', $field['value']));
                    $Names = array_map('idfy', $Values);
                    $form[] = new select($field['label'], $fieldName, array_combine($Names, $Values), false, ($field['type'] == 'mselect'));
                    break;
                case 'Radio':
                    $Values = array_map('trim', explode(',', $field['value']));
                    $Names = array_map('idfy', $Values);
                    $form[] = new Radioset($field['label'], $fieldName, array_combine($Names, $Values));
                    break;
                case 'input': 
                case 'textarea':
                case 'htmlfield':
                    $form[] = new $field['type']($field['label'], $fieldName, $field['value']);
                    break;
            }
        }
        
        if(empty($form)) return '';
        return $uForm->set($form);
    }
    
    function editForm($page) {
        global $DB;
        Head::add('lightbox/jquery.lightbox-0.5.css', 'css-lib');
        return new Set(
            new Li(
                new select(false, 'form[type][]', array('input' => __('Text'), 'select' => __('Select'), 'Checkbox' => __('Checkbox'), 'Radio' => __('Radio button'))),
                new Input(false, 'form[lbl][]')
            ),
            new submit(__('New field'), 'newFormField')
        );
    }
    
    function viewResult($page) {
        global $DB;
        return '';
    }
}
?>