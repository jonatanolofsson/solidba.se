<?php

class Bookings extends Page {
    public $editable = array(
        'PageSettings' => EDIT,
        'PermissionEditor' => EDIT_PRIVILEGES,
        'MenuEditor' => EDIT,
        'Delete' => DELETE
    );

    function __construct($id, $language=false) {
        parent::__construct($id, $language);

        $this->alias = 'bookings';

        $this->suggestName('Bookings', 'en');
        $this->suggestName('Bokningar', 'sv');

        $this->icon = 'small/book';
    }

    function run() {
        global $Templates;
        if(!$this->mayI(READ)) errorPage(401);
        $this->saveChanges();

        $this->schematicEditor();

        $Templates->admin->render();
    }

    function saveChanges() {
        global $Controller;
        $_POST->setType('oname', 'string');
        $_POST->setType('oparent', 'numeric');

        if($_POST['oname']) {
            $Obj = $Controller->newObj('Booking_Object');
            Flash::queue(__('New object created'));
            $Obj->Name = $_POST['oname'];
            $Obj->sortBooking('last', $_POST['oparent']);
            $Controller->forceReload($Obj);
        }
    }

    function schematicEditor() {
        global $DB, $CONFIG, $USER, $Controller;
        $bookItems = $DB->booking_items->asArray(false, 'id,parent,place', false, false, 'place ASC');
        $Controller->get(arrayExtract($bookItems, 'id'));
        foreach($bookItems as &$bi) $bi['item'] = $Controller->{$bi['id']};
        $inflatedBookItems = inflate($bookItems);

        $content = self::renderItems($inflatedBookItems);

        if($this->mayI(EDIT)) {
            $content .= Form::quick(null,null,
                    new Input(__('Object name'), 'oname'),
                    new Select(__('Parent'), 'oparent', $inflatedBookItems, false, false, __('None'))
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
                .'<span class="fixed-width">'.$o->link().'</span>'
                .Box::tools($o);

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
