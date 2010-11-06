<?php

class Bookings extends Page {
    static function installable(){return __CLASS__;}
    function install() {
        global $Controller;
        $Controller->newObj('Bookings')->move('last', 'adminMenu');
    }
    
    function __construct($id, $language=false) {
        parent::__construct($id, $language);
        
        $this->alias = 'bookings';
        
        $this->suggestName('Bookings', 'en');
        $this->suggestName('Bokningar', 'sv');
        
        $this->icon = 'small/book';
    }
    
    function run() {
        global $Templates;
        $this->udialogue();
        
        if(!$this->mayI(READ)) errorPage(401);
        
        $this->schematicEditor();
        
        $Templates->render();
    }
    
    function udialogue() {
        global $Controller;
        $_POST->setType('newObj', 'any');
        $_REQUEST->setType('editObj', 'numeric');
        $_REQUEST->addType('edit', 'numeric');
        $_POST->setType('editObj', 'numeric');
        $_POST->setType('oname', 'string');
        $_POST->setType('nlang', 'string');
        $_POST->setType('oparent', 'numeric');
        
        if($_POST['newObj'] XOR ($_POST['editObj'] && $Controller->{(string)$_POST['editObj']})) {
            if(!$_POST['oname']) {
                Flash::create(__('Invalid name'), 'warning');
                break;
            } else {
                if($_POST['newObj']) {
                    $Obj = $Controller->newObj('Booking_Object', $_POST['nlang']);
                    Flash::create(__('New object created'));
                } else {
                    $Obj = new Booking_Object($_POST['editObj'], $_POST['nlang']);
                    Flash::create(__('Object was edited'));
                }
                $Obj->Name = $_POST['oname'];
                $Obj->sortBooking('last', $_POST['oparent']);
                $Controller->forceReload($Obj);
            }
        }
    }
    
    function schematicEditor() {
        global $DB, $CONFIG, $USER, $Controller;
        $bookItems = $DB->booking_items->asArray(false, 'id,parent,place', false, false, 'place ASC');
        $Controller->get(arrayExtract($bookItems, 'id'));
        foreach($bookItems as &$bi) $bi['item'] = $Controller->{$bi['id']};
        $inflatedBookItems = inflate($bookItems);
        
        $content = '';
        $content .= self::renderItems($inflatedBookItems);
        
        $nform = new Form('newObj');
        if($this->mayI(EDIT)) {
            $content .= new Tabber('bookprop',
                new EmptyTab(__('New Item'),
                    $nform->set(
                        new Li(
                            new Input(__('Object name'), 'oname'), 
                            new select(false, 'nlang', google::languages($CONFIG->Site->languages), $USER->settings['language'])
                        ),
                        new select(__('Parent'), 'oparent', $inflatedBookItems, false, false, __('None'))
                    )
                )
            );
        }
        
        $this->setContent('main', $content);
    }
    
    function renderItems($items, $level=0) {
        global $Controller;
        $r = '';
        $r .= '<ol class="'.($level?'':'list ').'bookitems bookitems_'.$level.'">';
        $i=1;
        foreach($items as $item) {
            if(!($o = $Controller->{$item['id']}(READ))) continue;
            $r .= '<li class="'.($i++%2?'odd':'even').'">'
                .'<div class="fixed-width">'.$o->link().'</div>'
                .'<div class="tools">'
                    .($o->mayI(EDIT)
                        ? icon('small/pencil', __('Edit'), url(array('editObj' => $o->ID, 'id')))
                        :''
                    )
                    .($o->mayI(EDIT_PRIVILEGES)
                        ? icon('small/key', __('Edit privileges'), url(array('id' => 'permissionEditor', 'edit' => $o->ID)))
                        :''
                    )
                    .($o->mayI(DELETE)
                        ? icon('small/delete', __('Delete'), url(array('deleteObj' => $o->ID, 'id')))
                        :''
                    )
                .'</div>';
            
            if(isset($item['children'])) {
                $r .= self::renderItems($item['children'], $level+1);
            }
            $r .= '</li>';
        }
        $r .= '</ol>';
        
        return $r;
    }
}
?>