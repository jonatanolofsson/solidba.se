<?php
class Article extends Page {
    function __construct($id, $language=false) {
        parent::__construct($id, $language);
        
        $this->getMetadata();
    }
    
    function __get($property) {
        if($property == 'Publish') {
            $r = $this->getActive();
            return $r['start'];
        } else return parent::__get($property);
    }
    
    function __set($property, $value) {
        if($property == 'Publish') {
            $this->setActive($value);
        } else parent::__set($property, $value);
    }
    
    function run() {
        global $DB;
        $this->content = array(	'header' => $this->Name, 
                                'main' => '<div class="newspreamble">' . @$this->content['Preamble'] . '</div><div class="newsbody">' . @$this->content['Text'] . '</div>',
                                'author' => '<div class="authorpresentation">'.$this->author->presentation().'</div>');
        parent::run();
    }
}
?>