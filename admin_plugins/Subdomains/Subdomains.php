<?php
class Subdomains extends Page {
    static function installable() { return __CLASS__; }
    
    function install() {
        global $Controller;
        $Controller->newObj('Subdomains')->move('last', 'adminMenu');
    }
    
    function __construct($id) {
        parent::__construct($id);
        $this->icon = 'small/bricks';
        $this->suggestName('Subdomains', 'en');
        $this->suggestName('Subdomäner', 'sv');
    }
    
    function run() {
        global $DB, $Templates;
        if(!$this->mayI(READ)) errorPage(401);
        
        $_REQUEST->setType('delsd', 'string');
        $_REQUEST->setType('editsd', 'string');
        $_POST->setType('sdname', 'string');
        $_POST->setType('sdassoc', 'string');
        
        if($_POST['sdname']) {
            if($_REQUEST['editsd']) {
                if($DB->subdomains->update(array('subdomain' => $_POST['sdname'], 'assoc' => $_POST['sdassoc']), array('subdomain' => $_REQUEST['editsd'])))
                    Flash::create(__('Subdomain updated'), 'confirmation');
                else Flash::create(__('Subdomain in use'), 'warning');
            }
            else {
                if($DB->subdomains->insert(array('subdomain' => $_POST['sdname'], 'assoc' => $_POST['sdassoc'])))
                    Flash::create(__('New subdomain inserted'), 'confirmation');
                else Flash::create(__('Subdomain in use'), 'warning');
            }
        }
        elseif($_REQUEST['delsd'] && $this->mayI(EDIT)) {
            $DB->subdomains->delete(array('subdomain' => $_REQUEST['delsd']));
        }
        
        $r = $DB->subdomains->get(false, false, false, 'subdomain');
        
        $tablerows = array();
        while(false !== ($subdomain = Database::fetchAssoc($r))) {
            $tablerows[] = new tablerow($subdomain['subdomain'], $subdomain['assoc'], icon('small/delete', __('Delete subdomain'), url(array('delsd' => $subdomain['subdomain']), 'id')).icon('small/pencil', __('Edit subdomain'), url(array('editsd' => $subdomain['subdomain']), 'id')));
        }
        
        if($_REQUEST['editsd']) {
            $sd = $DB->subdomains->getRow(array('subdomain' => $_REQUEST['editsd']));
            $form = new Form('editSubdomain');
        } else {
            $sd = false;
            $form = new Form('newSubdomain');
        }
        
        $this->setContent('main',
            (!empty($tablerows)?new Table(new tableheader(__('Subdomain'), __('Associated with..'), __('Actions')), $tablerows):'')
            .$form->set(
                ($_REQUEST['editsd']?new Hidden('editsd', $_REQUEST['editsd']):null),
                new input(__('Subdomain'), 'sdname', @$sd['subdomain']),
                new input(__('Associate with'), 'sdassoc', @$sd['assoc'], false, __('ID or alias to associate with the subdomain'))
            )
        );
        
        $Templates->render();
    }
}