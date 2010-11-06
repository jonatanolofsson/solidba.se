<?php

class PageLayoutEditor extends Page {
    public $privilegeGroup = 'Administrationpages';
    static $VERSION = 1;
    static public function installable() {return __CLASS__;}
    //static public function uninstallable() {return __CLASS__;}
    static public function upgradable() {return __CLASS__;}
    private $maxcols = 12;

    function upgrade() {}

    function install() {
        global $Controller, $DB, $CONFIG;
        $Controller->newObj('PageLayoutEditor')->move('last', 'adminMenu');
        $DB->query("CREATE TABLE IF NOT EXISTS `pagelayout` (
                      `pid` int(11) NOT NULL,
                      `id` int(11) NOT NULL,
                      `module` varchar(255) collate utf8_swedish_ci NOT NULL,
                      `type` varchar(11) collate utf8_swedish_ci NOT NULL,
                      `row` int(11) NOT NULL,
                      `place` int(11) NOT NULL,
                      `size` int(11) NOT NULL,
                      `content` text collate utf8_swedish_ci NOT NULL,
                      KEY `row` (`row`),
                      KEY `id` (`id`),
                      KEY `pid` (`pid`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;");
        Design::registerModule('news', 'News', 'large/agt_announcements-32', array(3, 6, 12), array('autofill','id'), array('Page'), false);
        Design::registerModule('html', 'HTML', 'large/contents-32', array(2,3,4,5,6,7,8,10,12), array('text'), array('Page'), false); 
        Design::registerModule('image', 'Image', 'large/thumbnail-32', array(2,3,4,5,6,7,8,10,12), array('image'), array('Page'), false);
        return self::$VERSION;
    }

    
    function __construct($id=false){
        global $CONFIG;
        parent::__construct($id);
        $this->alias = 'pagelayouteditor';
        $this->suggestName('PageLayoutEditor', 'en');
        $this->suggestName('Sidlayout', 'sv');
        $this->icon = 'small/layout';
    }

    
    function run(){
        global $Templates, $DB, $Controller;
        
        if(!$this->mayI(READ|EDIT)) { errorPage('401'); return false; }
        
        $_REQUEST->setType('add','string');
        $_REQUEST->setType('edit','numeric');
        $_REQUEST->setType('del','numeric');
        $_REQUEST->setType('module', 'string');
        $_REQUEST->setType('type','string');
        $_REQUEST->setType('size','string');
        $_REQUEST->setType('content','string');
        $_REQUEST->setType('row','numeric');
        $_REQUEST->setType('place','numeric');
        
        //FIXME: Tillsvidare: Id på sidan som editeras
        $pID = 8;
        
        if($_REQUEST['add']) {
            $newModule = $Controller->newObj('PageModule');
            $newModule->addData($pID, $_REQUEST['add']);
        } elseif($_REQUEST['edit']) {
            $module = $Controller->{$_REQUEST['edit']};
            if($_REQUEST['module']) $module->module = $_REQUEST['module'];
            elseif($_REQUEST['size']) $module->size = $_REQUEST['size'];
            elseif($_REQUEST['type']) $module->type = $_REQUEST['type'];
            elseif($_REQUEST['row'] !==false && $_REQUEST['place'] !== false) $module->move($_REQUEST['row'], $_REQUEST['place']);
            elseif($_REQUEST['content']) $module->content = $_REQUEST['content'];
        } elseif($_REQUEST['del']) {
            $Controller->{$_REQUEST['del']}->delete();
        }
                
        /* Get numbers of rows on page*/
        $rowNum = $DB->pagelayout->getCell(array('pid' => $pID), "MAX(ROW)");
        
        $pagecontent = false;
        /* Get modules from each row */
        for($row=0; $row<=$rowNum; $row++) {
            $moduleIDs = $DB->pagelayout->asList(array('pid' => $pID, 'row' => $row),'id',false,false,'place');
            $rowContent = array();
            foreach($moduleIDs as $mID) {
                $moduleObj = $Controller->{$mID};
                $rowContent[] = $moduleObj;
            }
            $pagecontent[$row] = $rowContent;
        }

        JS::loadjQuery();
        JS::lib('pagelayoutedit');

        $this->header = __('Page Layout');
        $this->setContent('main','<h1>Page Layout</h1>'.$this->displayEditor($pagecontent));
        $Templates->admin->render();
    }
    
    function displayEditor($content) {
        global $CONFIG, $Controller;
        $i = 0;
        $r = '<div class="ui-helper-reset ui-helper-clearfix ui-widget-header ui-corner-all">';
/*		$r .= '<div class="ui-helper-reset ui-helper-clearfix ui-widget-header ui-corner-all">'
            .'<a href="'.url(array('add' => 'box'), 'id').'">'.icon('large/agt_announcements-32').__('News').'</a>'
            .'<a href="'.url(array('add' => 'box'), 'id').'">'.icon('large/appointment-32').__('Calendar').'</a>'
            .'<a href="'.url(array('add' => 'box'), 'id').'">'.icon('large/contents-32').__('HTML').'</a>'
            .'<a href="'.url(array('add' => 'box'), 'id').'">'.icon('large/thumbnail-32').__('Image').'</a>'
            .'<a href="'.url(array('add' => 'box'), 'id').'">'.icon('large/signature-32').__('Form').'</a>'
            .'<a href="'.url(array('add' => 'box'), 'id').'">'.icon('large/signature-32').__('Extern RSS').'</a>';
        
        Design::registerModule('news', 'News', 'large/agt_announcements-32', array(3, 6, 12), array('autofill','id'), array('Page'), false);
        Design::registerModule('calendar', 'Calendar', 'large/appointment-32', array(3, 6, 12), array('autofill','id'), array('Page'), false);
        Design::registerModule('html', 'HTML', 'large/contents-32', array(2,3,4,5,6,7,8,10,12), array('text'), array('Page'), false); 
        Design::registerModule('image', 'Image', 'large/thumbnail-32', array(2,3,4,5,6,7,8,10,12), array('image'), array('Page'), false);

        */
        
        foreach($CONFIG->pagemodules->settings as $module => $settings) {
            $r .= '<a href="'.url(array('add' => $module), 'id').'">'.icon($settings['icon']).__($settings['name']).'</a>';
        }
        $r .= '</div><div id="page">';
        
        if(!empty($content)) {
            foreach($content as $row) {
                $r .= '<div id="'.$i++.'" class="row">';
                foreach($row as $module) {
                    $r .= $module->display();				
                }
                $r .= '</div>';
            }
        } else $r .= '<h2>'.__('No content').'</h2>';
        $r .= '</div>';		
/* 		$r .= '<div class="submit">'.icon('large/search-32', __('Preview page'), 'javascript:pagepreview()', 'preview').'</div>'; */
        return $r;
    }

}	

class PageModule extends Base {
    private $_module	= false;
    private $_page		= false;
    private $_type		= false;
    private $_size		= false;
    private $_row		= false;
    private $_place		= false;
    private $_content	= false;
    private $maxcols	= 12;
    private $_settings	= false;
    
    
    //Formaly __create()
    //FIXME: Problem med att lägga till första boxen vid tom sida
    function addData($pID, $module) {
        global $DB;
        $lastRow = (int)$DB->pagelayout->getCell(array('pid' => $pID), "MAX(`row`)");
        $totalSize = (int)$DB->pagelayout->getCell(array('pid' => $pID, 'row' => $lastRow), "SUM(`size`)");
        $defaultSize = (int)$this->settings[$module]['size'][0];
        $defualtType = $this->settings[$module]['type'][0];
        
        if($totalSize + $defaultSize < $this->maxcols) {
            $newRow = $lastRow;
            $lastPlace = (int)$DB->pagelayout->getCell(array('pid' => $pID, 'row' => $lastRow), "MAX(`place`)");
            $newPlace = $lastPlace+1;
        } else {
            $newRow = $lastRow+1;
            $newPlace = 0;
        }
        $DB->pagelayout->insert(array('id' => $this->ID, 'pid' => $pID, 'module' => $module, 'type' => $defualtType, 'size' => $defaultSize, 'row' => $newRow, 'place' => $newPlace));
    }	
    
    function __get($property) {
        if(in_array($property, array('module', 'page', 'type', 'size', 'row', 'place', 'content'))) {
            $ipn = '_'.$property;
            if($this->$ipn === false) $this->ld();
            return $this->$ipn;
        } elseif($property == 'settings') {
            if($this->_settings === false) $this->ld();
            return $this->_settings;
        } else return parent::__get($property);
    }
    
    function __set($property, $value) {
        global $DB;
        $ipn = '_'.$property;
        if(in_array($property, array('page', 'type', 'row', 'place', 'content'))) {
            $this->$ipn = $value;
            $DB->pagelayout->{$this->ID} = array($property => $value);
        } elseif($property == 'module') {
            $this->$ipn = $value;
            $DB->pagelayout->{$this->ID} = array($property => $value);
            $this->reset();
        } elseif($property == 'size') {
            if($this->_row === false) $this->ld();
            $size = $DB->pagelayout->getCell(array('id!' => $this->ID, 'pid' => $this->page, 'row' => $this->row), "SUM(`size`)");
            if($size + $value > $this->maxcols) {
                Flash::create('Wrong size! The box won\'t fit');
                return false;
            }
            $this->$ipn = $value;
            $DB->pagelayout->{$this->ID} = array($property => $value);
        } else parent::__set($property, $value);
        
    }
    
    function ld() {
        global $DB, $CONFIG;
        $data = $DB->pagelayout->{$this->ID};
        $this->_module = $data['module'];
        $this->_page = $data['pid'];
        $this->_type = $data['type'];
        $this->_size = $data['size'];
        $this->_row = $data['row'];
        $this->_place = $data['place'];
        $this->_content = $data['content'];
        $this->_settings = $CONFIG->pagemodules->settings;
    }
    
    function move($newRow, $newPlace) {
        global $DB;
        if(!is_numeric($newRow) || !is_numeric($newPlace)) return false;
        if($this->row == $newRow && $this->place == $newPlace) return true;
        
        $size = $DB->pagelayout->getCell(array('id!' => $this->ID, 'pid' => $this->page, 'row' => $newRow), "SUM(`size`)");
        if($size + $this->size > $this->maxcols) {
            Flash::create('Can\'t perform move! The box won\'t fit');
            return false;
        }
        
        $length = $DB->pagelayout->count(array('pid' => $this->page, 'row' => $newRow));
        if($newPlace > $length) $newPlace = $length;
        $tonext = ($this->row == $newRow && $this->place !== false && $newPlace == $this->place + 1);
        
        $DB->pagelayout->update(array('!!place' => '(`pagelayout`.`place`+1)'), array('pid' => $this->page, 'place>'.($tonext?'':'=') => $newPlace, 'row' => $newRow), false, false);
        $DB->pagelayout->update(array('row' => $newRow, 'place' => $newPlace+$tonext), array('id' => $this->ID), true);
        if($this->place !== false) $DB->pagelayout->update(array('!!place' => '(`pagelayout`.`place`-1)'), array('pid' => $this->page, 'place>' => $this->place, 'row' => $this->row), false, false);
        
        $this->place = $newPlace;
        $this->row = $newRow;
        return true;
    }
    
    function delete() {
        global $DB;
        if($this->mayI(DELETE)) {
            $DB->pagelayout->delete($this->ID);
            $DB->query("UPDATE `pagelayout` SET (`pagelayout`.`place`=`pagelayout`.`place`-1) WHERE `pagelayout`.`place`>'".$this->place."' AND `pid`='".$this->page."' `row`='".$this->row."'");
            return parent::delete();
        } else return false;
    }
    
    
    
    function display() {
        $r = '<div id="'.$this->ID.'" class="col '.($this->place==0?'first ':'').'module '.numberToText($this->size).' '.$this->module.'">'
                .'<h3>'.__($this->settings[$this->module]['name']).icon('small/delete', __('Delete module'), url(array('del' => $this->ID),'id')).'</h3>';

        /* Module Select */
        $r .= '<label for="module">'.__('Module').'</label><select id="module" name="btype" class="boxselector">';
        foreach($this->settings as $module => $settings) {
            $r .= '<option value="'.$module.'"'.($this->module==$module?' selected="selected"':'').'>'.__($settings['name']).'</option>';
        }
        $r .= '</select><br />';
        
        /* Size select */
        $r .= '<label for="size">'.__('Size').'</label><select id="size" name="msize" class="boxselector">';
        foreach($this->settings[$this->module]['size'] as $size) {
            $r .= '<option value="'.$size.'"'.($this->size==$size?' selected="selected"':'').'>'.$size.'</option>';
        }
        $r .= '</select><br />';

        /* Content select */
        $moduletype = $this->settings[$this->module]['type'];
        $r .= '<label for="type">'.__('Content type').'</label><select id="type" name="mcont" class="boxselector"'.(count($moduletype)==1?' disabled="disabled"':'').'>';
        foreach($moduletype as $type) {	
            $r .= '<option value="'.$type.'"'.($this->type==$type?' selected="selected"':'').'>'.__($type).'</option>'; 
        }
        $r .= '</select>';
        
        /* Specific ID option */
        if($this->type == 'id') $r .= '<br/><label for="id">'.$this->settings[$this->module]['name'].'ID</label><input id="id" name="cid" value="'.(is_numeric($this->type)?$this->content:'').'
        " class="text" /><div class="tools">'.icon('small/folder_picture', __('Browse picture'), "javascript:exploreObj(".$this->module.");").'</div>';
        
        /* Input content option */
        if($this->type == 'input') {
            $form = new Form('textcontent', url(array('edit' => $this->ID), 'id'), __('Save'));
            $r .= $form->collection(
                    new Input(__('Content'), 'content', @$this->content)
                );
        }
        
        /* Text content option */
        if($this->type == 'text') {
            $form = new Form('textcontent', url(array('edit' => $this->ID), 'id'), __('Save'));
            $r .= $form->collection(
                    new TextArea(__('Content'), 'content', @$this->content)
                );
        }
        
        /* Image content option */
        if($this->type == 'image') {
            $form = new Form('imagecontent', url(array('edit' => $this->ID), 'id'), __('Save'));
            $r .= $form->collection(
                    new ImagePicker(__('Content'), 'content', @$this->content)
                );
        }	

        $r .= '</div>';
        return $r;
    }
    
    function resize($org) {
        //FIXME: Ändra storlekar för att passa editorn
        return numberToText(($org/3)*2);
    }
    
    function reset() {
        $this->size = (int)$this->settings[$this->module]['size'][0];
        $this->type = $this->settings[$this->module]['type'][0];
        $this->content = '';
    }

}

class NewsModule extends PageModule {


}

class CalendarModule extends PageModule {
}

class HTMLModule extends PageModule {
}

class ImageModule extends PageModule {
}

class RSSModule extends PageModule {
}

?>