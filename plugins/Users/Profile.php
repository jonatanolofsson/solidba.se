<?php
class Profile extends Page {
    function __construct($id, $lang=false) {
        parent::__construct($id, $lang);
        $this->suggestName('Profile', 'en');
        $this->suggestName('Profil', 'sv');
        $this->alias = 'profile';
        $this->icon = 'small/user';
    }
    
    function may($u, $lvl) {
        if(is_numeric($u)) $id = $u;
        elseif(is_string($u)) $u = $Controller->$u(OVERRIDE);
        if(is_object($u)) $id = $u->ID;
        if($id == NOBODY) return false;
        return parent::may($u, $lvl);
    }
    
    function run() {
        global $USER, $Templates;
        UserEditor::saveChanges();
        $this->setContent('main', UserEditor::edit($USER));
        $Templates->render();
    }
}