<?php
class Terms extends Page {
    static function installable(){return __CLASS__;}
    protected $KeepRevisions = false;
    
    function install() {
        global $Controller;
        $Controller->newObj('Terms')->setAlias('Terms');
    }
    
    function lastUpdated($language) {
        global $DB;
        return $DB->content->getCell(array('id' => $this->ID, 'language' => $language), 'MAX(revision)');
    }
    
    function __construct($id, $lang=false) {
        parent::__construct($id, $lang);
        $this->Name = __('Terms and Conditions');
    }
     
    function run() {
        global $USER, $Templates;
        $this->setContent('header', __('Terms and Conditions'));
        $_POST->setType('termsAgreed', 'any');
        if($_POST['termsAgreed']) {
            $USER->acceptTerms();
            $_REQUEST->setType('return', array('numeric', 'string'));
            $this->setContent('main', __('Thank you').($_REQUEST['return']?'<p><a href="'.url(array('id' => $_REQUEST['return'])).'">'.__('Return').'</a></p>':''));
        }
        else {
            $form = new Form('Terms');
            $this->setContent('main', @$this->content['Terms']
            .$form->collection(new Set(
                new Minicheck(__('I agree'), 'termsAgreed', false, 'checked')
            )));
        }
        $Templates->render();
    }
    
    function may($u, $lvl) {
        global $USER;
        if($lvl & READ) {
            return $USER->ID != NOBODY;
        }
        else return parent::may($u, $lvl);
    }
}
?>