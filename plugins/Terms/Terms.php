<?php
class Terms extends Page {
    static function installable(){return __CLASS__;}
    protected $KeepRevisions = false;

    public $editable = array('TermsEditor' => EDIT);

    static public $edit_icon = 'small/script_edit';
    static public $edit_text = 'Modify terms';

    function install() {
        global $Controller;
        $Controller->newObj('Terms')->setAlias('Terms');
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
            $this->setContent('main', __('Thank you'));
            redirect(-2, 3);
        }
        else {
            $form = new Form('Terms');
            $this->appendContent('main', $this->getContent('Terms').$form->collection(new Set(
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
